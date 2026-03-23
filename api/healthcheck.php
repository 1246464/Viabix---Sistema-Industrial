<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$checks = [];
$overallStatus = 'ok';

function viabixHealthcheckAdd(&$checks, &$overallStatus, $name, $status, $details = []) {
    $checks[$name] = [
        'status' => $status,
        'details' => $details,
    ];

    if ($status !== 'ok' && $overallStatus === 'ok') {
        $overallStatus = $status === 'warning' ? 'warning' : 'error';
    }

    if ($status === 'error') {
        $overallStatus = 'error';
    }
}

try {
    $pdo->query('SELECT 1');
    viabixHealthcheckAdd($checks, $overallStatus, 'database', 'ok', [
        'host' => DB_HOST,
        'database' => DB_NAME,
    ]);
} catch (Throwable $exception) {
    viabixHealthcheckAdd($checks, $overallStatus, 'database', 'error', [
        'message' => 'Falha ao consultar o banco de dados.',
    ]);
}

$logDir = realpath(__DIR__ . '/../logs') ?: __DIR__ . '/../logs';
$logExists = is_dir($logDir);
$logWritable = $logExists && is_writable($logDir);

viabixHealthcheckAdd(
    $checks,
    $overallStatus,
    'logs',
    $logWritable ? 'ok' : ($logExists ? 'warning' : 'warning'),
    [
        'path' => $logDir,
        'exists' => $logExists,
        'writable' => $logWritable,
    ]
);

$envFile = __DIR__ . '/../.env';
viabixHealthcheckAdd(
    $checks,
    $overallStatus,
    'environment',
    is_file($envFile) ? 'ok' : 'warning',
    [
        'app_env' => defined('APP_ENV') ? APP_ENV : null,
        'debug' => defined('APP_DEBUG') ? APP_DEBUG : null,
        'env_file_present' => is_file($envFile),
        'session_secure' => defined('SESSION_SECURE') ? SESSION_SECURE : null,
    ]
);

http_response_code($overallStatus === 'error' ? 503 : 200);

echo json_encode([
    'status' => $overallStatus,
    'service' => 'viabix',
    'timestamp' => gmdate('c'),
    'checks' => $checks,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);