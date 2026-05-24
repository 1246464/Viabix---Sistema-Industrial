<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DIAGNÓSTICO DO DASHBOARD_VIABILIDADE ===\n\n";

require 'config.php';

// Simular sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['tenant_id'] = 'admin';

try {
    echo "1. Verificando tabelas...\n";
    $tables = ['anvis', 'projetos', 'usuarios', 'invoices', 'subscriptions'];
    foreach ($tables as $table) {
        try {
            $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "   ✓ $table existe\n";
        } catch (Exception $e) {
            echo "   ✗ $table NÃO existe: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n2. Verificando ANVIs...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM anvis");
    $count = $result->fetch()['count'];
    echo "   Total: $count ANVIs\n";
    
    if ($count > 0) {
        $result2 = $pdo->query("SELECT id, numero, cliente, status FROM anvis LIMIT 3");
        while ($row = $result2->fetch()) {
            echo "   - ID: {$row['id']}, Número: {$row['numero']}, Cliente: {$row['cliente']}, Status: {$row['status']}\n";
        }
    }
    
    echo "\n3. Testando query do dashboard (anvi_id=1)...\n";
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.numero,
            a.cliente,
            a.projeto,
            a.status,
            a.dados,
            a.dados_financeiros
        FROM anvis a
        LEFT JOIN projetos p ON a.projeto_id = p.id
        WHERE a.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([1]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($anvi) {
        echo "   ✓ ANVI encontrado!\n";
        echo "     - Número: {$anvi['numero']}\n";
        echo "     - Cliente: {$anvi['cliente']}\n";
        echo "     - Status: {$anvi['status']}\n";
        echo "     - Dados: " . strlen($anvi['dados'] ?? '') . " bytes\n";
    } else {
        echo "   ✗ ANVI não encontrado\n";
    }
    
    echo "\n4. Testando JSON_EXTRACT...\n";
    try {
        $result = $pdo->query("
            SELECT 
                JSON_EXTRACT(dados, '$.financeiro') as fin,
                JSON_EXTRACT(dados_financeiros, '$.investimento_total') as inv
            FROM anvis
            LIMIT 1
        ");
        $row = $result->fetch();
        echo "   ✓ JSON_EXTRACT funciona\n";
    } catch (Exception $e) {
        echo "   ✗ JSON_EXTRACT error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
}
