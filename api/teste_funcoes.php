<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

$results = [];

// Teste 1: Database
try {
    global $pdo;
    $pdo->query("SELECT 1");
    $results['database'] = 'OK';
} catch (Throwable $e) {
    $results['database'] = 'ERRO: ' . $e->getMessage();
}

// Teste 2: CSRF
try {
    $token = viabixGetCsrfToken();
    $results['csrf'] = $token ? 'OK' : 'ERRO: Token vazio';
} catch (Throwable $e) {
    $results['csrf'] = 'ERRO: ' . $e->getMessage();
}

// Teste 3: Rate Limit
try {
    $is_limited = viabixCheckIpRateLimit('test', 100, 3600);
    $results['rate_limit'] = 'OK';
} catch (Throwable $e) {
    $results['rate_limit'] = 'ERRO: ' . $e->getMessage();
}

// Teste 4: Validator Class
try {
    $validator = new ViabixValidator();
    $results['validator_class'] = 'OK';
} catch (Throwable $e) {
    $results['validator_class'] = 'ERRO: ' . $e->getMessage();
}

// Teste 5: 2FA Class
try {
    $auth = new ViabixTwoFactorAuth();
    $results['2fa_class'] = 'OK';
} catch (Throwable $e) {
    $results['2fa_class'] = 'ERRO: ' . $e->getMessage();
}

// Teste 6: Audit
try {
    $audit = viabixAudit();
    $results['audit'] = 'OK';
} catch (Throwable $e) {
    $results['audit'] = 'ERRO: ' . $e->getMessage();
}

// Teste 7: OpenAPI
try {
    $spec = viabixGetOpenAPISpec();
    $results['openapi'] = 'OK';
} catch (Throwable $e) {
    $results['openapi'] = 'ERRO: ' . $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>