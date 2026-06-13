<?php
/**
 * API index mínimo.
 */

require_once __DIR__ . '/../bootstrap_env.php';

header('Content-Type: application/json; charset=utf-8');

$appEnv = viabix_env('APP_ENV', 'development');
$appDebug = viabix_env_bool('APP_DEBUG', $appEnv !== 'production');

echo json_encode([
    'success' => true,
    'service' => 'viabix-api',
    'status' => 'operational',
    'env' => $appEnv,
    'debug' => $appDebug,
    'php' => PHP_VERSION,
], JSON_UNESCAPED_UNICODE);
