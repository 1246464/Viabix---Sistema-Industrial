<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "Testing config.php...\n";

try {
    require_once 'api/config.php';
    echo "✓ Config loaded successfully!\n";
    echo "PDO Connected: " . (isset($pdo) ? "YES" : "NO") . "\n";
} catch (Throwable $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}
?>
