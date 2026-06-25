<?php
/**
 * Bootstrap, sessão, serviços base, banco e headers globais.
 */

require_once __DIR__ . '/../../bootstrap_env.php';

// ======================================================
// DEFINIR CONSTANTE GLOBAL DA APLICAÇÃO
// ======================================================
if (!defined('VIABIX_APP')) {
    define('VIABIX_APP', true);
}

// ======================================================
// CARREGAR CONFIGURAÇÕES DE AMBIENTE (ANTES DE INICIALIZAR SESSÃO)
// ======================================================
if (!defined('APP_ENV')) {
    define('APP_ENV', viabix_env('APP_ENV', 'development'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', viabix_env_bool('APP_DEBUG', APP_ENV !== 'production'));
}
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

// Bloqueia endpoints de debug/teste em produção para reduzir superfície de ataque.
$viabixCurrentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$viabixIsSensitiveDebugScript = (bool) preg_match(
    '/^(debug_|test_|check_mysql_users\.php|diagnostico.*\.php|diagnose.*\.php|generate_test_token\.php)/i',
    $viabixCurrentScript
);

if ($viabixIsSensitiveDebugScript && APP_ENV === 'production' && !APP_DEBUG) {
    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Not Found']);
    exit;
}

// ======================================================
// CONFIGURAR E INICIALIZAR SESSÃO
// ======================================================
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
    session_start();
}

// ======================================================
// INICIALIZAR SENTRY (MONITORING & ERROR TRACKING)
// ======================================================
require_once __DIR__ . '/../sentry.php';

// ======================================================
// INICIALIZAR SISTEMA CENTRALIZADO DE AUTENTICAÇÃO
// ======================================================
// Sistema unificado de auth + autorização baseado em permissões
// Usar: viabixRequireAuthentication(), viabixRequirePermission(), etc
require_once __DIR__ . '/../auth_system.php';

// ======================================================
// INICIALIZAR CSRF PROTECTION
// ======================================================
require_once __DIR__ . '/../csrf.php';

// ======================================================
// INICIALIZAR JWT TOKEN GENERATION (para mobile apps)
// ======================================================
require_once __DIR__ . '/../jwt.php';

// ======================================================
// INICIALIZAR CORS PROTECTION
// ======================================================
require_once __DIR__ . '/../cors.php';

// ======================================================
// INICIALIZAR REDIS CONNECTION (para rate limiting, sessions, cache)
// ======================================================
// Redis é opcional - sistema funciona com session fallback se Redis não disponível
$redis = null;
require_once __DIR__ . '/../rate_limit.php';
viabixInitializeRedis(); // Initialize Redis for rate limiting & caching

// ======================================================
// INICIALIZAR RATE LIMITING & THROTTLING
// ======================================================
// Agora que Redis foi inicializado, rate limiting funciona com persistência
// Se Redis não disponível, falls back para $_SESSION (desenvolvimento)

// ======================================================
// INICIALIZAR EMAIL SERVICE
// ======================================================
require_once __DIR__ . '/../email.php';

// ======================================================
// INICIALIZAR INPUT VALIDATION & SANITIZATION
// ======================================================
require_once __DIR__ . '/../validation.php';

// ======================================================
// INICIALIZAR TWO-FACTOR AUTHENTICATION (2FA)
// ======================================================
require_once __DIR__ . '/../two_factor_auth.php';

// ======================================================
// INICIALIZAR AUDIT LOGGING SYSTEM
// ======================================================
require_once __DIR__ . '/../audit.php';

// ======================================================
// INICIALIZAR API ROUTES & SWAGGER/OPENAPI
// ======================================================
require_once __DIR__ . '/../routes.php';
require_once __DIR__ . '/../swagger.php';

// ======================================================
// INICIALIZAR PROTEÇÃO CSRF
// ======================================================
if (function_exists('viabixInitializeCsrfProtection')) {
    viabixInitializeCsrfProtection();
}

// ======================================================
// DEFINIR SENTRY CONFIGURATION
// ======================================================
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

// ======================================================
// DEFINIR CONFIGURAÇÕES DE BANCO DE DADOS
// ======================================================
if (!defined('DB_HOST')) {
    define('DB_HOST', viabix_env('DB_HOST', viabix_env('MYSQL_HOST', '127.0.0.1')));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', viabix_env('DB_NAME', viabix_env('MYSQL_DATABASE', 'viabix_db')));
}
if (!defined('DB_USER')) {
    define('DB_USER', viabix_env('DB_USER', viabix_env('MYSQL_USER', 'viabix')));
}
if (!defined('DB_PASS')) {
    $db_pass = viabix_env('DB_PASS', viabix_env('DB_PASSWORD', viabix_env('MYSQL_PASSWORD', '')));
    if ($db_pass === '' && viabix_env('APP_ENV', 'development') === 'production') {
        error_log('[VIABIX] CRÍTICO: DB_PASS não configurado no ambiente de produção!');
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Erro de configuração do servidor']);
        exit;
    }
    define('DB_PASS', $db_pass);
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', viabix_env('DB_CHARSET', 'utf8mb4'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) viabix_env('DB_PORT', '3306'));
}

// ======================================================
// DEFINIR CONFIGURAÇÕES DE SEGURANÇA
// ======================================================
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

// Conexão PDO
try {
    // DSN com suporte a SSL para DigitalOcean MySQL
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Opções PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt',
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
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

