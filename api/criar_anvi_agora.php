<?php
header('Content-Type: application/json');

try {
    // Conexão PDO DIRETA (usar localhost, não IP externo)
    $dsn = "mysql:host=localhost;port=3306;dbname=viabix_db;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '59380204Mm', [
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
    
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'ANVI de teste criada com sucesso!',
        'anvi_id' => $anvi_id,
        'numero' => 'ANVI-2026-001',
        'cliente' => 'Empresa XYZ',
        'projeto' => 'Sistema de Gestão',
        'tenant_id' => $tenant_id,
        'dados_json_size' => strlen($dados),
        'proximo_passo' => 'Acesse /dashboard_viabilidade.html e clique em "Carregar Análise" com ID: ' . $anvi_id
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
