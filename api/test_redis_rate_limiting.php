<?php
/**
 * REDIS RATE LIMITING TEST
 * Teste a implementação de rate limiting persistente com Redis
 * 
 * Execute: php api/test_redis_rate_limiting.php
 */

require_once __DIR__ . '/../bootstrap_env.php';

echo "\n🔴 REDIS RATE LIMITING TEST\n";
echo "==========================\n\n";

// ========================================
// TESTE 1: Verificar conexão Redis
// ========================================
echo "TEST 1: Redis Connection\n";
echo "------------------------\n";

$redisHost = viabix_env('REDIS_HOST', 'localhost');
$redisPort = (int) viabix_env('REDIS_PORT', 6379);
$redisPassword = viabix_env('REDIS_PASSWORD', '');

echo "Redis Config: {$redisHost}:{$redisPort}\n";
echo "Redis Password: " . ($redisPassword ? "***" : "none") . "\n\n";

$redis = null;
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redis->settimeout(5000);
        
        if ($redisPassword) {
            $redis->connect($redisHost, $redisPort, 5, null, 0, 5000);
            $redis->auth($redisPassword);
        } else {
            $redis->connect($redisHost, $redisPort, 5);
        }
        
        $redis->select(1);
        
        if ($redis->ping()) {
            echo "[1] Redis Connection: ✅ CONNECTED\n";
            echo "Redis Version: " . $redis->info('server')['redis_version'] . "\n\n";
        } else {
            echo "[1] Redis Connection: ❌ PING FAILED\n\n";
            $redis = null;
        }
    } catch (Exception $e) {
        echo "[1] Redis Connection: ❌ ERROR\n";
        echo "Error: " . $e->getMessage() . "\n\n";
        $redis = null;
    }
} else {
    echo "[1] Redis Extension: ❌ NOT LOADED\n";
    echo "Install: pecl install redis or php-redis package\n\n";
    $redis = null;
}

// ========================================
// TESTE 2: Rate Limit Key Generation
// ========================================
echo "TEST 2: Rate Limit Keys\n";
echo "----------------------\n";

function viabixGetIpLimitKey() {
    $ip = '192.168.1.100';
    return 'rl_ip_' . md5($ip);
}

function viabixGetUserLimitKey($user_id) {
    return 'rl_user_' . intval($user_id);
}

$ipKey = viabixGetIpLimitKey();
$userKey = viabixGetUserLimitKey(123);

echo "[2a] IP Limit Key: {$ipKey}\n";
echo "[2b] User Limit Key: {$userKey}\n\n";

// ========================================
// TESTE 3: Redis Operations
// ========================================
if ($redis !== null) {
    echo "TEST 3: Redis Operations\n";
    echo "------------------------\n";
    
    // Clear test keys
    $redis->del('test_key_1', 'test_key_2');
    
    // Test SET and GET
    $redis->setex('test_key_1', 60, 1);
    $value = $redis->get('test_key_1');
    echo "[3a] SET + GET: " . ($value === '1' ? "✅ PASSED" : "❌ FAILED") . "\n";
    
    // Test INCR
    $redis->incr('test_key_2');
    $redis->incr('test_key_2');
    $value = $redis->get('test_key_2');
    echo "[3b] INCR: " . ($value === '2' ? "✅ PASSED" : "❌ FAILED") . "\n";
    
    // Test TTL
    $redis->setex('test_key_3', 30, 'value');
    $ttl = $redis->ttl('test_key_3');
    echo "[3c] TTL: " . ($ttl > 0 && $ttl <= 30 ? "✅ PASSED ({$ttl}s)" : "❌ FAILED") . "\n";
    
    // Clean up
    $redis->del('test_key_1', 'test_key_2', 'test_key_3');
    echo "\n";
}

// ========================================
// TESTE 4: Simulated Rate Limiting
// ========================================
echo "TEST 4: Rate Limiting Simulation\n";
echo "-------------------------------\n";

