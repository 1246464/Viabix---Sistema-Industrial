<?php
// Script para fazer deploy via git pull
// Usar com cuidado - deveria ter autenticação em produção

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$output = [];
$return_code = 0;

// Execute git pull
chdir('/var/www/html');
$cmd = 'git pull origin main 2>&1';
exec($cmd, $output, $return_code);

echo json_encode([
    'success' => $return_code === 0,
    'return_code' => $return_code,
    'output' => implode("\n", $output),
    'file_exists' => file_exists('/var/www/html/dashboard_viabilidade.html'),
    'file_size' => filesize('/var/www/html/dashboard_viabilidade.html') ?? 0
]);
?>
