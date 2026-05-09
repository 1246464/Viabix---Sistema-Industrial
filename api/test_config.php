<?php
header('Content-Type: application/json');

$debug = [];

try {
    // Debug 1: Show connection parameters
    $debug['db_host'] = getenv('DB_HOST') ?: 'default (should be 127.0.0.1)';
    $debug['db_user'] = getenv('DB_USER') ?: 'default (should be viabix)';
    $debug['db_name'] = getenv('DB_NAME') ?: 'default (should be viabix_db)';
    
    require_once __DIR__ . '/config.php';
    
    $debug['defined_db_host'] = DB_HOST;
    $debug['defined_db_user'] = DB_USER;
    $debug['defined_db_name'] = DB_NAME;
    
    // Debug 2: List all tables
    $stmt = $pdo->query("SHOW TABLES");
    $all_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $debug['all_tables'] = $all_tables;
    $debug['anvis_exists'] = in_array('anvis', $all_tables);
    
    // Debug 3: Try to query anvis
    if (in_array('anvis', $all_tables)) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
        $count = $stmt->fetch();
        $debug['anvis_count'] = $count['total'];
    }
    
    echo json_encode([
        'status' => 'OK',
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'debug' => $debug
    ], JSON_PRETTY_PRINT);
}
?>