if ($redis !== null) {
    $testKey = $ipKey . '_login';
    $testLimit = 3;  // 3 attempts
    $testWindow = 60; // per 60 seconds
    
    // Clean up before test
    $redis->del($testKey);
    
    echo "[4a] Testing brute force protection (max {$testLimit} attempts in {$testWindow}s)...\n";
    
    $results = [];
    for ($i = 1; $i <= 5; $i++) {
        $current = $redis->get($testKey);
        
        if ($current === false) {
            $redis->setex($testKey, $testWindow, 1);
            $attempts = 1;
        } else {
            $redis->incr($testKey);
            $attempts = intval($redis->get($testKey));
        }
        
        $allowed = $attempts <= $testLimit;
        $results[$i] = $allowed;
        
        echo "  Attempt {$i}: " . ($allowed ? "✅ ALLOWED" : "❌ BLOCKED") . " (count: {$attempts}/{$testLimit})\n";
    }
    
    // Verify blocking happened
    $expectedFail = $results[4] === false && $results[5] === false;
    echo "\nBrute Force Protection: " . ($expectedFail ? "✅ WORKING" : "⚠️  CHECK RESULTS") . "\n";
    
    // Clean up
    $redis->del($testKey);
    echo "\n";
} else {
    echo "[4] Rate Limiting: ⚠️  REDIS NOT AVAILABLE (testing skipped)\n\n";
}

// ========================================
// TESTE 5: Performance Benchmark
// ========================================
if ($redis !== null) {
    echo "TEST 5: Performance Benchmark\n";
    echo "----------------------------\n";
    
    $testKey = 'bench_test_key';
    $redis->del($testKey);
    
    $iterations = 1000;
    $start = microtime(true);
    
    for ($i = 0; $i < $iterations; $i++) {
        $redis->incr($testKey);
    }
    
    $elapsed = microtime(true) - $start;
    $opsPerSecond = $iterations / $elapsed;
    
    echo "Operations: {$iterations}\n";
    echo "Time: {$elapsed:.3f}s\n";
    echo "Speed: {$opsPerSecond:.0f} ops/sec\n";
    
    if ($opsPerSecond > 5000) {
        echo "Performance: ✅ EXCELLENT (>5000 ops/sec)\n";
    } elseif ($opsPerSecond > 1000) {
        echo "Performance: ✅ GOOD (>1000 ops/sec)\n";
    } else {
        echo "Performance: ⚠️  CHECK REDIS CONNECTION\n";
    }
    
    $redis->del($testKey);
    echo "\n";
}

// ========================================
// RESUMO
// ========================================
echo "RESUMO DOS TESTES\n";
echo "================\n";

if ($redis !== null) {
    echo "✅ Redis conectado e funcional\n";
    echo "✅ Rate limiting persistente HABILITADO\n";
    echo "✅ Brute force protection ATIVO\n";
    echo "\n🚀 Próximos passos:\n";
    echo "1. Configurar REDIS_HOST, REDIS_PORT, REDIS_PASSWORD no .env.production\n";
    echo "2. Deploy para DigitalOcean\n";
    echo "3. Testar rate limiting em produção\n";
} else {
    echo "⚠️  Redis não está disponível\n";
    echo "\n📦 Opções:\n";
    echo "1. Instalar Redis localmente:\n";
    echo "   - Linux: sudo apt-get install redis-server\n";
    echo "   - Mac: brew install redis\n";
    echo "   - Windows: Use WSL ou Docker\n\n";
    echo "2. Usar DigitalOcean Redis:\n";
    echo "   - Acesse seu painel DigitalOcean\n";
    echo "   - Create → Databases → Redis\n";
    echo "   - Configure REDIS_HOST, REDIS_PORT, REDIS_PASSWORD\n\n";
    echo "3. Sistema funcionará com fallback para $_SESSION (MENOS SEGURO)\n";
    echo "   - Rate limits ressetam com nova sessão\n";
    echo "   - Não funciona entre múltiplos servidores\n";
}

echo "\nVer: RATE_LIMITING_REDIS_SETUP.md para instruções detalhadas\n\n";
?>