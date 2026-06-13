<?php

require_once __DIR__ . '/../bootstrap_env.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$scope = strtolower((string) ($_GET['scope'] ?? $_GET['check'] ?? 'ready'));
$checks = [];
$overallStatus = 'ok';
$startedAt = microtime(true);

function viabixHealthcheckAdd(array &$checks, string &$overallStatus, string $name, string $status, array $details = []): void
{
    $checks[$name] = [
        'status' => $status,
        'details' => $details,
    ];

    if ($status === 'error') {
        $overallStatus = 'error';
        return;
    }

    if ($status === 'warning' && $overallStatus === 'ok') {
        $overallStatus = 'warning';
    }
}

function viabixHealthcheckBoolEnv(string $name, bool $default = false): bool
{
    return viabix_env_bool($name, $default);
}

viabixHealthcheckAdd($checks, $overallStatus, 'application', 'ok', [
    'service' => 'viabix',
    'php' => PHP_VERSION,
    'scope' => $scope,
]);

if ($scope === 'live' || $scope === 'liveness') {
    echo json_encode([
        'status' => $overallStatus,
        'service' => 'viabix',
        'timestamp' => gmdate('c'),
        'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
        'checks' => $checks,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$appEnv = viabix_env('APP_ENV', 'development');
$appDebug = viabixHealthcheckBoolEnv('APP_DEBUG', $appEnv !== 'production');
$sessionSecure = viabixHealthcheckBoolEnv('SESSION_SECURE', $appEnv === 'production');
$envFile = __DIR__ . '/../.env';

viabixHealthcheckAdd(
    $checks,
    $overallStatus,
    'environment',
    ($appEnv === 'production' && $appDebug) ? 'warning' : 'ok',
    [
        'app_env' => $appEnv,
        'debug' => $appDebug,
        'env_file_present' => is_file($envFile),
        'session_secure' => $sessionSecure,
        'sentry_configured' => viabix_env('SENTRY_DSN', '') !== '',
    ]
);

$dbHost = viabix_env('DB_HOST', viabix_env('MYSQL_HOST', '127.0.0.1'));
$dbPort = (int) viabix_env('DB_PORT', '3306');
$dbName = viabix_env('DB_NAME', viabix_env('MYSQL_DATABASE', 'viabix_db'));
$dbUser = viabix_env('DB_USER', viabix_env('MYSQL_USER', 'viabix'));
$dbPass = viabix_env('DB_PASS', viabix_env('MYSQL_PASSWORD', ''));
$dbCharset = viabix_env('DB_CHARSET', 'utf8mb4');

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset={$dbCharset}";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    $databaseStarted = microtime(true);
    $pdo->query('SELECT 1')->fetchColumn();
    viabixHealthcheckAdd($checks, $overallStatus, 'database', 'ok', [
        'host' => $dbHost,
        'database' => $dbName,
        'latency_ms' => round((microtime(true) - $databaseStarted) * 1000, 2),
    ]);

    $requiredTables = ['usuarios', 'tenants', 'anvis'];
    $missingTables = [];
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
    );
    foreach ($requiredTables as $table) {
        $stmt->execute([$table]);
        if ((int) $stmt->fetchColumn() === 0) {
            $missingTables[] = $table;
        }
    }
    viabixHealthcheckAdd($checks, $overallStatus, 'schema', $missingTables ? 'error' : 'ok', [
        'required_tables' => $requiredTables,
        'missing_tables' => $missingTables,
    ]);
} catch (Throwable $exception) {
    viabixHealthcheckAdd($checks, $overallStatus, 'database', 'error', [
        'message' => 'Falha ao consultar o banco de dados.',
    ]);
}

$logDirs = [
    'app_logs' => realpath(__DIR__ . '/../logs') ?: __DIR__ . '/../logs',
    'system_logs' => '/var/log/viabix',
];
foreach ($logDirs as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    viabixHealthcheckAdd($checks, $overallStatus, $name, $writable ? 'ok' : 'warning', [
        'path' => $path,
        'exists' => $exists,
        'writable' => $writable,
    ]);
}

$redisEnabled = viabixHealthcheckBoolEnv('REDIS_ENABLED', true);
if ($redisEnabled && extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redisHost = viabix_env('REDIS_HOST', 'localhost');
        $redisPort = (int) viabix_env('REDIS_PORT', '6379');
        $redisPassword = viabix_env('REDIS_PASSWORD', '');
        $redisDb = (int) viabix_env('REDIS_DB', '0');
        $redis->connect($redisHost, $redisPort, 2);
        if ($redisPassword !== '') {
            $redis->auth($redisPassword);
        }
        $redis->select($redisDb);
        $redis->ping();
        viabixHealthcheckAdd($checks, $overallStatus, 'redis', 'ok', [
            'host' => $redisHost,
            'db' => $redisDb,
        ]);
    } catch (Throwable $exception) {
        viabixHealthcheckAdd($checks, $overallStatus, 'redis', 'warning', [
            'message' => 'Redis indisponivel; o sistema usa fallback de sessao.',
        ]);
    }
} else {
    viabixHealthcheckAdd($checks, $overallStatus, 'redis', 'warning', [
        'enabled' => $redisEnabled,
        'extension_loaded' => extension_loaded('redis'),
    ]);
}

http_response_code($overallStatus === 'error' ? 503 : 200);

echo json_encode([
    'status' => $overallStatus,
    'service' => 'viabix',
    'timestamp' => gmdate('c'),
    'duration_ms' => round((microtime(true) - $startedAt) * 1000, 2),
    'checks' => $checks,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
