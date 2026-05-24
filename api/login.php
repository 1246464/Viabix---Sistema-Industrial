<?php
/**
 * Login - Sistema Viabix
 */

require_once 'config.php';

header('Content-Type: application/json');

// Handle CORS preflight requests
viabixHandleCorsPreflight(
    ['GET', 'POST', 'OPTIONS'],
    3600,
    ['Content-Type', 'Authorization', 'X-CSRF-Token', 'X-Mobile-App']
);

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados do corpo da requisição (suportar JSON e form-urlencoded)
$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

// Ler php://input UMA ÚNICA VEZ para evitar consumir o stream
$rawInput = file_get_contents('php://input');
$rawInputLength = strlen($rawInput);

// Log inicial
error_log('[LOGIN] ====== REQUEST START ======');
error_log('[LOGIN] Content-Type: ' . $contentType);
error_log('[LOGIN] Content-Length: ' . ($_SERVER['CONTENT_LENGTH'] ?? 'N/A'));
error_log('[LOGIN] php://input length: ' . $rawInputLength);
error_log('[LOGIN] php://input preview: ' . substr($rawInput, 0, 100));
error_log('[LOGIN] $_POST keys: ' . json_encode(array_keys($_POST)));

// Se for form-urlencoded (Android app), usar $_POST
if (stripos($contentType, 'application/x-www-form-urlencoded') !== false) {
    $input = $_POST;
    error_log('[LOGIN] Using $_POST data (form-urlencoded)');
    error_log('[LOGIN] $_POST content: ' . json_encode($_POST));
} 
// Se for JSON, decodificar
else if (stripos($contentType, 'application/json') !== false) {
    $input = json_decode($rawInput, true) ?? [];
    error_log('[LOGIN] Using JSON data');
}
// Tentar JSON como fallback
else if (empty($input)) {
    $input = json_decode($rawInput, true) ?? $_POST ?? [];
    error_log('[LOGIN] Using fallback (JSON or POST)');
}

