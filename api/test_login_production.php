<?php
/**
 * Test Login - Production Mode (with CSRF disabled for testing)
 * This file tests the login functionality against the production database
 */

// Enable testing mode to skip CSRF validation
define('TESTING_MODE', true);

require_once 'config.php';

header('Content-Type: application/json');

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Test data
$test_login = $_GET['login'] ?? 'admin';
$test_password = $_GET['password'] ?? '123456';

// Include the login logic
$input = [
    'login' => $test_login,
    'password' => $test_password
];

// Mock the input to avoid reading php://input
$_POST = $input;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Initialize CSRF protection
viabixInitializeCsrfProtection();

// Skip CSRF validation in testing mode
try {
    // Perform authentication
    $user = viabixFindUserForAuth($test_login);
    
    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário não encontrado',
            'user_searched' => $test_login
        ]);
        exit;
    }
    
    // Verify password
    if (!password_verify($test_password, $user['senha'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Senha incorreta',
            'debug' => [
                'provided_password' => $test_password,
                'stored_hash' => substr($user['senha'], 0, 20) . '...',
                'match_result' => false
            ]
        ]);
        exit;
    }
    
    // Password OK - set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_name'] = $user['nome'];
    $_SESSION['user_level'] = $user['nivel'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'user' => [
            'id' => $user['id'],
            'login' => $user['login'],
            'nome' => $user['nome'],
            'nivel' => $user['nivel'],
            'ativo' => $user['ativo']
        ],
        'session_id' => session_id()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro durante autenticação: ' . $e->getMessage(),
        'error' => get_class($e)
    ]);
    exit(1);
}
?>
