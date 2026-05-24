<?php
// GitHub Webhook Receiver para Auto-Deploy
// Configure este URL no seu repositório GitHub como webhook

header('Content-Type: application/json');

// Verificar se é um POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST.']);
    exit;
}

// Opcionalmente, verificar o secret do webhook (deixar vazio se não configurado)
$secret = getenv('GITHUB_WEBHOOK_SECRET') ?: '';

if (!empty($secret)) {
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    $payload = file_get_contents('php://input');
    
    $expected_signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    
    if (!hash_equals($signature, $expected_signature)) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
} else {
    // Se não houver secret, apenas ler o payload
    $payload = file_get_contents('php://input');
}

// Decodificar JSON
$data = json_decode($payload, true);

// Verificar se é um push event na branch main
if ($data['ref'] !== 'refs/heads/main') {
    echo json_encode(['message' => 'Not main branch, skipping deploy']);
    exit;
}

// Executar git pull
$output = [];
$return_code = 0;

chdir('/var/www/html');

// Fazer pull com HTTPS
putenv('GIT_TERMINAL_PROMPT=0');
$cmd = 'git pull origin main 2>&1';
exec($cmd, $output, $return_code);

// Log do deploy
$log_entry = date('[Y-m-d H:i:s]') . ' Deploy executado: ' . ($return_code === 0 ? 'SUCCESS' : 'FAILED') . PHP_EOL;
error_log($log_entry, 3, '/var/www/html/deploy.log');

echo json_encode([
    'success' => ($return_code === 0 || strpos(implode("\n", $output), 'Already up to date') !== false),
    'message' => implode("\n", $output),
    'timestamp' => date('Y-m-d H:i:s'),
    'branch' => $data['ref'] ?? 'unknown'
]);
?>
