<?php
header('Content-Type: application/json');

try {
    // Usar exatamente o padrão de Controle_de_projetos que funciona
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
    
    // 1. Get database version
    $result = $conn->query("SELECT VERSION() as version");
    $version = $result->fetch_assoc()['version'];
    
    // 2. List all tables
    $result = $conn->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // 3. Check if anvis table exists
    $anvis_exists = in_array('anvis', $tables);
    
    // 4. Get count if exists
    $anvis_count = 0;
    if ($anvis_exists) {
        $result = $conn->query("SELECT COUNT(*) as total FROM anvis");
        $anvis_count = $result->fetch_assoc()['total'];
    }
    
    $conn->close();
    
    echo json_encode([
        'status' => 'OK',
        'connection' => [
            'host' => $DB_HOST,
            'port' => $DB_PORT,
            'database' => $DB_NAME,
            'user' => $DB_USER
        ],
        'version' => $version,
        'tables' => $tables,
        'anvis_table_exists' => $anvis_exists,
        'anvis_count' => $anvis_count
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
