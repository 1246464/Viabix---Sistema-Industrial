<?php
/**
 * CORS Protection - Cross-Origin Resource Sharing
 * 
 * Gerencia acesso cross-origin de forma segura com whitelist de domínios.
 * Substitui a prática insegura de usar "Access-Control-Allow-Origin: *"
 */

/**
 * Domínios permitidos por padrão
 * Adicione o domínio de produção aqui quando for fazer deploy
 */
function viabixGetAllowedCorsOrigins() {
    $allowed = [
        'http://localhost:80',
        'http://localhost:8080',
        'http://localhost:3000',
        'http://127.0.0.1:80',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:3000',
    ];

    // Adicionar domínios de produção via variáveis de ambiente
    $envOrigins = viabix_env('CORS_ALLOWED_ORIGINS', '');
    if (!empty($envOrigins)) {
        $envList = array_map('trim', explode(',', $envOrigins));
        $allowed = array_merge($allowed, $envList);
    }

    return array_unique($allowed);
}

/**
 * Normalizar origin de request (remover trailing slash, etc)
 */
function viabixNormalizeOrigin($origin) {
    if (empty($origin)) {
        return null;
    }

    $origin = trim($origin);
    $origin = rtrim($origin, '/');

    // Validar que é uma URL válida
    if (!filter_var($origin, FILTER_VALIDATE_URL)) {
        return null;
    }

    return strtolower($origin);
}

/**
 * Verificar se origin é permitido
 */
function viabixIsOriginAllowed($origin) {
    $normalized = viabixNormalizeOrigin($origin);
    if (!$normalized) {
        return false;
    }

    $allowed = viabixGetAllowedCorsOrigins();
    return in_array($normalized, $allowed, true);
}

/**
 * Obter origin do request
 */
function viabixGetRequestOrigin() {
    // Tentar obter de header HTTP
    $origin = viabixGetRequestHeader('Origin');

    if (!empty($origin)) {
        return $origin;
    }

    // Fallback: construir a partir de SERVER vars
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return "$scheme://$host";
}

/**
 * Configurar headers CORS seguros
 * 
 * Deve ser chamado no início de endpoints que suportam CORS
 * 
 * @param array $allowedMethods GET, POST, PUT, DELETE, PATCH, OPTIONS
 * @param array $allowedHeaders X-Custom-Header, Content-Type, etc
 */
function viabixConfigureCors($allowedMethods = ['GET', 'POST', 'OPTIONS'], $allowedHeaders = ['Content-Type', 'Authorization', 'X-CSRF-Token']) {
    // Obter origin do request
    $requestOrigin = viabixGetRequestOrigin();

    // Verificar se é permitido
    if (!viabixIsOriginAllowed($requestOrigin)) {
        // Registrar tentativa suspeita
        viabix_sentry_message(
            'CORS Origin não permitida',
            'warning',
            'security.cors',
            [
                'requested_origin' => $requestOrigin,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ]
        );

        // NÃO enviar headers CORS se origin não permitido
        // Navegador vai rejeitar a requisição
        return;
    }

    // Headers CORS seguros
    header('Access-Control-Allow-Origin: ' . viabixNormalizeOrigin($requestOrigin), true);
    header('Access-Control-Allow-Methods: ' . implode(', ', $allowedMethods), true);
    header('Access-Control-Allow-Headers: ' . implode(', ', $allowedHeaders), true);
    header('Access-Control-Allow-Credentials: true', true);
    header('Access-Control-Max-Age: 3600', true);

    // Informar que o servidor entende CORS
    header('Vary: Origin', true);
}

/**
 * Handle preflight CORS requests (OPTIONS)
 * 
 * Deve ser chamado antes de qualquer lógica do endpoint
 * 
 * Uso:
 * <?php
 * require_once 'config.php';
 * viabixHandleCorsPreflight();
 * 
 * // Seu código aqui
 */
function viabixHandleCorsPreflight($allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], $maxAge = 3600) {
    // Configurar CORS primeiro
    viabixConfigureCors($allowedMethods);

    // Responder a preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * Middleware CORS para endpoints de API
 * 
 * Uso:
 * <?php
 * require_once 'config.php';
 * viabixCorsMiddleware(['GET', 'POST']);
 */
function viabixCorsMiddleware($allowedMethods = ['GET', 'POST', 'OPTIONS']) {
    viabixHandleCorsPreflight($allowedMethods);
}

/**
 * Verificar se request vem de origin permitido
 * 
 * Retorna verdadeiro se:
 * - Origin header é permitido, ou
 * - Não é uma requisição cross-origin (mesmo domínio)
 */
function viabixIsCorsRequestAllowed() {
    $origin = viabixGetRequestOrigin();
    return viabixIsOriginAllowed($origin);
}

/**
 * Obter lista de origins permitidas (para admin)
 */
function viabixGetCorsConfig() {
    return [
        'allowed_origins' => viabixGetAllowedCorsOrigins(),
        'current_origin' => viabixGetRequestOrigin(),
        'is_allowed' => viabixIsOriginAllowed(viabixGetRequestOrigin()),
        'environment' => APP_ENV,
    ];
}
