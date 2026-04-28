<?php
/**
 * DIAGNÓSTICO VIABIX PRODUCTION
 * 
 * Use: php api/diagnostico_producao.php
 * Ou acesse: https://viabix.com.br/api/diagnostico_producao.php
 */

header('Content-Type: application/json; charset=utf-8');

$diagnostics = [];
$errors = [];
$warnings = [];

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║     VIABIX PRODUCTION DIAGNOSTICS                              ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ============================================================
// 1. AMBIENTE
// ============================================================
echo "[1/10] Verificando Ambiente...\n";

$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    echo "✓ .env existe\n";
    $env_content = file_get_contents($env_file);
    $app_env = preg_match('/APP_ENV=(\w+)/', $env_content, $m) ? $m[1] : 'unknown';
    $app_debug = preg_match('/APP_DEBUG=(\w+)/', $env_content, $m) ? $m[1] : 'unknown';
    echo "  - APP_ENV: $app_env\n";
    echo "  - APP_DEBUG: $app_debug\n";
    
    if ($app_env !== 'production') {
        $warnings[] = "APP_ENV não está em 'production'";
    }
} else {
    $errors[] = ".env NÃO EXISTE!";
    echo "✗ .env não encontrado\n";
}

// ============================================================
// 2. PHP
// ============================================================
echo "\n[2/10] Verificando PHP...\n";
echo "✓ PHP Version: " . phpversion() . "\n";
echo "✓ SAPI: " . php_sapi_name() . "\n";

$required_extensions = ['pdo', 'pdo_mysql', 'json', 'session', 'curl', 'openssl', 'mbstring'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ Extensão $ext OK\n";
    } else {
        $errors[] = "Extensão PHP $ext NÃO CARREGADA!";
        echo "✗ Extensão $ext FALTANDO\n";
    }
}

// ============================================================
// 3. FILESYSTEM & PERMISSIONS
// ============================================================
echo "\n[3/10] Verificando Filesystem...\n";

$dirs_to_check = [
    __DIR__ . '/../logs' => 'logs',
    __DIR__ . '/../templates' => 'templates',
    __DIR__ . '/../uploads' => 'uploads (opcional)',
];

foreach ($dirs_to_check as $dir => $name) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? '✓' : '✗';
        echo "$writable $name: $dir (perms: $perms)\n";
        if (!is_writable($dir) && in_array($name, ['logs', 'templates', 'uploads'])) {
            $warnings[] = "$name não é gravável";
        }
    } else {
        if (in_array($name, ['logs', 'templates'])) {
            $errors[] = "Diretório $name NÃO EXISTE!";
            echo "✗ $name: $dir (NÃO EXISTE)\n";
        }
    }
}

// ============================================================
// 4. BANCO DE DADOS
// ============================================================
echo "\n[4/10] Testando Conexão com Banco de Dados...\n";

$env_file = __DIR__ . '/../.env';
$db_host = getenv('DB_HOST');
$db_name = getenv('DB_NAME');
$db_user = getenv('DB_USER');
$db_pass = getenv('DB_PASS');

if (!$db_host || !$db_name || !$db_user) {
    $errors[] = "Credenciais de BD incompletas no .env";
    echo "✗ Credenciais de BD incomplete\n";
    echo "  DB_HOST: " . ($db_host ? '✓' : '✗') . "\n";
    echo "  DB_NAME: " . ($db_name ? '✓' : '✗') . "\n";
    echo "  DB_USER: " . ($db_user ? '✓' : '✗') . "\n";
} else {
    try {
        $dsn = "mysql:host=" . $db_host . ";dbname=" . $db_name . ";charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        echo "✓ Conexão PDO OK\n";
        echo "  - Host: $db_host\n";
        echo "  - Database: $db_name\n";
        echo "  - User: $db_user\n";
        
        // Test query
        $stmt = $pdo->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "✓ Query Test OK\n";
        
        // Check tables
        $stmt = $pdo->query("SHOW TABLES");
        $count = 0;
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $count++;
        }
        echo "✓ Tables: $count\n";
        
    } catch (PDOException $e) {
        $errors[] = "Erro ao conectar no banco: " . $e->getMessage();
        echo "✗ Erro PDO: " . $e->getMessage() . "\n";
    } catch (Exception $e) {
        $errors[] = "Erro geral: " . $e->getMessage();
        echo "✗ Erro: " . $e->getMessage() . "\n";
    }
}

// ============================================================
// 5. REDIS (se configurado)
// ============================================================
echo "\n[5/10] Testando Redis...\n";

$redis_host = getenv('SESSION_REDIS_HOST') ?: getenv('REDIS_HOST') ?: 'localhost';
$redis_port = getenv('SESSION_REDIS_PORT') ?: 6379;

