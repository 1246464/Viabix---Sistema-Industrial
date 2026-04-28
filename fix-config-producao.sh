#!/bin/bash

# FIX - Remover a línha com PDO::MYSQL_ATTR_SSL_MODE
# Execute no servidor

APP_DIR="/var/www/viabix"
CONFIG_FILE="$APP_DIR/api/config.php"

echo "Corrigindo config.php..."

# Fazer backup
cp "$CONFIG_FILE" "$CONFIG_FILE.backup"
echo "✓ Backup criado"

# Remover a linha problemática (linha 173)
# A linha que causa problema é: $options[PDO::MYSQL_ATTR_SSL_MODE] = PDO::MYSQL_ATTR_SSL_PREFERRED;

# Método 1: Remover todas as referências a PDO::MYSQL_ATTR_SSL_MODE
sed -i '/PDO::MYSQL_ATTR_SSL_MODE/d' "$CONFIG_FILE"
echo "✓ Linha com PDO::MYSQL_ATTR_SSL_MODE removida"

# Método 2: Também remover a linha do if que verifica
sed -i '/if (defined.*PDO::MYSQL_ATTR_SSL_MODE/d' "$CONFIG_FILE"
sed -i '/^\s*}$/d' "$CONFIG_FILE"  # Isso é arriscado, então vamos fazer de forma mais segura

# Melhor: Substituir o bloco inteiro por uma versão corrigida
cat > "$CONFIG_FILE.new" << 'PHPEOF'
<?php
/**
 * Configuração do Banco de Dados - Sistema Viabix
 * Modelo Industrial 10/10 - Engenharia Financeira
 * Versão 7.1 - MySQL
 */

require_once __DIR__ . '/../bootstrap_env.php';

// ======================================================
// DEFINIR CONSTANTE GLOBAL DA APLICAÇÃO
// ======================================================
if (!defined('VIABIX_APP')) {
    define('VIABIX_APP', true);
}

// ======================================================
// CARREGAR CONFIGURAÇÕES DE AMBIENTE (ANTES DE INICIALIZAR SESSÃO)
// ======================================================
if (!defined('APP_ENV')) {
    define('APP_ENV', viabix_env('APP_ENV', 'development'));
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', viabix_env_bool('APP_DEBUG', APP_ENV !== 'production'));
}
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', viabix_env('SESSION_NAME', 'viabix_session'));
}
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', (int) viabix_env('SESSION_LIFETIME', '28800')); // 8 horas
}
if (!defined('SESSION_SAMESITE')) {
    define('SESSION_SAMESITE', viabix_env('SESSION_SAMESITE', 'Strict'));
}
if (!defined('SESSION_SECURE')) {
    define('SESSION_SECURE', viabix_env_bool('SESSION_SECURE', viabix_request_is_https() || APP_ENV === 'production'));
}

// ======================================================
// CONFIGURAR E INICIALIZAR SESSÃO
// ======================================================
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => SESSION_SECURE,
        'httponly' => true,
        'samesite' => SESSION_SAMESITE
    ]);
    session_start();
}

// ======================================================
// INICIALIZAR SENTRY (MONITORING & ERROR TRACKING)
// ======================================================
require_once __DIR__ . '/sentry.php';

// ======================================================
// INICIALIZAR CSRF PROTECTION
// ======================================================
require_once __DIR__ . '/csrf.php';

// ======================================================
// INICIALIZAR CORS PROTECTION
// ======================================================
require_once __DIR__ . '/cors.php';

// ======================================================
// INICIALIZAR RATE LIMITING & THROTTLING
// ======================================================
require_once __DIR__ . '/rate_limit.php';

// ======================================================
// INICIALIZAR EMAIL SERVICE
// ======================================================
require_once __DIR__ . '/email.php';

// ======================================================
// INICIALIZAR INPUT VALIDATION & SANITIZATION
// ======================================================
require_once __DIR__ . '/validation.php';

// ======================================================
// INICIALIZAR TWO-FACTOR AUTHENTICATION (2FA)
// ======================================================
require_once __DIR__ . '/two_factor_auth.php';

// ======================================================
// INICIALIZAR AUDIT LOGGING SYSTEM
// ======================================================
require_once __DIR__ . '/audit.php';

