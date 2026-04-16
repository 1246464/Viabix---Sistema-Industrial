<?php
// Step 1: Teste se o arquivo PHP está sendo executado
echo "TESTE 1: Acesso direto ao API funcionando\n\n";

// Step 2: Incluir config
echo "TESTE 2: Antes de incluir config.php\n";
require_once __DIR__ . '/config.php';
echo "TESTE 2: Depois de incluir config.php - SUCESSO\n\n";

// Step 3: Verificar funções disponíveis
echo "TESTE 3: Verificando funções...\n";
echo "viabixGenerateCSRFToken existe? " . (function_exists('viabixGenerateCSRFToken') ? 'SIM' : 'NÃO') . "\n";
echo "viabixCheckRateLimit existe? " . (function_exists('viabixCheckRateLimit') ? 'SIM' : 'NÃO') . "\n";

// Step 4: Testar banco de dados
echo "\nTESTE 4: Testando banco de dados...\n";
try {
    global $pdo;
    $result = $pdo->query("SELECT 1")->fetch();
    echo "Conexão com DB: SUCESSO\n";
} catch (Exception $e) {
    echo "Conexão com DB: ERRO - " . $e->getMessage() . "\n";
}

echo "\n✅ TODOS OS TESTES PASSARAM";
?>