if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        if ($redis->connect($redis_host, $redis_port, 2)) {
            echo "✓ Redis conectado\n";
            echo "  - Host: $redis_host:$redis_port\n";
            $redis->ping();
            echo "✓ Redis PING OK\n";
        } else {
            $warnings[] = "Não conseguiu conectar ao Redis";
            echo "✗ Não conseguiu conectar ao Redis\n";
        }
    } catch (Exception $e) {
        $warnings[] = "Erro ao conectar Redis: " . $e->getMessage();
        echo "✗ Erro Redis: " . $e->getMessage() . "\n";
    }
} else {
    echo "ℹ Extensão Redis não carregada (usar Sessions em arquivo se não tiver Redis)\n";
}

// ============================================================
// 6. CONFIGURAÇÃO DE SESSÃO
// ============================================================
echo "\n[6/10] Verificando Config de Sessão...\n";

$session_driver = getenv('SESSION_DRIVER') ?: 'file';
$session_name = getenv('SESSION_NAME') ?: 'viabix_session';
$session_secure = getenv('SESSION_SECURE') ?: 'true';
$session_httponly = getenv('SESSION_HTTPONLY') ?: 'true';

echo "✓ SESSION_DRIVER: $session_driver\n";
echo "✓ SESSION_NAME: $session_name\n";
echo "✓ SESSION_SECURE: $session_secure\n";
echo "✓ SESSION_HTTPONLY: $session_httponly\n";

if ($session_driver === 'redis' && !extension_loaded('redis')) {
    $errors[] = "SESSION_DRIVER é 'redis' mas extensão Redis não está carregada!";
    echo "✗ Redis requerido mas não disponível!\n";
}

// ============================================================
// 7. HEADERS DE SEGURANÇA
// ============================================================
echo "\n[7/10] Verificando Headers de Segurança...\n";

if (function_exists('apache_request_headers')) {
    echo "✓ Function apache_request_headers disponível\n";
}

// ============================================================
// 8. LOGS
// ============================================================
echo "\n[8/10] Verificando Logs...\n";

$log_dir = __DIR__ . '/../logs';
if (is_dir($log_dir)) {
    $error_log = $log_dir . '/error.log';
    if (file_exists($error_log)) {
        $size = filesize($error_log);
        $lines = count(file($error_log));
        echo "✓ error.log existe\n";
        echo "  - Tamanho: " . round($size / 1024, 2) . " KB\n";
        echo "  - Linhas: $lines\n";
        
        echo "  - Últimas 5 linhas:\n";
        $tail = array_slice(file($error_log), -5);
        foreach ($tail as $line) {
            echo "    " . trim($line) . "\n";
        }
    }
} else {
    $warnings[] = "Diretório logs não existe";
}

// ============================================================
// 9. CONFIGURAÇÕES CRÍTICAS
// ============================================================
echo "\n[9/10] Verificando Config Críticas...\n";

$config_file = __DIR__ . '/config.php';
if (file_exists($config_file)) {
    echo "✓ config.php existe\n";
    
    // Try loading
    try {
        ob_start();
        @require_once $config_file;
        ob_end_clean();
        echo "✓ config.php carregou sem erros\n";
    } catch (Exception $e) {
        $errors[] = "config.php tem erro: " . $e->getMessage();
        echo "✗ config.php erro: " . $e->getMessage() . "\n";
    }
} else {
    $errors[] = "config.php NÃO EXISTE!";
    echo "✗ config.php não encontrado\n";
}

// ============================================================
// 10. APIs
// ============================================================
echo "\n[10/10] Testando Endpoints...\n";

$endpoints = [
    '/api/check_session.php',
    '/api/login.php',
    '/api/healthcheck.php',
];

foreach ($endpoints as $endpoint) {
    $file = __DIR__ . ($endpoint === '/api/healthcheck.php' ? '/healthcheck.php' : str_replace('/api', '', $endpoint));
    if (file_exists($file)) {
        echo "✓ $endpoint existe\n";
    } else {
        $warnings[] = "$endpoint não existe";
        echo "✗ $endpoint não encontrado\n";
    }
}

// ============================================================
// RESUMO
// ============================================================
echo "\n╔════════════════════════════════════════════════════════════════╗\n";
echo "║                        RESUMO                                   ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

if (empty($errors) && empty($warnings)) {
    echo "✓ TUDO OK! Sistema pronto para produção.\n";
} else {
    if (!empty($errors)) {
        echo "ERROS CRÍTICOS (" . count($errors) . "):\n";
        foreach ($errors as $i => $error) {
            echo "  " . ($i + 1) . ". ✗ $error\n";
        }
        echo "\n";
    }
    
    if (!empty($warnings)) {
        echo "AVISOS (" . count($warnings) . "):\n";
        foreach ($warnings as $i => $warning) {
            echo "  " . ($i + 1) . ". ⚠ $warning\n";
        }
        echo "\n";
    }
}

/**
 * JSON output for programmatic access
 */
$output = [
    'status' => empty($errors) ? 'OK' : 'ERROR',
    'errors' => $errors,
    'warnings' => $warnings,
    'system' => [
        'php_version' => phpversion(),
        'php_sapi' => php_sapi_name(),
        'os' => php_uname('s'),
    ],
];

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo "\n\n" . json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>
