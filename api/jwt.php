<?php
/**
 * Simple JWT Token Generator for Mobile Apps
 *
 * Suporte a dupla chave durante migração:
 * - JWT_SECRET: chave atual (usada para gerar e validar tokens novos)
 * - JWT_SECRET_LEGACY: chave antiga (usada apenas para validar tokens existentes em transição)
 */

/**
 * Retorna o JWT secret ativo, exigindo que esteja definido em produção.
 */
function viabixGetJwtSecret(): string {
    $secret = viabix_env('JWT_SECRET', '');
    if ($secret === '') {
        if (viabix_env('APP_ENV', 'development') === 'production') {
            error_log('[VIABIX] CRÍTICO: JWT_SECRET não configurado no ambiente de produção!');
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['error' => 'Erro de configuração do servidor']);
            exit;
        }
        // Fallback apenas para ambiente de desenvolvimento local
        $secret = 'viabix_dev_only_secret_not_for_production';
    }
    return $secret;
}

if (!function_exists('viabixGenerateJwt')) {
    function viabixGenerateJwt($userId, $tenantId = null, $expiresIn = 2592000) {
        $secret = viabixGetJwtSecret();
        
        // Header
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        // Payload
        $now = time();
        $payload = json_encode([
            'iat' => $now,
            'exp' => $now + $expiresIn,
            'user_id' => $userId,
            'tenant_id' => $tenantId,
            'type' => 'mobile_app'
        ]);
        
        // Encode
        $base64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $base64Payload = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        
        // Sign
        $signature = rtrim(
            strtr(
                base64_encode(
                    hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true)
                ),
                '+/',
                '-_'
            ),
            '='
        );
        
        return "$base64Header.$base64Payload.$signature";
    }
}

if (!function_exists('viabixValidateJwt')) {
    function viabixValidateJwt($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        [$base64Header, $base64Payload, $signature] = $parts;

        // Tenta validar com a chave atual primeiro
        $secrets = [viabixGetJwtSecret()];

        // Se JWT_SECRET_LEGACY estiver definido, aceita tokens antigos durante migração
        $legacySecret = viabix_env('JWT_SECRET_LEGACY', '');
        if ($legacySecret !== '') {
            $secrets[] = $legacySecret;
        }

        foreach ($secrets as $secret) {
            $expectedSignature = rtrim(
                strtr(
                    base64_encode(
                        hash_hmac('sha256', "$base64Header.$base64Payload", $secret, true)
                    ),
                    '+/',
                    '-_'
                ),
                '='
            );

            if (hash_equals($signature, $expectedSignature)) {
                // Decode payload
                $payload = json_decode(
                    base64_decode(strtr($base64Payload, '-_', '+/')),
                    true
                );

                if (!$payload || ($payload['exp'] ?? 0) < time()) {
                    return null;
                }

                return $payload;
            }
        }
        
        return null;
    }
}

/**
 * Valida JWT token vindo de múltiplas fontes:
 * 1. Authorization header (Bearer token)
 * 2. Cookie jwt_token
 * 3. GET parameter token (fallback)
 * 
 * Retorna o payload decodificado ou null se inválido
 */
if (!function_exists('viabixValidateJwtFromRequest')) {
    function viabixValidateJwtFromRequest() {
        $token = null;

        // 1. Tenta Authorization header
        $authHeader = isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : null;
        if ($authHeader && preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // 2. Tenta Cookie jwt_token
        if (!$token && isset($_COOKIE['jwt_token'])) {
            $token = $_COOKIE['jwt_token'];
        }

        // 3. Tenta GET parameter token (último recurso)
        if (!$token && isset($_GET['token'])) {
            $token = $_GET['token'];
        }

        if (!$token) {
            return null;
        }

        return viabixValidateJwt($token);
    }
}

/**
 * Valida autenticação com fallback:
 * 1. Primeiro tenta sessão PHP ($_SESSION)
 * 2. Depois tenta JWT (Authorization, Cookie, etc)
 * 
 * Retorna os dados do usuário ou null se não autenticado
 */
if (!function_exists('viabixGetAuthenticatedUser')) {
    function viabixGetAuthenticatedUser() {
        // Tenta sessão PHP primeiro
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return [
                'id' => $_SESSION['user_id'],
                'login' => $_SESSION['user_login'] ?? null,
                'nome' => $_SESSION['user_nome'] ?? null,
                'nivel' => $_SESSION['user_level'] ?? null,
                'tenant_id' => $_SESSION['tenant_id'] ?? null,
                'source' => 'session'
            ];
        }

        // Fallback para JWT
        $jwtPayload = viabixValidateJwtFromRequest();
        if ($jwtPayload && isset($jwtPayload['user_id'])) {
            global $pdo;

            $select = 'id, login, nome, nivel, ativo';
            if (viabixHasColumn('usuarios', 'tenant_id')) {
                $select .= ', tenant_id';
            }

            $params = [$jwtPayload['user_id']];
            $sql = 'SELECT ' . $select . ' FROM usuarios WHERE id = ?';
            if (viabixHasColumn('usuarios', 'tenant_id') && !empty($jwtPayload['tenant_id'])) {
                $sql .= ' AND tenant_id = ?';
                $params[] = $jwtPayload['tenant_id'];
            }

            $stmt = $pdo->prepare($sql . ' LIMIT 1');
            $stmt->execute($params);
            $user = $stmt->fetch();
            
            if ($user && (int) ($user['ativo'] ?? 1) === 1) {
                return [
                    'id' => $jwtPayload['user_id'],
                    'login' => $user['login'] ?? null,
                    'nome' => $user['nome'] ?? null,
                    'nivel' => $user['nivel'] ?? null,
                    'tenant_id' => $jwtPayload['tenant_id'] ?? ($user['tenant_id'] ?? null),
                    'source' => 'jwt'
                ];
            }
        }

        return null;
    }
}

