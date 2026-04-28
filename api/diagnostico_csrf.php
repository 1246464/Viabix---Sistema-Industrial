<?php
/**
 * Diagnóstico de CSRF e Login - Sistema Viabix
 * 
 * Este arquivo ajuda a identificar problemas com:
 * - Carregamento do token CSRF
 * - Validação de sessão
 * - Respostas JSON
 * 
 * Uso: http://localhost/api/diagnostico_csrf.php
 * Ou (prod): https://viabix.com.br/api/diagnostico_csrf.php
 */

require_once 'config.php';

// Retornar JSON
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$diagnostics = [
    'timestamp' => date('Y-m-d H:i:s'),
    'environment' => [
        'app_env' => APP_ENV,
        'app_url' => APP_URL,
        'php_version' => phpversion(),
        'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT_ACTIVE',
        'session_name' => session_name(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    ],
    'csrf' => [
        'token_exists' => !empty($_SESSION['_csrf_token']),
        'token_length' => strlen($_SESSION['_csrf_token'] ?? ''),
        'token_age' => time() - ($_SESSION['_csrf_token_time'] ?? 0),
    ],
    'session' => [
        'user_logged_in' => isset($_SESSION['user_id']) && isset($_SESSION['user_login']),
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_login' => $_SESSION['user_login'] ?? null,
        'tenant_id' => $_SESSION['tenant_id'] ?? null,
        'session_keys' => array_keys($_SESSION),
    ],
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'remote_addr' => $_SERVER['REMOTE_ADDR'],
        'http_host' => $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'unknown',
    ],
    'database' => [
        'connected' => false,
        'error' => null,
    ],
];

// Testar conexão com banco
try {
    $stmt = $pdo->query("SELECT 1");
    $diagnostics['database']['connected'] = true;
} catch (PDOException $e) {
    $diagnostics['database']['connected'] = false;
    $diagnostics['database']['error'] = $e->getMessage();
}

// Testar função CSRF
try {
    viabixInitializeCsrfProtection();
    $token = viabixGetCsrfToken();
    $diagnostics['csrf']['initialized'] = true;
    $diagnostics['csrf']['token'] = substr($token, 0, 8) . '...' . substr($token, -8); // Mostrar apenas primeira e última parte
} catch (Exception $e) {
    $diagnostics['csrf']['initialized'] = false;
    $diagnostics['csrf']['error'] = $e->getMessage();
}

// Testar resposta JSON válida
$diagnostics['response'] = [
    'json_valid' => true,
    'status_code' => http_response_code(),
    'headers_sent' => headers_sent(),
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
