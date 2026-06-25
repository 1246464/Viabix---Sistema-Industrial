<?php
/**
 * Observabilidade, senhas, erros e helpers HTTP.
 */

if (!defined('VIABIX_APP')) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

// GLOBAL ERROR & EXCEPTION HANDLERS
// ======================================================

/**
 * Handler global para erros do PHP
 */
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Não capturar erros silenciados com @
    if (!(error_reporting() & $errno)) {
        return true;
    }

    $level = 'error';
    $category = 'error.php';

    switch ($errno) {
        case E_WARNING:
        case E_USER_WARNING:
            $level = 'warning';
            $category = 'error.warning';
            break;
        case E_NOTICE:
        case E_USER_NOTICE:
            $level = 'info';
            $category = 'error.notice';
            break;
        case E_DEPRECATED:
        case E_USER_DEPRECATED:
            $level = 'info';
            $category = 'error.deprecated';
            break;
    }

    // Registrar breadcrumb para o Sentry
    viabix_sentry_breadcrumb($errstr, $category, $level, [
        'file' => $errfile,
        'line' => $errline,
    ]);

    // Log local
    logError("PHP Error [{$errno}]", [
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
    ]);

    return false;
});

/**
 * Handler global para exceções não capturadas
 */
set_exception_handler(function (\Throwable $e) {
    $level = 'error';
    if ($e instanceof \PDOException) {
        $level = 'error';
        viabix_sentry_tag('exception_type', 'database');
    }

    // Capturar no Sentry
    viabix_sentry_exception($e, $level, [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // Log local
    logError("Uncaught Exception: " . get_class($e), [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
    ]);

    // Responder com JSON
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Erro interno do servidor'
    ]);
    exit;
});

/**
 * Handler para shutdown - capturar erros fatais
 */
register_shutdown_function(function () {
    $lastError = error_get_last();
    if ($lastError !== null && in_array($lastError['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        viabix_sentry_message(
            'Fatal Error: ' . $lastError['message'],
            'error',
            'error.fatal',
            [
                'file' => $lastError['file'],
                'line' => $lastError['line'],
            ]
        );
    }
});

/**
 * Função para gerar hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * Função para verificar senha
 * Suporta tanto bcrypt (novo) quanto MD5 (legado)
 */
function verifyPassword($password, $hash) {
    $hash = (string) $hash;

    // Verificar hashes suportados pelo PHP, incluindo bcrypt/argon quando disponíveis.
    if (password_get_info($hash)['algo'] !== 0) {
        return password_verify($password, $hash);
    }
    
    // Verificar MD5 (legado - 32 caracteres hexadecimais)
    if (strlen($hash) === 32 && ctype_xdigit($hash)) {
        return md5($password) === $hash;
    }
    
    // Verificar SHA-256 legado (64 caracteres hexadecimais)
    if (strlen($hash) === 64 && ctype_xdigit($hash)) {
        return hash_equals(strtolower($hash), hash('sha256', $password));
    }

    // Hash desconhecido
    return false;
}

/**
 * Função para gerar ID único
 */
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

/**
 * Função para log seguro
 */
function logError($message, $context = []) {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    if (empty($context['error_id'])) {
        $context['error_id'] = viabixGenerateErrorId('err');
    }
    $context['url'] = $_SERVER['REQUEST_URI'] ?? null;
    $context['user_id'] = $context['user_id'] ?? ($_SESSION['user_id'] ?? null);
    $context['tenant_id'] = $context['tenant_id'] ?? ($_SESSION['tenant_id'] ?? null);

    $logEntry = json_encode([
        'timestamp' => date('c'),
        'level' => 'error',
        'message' => $message,
        'context' => $context,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    error_log($logEntry, 3, $logDir . '/error.log');

    // Enviar para Sentry também
    viabix_sentry_message($message, 'error', 'app.error', $context);
}

function viabixGenerateErrorId($prefix = 'err') {
    return $prefix . '_' . date('Ymd_His') . '_' . substr(hash('sha256', microtime(true) . random_int(1000, 9999)), 0, 8);
}

function viabixPublicErrorMessage(Throwable $e, $fallback = 'Não foi possível concluir esta ação agora. Tente novamente em instantes.') {
    if ($e instanceof PDOException) {
        $message = $e->getMessage();
        if (stripos($message, 'server has gone away') !== false || stripos($message, 'timeout') !== false || stripos($message, 'lock wait') !== false) {
            return 'O banco demorou para responder. Aguarde alguns segundos e tente novamente.';
        }
        if (stripos($message, 'unknown column') !== false || stripos($message, 'base table') !== false) {
            return 'A estrutura do banco precisa ser atualizada antes de continuar.';
        }
        if (stripos($message, 'duplicate') !== false) {
            return 'Já existe um registro com estas informações.';
        }
        if (stripos($message, 'foreign key') !== false) {
            return 'O vínculo escolhido não está mais disponível. Atualize a tela e tente novamente.';
        }
    }

    return $fallback;
}

/**
 * Função para verificar autenticação
 */
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado']);
        exit;
    }
    return $_SESSION['user_id'];
}

/**
 * Helper para verificar autenticação e inicializar CSRF na mesma chamada
 * Use em endpoints que exigem autenticação E CSRF protection
 */
function viabixRequireAuthenticatedSessionWithCsrf() {
    checkAuth();
    if (session_status() === PHP_SESSION_ACTIVE) {
        viabixInitializeCsrfProtection();
    }
}

/**
 * Função para verificar nível de acesso
 */
function checkLevel($requiredLevel) {
    if (!isset($_SESSION['user_level'])) {
        return false;
    }
    
    $levels = ['visitante' => 0, 'usuario' => 1, 'admin' => 2];
    $userLevel = $levels[$_SESSION['user_level']] ?? 0;
    $required = $levels[$requiredLevel] ?? 1;
    
    return $userLevel >= $required;
}

/**
 * Normaliza leitura de variáveis de ambiente com fallback padrão.
 */
function viabixEnv($name, $default = null) {
    return viabix_env($name, $default);
}

/**
 * Mantém apenas dígitos de documentos e telefones.
 */
function viabixDigitsOnly($value) {
    return preg_replace('/\D+/', '', (string) $value);
}

/**
 * Lê headers da requisição em ambientes variados do PHP.
 */
function viabixGetRequestHeader($headerName) {
    $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));

    if (isset($_SERVER[$normalized])) {
        return $_SERVER[$normalized];
    }

    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, $headerName) === 0) {
                return $value;
            }
        }
    }

    return null;
}

/**
 * Gera URL absoluta com base na requisição atual.
 */
function viabixBuildAbsoluteUrl($path) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 80) === 443)
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $relativePath = ltrim(str_replace('\\', '/', $path), '/');

    if ($basePath === '' || $basePath === '.') {
        return $scheme . '://' . $host . '/' . $relativePath;
    }

    return $scheme . '://' . $host . $basePath . '/' . $relativePath;
}

/**
 * Cache simples do schema para evitar consultas repetidas ao information_schema.
 */

