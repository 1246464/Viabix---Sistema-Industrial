<?php
/**
 * Deploy script - executes git pull on production
 * Should only be accessible from localhost or with proper token validation
 */

header('Content-Type: application/json');

// Security check - only allow from localhost in production or with valid token
$allowed_ips = ['127.0.0.1', 'localhost'];
$request_token = $_GET['token'] ?? $_POST['token'] ?? '';
$token_secret = 'viabix-deploy-secret-2024';

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && $request_token !== $token_secret) {
    http_response_code(403);
    die(json_encode(['status' => 'erro', 'mensagem' => 'Acesso negado']));
}

try {
    $output = shell_exec('cd /var/www/viabix && git pull origin main 2>&1');
    
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Deploy realizado com sucesso',
        'output' => $output
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
}
