<?php
/**
 * Configuração do Banco de Dados - Sistema Viabix
 * Modelo Industrial 10/10 - Engenharia Financeira
 * Versão 7.1 - MySQL
 */

require_once __DIR__ . '/../bootstrap_env.php';

// ======================================================
// DEFINIR CONSTANTE GLOBAL DA APLICAÇÃO
// ======================================================
if (!defined('VIABIX_APP')) {
    define('VIABIX_APP', true);
}

// ======================================================
// INICIALIZAR SESSÃO
// ======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======================================================
// INICIALIZAR SENTRY (MONITORING & ERROR TRACKING)
// ======================================================
require_once __DIR__ . '/sentry.php';

// ======================================================
// INICIALIZAR CSRF PROTECTION
// ======================================================
require_once __DIR__ . '/csrf.php';

// ======================================================
// INICIALIZAR CORS PROTECTION
// ======================================================
require_once __DIR__ . '/cors.php';

// ======================================================
// INICIALIZAR RATE LIMITING & THROTTLING
// ======================================================
require_once __DIR__ . '/rate_limit.php';

// ======================================================
// INICIALIZAR EMAIL SERVICE
// ======================================================
require_once __DIR__ . '/email.php';

// ======================================================
// INICIALIZAR INPUT VALIDATION & SANITIZATION
// ======================================================
require_once __DIR__ . '/validation.php';

// ======================================================
// INICIALIZAR TWO-FACTOR AUTHENTICATION (2FA)
// ======================================================
require_once __DIR__ . '/two_factor_auth.php';

// ======================================================
// INICIALIZAR AUDIT LOGGING SYSTEM
// ======================================================
require_once __DIR__ . '/audit.php';

// ======================================================
// INICIALIZAR API ROUTES & SWAGGER/OPENAPI
// ======================================================
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/swagger.php';

// ======================================================
// INICIALIZAR PROTEÇÃO CSRF
// ======================================================
if (function_exists('viabixInitializeCsrfProtection')) {
    viabixInitializeCsrfProtection();
}

if (!defined('SENTRY_DSN')) {
    define('SENTRY_DSN', viabix_env('SENTRY_DSN', ''));
}
if (!defined('SENTRY_ENVIRONMENT')) {
    define('SENTRY_ENVIRONMENT', viabix_env('SENTRY_ENVIRONMENT', 'production'));
}
if (!defined('SENTRY_RELEASE')) {
    define('SENTRY_RELEASE', viabix_env('SENTRY_RELEASE', '1.0.0'));
}

// Inicializar Sentry se DSN está configurada
$_viabix_sentry = viabix_sentry_init(SENTRY_DSN, SENTRY_ENVIRONMENT, SENTRY_RELEASE);

