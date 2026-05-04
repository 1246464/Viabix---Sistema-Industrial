<?php
// Script para fazer deploy via git pull com HTTPS
// Configurar GIT_TERMINAL_PROMPT=0 para usar credenciais armazenadas

header('Content-Type: application/json');

$output = [];
$return_code = 0;

// Set git to use stored credentials / skip password prompt
putenv('GIT_TERMINAL_PROMPT=0');

// Execute git pull
chdir('/var/www/html');

// First, check current status
$status_cmd = 'git status --short 2>&1';
exec($status_cmd, $status_output, $status_code);

// Try pull with HTTPS (uses git credential helper if configured)
$cmd = 'git pull origin main 2>&1';
exec($cmd, $output, $return_code);

// Check if file exists
$file_path = '/var/www/html/dashboard_viabilidade.html';
$file_exists = file_exists($file_path);
$file_size = $file_exists ? filesize($file_path) : 0;

// Check git log
$log_cmd = 'git log --oneline -n 3 2>&1';
exec($log_cmd, $log_output, $log_code);

echo json_encode([
    'success' => ($return_code === 0 || strpos(implode("\n", $output), 'Already up to date') !== false),
    'return_code' => $return_code,
    'output' => implode("\n", $output),
    'status' => implode("\n", $status_output),
    'log' => implode("\n", $log_output),
    'file_exists' => $file_exists,
    'file_size' => $file_size,
    'timestamp' => date('Y-m-d H:i:s'),
    'git_user' => trim(shell_exec('git config --global user.name 2>/dev/null') ?? 'not set')
]);
?>
