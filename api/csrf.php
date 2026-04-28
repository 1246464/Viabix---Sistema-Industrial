<?php
/**
 * CSRF Protection - Sistema Viabix
 * 
 * Proteção contra Cross-Site Request Forgery usando tokens únicos por sessão.
 * Funciona com formulários HTML tradicionais e requisições AJAX.
 */

/**
 * Inicializar proteção CSRF na sessão
 * Deve ser chamado após session_start()
 */
function viabixInitializeCsrfProtection() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Sessão deve estar ativa antes de inicializar CSRF');
    }

    // Garantir que o token CSRF existe e é válido
    if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32)); // 64 caracteres
        $_SESSION['_csrf_token_time'] = time();
    }

    // Regenerar token periodicamente (a cada 1 hora)
    $tokenAge = time() - ($_SESSION['_csrf_token_time'] ?? 0);
    if ($tokenAge > 3600) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['_csrf_token_time'] = time();
        viabix_sentry_breadcrumb('Token CSRF regenerado', 'security.csrf', 'info');
    }
}

/**
 * Obter o token CSRF atual
 * Use em formulários HTML e chamadas AJAX
 */
function viabixGetCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return null;
    }

    viabixInitializeCsrfProtection();
    return $_SESSION['_csrf_token'] ?? null;
}

/**
 * Campo oculto para formulários HTML
 * 
 * Uso:
 * <form method="POST">
 *     <?php echo viabixCsrfField(); ?>
 *     <input type="text" name="username">
 *     <button>Enviar</button>
 * </form>
 */
function viabixCsrfField() {
    $token = viabixGetCsrfToken();
    if (!$token) {
        return '';
    }
    return sprintf(
        '<input type="hidden" name="_csrf_token" value="%s">',
        htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
    );
}

/**
 * Verificar e validar token CSRF com input já decodificado
 * 
 * Use quando já decodificou o input JSON para evitar ler php://input novamente.
 * 
 * @param array $input Dados já decodificados
 * @throws RuntimeException Se token inválido
 */
function viabixValidateCsrfTokenWithInput($input = []) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('CSRF: Sessão não ativa');
    }

    // Pegar token do request
    $submittedToken = null;

    // 1. Verificar formulário POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // POST form-data
        $submittedToken = $_POST['_csrf_token'] ?? null;

        // JSON body (usar input já decodificado)
        if (!$submittedToken && is_array($input)) {
            $submittedToken = $input['_csrf_token'] ?? null;
        }

        // Header HTTP
        if (!$submittedToken) {
            $submittedToken = viabixGetRequestHeader('X-CSRF-Token');
        }
    }

    // Token da sessão
    $sessionToken = $_SESSION['_csrf_token'] ?? null;

    // Validação
    if (empty($submittedToken) || empty($sessionToken)) {
        viabix_sentry_message('CSRF token ausente ou vazio', 'warning', 'security.csrf', [
            'has_submitted' => (bool) $submittedToken,
            'has_session' => (bool) $sessionToken,
        ]);
        throw new RuntimeException('CSRF token ausente ou inválido');
    }

    // Comparação segura com hash_equals
    if (!hash_equals($sessionToken, $submittedToken)) {
        viabix_sentry_message('CSRF token mismatch', 'error', 'security.csrf', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
        throw new RuntimeException('CSRF token inválido');
    }
}

/**
 * Verificar e validar token CSRF
 * 
 * Lança exceção se token inválido ou ausente.
 * Use no início de endpoints que aceitam POST/PUT/DELETE.
 * 
 * @throws RuntimeException Se token inválido
 */
function viabixValidateCsrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('CSRF: Sessão não ativa');
    }

    // Pegar token do request
    $submittedToken = null;

    // 1. Verificar formulário POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // POST form-data
        $submittedToken = $_POST['_csrf_token'] ?? null;

        // JSON body
        if (!$submittedToken) {
            $input = json_decode(file_get_contents('php://input'), true);
            $submittedToken = $input['_csrf_token'] ?? null;
        }

        // Header HTTP
        if (!$submittedToken) {
            $submittedToken = viabixGetRequestHeader('X-CSRF-Token');
        }
    }

    // Token da sessão
    $sessionToken = $_SESSION['_csrf_token'] ?? null;

    // Validação
    if (empty($submittedToken) || empty($sessionToken)) {
        viabix_sentry_message('CSRF token ausente ou vazio', 'warning', 'security.csrf', [
            'has_submitted' => (bool) $submittedToken,
            'has_session' => (bool) $sessionToken,
        ]);
        throw new RuntimeException('CSRF token ausente ou inválido');
    }

    // Comparação segura com hash_equals
    if (!hash_equals($sessionToken, $submittedToken)) {
        viabix_sentry_message('CSRF token mismatch', 'error', 'security.csrf', [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]);
        throw new RuntimeException('CSRF token inválido');
    }
}

/**
 * Middleware para validação automática de CSRF
 * 
 * Use no topo de endpoints que aceitam POST/PUT/DELETE:
 * 
 * <?php
 * require_once 'config.php';
 * viabixRequirePostMethod();
 * viabixValidateCsrfMiddleware();
 */
function viabixValidateCsrfMiddleware() {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Pular validação para GET, HEAD, OPTIONS
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    // Validar CSRF para POST, PUT, DELETE, PATCH
    try {
        viabixValidateCsrfToken();
    } catch (RuntimeException $e) {
        viabix_sentry_exception($e, 'warning');
        
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'CSRF token inválido. Recarregue a página e tente novamente.',
        ]);
        exit;
    }
}

/**
 * Helper para POST-only endpoints com CSRF validação
 * 
 * Uso:
 * <?php
 * require_once 'config.php';
 * viabixRequirePostWithCsrf();
 */
function viabixRequirePostWithCsrf() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Método não permitido']);
        exit;
    }

    viabixValidateCsrfToken();
}

/**
 * Enviar token CSRF como JSON (para AJAX)
 */
function viabixRespondWithCsrfToken() {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'csrf_token' => viabixGetCsrfToken(),
    ]);
    exit;
}

/**
 * Gerar script JavaScript para incluir CSRF automaticamente em AJAX
 * 
 * Adicione isso no <head> de páginas que fazem requisições AJAX:
 * 
 * <script>
 *     <?php echo viabixCsrfAjaxScript(); ?>
 * </script>
 */
function viabixCsrfAjaxScript() {
    $token = viabixGetCsrfToken();
    if (!$token) {
        return '';
    }

    return <<<'JS'
// CSRF Protection para AJAX
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                      document.querySelector('input[name="_csrf_token"]')?.getAttribute('value') ||
                      null;
    
    if (!csrfToken) {
        console.warn('CSRF token não encontrado na página. AJAX pode estar vulnerável.');
        return;
    }
    
    // Interceptar fetch
    const originalFetch = window.fetch;
    window.fetch = function(resource, config = {}) {
        config = config || {};
        
        // Adicionar CSRF token para POST, PUT, DELETE, PATCH
        const method = (config.method || 'GET').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
            config.headers = config.headers || {};
            config.headers['X-CSRF-Token'] = csrfToken;
        }
        
        return originalFetch.call(this, resource, config);
    };
    
    // Interceptar XMLHttpRequest (para jquery.ajax, etc)
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this._ajaxMethod = method;
        return originalOpen.call(this, method, url, ...rest);
    };
    
    const originalSetRequestHeader = XMLHttpRequest.prototype.setRequestHeader;
    XMLHttpRequest.prototype.setRequestHeader = function(header, value) {
        if (header.toLowerCase() === 'x-csrf-token') {
            this._csrfTokenSet = true;
        }
        return originalSetRequestHeader.call(this, header, value);
    };
    
    const originalSend = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(data) {
        const method = (this._ajaxMethod || '').toUpperCase();
        if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method) && !this._csrfTokenSet) {
            originalSetRequestHeader.call(this, 'X-CSRF-Token', csrfToken);
        }
        return originalSend.call(this, data);
    };
})();
JS;
}
