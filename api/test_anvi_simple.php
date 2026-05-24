<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

// Simular sessão
$_SESSION['user_id'] = 1;
$_SESSION['tenant_id'] = 'admin';

echo "Teste de ANVI Dashboard\n\n";

$anvi_id = 1;
$tenant_id = 'admin';

try {
    echo "1. Testando SELECT básico...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM anvis");
    $count = $result->fetch()['count'];
    echo "   ✓ Total de ANVIs: $count\n\n";
    
    if ($count == 0) {
        echo "2. Nenhum ANVI encontrado - Criando ANVI de teste...\n";
        
        $sql = "INSERT INTO anvis (numero, revisao, cliente, projeto, produto, status, data_anvi, data_criacao, data_atualizacao, dados, dados_financeiros) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'TEST-001',
            1,
            'Cliente Teste',
            'Projeto Teste',
            'Produto Teste',
            'ativo',
            date('Y-m-d'),
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            json_encode(['financeiro' => ['orcamento' => 100000, 'gasto' => 50000]]),
            json_encode(['investimento_total' => 100000, 'roi_esperado_pct' => 25])
        ]);
        
        echo "   ✓ ANVI criado!\n\n";
    }
    
    echo "3. Testando query do dashboard...\n";
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.numero,
            a.cliente,
            a.projeto,
            a.status
        FROM anvis a
        LEFT JOIN projetos p ON a.projeto_id = p.id
        WHERE a.id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$anvi_id]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($anvi) {
        echo "   ✓ ANVI encontrado!\n";
        echo "     ID: " . $anvi['id'] . "\n";
        echo "     Número: " . $anvi['numero'] . "\n";
        echo "     Cliente: " . $anvi['cliente'] . "\n";
        echo "     Projeto: " . $anvi['projeto'] . "\n";
        echo "     Status: " . $anvi['status'] . "\n";
    } else {
        echo "   ✗ ANVI não encontrado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . " Linha: " . $e->getLine() . "\n";
}
