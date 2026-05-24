<?php
// Ativando error_reporting para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

echo "=== DIAGNÓSTICO DE BANCO DE DADOS ===\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";

// Testar conexão
try {
    echo "\n1. Testando conexão PDO...";
    $pdo->exec("SELECT 1");
    echo " ✓ OK\n";
} catch (Exception $e) {
    echo " ✗ ERRO: " . $e->getMessage() . "\n";
    exit;
}

// Verificar se banco existe
try {
    echo "2. Verificando banco viabix_db...";
    $result = $pdo->query("SELECT DATABASE()");
    $rows = $result->fetchAll(PDO::FETCH_COLUMN);
    $db = $rows[0] ?? null;
    echo " ✓ Conectado a: $db\n";
} catch (Exception $e) {
    echo " ✗ ERRO: " . $e->getMessage() . "\n";
}

// Listar todas as tabelas
try {
    echo "3. Listando todas as tabelas...\n";
    $result = $pdo->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "   Total: " . count($tables) . " tabelas\n";
    if (in_array('anvis', $tables)) {
        echo "   ✓ Tabela 'anvis' EXISTE\n";
    } else {
        echo "   ✗ Tabela 'anvis' NÃO EXISTE!\n";
        echo "   Tabelas encontradas: " . implode(", ", $tables) . "\n";
    }
} catch (Exception $e) {
    echo "   ✗ ERRO ao listar tabelas: " . $e->getMessage() . "\n";
}

// Tentar contar ANVIs
try {
    echo "4. Tentando contar ANVIs...";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM anvis");
    $row = $stmt->fetchAll();
    $cnt = $row[0]['cnt'] ?? 0;
    echo " ✓ Total: $cnt\n";
} catch (Exception $e) {
    echo " ✗ ERRO: " . $e->getMessage() . "\n";
}

echo "\n=== TENTANDO CRIAR ANVI ===\n";

try {
    $tenant_id = 'admin';
    
    // Preparar dados
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    
    $dados = json_encode([
        'financeiro' => [
            'orcamento' => 150000,
            'gasto' => 87500,
            'margem_prevista' => 35,
            'investimento_total' => 150000,
            'roi_esperado_pct' => 45,
            'payback_meses' => 18
        ],
        'planejamento' => [
            'duracao_prevista_dias' => 180,
            'duracao_realizada_dias' => 165,
            'etapas_totais' => 5,
            'etapas_completas' => 3
        ],
        'qualidade' => [
            'testes_total' => 150,
            'testes_passou' => 142
        ],
        'recursos' => [
            'disponibilidade' => 98.5
        ]
    ]);
    
    echo "ANVI ID a criar: " . $anvi_id . "\n";
    echo "Tenant: " . $tenant_id . "\n";
    
    // INSERIR
    $stmt = $pdo->prepare("
        INSERT INTO anvis (
            id, tenant_id, numero, revisao, cliente, projeto, produto, 
            status, volume_mensal, data_anvi, dados, criado_por
        ) VALUES (
            :id, :tenant_id, :numero, :revisao, :cliente, :projeto, :produto,
            :status, :volume, :data, :dados, :criado_por
        )
    ");
    
    $result = $stmt->execute([
        ':id' => $anvi_id,
        ':tenant_id' => $tenant_id,
        ':numero' => 'ANVI-2026-001',
        ':revisao' => 1,
        ':cliente' => 'Empresa XYZ',
        ':projeto' => 'Sistema de Gestão',
        ':produto' => 'Software SaaS',
        ':status' => 'em-andamento',
        ':volume' => 1000,
        ':data' => date('Y-m-d'),
        ':dados' => $dados,
        ':criado_por' => 'user-admin'
    ]);
    
    if ($result) {
        echo "\n✓ ANVI CRIADO COM SUCESSO!\n";
        echo "ID: " . $anvi_id . "\n";
        echo "Verifique com:\n";
        echo "  SELECT * FROM anvis WHERE id = '" . $anvi_id . "';\n";
    }
    
} catch (Exception $e) {
    echo "\n✗ ERRO ao criar ANVI: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
