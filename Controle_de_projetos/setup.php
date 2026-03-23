<?php
// setup.php - Script de inicialização do banco de dados
require_once 'config.php';

echo "=== Inicializando Sistema de Login ===\n\n";

try {
    // Criar tabelas
    echo "Criando tabelas...\n";
    createTables();
    echo "✓ Tabelas criadas com sucesso!\n\n";
    
    // Verificar se existem usuários
    $conn = getConnection();
    $result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
    $row = $result->fetch_assoc();
    
    echo "Total de usuários cadastrados: " . $row['total'] . "\n\n";
    
    if ($row['total'] == 0) {
        echo "⚠️ Nenhum usuário encontrado. Os usuários padrão já foram criados.\n";
    } else {
        echo "✓ Sistema pronto para uso!\n\n";
        echo "Usuários cadastrados:\n";
        $result = $conn->query("SELECT username, nome, nivel FROM usuarios");
        while ($user = $result->fetch_assoc()) {
            echo "  - {$user['username']} ({$user['nome']}) - Nível: {$user['nivel']}\n";
        }
    }
    
    $conn->close();
    
    echo "\n=== Sistema inicializado com sucesso! ===\n";
    echo "\nAcesse: http://localhost/cristiano/login.php\n";
    echo "Usuário: admin\n";
    echo "Senha: 123\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