// Configurações do banco de dados
if (!defined('APP_ENV')) {
    define('APP_ENV', viabix_env('APP_ENV', 'development'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', viabix_env_bool('APP_DEBUG', APP_ENV !== 'production'));
}
if (!defined('DB_HOST')) {
    define('DB_HOST', viabix_env('DB_HOST', '127.0.0.1'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', viabix_env('DB_NAME', 'viabix_db'));
}
if (!defined('DB_USER')) {
    define('DB_USER', viabix_env('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', viabix_env('DB_PASS', ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', viabix_env('DB_CHARSET', 'utf8mb4'));
}

// Configurações de sessão
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', viabix_env('SESSION_NAME', 'viabix_session'));
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', (int) viabix_env('SESSION_LIFETIME', '28800')); // 8 horas
}
if (!defined('SESSION_SAMESITE')) {
    define('SESSION_SAMESITE', viabix_env('SESSION_SAMESITE', 'Strict'));
}
if (!defined('SESSION_SECURE')) {
    define('SESSION_SECURE', viabix_env_bool('SESSION_SECURE', viabix_request_is_https() || APP_ENV === 'production'));
}

// Configurações de segurança
if (!defined('HASH_COST')) {
    define('HASH_COST', 12); // Custo do Bcrypt
}
if (!defined('VIABIX_BILLING_PROVIDER')) {
    define('VIABIX_BILLING_PROVIDER', viabix_env('VIABIX_BILLING_PROVIDER', 'manual'));
}
if (!defined('VIABIX_ASAAS_ENV')) {
    define('VIABIX_ASAAS_ENV', viabix_env('VIABIX_ASAAS_ENV', 'sandbox'));
}
if (!defined('VIABIX_ASAAS_API_KEY')) {
    define('VIABIX_ASAAS_API_KEY', viabix_env('VIABIX_ASAAS_API_KEY', ''));
}
if (!defined('VIABIX_ASAAS_WEBHOOK_TOKEN')) {
    define('VIABIX_ASAAS_WEBHOOK_TOKEN', viabix_env('VIABIX_ASAAS_WEBHOOK_TOKEN', ''));
}

// Configurar sessão (mas não iniciar ainda - deixar para cada script)
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => SESSION_SECURE,
        'httponly' => true,
        'samesite' => SESSION_SAMESITE
    ]);
}

// Conexão PDO
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // Log do erro (não exibir em produção)
    error_log("Erro de conexão: " . $e->getMessage());
    
    // Resposta JSON amigável
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

// ======================================================
// HEADERS DE SEGURANÇA GLOBAIS
// ======================================================

// Prevenir click-jacking
header('X-Frame-Options: SAMEORIGIN', true);

// Prevenir MIME type sniffing
header('X-Content-Type-Options: nosniff', true);

// Prevenir XSS (browsers modernos)
header('X-XSS-Protection: 1; mode=block', true);

// Referrer policy
header('Referrer-Policy: strict-origin-when-cross-origin', true);

// Feature policy (Permissions policy)
header('Permissions-Policy: geolocation=(), microphone=(), camera=()', true);

// HSTS (apenas se HTTPS em produção)
if (viabix_request_is_https() && APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains', true);
}

// ======================================================
// GLOBAL ERROR & EXCEPTION HANDLERS
// ======================================================

/**
 * Handler global para erros do PHP
 */
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Não capturar erros silenciados com @
    if (!(error_reporting() & $errno)) {
        return true;
    }

    $level = 'error';
    $category = 'error.php';

    switch ($errno) {
        case E_WARNING:
        case E_USER_WARNING:
            $level = 'warning';
            $category = 'error.warning';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $level = 'info';
            $category = 'error.notice';
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $level = 'info';
            $category = 'error.deprecated';
            break;
    }

    // Registrar breadcrumb para o Sentry
    viabix_sentry_breadcrumb($errstr, $category, $level, [
        'file' => $errfile,
        'line' => $errline,
    ]);

    // Log local
    logError("PHP Error [{$errno}]", [
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
    ]);

    return false;
});

/**
 * Handler global para exceções não capturadas
 */
set_exception_handler(function (\Throwable $e) {
    $level = 'error';
    if ($e instanceof \PDOException) {
        $level = 'error';
        viabix_sentry_tag('exception_type', 'database');
    }

    // Capturar no Sentry
    viabix_sentry_exception($e, $level, [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // Log local
    logError("Uncaught Exception: " . get_class($e), [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // Responder com JSON
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Erro interno do servidor'
    ]);
    exit;
});

/**
 * Handler para shutdown - capturar erros fatais
 */
register_shutdown_function(function () {
    $lastError = error_get_last();
    if ($lastError !== null && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        viabix_sentry_message(
            'Fatal Error: ' . $lastError['message'],
            'error',
            'error.fatal',
            [
                'file' => $lastError['file'],
                'line' => $lastError['line'],
            ]
        );
    }
});

/**
 * Função para gerar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * Função para verificar senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Função para gerar ID único
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Função para log seguro
 */
function logError($message, $context = []) {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    $logEntry = date('Y-m-d H:i:s') . " - " . $message . " - " . json_encode($context) . PHP_EOL;
    error_log($logEntry, 3, $logDir . '/error.log');

    // Enviar para Sentry também
    viabix_sentry_message($message, 'error', 'app.error', $context);
}

/**
 * Função para verificar autenticação
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado']);
        exit;
    }
    return $_SESSION['user_id'];
}

/**
 * Helper para verificar autenticação e inicializar CSRF na mesma chamada
 * Use em endpoints que exigem autenticação E CSRF protection
 */
function viabixRequireAuthenticatedSessionWithCsrf() {
    checkAuth();
    if (session_status() === PHP_SESSION_ACTIVE) {
        viabixInitializeCsrfProtection();
    }
}

/**
 * Função para verificar nível de acesso
 */
function checkLevel($requiredLevel) {
    if (!isset($_SESSION['user_level'])) {
        return false;
    }
    
    $levels = ['visitante' => 0, 'usuario' => 1, 'admin' => 2];
    $userLevel = $levels[$_SESSION['user_level']] ?? 0;
    $required = $levels[$requiredLevel] ?? 1;
    
    return $userLevel >= $required;
}

/**
 * Normaliza leitura de variáveis de ambiente com fallback padrão.
 */
function viabixEnv($name, $default = null) {
    return viabix_env($name, $default);
}

/**
 * Mantém apenas dígitos de documentos e telefones.
 */
function viabixDigitsOnly($value) {
    return preg_replace('/\D+/', '', (string) $value);
}

/**
 * Lê headers da requisição em ambientes variados do PHP.
 */
function viabixGetRequestHeader($headerName) {
    $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));

    if (isset($_SERVER[$normalized])) {
        return $_SERVER[$normalized];
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, $headerName) === 0) {
                return $value;
            }
        }
    }

    return null;
}

/**
 * Gera URL absoluta com base na requisição atual.
 */
function viabixBuildAbsoluteUrl($path) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $relativePath = ltrim(str_replace('\\', '/', $path), '/');

    if ($basePath === '' || $basePath === '.') {
        return $scheme . '://' . $host . '/' . $relativePath;
    }

    return $scheme . '://' . $host . $basePath . '/' . $relativePath;
}

/**
 * Cache simples do schema para evitar consultas repetidas ao information_schema.
 */
function viabixHasTable($tableName) {
    static $cache = [];
    global $pdo;

    if (array_key_exists($tableName, $cache)) {
        return $cache[$tableName];
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?
         LIMIT 1"
    );
    $stmt->execute([$tableName]);
    $cache[$tableName] = (bool) $stmt->fetchColumn();

    return $cache[$tableName];
}

