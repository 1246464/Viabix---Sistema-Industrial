<?php
/**
 * Debug - Tester de Login
 * Acesse: http://localhost/api/test_login_error.php
 */

require_once 'config.php';

header('Content-Type: application/json');

// Verificar funções
$diagnostics = [
    'functions' => [
        'viabixInitializeCsrfProtection' => function_exists('viabixInitializeCsrfProtection'),
        'viabixValidateCsrfToken' => function_exists('viabixValidateCsrfToken'),
        'viabixValidateCsrfTokenWithInput' => function_exists('viabixValidateCsrfTokenWithInput'),
        'viabixGetCsrfToken' => function_exists('viabixGetCsrfToken'),
    ],
    'session' => [
        'status' => session_status(),
        'status_name' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : (session_status() === PHP_SESSION_NONE ? 'NONE' : 'DISABLED'),
    ],
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'has_input' => !empty($_POST) || !empty(file_get_contents('php://input')),
    ],
];

// Tentar inicializar CSRF
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('viabix_session');
        session_start();
    }
    
    viabixInitializeCsrfProtection();
    $diagnostics['csrf'] = ['initialized' => true, 'token_exists' => (bool) ($_SESSION['_csrf_token'] ?? null)];
} catch (Exception $e) {
    $diagnostics['csrf'] = ['error' => $e->getMessage()];
}

// Se for POST, simular login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $diagnostics['post_test'] = [
        'input_received' => (bool) $input,
        'csrf_token_in_input' => isset($input['_csrf_token']),
        'has_function' => function_exists('viabixValidateCsrfTokenWithInput'),
    ];
    
    if (function_exists('viabixValidateCsrfTokenWithInput')) {
        try {
            viabixValidateCsrfTokenWithInput($input);
            $diagnostics['post_test']['csrf_validation'] = 'SUCCESS';
        } catch (Exception $e) {
            $diagnostics['post_test']['csrf_validation'] = $e->getMessage();
        }
    }
}

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
