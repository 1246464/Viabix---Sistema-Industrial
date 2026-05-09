<?php
header('Content-Type: application/json');

try {
    // Conectar ao banco EXTERNO (138.197.148.138) - mesmo que config.php usa
    $pdo = new PDO(
        "mysql:host=138.197.148.138;port=3306;dbname=viabix_db;charset=utf8mb4",
        'doadmin',
        '59380204Mm',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 30,
        ]
    );
    
    // 1. Criar tabela anvis (sem FOREIGN KEY para evitar timeout)
    $pdo->exec("CREATE TABLE IF NOT EXISTS anvis (
        id VARCHAR(50) PRIMARY KEY,
        tenant_id VARCHAR(50) NOT NULL,
        numero VARCHAR(100) NOT NULL,
        revisao VARCHAR(20),
        cliente VARCHAR(255),
        projeto VARCHAR(255),
        dados JSON,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_numero (numero)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // 2. Inserir ANVI de teste
    $stmt = $pdo->prepare("INSERT INTO anvis (id, tenant_id, numero, revisao, cliente, projeto, dados) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)
                          ON DUPLICATE KEY UPDATE atualizado_em=NOW()");
    
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $result = $stmt->execute([
        $anvi_id,
        'admin',
        'ANVI-' . date('Y') . '-001',
        '1.0',
        'Empresa Teste',
        'Sistema de Gestão',
        json_encode([
            'status' => 'em_desenvolvimento',
            'progresso' => 0
        ])
    ]);
    
    // 3. Verificar se foi inserido
    $verify = $pdo->query("SELECT COUNT(*) as total FROM anvis");
    $count = $verify->fetch();
    
    echo json_encode([
        'status' => 'OK',
        'message' => 'Tabela e dados criados no banco externo com sucesso',
        'anvi_id' => $anvi_id,
        'total_anvis' => $count['total'],
        'database_connection' => '138.197.148.138 (externo)'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
?>
