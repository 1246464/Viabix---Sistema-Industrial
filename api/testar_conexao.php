<?php
/**
 * Teste de conexão com o banco de dados
 */

require_once 'config.php';

echo "Testando conexão com o banco de dados...\n\n";

try {
    // Verificar se a conexão foi estabelecida
    echo "✓ Conexão estabelecida com sucesso!\n";
    echo "  Host: " . DB_HOST . "\n";
    echo "  Database: " . DB_NAME . "\n";
    echo "  User: " . DB_USER . "\n\n";
    
    // Verificar tabelas
    echo "Verificando tabelas...\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        echo "  ✓ $table\n";
    }
    
    // Contar usuários
    echo "\nVerificando usuários...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    echo "  Total de usuários: " . $result['total'] . "\n";
    
    // Listar usuários
    $stmt = $pdo->query("SELECT login, nome, nivel FROM usuarios");
    $usuarios = $stmt->fetchAll();
    
    foreach ($usuarios as $user) {
        echo "  - " . $user['login'] . " (" . $user['nome'] . ") - Nível: " . $user['nivel'] . "\n";
    }
    
    echo "\n===========================================\n";
    echo "BANCO DE DADOS PRONTO PARA USO!\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "\n✗ Erro: " . $e->getMessage() . "\n";
}
