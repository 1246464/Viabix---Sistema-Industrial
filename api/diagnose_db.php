<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';
    
    // Mostrar informações do PDO
    $dsn_info = [
        'DB_HOST' => DB_HOST,
        'DB_USER' => DB_USER,
        'DB_NAME' => DB_NAME,
        'DB_PORT' => DB_PORT,
    ];
    
    // List all databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // List all tables in viabix_db
    $stmt = $pdo->query("SHOW TABLES FROM viabix_db");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Check if anvis table exists
    $anvis_exists = in_array('anvis', $tables);
    
    // If anvis exists, show count
    $anvis_count = 0;
    if ($anvis_exists) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
        $result = $stmt->fetch();
        $anvis_count = $result['total'];
    }
    
    echo json_encode([
        'status' => 'OK',
        'connection' => $dsn_info,
        'databases' => $databases,
        'tables_in_viabix_db' => $tables,
        'anvis_table_exists' => $anvis_exists,
        'anvis_record_count' => $anvis_count
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
