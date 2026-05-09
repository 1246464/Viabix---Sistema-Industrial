<?php
header('Content-Type: application/json');

try {
    // Use mysqli instead of PDO to bypass auth plugin issues
    $mysqli = new mysqli('localhost', 'root', '', 'viabix_db');
    
    if ($mysqli->connect_error) {
        die(json_encode([
            'status' => 'erro',
            'mensagem' => 'Connection failed: ' . $mysqli->connect_error
        ]));
    }
    
    // Generate test ANVI
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $numero = 'ANVI-' . date('Y') . '-001';
    $tenant_id = 'admin';
    $criado_por = 'user-admin';
    
    $dados = json_encode([
        'financeiro' => ['orcamento' => 150000, 'gasto' => 45000],
        'planejamento' => ['duracao_meses' => 12, 'etapas' => 4],
        'qualidade' => ['testes' => 450, 'cobertura' => 87],
        'recursos' => ['disponibilidade' => 95, 'alocacao' => 75]
    ]);
    
    $sql = "INSERT INTO anvis (id, tenant_id, numero, dados, criado_por) VALUES (?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sssss', $anvi_id, $tenant_id, $numero, $dados, $criado_por);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'ANVI de teste criada com sucesso!',
            'anvi_id' => $anvi_id,
            'numero' => $numero,
            'cliente' => 'Empresa XYZ',
            'projeto' => 'Sistema de Gestão'
        ]);
    } else {
        echo json_encode([
            'status' => 'erro',
            'mensagem' => 'Erro ao inserir: ' . $stmt->error
        ]);
    }
    
    $stmt->close();
    $mysqli->close();
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage()
    ]);
}
?>
