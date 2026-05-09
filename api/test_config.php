<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/config.php';
    
    // Test 1: Check database connection
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    
    // Test 2: Check if anvis table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'anvis'");
    $tables = $stmt->fetchAll();
    
    // Test 3: Count anvis
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
    $count = $stmt->fetch();
    
    echo json_encode([
        'status' => 'OK',
        'database_version' => $result['version'],
        'anvis_table_exists' => count($tables) > 0,
        'anvis_count' => $count['total'],
        'db_user' => DB_USER,
        'db_name' => DB_NAME
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}
?>
