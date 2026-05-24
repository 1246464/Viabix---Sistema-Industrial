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
    $pdo = getConnection();
    $total = (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();

    echo "Total de usuários cadastrados: " . $total . "\n\n";

    if ($total == 0) {
        echo "⚠️ Nenhum usuário encontrado. Os usuários padrão já foram criados.\n";
    } else {
        echo "✓ Sistema pronto para uso!\n\n";
        echo "Usuários cadastrados:\n";
        $stmt = $pdo->query("SELECT username, nome, nivel FROM usuarios");
        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$user['username']} ({$user['nome']}) - Nível: {$user['nivel']}\n";
        }
    }
    
    echo "\n=== Sistema inicializado com sucesso! ===\n";
    echo "\nAcesse: http://localhost/cristiano/login.php\n";
    echo "Usuário: admin\n";
    echo "Senha: 123\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
?>
