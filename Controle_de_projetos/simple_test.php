<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/error_simple_test.log');

try {
    // Try to load config WITHOUT session requirement
    require_once 'config.php';
    
    // Try to get connection
    $pdo = getConnection();
    
    // Try to create tables
    createTables($pdo);
    
    // Test query
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    
    echo json_encode(['success' => true, 'message' => 'All tests passed', 'mysql_version' => $version]);
    
} catch (Throwable $e) {
    http_response_code(500);
    error_log('Simple Test Error: ' . $e->getMessage() . ' | File: ' . $e->getFile() . ' | Line: ' . $e->getLine());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
