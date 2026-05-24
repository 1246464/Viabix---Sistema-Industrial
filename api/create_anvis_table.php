<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../bootstrap_env.php';
    
    $DB_HOST = getenv('DB_HOST') ?: '138.197.148.138';
    $DB_USER = getenv('DB_USER') ?: 'doadmin';
    $DB_PASS = getenv('DB_PASS') ?: '59380204Mm';
    $DB_NAME = getenv('DB_NAME') ?: 'viabix_db';
    $DB_PORT = (int)(getenv('DB_PORT') ?: 25060);
    
    // Usar mysqli_init + ssl_set para DigitalOcean (porta 25060)
    $conn = mysqli_init();
    
    if (!$conn) {
        throw new Exception('mysqli_init() falhou');
    }
    
    // SSL options for DigitalOcean MySQL (port 25060)
    if ($DB_PORT == 25060) {
        $conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        $conn->ssl_set(null, null, null, null, null);
    }
    
    // Connection timeout (30 seconds)
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);
    
    // Real connect
    if (!$conn->real_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT)) {
        throw new Exception('Conexão falhou: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // 1. Create anvis table
    $create_table_sql = "CREATE TABLE IF NOT EXISTS anvis (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($create_table_sql)) {
        throw new Exception('Erro ao criar tabela: ' . $conn->error);
    }
    
    // 2. Insert test ANVI
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $numero = 'ANVI-' . date('Y') . '-001';
    $tenant_id = 'admin';
    $revisao = '1.0';
    $cliente = 'Empresa Teste';
    $projeto = 'Sistema de Gestão';
    $dados = json_encode([
        'status' => 'em_desenvolvimento',
        'progresso' => 0
    ]);
    
    $stmt = $conn->prepare(
        "INSERT INTO anvis (id, tenant_id, numero, revisao, cliente, projeto, dados) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar statement: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "sssssss",
        $anvi_id,
        $tenant_id,
        $numero,
        $revisao,
        $cliente,
        $projeto,
        $dados
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir ANVI: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // 3. Verify insertion
    $result = $conn->query("SELECT COUNT(*) as total FROM anvis");
    $row = $result->fetch_assoc();
    $anvis_count = $row['total'];
    
    $conn->close();
    
    echo json_encode([
        'status' => 'OK',
        'message' => 'Tabela anvis criada com sucesso',
        'anvi_id' => $anvi_id,
        'anvi_numero' => $numero,
        'anvis_total_count' => $anvis_count
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
