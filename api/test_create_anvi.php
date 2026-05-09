<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'api/config.php';

// Criar um ANVI de teste se não existir
try {
    echo "Verificando ANVIs existentes...\n";
    $result = $pdo->query("SELECT COUNT(*) as count FROM anvis");
    $count = $result->fetch()['count'];
    echo "Total de ANVIs: $count\n\n";
    
    if ($count == 0) {
        echo "Criando ANVI de teste...\n";
        
        $stmt = $pdo->prepare("
            INSERT INTO anvis (
                numero, revisao, cliente, projeto, produto, status,
                data_anvi, data_criacao, data_atualizacao,
                dados, dados_financeiros
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $numero = 'TEST-001';
        $revisao = 1;
        $cliente = 'Cliente Teste';
        $projeto = 'Projeto Teste';
        $produto = 'Produto Teste';
        $status = 'ativo';
        $data_anvi = date('Y-m-d');
        $data_criacao = date('Y-m-d H:i:s');
        $data_atualizacao = date('Y-m-d H:i:s');
        
        $dados = json_encode([
            'financeiro' => ['orcamento' => 100000, 'gasto' => 50000],
            'planejamento' => [],
            'qualidade' => ['testes_total' => 100, 'testes_passou' => 95],
            'recursos' => ['disponibilidade' => 95]
        ]);
        
        $dados_financeiros = json_encode([
            'investimento_total' => 100000,
            'roi_esperado_pct' => 25,
            'payback_meses' => 12,
            'duracao_meses' => 24,
            'riscos_identificados' => ['alto' => 0, 'medio' => 1, 'baixo' => 2]
        ]);
        
        $stmt->execute([
            $numero, $revisao, $cliente, $projeto, $produto, $status,
            $data_anvi, $data_criacao, $data_atualizacao,
            $dados, $dados_financeiros
        ]);
        
        echo "✅ ANVI de teste criado!\n\n";
    }
    
    // Listar ANVIs
    echo "ANVIs disponíveis:\n";
    $result = $pdo->query("SELECT id, numero, cliente, projeto, status FROM anvis ORDER BY id DESC LIMIT 5");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID: {$row['id']} | Número: {$row['numero']} | Cliente: {$row['cliente']} | Projeto: {$row['projeto']} | Status: {$row['status']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
