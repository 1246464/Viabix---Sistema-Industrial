<?php
/**
 * Password Reset System
 * 
 * Fluxo:
 * 1. User pede reset enviando email: POST /api/password_reset.php?action=request
 * 2. Sistema gera token e envia email
 * 3. User clica link no email
 * 4. User submete nova senha: POST /api/password_reset.php?action=reset
 * 
 * Location: api/password_reset.php
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'request';

try {
    switch ($action) {
        case 'request':
            handlePasswordResetRequest();
            break;
            
        case 'reset':
            handlePasswordReset();
            break;
            
        case 'validate':
            handleTokenValidation();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida']);
            break;
    }
} catch (Exception $e) {
    logError('Password reset error', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar reset de senha']);
}

/**
 * REQUEST: Usuário pede reset de senha
 * POST /api/password_reset.php?action=request
 * Body: { "email": "user@example.com" }
 */
function handlePasswordResetRequest() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }
    
    // Rate limiting
    $rateLimit = viabixCheckIpRateLimit('password_reset', 3, 3600); // 3 tentativas por hora
    if (!$rateLimit['allowed']) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Muitas tentativas. Tente novamente em ' . $rateLimit['reset_in'] . ' segundos',
            'error_code' => 'rate_limit_exceeded'
        ]);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $email = trim($input['email'] ?? '');
    $tenantId = trim($input['tenant_id'] ?? '');
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Email inválido']);
        exit;
    }
    
    // Buscar usuário por email (com tenant_id se disponível)
    // SEGURANÇA: Se houver múltiplos usuários com o mesmo email em tenants diferentes,
    // exigir tenant_id para evitar cross-tenant password reset
    $sql = 'SELECT id, login, nome, email, tenant_id FROM usuarios WHERE email = ?';
    $params = [$email];
    
    if (!empty($tenantId)) {
        $sql .= ' AND tenant_id = ?';
        $params[] = $tenantId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Segurança: Se há múltiplos usuários com este email (em tenants diferentes)
    // e nenhum tenant_id foi fornecido, exigir tenant_id
    if (count($users) > 1 && empty($tenantId)) {
        // Ao invés de revelar que há múltiplos, exigir tenant_id
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Por favor, forneça seu tenant_id para completar o reset de senha',
            'error_code' => 'tenant_id_required'
        ]);
        exit;
    }
    
    if (count($users) === 0) {
        // Por segurança, não revelar se email existe ou não
        // Apenas retornar sucesso (segue o padrão de muitos sites)
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Se o email estiver cadastrado, você receberá um link de reset em breve'
        ]);
        exit;
    }
    
    $user = $users[0];
    
    // Gerar token único
    $token = bin2hex(random_bytes(32)); // 64 caracteres hexadecimais
    $tokenHash = hash('sha256', $token); // Hash para armazenar (nunca armazenar token em plaintext)
    $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora de validade
    
    // Armazenar token de reset
    if (!viabixHasTable('password_reset_tokens')) {
        // Criar tabela se não existir
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS password_reset_tokens (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id VARCHAR(36) NOT NULL,
                token_hash VARCHAR(64) NOT NULL UNIQUE,
                email VARCHAR(255) NOT NULL,
                used_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                
                FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at)
            )
        ");
    }
    
    // Limpar tokens antigos do usuário
    $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? AND expires_at < NOW()');
    $stmt->execute([$user['id']]);
    
    // Inserir novo token
    $stmt = $pdo->prepare('
        INSERT INTO password_reset_tokens (user_id, token_hash, email, expires_at)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([$user['id'], $tokenHash, $email, $expiresAt]);
    
    // Enviar email com link de reset
    $resetUrl = (getenv('APP_URL') ?: 'https://app.viabix.com') . '/reset-password.html?token=' . $token;
    
    $emailResult = viabixSendPasswordResetEmail(
        $email,
        $user['nome'],
        $token,
        $resetUrl
    );
    
    if (!$emailResult['success']) {
        logError('Failed to send password reset email', [
            'email' => $email,
            'user_id' => $user['id'],
            'error' => $emailResult['message']
        ]);
        
        if (function_exists('viabixSentryMessage')) {
            viabixSentryMessage("Password reset email failed: {$email}", 'error');
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Link de reset de senha foi enviado para seu email'
    ]);
}

/**
 * VALIDATE: Validar se token é válido
 * GET /api/password_reset.php?action=validate&token=xxxxx
 */
function handleTokenValidation() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }
    
    $token = $_GET['token'] ?? '';
    
    if (empty($token) || strlen($token) !== 64) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    if (!viabixHasTable('password_reset_tokens')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
        exit;
    }
    
    $tokenHash = hash('sha256', $token);
    
    $stmt = $pdo->prepare('
        SELECT id, user_id, email, expires_at, used_at
        FROM password_reset_tokens
        WHERE token_hash = ? LIMIT 1
    ');
    $stmt->execute([$tokenHash]);
    $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenRecord) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    // Verificar se já foi usado
    if ($tokenRecord['used_at'] !== null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token já foi utilizado']);
        exit;
    }
    
    // Verificar se expirou
    if (time() > strtotime($tokenRecord['expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token expirou. Solicite um novo']);
        exit;
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Token válido',
        'email' => $tokenRecord['email']
    ]);
}

/**
 * RESET: Resetar senha com token válido
 * POST /api/password_reset.php?action=reset
 * Body: { "token": "xxxxx", "password": "new_password" }
 */
function handlePasswordReset() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit;
    }
    
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $token = $input['token'] ?? '';
    $newPassword = $input['password'] ?? '';
    
    // Validar senha
    if (strlen($newPassword) < 8) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Senha deve ter no mínimo 8 caracteres']);
        exit;
    }
    
    if (empty($token) || strlen($token) !== 64) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    if (!viabixHasTable('password_reset_tokens')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido ou expirado']);
        exit;
    }
    
    $tokenHash = hash('sha256', $token);
    
    $stmt = $pdo->prepare('
        SELECT id, user_id, expires_at, used_at
        FROM password_reset_tokens
        WHERE token_hash = ? LIMIT 1
    ');
    $stmt->execute([$tokenHash]);
    $tokenRecord = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tokenRecord) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token inválido']);
        exit;
    }
    
    // Verificar se expirou
    if (time() > strtotime($tokenRecord['expires_at'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token expirou. Solicite um novo']);
        exit;
    }
    
    // Verificar se já foi usado
    if ($tokenRecord['used_at'] !== null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Token já foi utilizado']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Atualizar senha do usuário
        $hashedPassword = hashPassword($newPassword);
        $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $tokenRecord['user_id']]);
        
        // Marcar token como usado
        $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?');
        $stmt->execute([$tokenRecord['id']]);
        
        $pdo->commit();
        
        // Log de atividade
        if (function_exists('viabixLogActivity')) {
            viabixLogActivity($tokenRecord['user_id'], 'password_reset', 'Senha resetada via link de recuperação');
        }
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Senha atualizada com sucesso. Você pode fazer login agora.'
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        logError('Password reset failed', ['error' => $e->getMessage()]);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erro ao resetar senha']);
    }
}

?>
