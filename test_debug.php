<?php
require 'api/config.php';

try {
    // Verificar ANVIs
    $result = $pdo->query('SELECT COUNT(*) as total FROM anvis');
    $row = $result->fetch();
    echo "Total de ANVIs: " . $row['total'] . PHP_EOL;
    
    // Listar IDs
    $result2 = $pdo->query('SELECT id FROM anvis LIMIT 5');
    echo "ANVIs disponíveis:" . PHP_EOL;
    while($r = $result2->fetch()) {
        echo "  - ID: " . $r['id'] . PHP_EOL;
    }
    
    // Verificar tabelas
    echo PHP_EOL . "Tabelas existentes:" . PHP_EOL;
    $tables = ['anvis', 'anvis_historico', 'invoices', 'subscriptions', 'projetos'];
    foreach($tables as $table) {
        try {
            $pdo->query("SELECT 1 FROM $table LIMIT 1");
            echo "  ✓ $table existe" . PHP_EOL;
        } catch(Exception $e) {
            echo "  ✗ $table NÃO existe" . PHP_EOL;
        }
    }
    
} catch(Exception $e) {
    echo "Erro: " . $e->getMessage() . PHP_EOL;
}
