<?php
/**
 * Resetar usuários - APENAS PARA TESTE
 * Acesse: http://localhost/fanavid/api/reset_usuarios.php
 */

require_once 'config.php';

// Só permitir em ambiente local
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Acesso negado');
}

try {
    // Limpar tabela de usuários
    $pdo->exec("DELETE FROM usuarios WHERE login IN ('admin', 'usuario', 'visitante')");
    
    // Inserir usuários com hashes corretos
    $usuarios = [
        [
            'id' => 'admin-001',
            'login' => 'admin',
            'nome' => 'Administrador',
            'senha' => password_hash('admin123', PASSWORD_DEFAULT),
            'nivel' => 'admin'
        ],
        [
            'id' => 'usuario-001',
            'login' => 'usuario',
            'nome' => 'Usuário Padrão',
            'senha' => password_hash('123456', PASSWORD_DEFAULT),
            'nivel' => 'usuario'
        ],
        [
            'id' => 'visitante-001',
            'login' => 'visitante',
            'nome' => 'Visitante',
            'senha' => password_hash('visit', PASSWORD_DEFAULT),
            'nivel' => 'visitante'
        ]
    ];
    
    foreach ($usuarios as $user) {
        $stmt = $pdo->prepare("INSERT INTO usuarios (id, login, nome, senha, nivel, ativo) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user['id'], $user['login'], $user['nome'], $user['senha'], $user['nivel']]);
        echo "Usuário {$user['login']} criado com sucesso!<br>";
    }
    
    echo "<br>✅ Usuários resetados com sucesso!";
    echo "<br><br><a href='/fanavid/index.html'>Voltar para o sistema</a>";
    
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>