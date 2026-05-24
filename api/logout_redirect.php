<?php
/**
 * Logout Redirect - Sistema Viabix Unificado
 * Faz logout e redireciona para a tela de login
 */

require_once 'config.php';

// Iniciar sessão
session_start();

if (isset($_SESSION['user_id'])) {
    try {
        // Registrar log de logout
        $stmt = $pdo->prepare("INSERT INTO logs_atividade (usuario_id, acao, detalhes, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'logout',
            'Logout realizado',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (PDOException $e) {
        // Ignorar erros de log
    }
}

// Destruir sessão
$_SESSION = [];
session_destroy();

// Remover cookie da sessão
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirecionar para login
header('Location: ../login.html');
exit;
?>
