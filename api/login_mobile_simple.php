<?php
/**
 * Endpoint de Login para Mobile Apps - Versão Simplificada
 * Sem validação CSRF, apenas autenticação simples
 */

require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Apenas POST permitido']);
    exit;
}

// Obter dados (suporta form-urlencoded e JSON)
$data = $_POST;
if (empty($data)) {
    $json = json_decode(file_get_contents('php://input'), true);
    if ($json) {
        $data = $json;
    }
}

$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
    exit;
}

// Rate limiting: máximo 5 tentativas por IP a cada 5 minutos
require_once 'rate_limit.php';
$maxAttempts  = (int) viabix_env('RATE_LIMIT_LOGIN_MAX', 5);
$windowSeconds = (int) viabix_env('RATE_LIMIT_LOGIN_WINDOW', 300);
$rateLimitCheck = viabixCheckIpRateLimit('mobile_login', $maxAttempts, $windowSeconds);
viabixAddRateLimitHeaders($maxAttempts, max(0, $maxAttempts - $rateLimitCheck['attempts']), time() + $rateLimitCheck['reset_in']);
viabixEnforceRateLimit($rateLimitCheck, 'Muitas tentativas de login. Tente novamente em ' . ceil($rateLimitCheck['reset_in'] / 60) . ' minuto(s).');

try {
    // Iniciar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    
    // Buscar usuário
    $user = viabixFindUserForAuth($email);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
        exit;
    }
    
    if (!$user['ativo']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Usuário inativo']);
        exit;
    }
    
    // Verificar senha
    if (!verifyPassword($password, $user['senha'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Usuário ou senha inválidos']);
        exit;
    }
    
    // ✅ Login bem-sucedido!
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_level'] = $user['nivel'] ?? 'user';
    
    // Tenant
    if (viabixHasColumn('usuarios', 'tenant_id') && !empty($user['tenant_id'])) {
        $_SESSION['tenant_id'] = $user['tenant_id'];
    }
    
    // Gerar JWT token
    $jwtToken = viabixGenerateJwt($user['id'], $_SESSION['tenant_id'] ?? null);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'token' => $jwtToken,
        'user' => [
            'id' => $user['id'],
            'email' => $email,
            'nome' => $user['nome'] ?? 'N/A',
            'nivel' => $user['nivel'] ?? 'user'
        ]
    ]);
    
} catch (Exception $e) {
    error_log('[LOGIN_MOBILE] Error: ' . $e->getMessage());
    http_response_code(500);
    $isProduction = (viabix_env('APP_ENV', 'development') === 'production');
    echo json_encode([
        'success' => false,
        'message' => $isProduction ? 'Erro ao processar login' : ('Erro: ' . $e->getMessage()),
    ]);
}
