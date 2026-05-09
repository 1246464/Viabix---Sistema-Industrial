<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    // Get tenant from session
    $tenant_id = $_SESSION['tenant_id'] ?? null;
    
    if (!$tenant_id) {
        http_response_code(401);
        exit(json_encode(['erro' => 'Não autenticado']));
    }
    
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
    
    // 1. Create ANVI with correct tenant_id
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    $numero = 'ANVI-' . date('Y') . '-001';
    $revisao = '1.0';
    $cliente = 'Empresa Teste ' . substr($tenant_id, 0, 8);
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
    
    // 2. Verify insertion
    $result = $conn->query("SELECT COUNT(*) as total FROM anvis WHERE tenant_id = '$tenant_id'");
    $row = $result->fetch_assoc();
    $anvis_count = $row['total'];
    
    $conn->close();
    
    echo json_encode([
        'status' => 'OK',
        'message' => 'ANVI criado com sucesso para seu tenant',
        'anvi_id' => $anvi_id,
        'anvi_numero' => $numero,
        'tenant_id' => $tenant_id,
        'anvis_total_count' => $anvis_count
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
