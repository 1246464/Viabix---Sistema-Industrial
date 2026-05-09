#!/bin/bash
php -d error_reporting=E_ALL << 'PHPCODE'
<?php
try {
    // Conexão PDO DIRETA
    $dsn = "mysql:host=localhost;port=3306;dbname=viabix_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'doadmin', '59380204Mm', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ]);
    
    // Gerar ID único
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $tenant_id = 'admin';
    
    // Preparar dados completos
    $dados = json_encode([
        'financeiro' => [
            'orcamento' => 150000,
            'gasto' => 87500,
            'margem_prevista' => 35,
            'margem_realizada' => 42,
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
            'testes_passou' => 142,
            'testes_falhados' => 8,
            'cobertura_percentual' => 94.67
        ],
        'recursos' => [
            'disponibilidade' => 98.5,
            'utilização' => 87,
            'pessoas_alocadas' => 12
        ]
    ], JSON_UNESCAPED_UNICODE);
    
    // Inserir ANVI
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
        ':revisao' => '1',
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
        echo "✓ SUCESSO! ANVI criada\n";
        echo "ID: $anvi_id\n";
        echo "Número: ANVI-2026-001\n";
        echo "Cliente: Empresa XYZ\n";
        echo "Projeto: Sistema de Gestão\n";
        echo "Tenant: $tenant_id\n";
        
        // Verificar
        $check = $pdo->query("SELECT COUNT(*) as cnt FROM anvis WHERE id = '$anvi_id'");
        $row = $check->fetchAll();
        $cnt = $row[0]['cnt'] ?? 0;
        echo "\nVerificação: $cnt registro(s) encontrado(s)\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
PHPCODE
