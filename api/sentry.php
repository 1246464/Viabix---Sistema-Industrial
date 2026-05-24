<?php
/**
 * Sentry Logging Integration - Lightweight Implementation
 * Viabix SaaS Monitoring & Error Tracking
 * 
 * Conecta-se ao Sentry via HTTP para capturar erros, exceções e eventos.
 * Funciona sem dependências externas (apenas cURL).
 */

class ViabixSentry {
    private static $instance = null;
    private $dsn;
    private $projectId;
    private $publicKey;
    private $enabled = false;
    private $environment = 'production';
    private $release = '1.0.0';
    private $serverName;
    private $userId;
    private $tags = [];
    private $breadcrumbs = [];

    private function __construct($dsn, $environment = 'production', $release = '1.0.0') {
        if (empty($dsn)) {
            return;
        }

        $this->dsn = $dsn;
        $this->environment = $environment;
        $this->release = $release;
        $this->serverName = $_SERVER['HTTP_HOST'] ?? gethostname();
        $this->userId = $_SESSION['user_id'] ?? null;

        // Parse DSN: https://key@sentry.io/projectId
        if (preg_match('/^https?:\/\/([a-f0-9]+)@([^\/]+)\/(\d+)$/', $dsn, $matches)) {
            $this->publicKey = $matches[1];
            $this->projectId = $matches[3];
            $this->enabled = true;
        }
    }

    public static function getInstance($dsn = null, $environment = 'production', $release = '1.0.0') {
        if (self::$instance === null) {
            self::$instance = new self($dsn ?? getenv('SENTRY_DSN'), $environment, $release);
        }
        return self::$instance;
    }

    public function isEnabled() {
        return $this->enabled;
    }

    public function setUser($userId, $email = null, $username = null, $extraData = []) {
        $this->userId = $userId;
        $this->tags['user_id'] = (string) $userId;
        if ($email) {
            $this->tags['user_email'] = $email;
        }
        if ($username) {
            $this->tags['user_name'] = $username;
        }
    }

    public function setTenantContext($tenantId, $tenantName = null) {
        $this->tags['tenant_id'] = (string) $tenantId;
        if ($tenantName) {
            $this->tags['tenant_name'] = $tenantName;
        }
    }

    public function addTag($key, $value) {
        $this->tags[$key] = (string) $value;
    }

    public function addBreadcrumb($message, $category = 'general', $level = 'info', $data = []) {
        $this->breadcrumbs[] = [
            'timestamp' => time(),
            'message' => $message,
            'category' => $category,
            'level' => $level,
            'data' => $data,
        ];

        // Manter apenas os últimos 100 breadcrumbs
        if (count($this->breadcrumbs) > 100) {
            array_shift($this->breadcrumbs);
        }
    }

    public function captureException(\Throwable $exception, $level = 'error', $data = []) {
        if (!$this->enabled) {
            return null;
        }

        $eventId = $this->generateEventId();
        $payload = $this->buildPayload([
            'exception' => [
                'values' => [
                    $this->formatException($exception),
                ],
            ],
            'level' => $level,
        ], $eventId, $data);

        $this->send($payload);
        return $eventId;
    }

    public function captureMessage($message, $level = 'info', $category = 'general', $data = []) {
        if (!$this->enabled) {
            return null;
        }

        $eventId = $this->generateEventId();
        $payload = $this->buildPayload([
            'message' => [
                'formatted' => $message,
            ],
            'level' => $level,
        ], $eventId, $data);

        $this->send($payload);
        return $eventId;
    }

    private function formatException(\Throwable $exception) {
        $frames = [];
        $trace = $exception->getTrace();

        foreach ($trace as $frame) {
            $frames[] = [
                'function' => $frame['function'] ?? '<unknown>',
                'filename' => $frame['file'] ?? '<unknown>',
                'lineno' => $frame['line'] ?? 0,
                'colno' => 0,
            ];
        }

        return [
            'type' => get_class($exception),
            'value' => $exception->getMessage(),
            'stacktrace' => [
                'frames' => $frames,
            ],
        ];
    }

    private function buildPayload($event, $eventId, $customData = []) {
        $payload = array_merge([
            'event_id' => $eventId,
            'timestamp' => time(),
            'level' => 'error',
            'environment' => $this->environment,
            'release' => $this->release,
            'server_name' => $this->serverName,
            'tags' => $this->tags,
            'breadcrumbs' => [
                'values' => $this->breadcrumbs,
            ],
            'request' => [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'headers' => $this->sanitizeHeaders(),
            ],
            'contexts' => [
                'os' => [
                    'name' => PHP_OS_FAMILY,
                    'version' => PHP_VERSION,
                ],
            ],
            'extra' => $customData,
        ], $event);

        if ($this->userId) {
            $payload['user'] = [
                'id' => $this->userId,
            ];
        }

        return $payload;
    }

    private function sanitizeHeaders() {
        $headers = [];
        $dangerous = ['authorization', 'cookie', 'x-auth-token', 'x-api-key', 'x-csrf-token'];

        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = strtolower(substr($key, 5));
                if (!in_array($headerName, $dangerous, true)) {
                    $headers[$headerName] = (string) $value;
                }
            }
        }

        return $headers;
    }

    private function send($payload) {
        if (!function_exists('curl_init')) {
            error_log('[Sentry] cURL não disponível, não é possível enviar evento.');
            return;
        }

        $url = "https://sentry.io/api/{$this->projectId}/store/?sentry_key={$this->publicKey}&sentry_version=7";
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: ViabixSentry/1.0',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("[Sentry] Erro ao enviar evento: {$error}");
        } elseif ($httpCode !== 200) {
            error_log("[Sentry] HTTP {$httpCode}: {$response}");
        }
    }

    private function generateEventId() {
        return bin2hex(random_bytes(16));
    }
}

/**
 * Helpers rápidos de Sentry
 */
function viabix_sentry_init($dsn = null, $environment = 'production', $release = '1.0.0') {
    $dsn = $dsn ?? getenv('SENTRY_DSN');
    return ViabixSentry::getInstance($dsn, $environment, $release);
}

function viabix_sentry_exception(\Throwable $e, $level = 'error', $data = []) {
    $sentry = ViabixSentry::getInstance();
    return $sentry->captureException($e, $level, $data);
}

function viabix_sentry_message($message, $level = 'info', $category = 'general', $data = []) {
    $sentry = ViabixSentry::getInstance();
    return $sentry->captureMessage($message, $level, $category, $data);
}

function viabix_sentry_breadcrumb($message, $category = 'general', $level = 'info', $data = []) {
    $sentry = ViabixSentry::getInstance();
    $sentry->addBreadcrumb($message, $category, $level, $data);
}

/**
 * Log an error with optional data
 * Wrapper for consistent error logging across all systems
 */
function viabixLogError($message, $data = []) {
    viabix_sentry_message($message, 'error', 'error', $data);
}

function viabix_sentry_set_user($userId, $email = null, $username = null) {
    $sentry = ViabixSentry::getInstance();
    $sentry->setUser($userId, $email, $username);
}

function viabix_sentry_set_tenant($tenantId, $tenantName = null) {
    $sentry = ViabixSentry::getInstance();
    $sentry->setTenantContext($tenantId, $tenantName);
}

function viabix_sentry_tag($key, $value) {
    $sentry = ViabixSentry::getInstance();
    $sentry->addTag($key, $value);
}
