<?php
header('Content-Type: application/json');

try {
    // Use mysqli with dedicated application user (not root which uses auth_socket)
    $mysqli = new mysqli('localhost', 'viabix', '59380204Mm', 'viabix_db');
    
    if ($mysqli->connect_error) {
        die(json_encode([
            'status' => 'erro',
            'mensagem' => 'Connection failed: ' . $mysqli->connect_error
        ]));
    }
    
    // Generate test ANVI
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $numero = 'ANVI-' . date('Y') . '-001';
    $revisao = '1.0';
    $tenant_id = 'admin';
    $cliente = 'Empresa XYZ';
    $projeto = 'Sistema de Gestão';
    $criado_por = 'user-admin';
    
    $dados = json_encode([
        'financeiro' => ['orcamento' => 150000, 'gasto' => 45000],
        'planejamento' => ['duracao_meses' => 12, 'etapas' => 4],
        'qualidade' => ['testes' => 450, 'cobertura' => 87],
        'recursos' => ['disponibilidade' => 95, 'alocacao' => 75]
    ]);
    
    $sql = "INSERT INTO anvis (id, tenant_id, numero, revisao, cliente, projeto, dados, criado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssssssss', $anvi_id, $tenant_id, $numero, $revisao, $cliente, $projeto, $dados, $criado_por);
    
    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'sucesso',
            'mensagem' => 'ANVI de teste criada com sucesso!',
            'anvi_id' => $anvi_id,
            'numero' => $numero,
            'revisao' => $revisao,
            'cliente' => $cliente,
            'projeto' => $projeto
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