/**
 * Verifica se uma coluna existe antes de montar queries compatíveis com fases diferentes do schema.
 */
function viabixHasColumn($tableName, $columnName) {
    static $cache = [];
    global $pdo;

    $cacheKey = $tableName . '.' . $columnName;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }

    $stmt = $pdo->prepare(
        "SELECT 1
         FROM information_schema.columns
         WHERE table_schema = DATABASE()
           AND table_name = ?
           AND column_name = ?
         LIMIT 1"
    );
    $stmt->execute([$tableName, $columnName]);
    $cache[$cacheKey] = (bool) $stmt->fetchColumn();

    return $cache[$cacheKey];
}

/**
 * Mapeia níveis legados e novos para uma escala comum.
 */
function viabixNormalizeUserLevel($level) {
    $map = [
        'visitante' => 'visitante',
        'visualizador' => 'visitante',
        'usuario' => 'usuario',
        'lider' => 'usuario',
        'financeiro' => 'usuario',
        'admin' => 'admin',
        'owner' => 'admin',
        'suporte' => 'admin',
    ];

    return $map[$level] ?? 'visitante';
}

/**
 * Busca um usuário por login, mantendo compatibilidade com schema legado e SaaS.
 */
function viabixFindUserForAuth($login) {
    global $pdo;

    $select = [
        'id',
        'login',
        'nome',
        'senha',
        'nivel',
        'ativo',
    ];

    if (viabixHasColumn('usuarios', 'email')) {
        $select[] = 'email';
    }
    if (viabixHasColumn('usuarios', 'tenant_id')) {
        $select[] = 'tenant_id';
    }
    if (viabixHasColumn('usuarios', 'ultimo_acesso')) {
        $select[] = 'ultimo_acesso';
    }

    $where = 'login = ?';
    $params = [$login];

    if (viabixHasColumn('usuarios', 'email')) {
        $where = '(login = ? OR email = ?)';
        $params[] = $login;
    }

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM usuarios WHERE ' . $where . ' LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() ?: null;
}

/**
 * Carrega tenant, assinatura e plano quando a base ja estiver preparada para SaaS.
 */
function viabixGetTenantContext($tenantId) {
    global $pdo;

    $context = [
        'tenant_id' => $tenantId,
        'tenant_slug' => null,
        'tenant_nome' => null,
        'tenant_status' => null,
        'subscription_id' => null,
        'subscription_status' => null,
        'subscription_cycle' => null,
        'plan_id' => null,
        'plan_code' => null,
        'plan_name' => null,
        'features' => [
            'modulo_anvi' => true,
            'modulo_projetos' => true,
            'exportacao' => true,
            'api' => false,
            'sso' => false,
        ],
    ];

    if (!$tenantId || !viabixHasTable('tenants')) {
        return $context;
    }

    $stmt = $pdo->prepare(
        "SELECT id, slug, nome_fantasia, status
         FROM tenants
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        return $context;
    }

    $context['tenant_slug'] = $tenant['slug'] ?? null;
    $context['tenant_nome'] = $tenant['nome_fantasia'] ?? null;
    $context['tenant_status'] = $tenant['status'] ?? null;

    if (!viabixHasTable('subscriptions') || !viabixHasTable('plans')) {
        return $context;
    }

    $stmt = $pdo->prepare(
        "SELECT
            s.id AS subscription_id,
            s.status AS subscription_status,
            s.ciclo AS subscription_cycle,
            p.id AS plan_id,
            p.codigo AS plan_code,
            p.nome AS plan_name,
            p.permite_modulo_anvi,
            p.permite_modulo_projetos,
            p.permite_exportacao,
            p.permite_api,
            p.permite_sso
         FROM subscriptions s
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE s.tenant_id = ?
         ORDER BY
            CASE s.status
                WHEN 'ativa' THEN 1
                WHEN 'trial' THEN 2
                WHEN 'inadimplente' THEN 3
                WHEN 'suspensa' THEN 4
                WHEN 'cancelada' THEN 5
                ELSE 6
            END,
            s.updated_at DESC,
            s.created_at DESC
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);
    $subscription = $stmt->fetch();

    if (!$subscription) {
        return $context;
    }

    $context['subscription_id'] = $subscription['subscription_id'];
    $context['subscription_status'] = $subscription['subscription_status'];
    $context['subscription_cycle'] = $subscription['subscription_cycle'];
    $context['plan_id'] = $subscription['plan_id'];
    $context['plan_code'] = $subscription['plan_code'];
    $context['plan_name'] = $subscription['plan_name'];
    $context['features'] = [
        'modulo_anvi' => (bool) $subscription['permite_modulo_anvi'],
        'modulo_projetos' => (bool) $subscription['permite_modulo_projetos'],
        'exportacao' => (bool) $subscription['permite_exportacao'],
        'api' => (bool) $subscription['permite_api'],
        'sso' => (bool) $subscription['permite_sso'],
    ];

    return $context;
}

/**
 * Determina se o acesso SaaS esta liberado para o tenant atual.
 */
