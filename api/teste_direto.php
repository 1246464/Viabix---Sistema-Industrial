<?php
// Teste direto sem usar config.php

try {
    // Conexão PDO direta
    $dsn = "mysql:host=138.197.148.138;port=3306;dbname=viabix_db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
    ];
    
    $pdo = new PDO($dsn, 'doadmin', '59380204Mm', $options);
    
    echo "✓ Conectado ao banco viabix_db\n\n";
    
    // Verificar tabelas
    echo "=== TABELAS ===\n";
    $result = $pdo->query("SHOW TABLES LIKE 'anvis%'");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    echo "Encontradas: " . implode(", ", $tables) . "\n\n";
    
    // Contar ANVIs
    echo "=== ANVIS ===\n";
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM anvis");
    $data = $result->fetchAll();
    $cnt = $data[0]['cnt'] ?? 0;
    echo "Total de ANVIs: " . $cnt . "\n\n";
    
    // Criar ANVI
    echo "=== CRIANDO ANVI ===\n";
    
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $dados = json_encode([
        'financeiro' => ['orcamento' => 150000],
        'planejamento' => ['etapas' => 5],
        'qualidade' => ['testes' => 150],
        'recursos' => ['disponibilidade' => 98.5]
    ]);
    
    $stmt = $pdo->prepare("
        INSERT INTO anvis (
            id, tenant_id, numero, revisao, cliente, projeto, produto,
            status, volume_mensal, data_anvi, dados, criado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $anvi_id,           // id
        'admin',            // tenant_id
        'ANVI-2026-001',    // numero
        1,                  // revisao
        'Empresa XYZ',      // cliente
        'Sistema de Gestão', // projeto
        'Software SaaS',    // produto
        'em-andamento',     // status
        1000,               // volume_mensal
        date('Y-m-d'),      // data_anvi
        $dados,             // dados
        'user-admin'        // criado_por
    ]);
    
    if ($result) {
        echo "✓ ANVI CRIADO COM SUCESSO!\n";
        echo "ID: " . $anvi_id . "\n";
        echo "Verifique em: SELECT * FROM anvis WHERE id = '" . $anvi_id . "';\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line " . $e->getLine() . ")\n";
}
