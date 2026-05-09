<?php
// Check MySQL user authentication methods on server
// This helps diagnose why PHP PDO/mysqli can't connect as root

try {
    // Try connection as root with empty password
    $mysqli = @new mysqli('localhost', 'root', '');
    
    echo "=== MySQL Connection Test ===\n";
    
    if ($mysqli->connect_error) {
        echo "❌ Root connection FAILED: " . $mysqli->connect_error . "\n\n";
    } else {
        echo "✅ Root connection SUCCESS\n\n";
        
        // Check user auth methods
        $result = $mysqli->query("SELECT user, authentication_string, plugin FROM mysql.user;");
        if ($result) {
            echo "=== MySQL Users ===\n";
            while ($row = $result->fetch_assoc()) {
                echo "User: " . $row['user'] . "\n";
                echo "  Plugin: " . $row['plugin'] . "\n";
                echo "  Auth String: " . (strlen($row['authentication_string']) > 0 ? "[SET]" : "[EMPTY]") . "\n";
                echo "\n";
            }
        }
        $mysqli->close();
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
?>