function viabixCanAccessTenant($tenantContext) {
    if (empty($tenantContext['tenant_id'])) {
        return [true, null];
    }

    $tenantStatus = $tenantContext['tenant_status'] ?? null;
    if ($tenantStatus && !in_array($tenantStatus, ['trial', 'ativo'], true)) {
        return [false, 'Conta suspensa ou indisponível. Contate o administrador.'];
    }

    $subscriptionStatus = $tenantContext['subscription_status'] ?? null;
    if ($subscriptionStatus && !in_array($subscriptionStatus, ['trial', 'ativa'], true)) {
        return [false, 'Assinatura inativa ou bloqueada. Regularize o plano para continuar.'];
    }

    return [true, null];
}

/**
 * Persiste o contexto principal do usuário e do tenant na sessão.
 */
function viabixPopulateSession($user, $tenantContext = []) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_level'] = viabixNormalizeUserLevel($user['nivel']);
    $_SESSION['user_role_raw'] = $user['nivel'];
    $_SESSION['login_time'] = time();

    $_SESSION['tenant_id'] = $tenantContext['tenant_id'] ?? ($user['tenant_id'] ?? null);
    $_SESSION['tenant_slug'] = $tenantContext['tenant_slug'] ?? null;
    $_SESSION['tenant_nome'] = $tenantContext['tenant_nome'] ?? null;
    $_SESSION['tenant_status'] = $tenantContext['tenant_status'] ?? null;

    $_SESSION['subscription_id'] = $tenantContext['subscription_id'] ?? null;
    $_SESSION['subscription_status'] = $tenantContext['subscription_status'] ?? null;
    $_SESSION['subscription_cycle'] = $tenantContext['subscription_cycle'] ?? null;

    $_SESSION['plan_id'] = $tenantContext['plan_id'] ?? null;
    $_SESSION['plan_code'] = $tenantContext['plan_code'] ?? null;
    $_SESSION['plan_name'] = $tenantContext['plan_name'] ?? null;
    $_SESSION['features'] = $tenantContext['features'] ?? [
        'modulo_anvi' => true,
        'modulo_projetos' => true,
        'exportacao' => true,
        'api' => false,
        'sso' => false,
    ];
}

/**
 * Limpa a sessão autenticada quando o usuário nao pode mais acessar.
 */
function viabixClearAuthenticatedSession() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

/**
 * Retorna o tenant atual da sessão, quando existir.
 */
function viabixCurrentTenantId() {
    return $_SESSION['tenant_id'] ?? null;
}

/**
 * Registra log de atividade com tenant quando o schema já suportar esse contexto.
 */
function viabixLogActivity($userId, $action, $details, $entity = null, $entityId = null) {
    global $pdo;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if (viabixHasColumn('logs_atividade', 'tenant_id') && viabixHasColumn('logs_atividade', 'entidade')) {
        $stmt = $pdo->prepare(
            'INSERT INTO logs_atividade (tenant_id, usuario_id, acao, entidade, entidade_id, detalhes, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            viabixCurrentTenantId(),
            $userId,
            $action,
            $entity,
            $entityId,
            $details,
            $ipAddress,
            $userAgent,
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO logs_atividade (usuario_id, acao, detalhes, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
}

/**
 * Garante que a chamada atual seja autenticada e retorna o usuário da sessão.
 */
function viabixRequireAuthenticatedSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autenticado']);
        exit;
    }

    return [
        'id' => $_SESSION['user_id'],
        'login' => $_SESSION['user_login'] ?? null,
        'nome' => $_SESSION['user_nome'] ?? null,
        'nivel' => $_SESSION['user_level'] ?? null,
        'tenant_id' => $_SESSION['tenant_id'] ?? null,
    ];
}

/**
 * Exige sessão autenticada com permissão administrativa.
 */
function viabixRequireAdminSession() {
    $user = viabixRequireAuthenticatedSession();

    if (($user['nivel'] ?? null) !== 'admin') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso restrito à administração.']);
        exit;
    }

    return $user;
}

/**
 * Lista planos ativos para uso em painéis administrativos e checkout.
 */
function viabixListActivePlans() {
    global $pdo;

    $stmt = $pdo->query(
        "SELECT id, codigo, nome, descricao, preco_mensal, preco_anual,
                limite_usuarios, limite_anvis_mensal, limite_projetos_ativos,
                permite_modulo_anvi, permite_modulo_projetos,
                permite_exportacao, permite_api, permite_sso
         FROM plans
         WHERE status = 'ativo'
         ORDER BY preco_mensal ASC, nome ASC"
    );

    return $stmt->fetchAll();
}

/**
 * Retorna quais provedores de billing estão prontos no ambiente atual.
 */
function viabixGetBillingProviderAvailability() {
    $asaasEnabled = viabixAsaasEnabled();
    $configuredDefault = strtolower((string) viabixEnv('VIABIX_BILLING_PROVIDER', VIABIX_BILLING_PROVIDER));

    if (!in_array($configuredDefault, ['manual', 'asaas'], true)) {
        $configuredDefault = 'manual';
    }

    if ($configuredDefault === 'asaas' && !$asaasEnabled) {
        $configuredDefault = 'manual';
    }

    return [
        'default' => $configuredDefault,
        'manual' => [
            'enabled' => true,
            'mode' => 'local',
            'label' => 'Manual',
        ],
        'asaas' => [
            'enabled' => $asaasEnabled,
            'mode' => VIABIX_ASAAS_ENV,
            'label' => 'Asaas',
            'webhook_url' => viabixBuildAbsoluteUrl('webhook_billing.php'),
        ],
    ];
}

