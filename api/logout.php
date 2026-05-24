<?php
/**
 * Logout - Sistema Viabix
 */

require_once 'config.php';

header('Content-Type: application/json');

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
        logError("Erro ao registrar logout", ['error' => $e->getMessage()]);
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

echo json_encode(['success' => true, 'message' => 'Logout realizado com sucesso']);
?>