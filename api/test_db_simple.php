<?php
require 'config.php';

echo "Banco de dados: " . DB_NAME . "\n";
echo "Host: " . DB_HOST . "\n";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM anvis");
    $cnt = $stmt->fetch()['cnt'];
    echo "Total de ANVIs: " . $cnt . "\n";
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