/**
 * Resolve o provedor de checkout, preservando fallback manual quando necessário.
 */
function viabixResolveCheckoutProvider($requestedProvider = null) {
    $requestedProvider = strtolower(trim((string) $requestedProvider));
    $availability = viabixGetBillingProviderAvailability();

    if ($requestedProvider === '' || $requestedProvider === 'auto') {
        return $availability['default'];
    }

    if (!isset($availability[$requestedProvider])) {
        throw new InvalidArgumentException('Provedor de billing não suportado: ' . $requestedProvider);
    }

    if (empty($availability[$requestedProvider]['enabled'])) {
        throw new RuntimeException('O provedor ' . $requestedProvider . ' não está configurado neste ambiente.');
    }

    return $requestedProvider;
}

/**
 * Configuração consolidada do Asaas.
 */
function viabixAsaasConfig() {
    $environment = strtolower((string) viabixEnv('VIABIX_ASAAS_ENV', VIABIX_ASAAS_ENV));
    $baseUrl = $environment === 'production'
        ? 'https://api.asaas.com/v3'
        : 'https://sandbox.asaas.com/api/v3';

    return [
        'environment' => $environment,
        'api_key' => (string) viabixEnv('VIABIX_ASAAS_API_KEY', VIABIX_ASAAS_API_KEY),
        'webhook_token' => (string) viabixEnv('VIABIX_ASAAS_WEBHOOK_TOKEN', VIABIX_ASAAS_WEBHOOK_TOKEN),
        'base_url' => $baseUrl,
    ];
}

/**
 * Indica se o Asaas está disponível para uso no checkout.
 */
function viabixAsaasEnabled() {
    $config = viabixAsaasConfig();

    return trim($config['api_key']) !== '';
}

/**
 * Cliente HTTP JSON simples para integrações externas.
 */