if (empty($input)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Detectar se é um app mobile nativo (Android/iOS)
// Apps mobile enviam email/password em vez de login/senha e sem CSRF token
$isMobileApp = (!empty($input['email']) || !empty($input['password'])) && empty($input['_csrf_token']);
if ($isMobileApp) {
    $_SERVER['HTTP_X_MOBILE_APP'] = 'native'; // Marcar como mobile app
    error_log('[LOGIN] Detected mobile app request');
}

error_log('[LOGIN] Input keys: ' . json_encode(array_keys($input)));
error_log('[LOGIN] Is mobile: ' . ($isMobileApp ? 'YES' : 'NO'));

// Validar CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Inicializar proteção CSRF
viabixInitializeCsrfProtection();

// ✅ Se for mobile app, PULAR COMPLETAMENTE validação CSRF
if ($isMobileApp) {
    error_log('[LOGIN] Skipping CSRF validation for mobile app');
    // Don't validate CSRF for mobile apps
} else {
    // Validar CSRF apenas para web
    try {
        viabixValidateCsrfTokenWithInput($input);
        error_log('[LOGIN] CSRF validation PASSED');
    } catch (RuntimeException $e) {
        error_log('[LOGIN] CSRF validation FAILED: ' . $e->getMessage());
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Validação de segurança falhou. Recarregue a página.']);
        exit;
    }
}

// ======================================================
// CHECK RATE LIMITING (Brute Force Protection)
// ======================================================
$rate_limit_check = viabixCheckIpRateLimit('login', 5, 300); // 5 attempts per 5 minutes per IP
if (!$rate_limit_check['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . intval($rate_limit_check['reset_in']), true);
    echo json_encode([
        'success' => false,
        'message' => 'Muitas tentativas de login. Tente novamente em ' . intval($rate_limit_check['reset_in']) . ' segundos.',
        'error_code' => 'rate_limit_exceeded',
        'retry_after' => intval($rate_limit_check['reset_in'])
    ]);
    exit;
}

// Suportar ambos os formatos: web (login/senha) e mobile (email/password)
$login = trim($input['login'] ?? $input['email'] ?? '');
$senha = trim($input['senha'] ?? $input['password'] ?? '');

// Validações básicas
if (empty($login) || empty($senha)) {
    echo json_encode(['success' => false, 'message' => 'Usuário e senha são obrigatórios']);
    exit;
}

try {
    $user = viabixFindUserForAuth($login);
    
    if (!$user) {
        // Log de tentativa com usuário inexistente
        viabix_sentry_breadcrumb('Tentativa de login com usuário inexistente', 'auth.login', 'warning', ['login' => $login]);
        viabixLogError("Tentativa de login com usuário inexistente", ['login' => $login]);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
        exit;
    }
    
    if (!$user['ativo']) {
        viabix_sentry_breadcrumb('Tentativa de login com usuário inativo', 'auth.login', 'warning', ['login' => $login, 'user_id' => $user['id']]);
        viabixLogError("Tentativa de login com usuário inativo", ['login' => $login]);
        echo json_encode(['success' => false, 'message' => 'Usuário inativo. Contate o administrador.']);
        exit;
    }
    
    // Verificar senha
    if (!verifyPassword($senha, $user['senha'])) {
        // Log de tentativa com senha incorreta
        viabix_sentry_breadcrumb('Tentativa de login com senha incorreta', 'auth.login', 'warning', ['login' => $login, 'user_id' => $user['id']]);
        viabixLogError("Tentativa de login com senha incorreta", ['login' => $login]);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
        exit;
    }
    
    // Regenerar ID da sessão por segurança (sessão já foi iniciada em config.php)
    session_regenerate_id(true);

    $tenantContext = viabixGetTenantContext($user['tenant_id'] ?? null);
    [$canAccess, $accessMessage] = viabixCanAccessTenant($tenantContext);

    if (!$canAccess) {
        viabix_sentry_breadcrumb('Tentativa de login com tenant ou assinatura bloqueada', 'auth.login', 'error', [
            'login' => $login,
            'user_id' => $user['id'],
            'tenant_id' => $tenantContext['tenant_id'] ?? null,
            'tenant_status' => $tenantContext['tenant_status'] ?? null,
            'subscription_status' => $tenantContext['subscription_status'] ?? null,
        ]);
        
        viabixLogError("Tentativa de login com tenant ou assinatura bloqueada", [
            'login' => $login,
            'tenant_id' => $tenantContext['tenant_id'] ?? null,
            'tenant_status' => $tenantContext['tenant_status'] ?? null,
            'subscription_status' => $tenantContext['subscription_status'] ?? null,
        ]);
        echo json_encode(['success' => false, 'message' => $accessMessage]);
        exit;
    }

    viabixPopulateSession($user, $tenantContext);
    
    // Configurar contexto no Sentry
    viabix_sentry_set_user($user['id'], $login, $user['nome'] ?? null);
    if ($tenantContext['tenant_id'] ?? null) {
        viabix_sentry_set_tenant($tenantContext['tenant_id'], $tenantContext['tenant_nome'] ?? null);
    }
    
    // Log de login bem-sucedido
    viabix_sentry_breadcrumb('Login bem-sucedido', 'auth.login', 'info', [
        'user_id' => $user['id'],
        'tenantContext' => $tenantContext['tenant_id'] ?? null,
    ]);
    
    // Atualizar último acesso
    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
    $stmt->execute([$user['id']]);
    
    // Registrar log de atividade
    if (viabixHasColumn('logs_atividade', 'tenant_id')) {
        $stmt = $pdo->prepare(
            "INSERT INTO logs_atividade (tenant_id, usuario_id, acao, detalhes, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_SESSION['tenant_id'] ?? null,
            $user['id'],
            'login',
            'Login realizado com sucesso',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, detalhes, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $user['id'],
            'login',
            'Login realizado com sucesso',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    // Clear rate limit on successful login
    viabixClearRateLimit('login');
    
    // Gerar JWT token para mobile apps
    $jwtToken = viabixGenerateJwt($user['id'], $_SESSION['tenant_id'] ?? null);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'token' => $jwtToken, // ← Token para mobile apps
        'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'nome' => $user['nome'],
            'nivel' => $_SESSION['user_level'],
            'nivel_original' => $user['nivel']
        ],
        'tenant' => [
            'id' => $_SESSION['tenant_id'] ?? null,
            'slug' => $_SESSION['tenant_slug'] ?? null,
            'nome' => $_SESSION['tenant_nome'] ?? null,
            'status' => $_SESSION['tenant_status'] ?? null,
        ],
        'subscription' => [
            'id' => $_SESSION['subscription_id'] ?? null,
            'status' => $_SESSION['subscription_status'] ?? null,
            'ciclo' => $_SESSION['subscription_cycle'] ?? null,
            'plano_codigo' => $_SESSION['plan_code'] ?? null,
            'plano_nome' => $_SESSION['plan_name'] ?? null,
        ],
        'features' => $_SESSION['features'] ?? []
    ]);
    
} catch (PDOException $e) {
    viabixLogError("Erro no login", ['error' => $e->getMessage()]);
    http_response_code(500);
    
    // DEBUG: mostrar erro se APP_DEBUG=true
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo json_encode([
            'success' => false, 
            'message' => 'Erro interno do servidor',
            'debug_error' => $e->getMessage(),
            'debug_code' => $e->getCode(),
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
}
?>