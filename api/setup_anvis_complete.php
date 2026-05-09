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
    
    // 1. Drop existing table if needed
    $conn->query("DROP TABLE IF EXISTS anvis");
    
    // 2. Create COMPLETE anvis table with all required columns
    $create_table_sql = "CREATE TABLE IF NOT EXISTS anvis (
        id VARCHAR(50) PRIMARY KEY,
        tenant_id VARCHAR(50) NOT NULL,
        numero VARCHAR(100) NOT NULL,
        revisao VARCHAR(20),
        cliente VARCHAR(255),
        projeto VARCHAR(255),
        produto VARCHAR(255),
        status VARCHAR(50) DEFAULT 'rascunho',
        data_anvi DATE,
        data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        projeto_id INT,
        dados JSON,
        dados_financeiros JSON,
        criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_tenant (tenant_id),
        INDEX idx_numero (numero),
        INDEX idx_status (status),
        INDEX idx_projeto (projeto_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($create_table_sql)) {
        throw new Exception('Erro ao criar tabela: ' . $conn->error);
    }
    
    // 3. Insert test ANVI with all fields
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $numero = 'ANVI-' . date('Y') . '-001';
    $tenant_id = 'admin';
    $revisao = '1.0';
    $cliente = 'Empresa Teste';
    $projeto = 'Sistema de Gestão';
    $produto = 'SaaS Viabix';
    $status = 'em_desenvolvimento';
    $data_anvi = date('Y-m-d');
    $dados = json_encode([
        'financeiro' => [
            'investimento' => 50000,
            'margem' => 35
        ],
        'planejamento' => [
            'duracao_meses' => 6,
            'fases' => 3
        ],
        'qualidade' => [
            'cobertura_testes' => 85,
            'score_codigo' => 92
        ],
        'recursos' => [
            'equipe' => 5,
            'especialistas' => 2
        ]
    ]);
    $dados_financeiros = json_encode([
        'investimento_total' => 50000,
        'roi_esperado_pct' => 150,
        'payback_meses' => 4,
        'duracao_meses' => 6,
        'riscos_identificados' => [
            'escopo',
            'recursos',
            'timeline'
        ]
    ]);
    
    $stmt = $conn->prepare(
        "INSERT INTO anvis (id, tenant_id, numero, revisao, cliente, projeto, produto, status, data_anvi, dados, dados_financeiros) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    
    if (!$stmt) {
        throw new Exception('Erro ao preparar statement: ' . $conn->error);
    }
    
    $stmt->bind_param(
        "sssssssssss",
        $anvi_id,
        $tenant_id,
        $numero,
        $revisao,
        $cliente,
        $projeto,
        $produto,
        $status,
        $data_anvi,
        $dados,
        $dados_financeiros
    );
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao inserir ANVI: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // 4. Verify insertion
    $result = $conn->query("SELECT COUNT(*) as total FROM anvis");
    $row = $result->fetch_assoc();
    $anvis_count = $row['total'];
    
    // 5. Show schema
    $result = $conn->query("DESCRIBE anvis");
    $columns = [];
    while ($col = $result->fetch_assoc()) {
        $columns[] = $col['Field'];
    }
    
    $conn->close();
    
    echo json_encode([
        'status' => 'OK',
        'message' => 'Tabela anvis recriada com esquema completo',
        'anvi_id' => $anvi_id,
        'anvi_numero' => $numero,
        'anvis_total_count' => $anvis_count,
        'columns' => $columns
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