function viabixRequestJson($method, $url, array $headers = [], $payload = null, $timeout = 30) {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL não está disponível no PHP para integração externa.');
    }

    $curl = curl_init($url);
    $httpHeaders = ['Accept: application/json'];

    foreach ($headers as $name => $value) {
        $httpHeaders[] = is_string($name) ? ($name . ': ' . $value) : $value;
    }

    if ($payload !== null) {
        $body = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        $httpHeaders[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($rawResponse === false) {
        throw new RuntimeException('Falha na comunicação HTTP: ' . $curlError);
    }

    $decoded = json_decode($rawResponse, true);

    if ($httpCode >= 400) {
        $message = 'Erro HTTP ' . $httpCode;

        if (is_array($decoded)) {
            if (!empty($decoded['errors'][0]['description'])) {
                $message = $decoded['errors'][0]['description'];
            } elseif (!empty($decoded['message'])) {
                $message = $decoded['message'];
            }
        }

        throw new RuntimeException($message);
    }

    return is_array($decoded) ? $decoded : [];
}

/**
 * Requisição autenticada à API do Asaas.
 */
function viabixAsaasRequest($method, $path, array $payload = []) {
    $config = viabixAsaasConfig();

    if (trim($config['api_key']) === '') {
        throw new RuntimeException('A chave da API do Asaas não está configurada.');
    }

    $url = rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');

    return viabixRequestJson($method, $url, [
        'access_token' => $config['api_key'],
        'User-Agent' => 'Viabix-Billing/1.0',
    ], $payload === [] ? null : $payload);
}

/**
 * Busca dados de cobrança do tenant e do admin principal.
 */
function viabixGetTenantBillingProfile($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT
            t.id,
            t.slug,
            t.nome_fantasia,
            t.razao_social,
            t.cnpj,
            t.email_financeiro,
            t.telefone,
            u.id AS admin_user_id,
            u.nome AS admin_nome,
            u.email AS admin_email,
            u.login AS admin_login
         FROM tenants t
         LEFT JOIN usuarios u
           ON u.tenant_id = t.id
          AND u.ativo = 1
          AND u.nivel = 'admin'
         WHERE t.id = ?
         ORDER BY u.id ASC
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetch() ?: null;
}

/**
 * Garante que o tenant tenha um customer válido no Asaas.
 */
function viabixEnsureAsaasCustomer($tenantId, $subscriptionId = null) {
    global $pdo;

    $customerId = null;

    if ($subscriptionId) {
        $stmt = $pdo->prepare('SELECT gateway_customer_id FROM subscriptions WHERE id = ? LIMIT 1');
        $stmt->execute([$subscriptionId]);
        $customerId = $stmt->fetchColumn() ?: null;
    }

    if (!$customerId) {
        $stmt = $pdo->prepare(
            "SELECT gateway_customer_id
             FROM subscriptions
             WHERE tenant_id = ?
               AND gateway = 'asaas'
               AND gateway_customer_id IS NOT NULL
               AND gateway_customer_id <> ''
             ORDER BY updated_at DESC
             LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $customerId = $stmt->fetchColumn() ?: null;
    }

    if ($customerId) {
        return $customerId;
    }

    $profile = viabixGetTenantBillingProfile($tenantId);
    if (!$profile) {
        throw new RuntimeException('Tenant não encontrado para cadastro no Asaas.');
    }

    $payload = [
        'name' => $profile['razao_social'] ?: $profile['nome_fantasia'],
        'email' => $profile['email_financeiro'] ?: ($profile['admin_email'] ?: null),
        'phone' => viabixDigitsOnly($profile['telefone'] ?? ''),
        'mobilePhone' => viabixDigitsOnly($profile['telefone'] ?? ''),
        'cpfCnpj' => viabixDigitsOnly($profile['cnpj'] ?? ''),
        'notificationDisabled' => false,
        'externalReference' => $tenantId,
    ];

    $payload = array_filter($payload, static function ($value) {
        return $value !== null && $value !== '';
    });

    $response = viabixAsaasRequest('POST', '/customers', $payload);
    $customerId = $response['id'] ?? null;

    if (!$customerId) {
        throw new RuntimeException('Asaas não retornou o identificador do cliente.');
    }

    if ($subscriptionId) {
        $stmt = $pdo->prepare('UPDATE subscriptions SET gateway = ?, gateway_customer_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(['asaas', $customerId, $subscriptionId]);
    }

    return $customerId;
}

/**
 * Cria uma cobrança avulsa no Asaas para o ciclo/plano atual.
 */
function viabixCreateAsaasPayment($tenantId, $subscription, $plan, $amount, $cycle, $localInvoiceId) {
    $customerId = viabixEnsureAsaasCustomer($tenantId, $subscription['id'] ?? null);
    $dueDate = new DateTimeImmutable('now +' . ($cycle === 'anual' ? '3 days' : '2 days'));
    $description = sprintf('Plano %s (%s) - Viabix', $plan['nome'], $cycle);

    $payload = [
        'customer' => $customerId,
        'billingType' => 'UNDEFINED',
        'value' => round((float) $amount, 2),
        'dueDate' => $dueDate->format('Y-m-d'),
        'description' => $description,
        'externalReference' => 'invoice:' . $localInvoiceId . '|tenant:' . $tenantId . '|subscription:' . ($subscription['id'] ?? ''),
    ];

    $response = viabixAsaasRequest('POST', '/payments', $payload);

    if (empty($response['id'])) {
        throw new RuntimeException('Asaas não retornou o identificador da cobrança.');
    }

    return [
        'gateway_customer_id' => $customerId,
        'gateway_invoice_id' => $response['id'],
        'numero' => $response['invoiceNumber'] ?? null,
        'url_cobranca' => $response['invoiceUrl'] ?? ($response['bankSlipUrl'] ?? null),
        'vencimento_em' => !empty($response['dueDate']) ? ($response['dueDate'] . ' 23:59:59') : $dueDate->format('Y-m-d 23:59:59'),
        'raw' => $response,
    ];
}

/**
 * Valida o token opcional de webhook do Asaas, quando configurado.
 */
function viabixValidateAsaasWebhook() {
    $config = viabixAsaasConfig();
    $expectedToken = trim((string) $config['webhook_token']);

    if ($expectedToken === '') {
        return;
    }

    $providedToken = viabixGetRequestHeader('asaas-access-token');
    if ($providedToken === null) {
        $providedToken = viabixGetRequestHeader('x-asaas-access-token');
    }

    if (!hash_equals($expectedToken, (string) $providedToken)) {
        throw new RuntimeException('Token do webhook do Asaas inválido.');
    }
}

/**
 * Converte webhooks externos para o formato interno de billing.
 */
function viabixNormalizeBillingWebhook($provider, array $payload) {
    $provider = strtolower(trim((string) $provider));

    if (($provider === '' || $provider === 'auto') && isset($payload['event'], $payload['payment'])) {
        $provider = 'asaas';
    }

    if ($provider !== 'asaas') {
        return [
            'provider' => $provider ?: 'manual',
            'event_id' => trim((string) ($payload['event_id'] ?? (($provider ?: 'manual') . '_' . str_replace('-', '', generateUUID())))),
            'event_type' => trim((string) ($payload['event_type'] ?? '')),
            'tenant_id' => trim((string) ($payload['tenant_id'] ?? '')) ?: null,
            'payload' => $payload,
            'ignored' => false,
        ];
    }

    viabixValidateAsaasWebhook();

    $event = strtoupper(trim((string) ($payload['event'] ?? '')));
    $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
    $gatewayInvoiceId = trim((string) ($payment['id'] ?? '')) ?: null;
    $externalReference = trim((string) ($payment['externalReference'] ?? ''));
    $localInvoiceId = null;
    $tenantId = null;
    $subscriptionId = null;

    if ($externalReference !== '') {
        if (preg_match('/invoice:(\d+)/', $externalReference, $matches)) {
            $localInvoiceId = (int) $matches[1];
        }
        if (preg_match('/tenant:([a-f0-9\-]{8,})/i', $externalReference, $matches)) {
            $tenantId = $matches[1];
        }
        if (preg_match('/subscription:([a-f0-9\-]{8,})/i', $externalReference, $matches)) {
            $subscriptionId = $matches[1];
        }
    }

    $eventMap = [
        'PAYMENT_CREATED' => 'invoice.pending',
        'PAYMENT_UPDATED' => 'invoice.pending',
        'PAYMENT_BANK_SLIP_VIEWED' => 'invoice.pending',
        'PAYMENT_CHECKOUT_VIEWED' => 'invoice.pending',
        'PAYMENT_AWAITING_RISK_ANALYSIS' => 'invoice.pending',
        'PAYMENT_APPROVED_BY_RISK_ANALYSIS' => 'invoice.pending',
        'PAYMENT_AUTHORIZED' => 'invoice.pending',
        'PAYMENT_CONFIRMED' => 'invoice.paid',
        'PAYMENT_RECEIVED' => 'invoice.paid',
        'PAYMENT_OVERDUE' => 'payment.failed',
        'PAYMENT_DELETED' => 'payment.failed',
        'PAYMENT_BANK_SLIP_CANCELLED' => 'payment.failed',
        'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED' => 'payment.failed',
        'PAYMENT_REPROVED_BY_RISK_ANALYSIS' => 'payment.failed',
        'PAYMENT_REFUNDED' => 'payment.refunded',
        'PAYMENT_PARTIALLY_REFUNDED' => 'payment.refunded',
        'PAYMENT_REFUND_IN_PROGRESS' => 'payment.refunded',
        'PAYMENT_RECEIVED_IN_CASH_UNDONE' => 'payment.refunded',
        'PAYMENT_CHARGEBACK_REQUESTED' => 'payment.refunded',
        'PAYMENT_CHARGEBACK_DISPUTE' => 'payment.refunded',
        'PAYMENT_AWAITING_CHARGEBACK_REVERSAL' => 'payment.refunded',
    ];

    if (!isset($eventMap[$event])) {
        return [
            'provider' => 'asaas',
            'event_id' => 'asaas_' . ($event ?: 'unknown') . '_' . ($gatewayInvoiceId ?: str_replace('-', '', generateUUID())),
            'event_type' => $event ?: 'unknown',
            'tenant_id' => $tenantId,
            'payload' => [
                'raw_event' => $event,
                'raw_payload' => $payload,
                'gateway_invoice_id' => $gatewayInvoiceId,
            ],
            'ignored' => true,
            'ignore_reason' => 'Evento Asaas sem ação operacional no billing interno.',
        ];
    }

    return [
        'provider' => 'asaas',
        'event_id' => 'asaas_' . $event . '_' . ($gatewayInvoiceId ?: str_replace('-', '', generateUUID())),
        'event_type' => $eventMap[$event],
        'tenant_id' => $tenantId,
        'payload' => [
            'invoice_id' => $localInvoiceId,
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'gateway_invoice_id' => $gatewayInvoiceId,
            'gateway_payment_id' => $gatewayInvoiceId,
            'gateway_customer_id' => $payment['customer'] ?? null,
            'amount' => isset($payment['value']) ? (float) $payment['value'] : null,
            'method' => strtolower((string) ($payment['billingType'] ?? 'asaas')),
            'event_type' => $eventMap[$event],
            'raw_event' => $event,
            'raw_payload' => $payload,
        ],
        'ignored' => false,
    ];
}

/**
 * Garante que a estrutura mínima de billing exista antes de operar rotas comerciais.
 */
function viabixEnsureBillingSchema() {
    $requiredTables = ['tenants', 'plans', 'subscriptions', 'invoices', 'payments', 'subscription_events', 'webhook_events'];

    foreach ($requiredTables as $tableName) {
        if (!viabixHasTable($tableName)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => 'Billing indisponível. A migração SaaS ainda não foi aplicada por completo.',
                'missing_table' => $tableName,
            ]);
            exit;
        }
    }
}

/**
 * Busca o plano por código ou id.
 */
function viabixFindPlan($identifier) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM plans
         WHERE (id = ? OR codigo = ?)
           AND status = 'ativo'
         LIMIT 1"
    );
    $stmt->execute([$identifier, $identifier]);

    return $stmt->fetch() ?: null;
}

