<?php
/**
 * Login - Sistema Viabix
 */

require_once 'config.php';

header('Content-Type: application/json');

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obter dados do corpo da requisição

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Validar CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Validar CSRF token (skip em modo teste)
if (!defined('TESTING_MODE') || !TESTING_MODE) {
    try {
        viabixValidateCsrfToken();
    } catch (RuntimeException $e) {
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

$login = trim($input['login'] ?? '');
$senha = trim($input['senha'] ?? '');

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
    
    // Iniciar sessão com nome unificado
    session_name('viabix_session');
    session_start();
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
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
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>