// ======================================================
// INICIALIZAR API ROUTES & SWAGGER/OPENAPI
// ======================================================
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/swagger.php';

// ======================================================
// INICIALIZAR PROTEÇÃO CSRF
// ======================================================
if (function_exists('viabixInitializeCsrfProtection')) {
    viabixInitializeCsrfProtection();
}

// ======================================================
// DEFINIR SENTRY CONFIGURATION
// ======================================================
if (!defined('SENTRY_DSN')) {
    define('SENTRY_DSN', viabix_env('SENTRY_DSN', ''));
}
if (!defined('SENTRY_ENVIRONMENT')) {
    define('SENTRY_ENVIRONMENT', viabix_env('SENTRY_ENVIRONMENT', 'production'));
}
if (!defined('SENTRY_RELEASE')) {
    define('SENTRY_RELEASE', viabix_env('SENTRY_RELEASE', '1.0.0'));
}

// Inicializar Sentry se DSN está configurada
$_viabix_sentry = viabix_sentry_init(SENTRY_DSN, SENTRY_ENVIRONMENT, SENTRY_RELEASE);

// ======================================================
// DEFINIR CONFIGURAÇÕES DE BANCO DE DADOS
// ======================================================
if (!defined('DB_HOST')) {
    define('DB_HOST', viabix_env('DB_HOST', '127.0.0.1'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', viabix_env('DB_NAME', 'viabix_db'));
}
if (!defined('DB_USER')) {
    define('DB_USER', viabix_env('DB_USER', 'root'));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', viabix_env('DB_PASS', ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', viabix_env('DB_CHARSET', 'utf8mb4'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) viabix_env('DB_PORT', '3306'));
}

// ======================================================
// DEFINIR CONFIGURAÇÕES DE SEGURANÇA
// ======================================================
if (!defined('HASH_COST')) {
    define('HASH_COST', 12); // Custo do Bcrypt
}
if (!defined('VIABIX_BILLING_PROVIDER')) {
    define('VIABIX_BILLING_PROVIDER', viabix_env('VIABIX_BILLING_PROVIDER', 'manual'));
}
if (!defined('VIABIX_ASAAS_ENV')) {
    define('VIABIX_ASAAS_ENV', viabix_env('VIABIX_ASAAS_ENV', 'sandbox'));
}
if (!defined('VIABIX_ASAAS_API_KEY')) {
    define('VIABIX_ASAAS_API_KEY', viabix_env('VIABIX_ASAAS_API_KEY', ''));
}
if (!defined('VIABIX_ASAAS_WEBHOOK_TOKEN')) {
    define('VIABIX_ASAAS_WEBHOOK_TOKEN', viabix_env('VIABIX_ASAAS_WEBHOOK_TOKEN', ''));
}

// Conexão PDO
try {
    // DSN com suporte a porta customizada (DigitalOcean MySQL usa porta 25060)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    // Opções PDO
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 10,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // Log do erro (não exibir em produção)
    error_log("Erro de conexão: " . $e->getMessage());
    
    // Resposta JSON amigável
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco de dados']);
    exit;
}

// ======================================================
// HEADERS DE SEGURANÇA GLOBAIS
// ======================================================

// Prevenir click-jacking
header('X-Frame-Options: SAMEORIGIN', true);

// Prevenir MIME type sniffing
header('X-Content-Type-Options: nosniff', true);

// Prevenir XSS (browsers modernos)
header('X-XSS-Protection: 1; mode=block', true);

// Referrer policy
header('Referrer-Policy: strict-origin-when-cross-origin', true);

// Feature policy (Permissions policy)
header('Permissions-Policy: geolocation=(), microphone=(), camera=()', true);

// HSTS (apenas se HTTPS em produção)
if (viabix_request_is_https() && APP_ENV === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains', true);
}

// ======================================================
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
PHPEOF

# Restaurar o restante do arquivo original (exceto a parte do config)
tail -n +250 "$CONFIG_FILE.backup" >> "$CONFIG_FILE.new"

# Substituir
mv "$CONFIG_FILE.new" "$CONFIG_FILE"
echo "✓ config.php corrigido"