/**
 * Busca a assinatura mais relevante do tenant atual.
 */
function viabixGetCurrentSubscriptionRecord($tenantId) {
    global $pdo;

    if (!$tenantId || !viabixHasTable('subscriptions')) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, p.codigo AS plan_code, p.nome AS plan_name,
                p.preco_mensal, p.preco_anual,
                p.permite_modulo_anvi, p.permite_modulo_projetos,
                p.permite_exportacao, p.permite_api, p.permite_sso
         FROM subscriptions s
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE s.tenant_id = ?
         ORDER BY
            CASE s.status
                WHEN 'ativa' THEN 1
                WHEN 'trial' THEN 2
                WHEN 'inadimplente' THEN 3
                WHEN 'suspensa' THEN 4
                WHEN 'cancelada' THEN 5
                ELSE 6
            END,
            s.updated_at DESC,
            s.created_at DESC
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetch() ?: null;
}

/**
 * Busca faturas do tenant.
 */
function viabixGetInvoicesForTenant($tenantId, $limit = 20) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT i.*, s.status AS subscription_status, p.codigo AS plan_code, p.nome AS plan_name
         FROM invoices i
         INNER JOIN subscriptions s ON s.id = i.subscription_id
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE i.tenant_id = ?
         ORDER BY i.created_at DESC, i.id DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $tenantId);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Cria uma fatura manual/mocada para permitir integração progressiva do checkout.
 */
function viabixCreateInvoiceForSubscription($tenantId, $subscriptionId, $amount, $billingCycle, $gateway = 'manual', array $gatewayData = []) {
    global $pdo;

    $dueDate = !empty($gatewayData['vencimento_em'])
        ? new DateTimeImmutable($gatewayData['vencimento_em'])
        : new DateTimeImmutable('now +' . ($billingCycle === 'anual' ? '3 days' : '2 days'));
    $invoiceNumber = $gatewayData['numero'] ?? ('INV-' . date('Ymd') . '-' . strtoupper(substr(str_replace('-', '', generateUUID()), 0, 8)));

    $stmt = $pdo->prepare(
        'INSERT INTO invoices (tenant_id, subscription_id, gateway_invoice_id, numero, status, valor_total, valor_pago, moeda, url_cobranca, vencimento_em)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
    );
    $gatewayInvoiceId = $gatewayData['gateway_invoice_id'] ?? ($gateway . '_' . str_replace('-', '', generateUUID()));
    $billingUrl = $gatewayData['url_cobranca'] ?? ('../billing.html?invoice=' . rawurlencode($invoiceNumber));
    $stmt->execute([
        $tenantId,
        $subscriptionId,
        $gatewayInvoiceId,
        $invoiceNumber,
        'pendente',
        $amount,
        'BRL',
        $billingUrl,
        $dueDate->format('Y-m-d H:i:s'),
    ]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'gateway_invoice_id' => $gatewayInvoiceId,
        'numero' => $invoiceNumber,
        'status' => 'pendente',
        'valor_total' => $amount,
        'moeda' => 'BRL',
        'url_cobranca' => $billingUrl,
        'vencimento_em' => $dueDate->format('Y-m-d H:i:s'),
    ];
}

/**
 * Atualiza assinatura, tenant e payment a partir de um evento de billing simplificado.
 */
function viabixApplyBillingEvent($provider, $eventType, array $payload) {
    global $pdo;

    $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
    $gatewayInvoiceId = $payload['gateway_invoice_id'] ?? null;
    $paidAmount = isset($payload['amount']) ? (float) $payload['amount'] : null;

    if (!$invoiceId && !$gatewayInvoiceId) {
        throw new RuntimeException('Webhook sem invoice_id ou gateway_invoice_id.');
    }

    if ($invoiceId) {
        $stmt = $pdo->prepare(
            'SELECT i.*, s.plan_id, s.tenant_id, s.id AS subscription_uuid
             FROM invoices i
             INNER JOIN subscriptions s ON s.id = i.subscription_id
             WHERE i.id = ?
             LIMIT 1'
        );
        $stmt->execute([$invoiceId]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT i.*, s.plan_id, s.tenant_id, s.id AS subscription_uuid
             FROM invoices i
             INNER JOIN subscriptions s ON s.id = i.subscription_id
             WHERE i.gateway_invoice_id = ?
             LIMIT 1'
        );
        $stmt->execute([$gatewayInvoiceId]);
    }

    $invoice = $stmt->fetch();
    if (!$invoice) {
        throw new RuntimeException('Fatura não encontrada para o evento recebido.');
    }

    $tenantId = $invoice['tenant_id'];
    $subscriptionId = $invoice['subscription_uuid'];
    $paymentAmount = $paidAmount ?? (float) $invoice['valor_total'];

    switch ($eventType) {
        case 'invoice.paid':
        case 'payment.confirmed':
        case 'subscription.activated':
            $stmt = $pdo->prepare('UPDATE invoices SET status = ?, valor_pago = ?, pago_em = NOW(), updated_at = NOW() WHERE id = ?');
            $stmt->execute(['paga', $paymentAmount, $invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload, pago_em)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'confirmado',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare(
                "UPDATE subscriptions
                 SET status = 'ativa',
                     gateway = ?,
                     inicio_vigencia = COALESCE(inicio_vigencia, NOW()),
                     fim_vigencia = CASE ciclo WHEN 'anual' THEN DATE_ADD(NOW(), INTERVAL 1 YEAR) ELSE DATE_ADD(NOW(), INTERVAL 1 MONTH) END,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$provider, $subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'ativo', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'invoice.pending':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'pendente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);
            break;

        case 'invoice.failed':
        case 'payment.failed':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'vencida', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'falhou',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'payment.refunded':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'estornada', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'estornado',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'subscription.canceled':
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelada', cancelada_em = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'cancelado', cancelado_em = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        default:
            throw new RuntimeException('Tipo de evento não suportado: ' . $eventType);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $subscriptionId,
        $tenantId,
        $eventType,
        'webhook',
        json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    return [
        'tenant_id' => $tenantId,
        'subscription_id' => $subscriptionId,
        'invoice_id' => (int) $invoice['id'],
        'event_type' => $eventType,
    ];
}
?>