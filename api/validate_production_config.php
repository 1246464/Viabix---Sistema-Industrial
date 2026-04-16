<?php
/**
 * ⚙️ VIABIX PRODUCTION CONFIGURATION VALIDATOR
 * 
 * Este script valida todas as configurações .env antes de deploying para produção.
 * Uso: php api/validate_production_config.php
 * 
 * Status Codes:
 * ✅ PASS - Configuração OK
 * ⚠️  WARN - Aviso, mas funciona
 * ❌ FAIL - Erro crítico, impede produção
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class ProductionValidator {
    private $results = [];
    private $critical_failures = 0;
    private $warnings = 0;
    private $passes = 0;

    public function run() {
        echo "\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║     🔍 VIABIX PRODUCTION CONFIGURATION VALIDATOR              ║\n";
        echo "║     v1.0.0 - Deployment Check                                  ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        // Load environment
        $this->load_env();

        // Run all validations
        $this->validate_environment_mode();
        $this->validate_security_headers();
        $this->validate_database();
        $this->validate_sessions();
        $this->validate_cache();
        $this->validate_redis();
        $this->validate_email();
        $this->validate_billing();
        $this->validate_sentry();
        $this->validate_backup();
        $this->validate_encryption();
        $this->validate_file_permissions();
        $this->validate_ssl_certificate();
        $this->validate_rate_limiting();
        $this->validate_cors();

        // Print results
        $this->print_results();

        // Exit with appropriate code
        exit($this->critical_failures > 0 ? 1 : 0);
    }

    private function load_env() {
        $env_file = __DIR__ . '/../.env';
        if (!file_exists($env_file)) {
            $this->fail("❌ FAIL", ".env file not found at $env_file");
            return;
        }

        $this->pass("✅ PASS", ".env file found and readable");
        $_ENV = parse_ini_file($env_file, false);
    }

    private function validate_environment_mode() {
        echo "\n[1/15] Environment Mode\n";
        echo str_repeat("-", 60) . "\n";

        $app_env = $_ENV['APP_ENV'] ?? 'development';
        
        if ($app_env !== 'production') {
            $this->fail("❌ FAIL", "APP_ENV must be 'production', is: '$app_env'");
        } else {
            $this->pass("✅ PASS", "APP_ENV = production");
        }

        $app_debug = $_ENV['APP_DEBUG'] ?? 'true';
        if ($app_debug === 'true' || $app_debug === '1') {
            $this->fail("❌ FAIL", "APP_DEBUG must be false in production");
        } else {
            $this->pass("✅ PASS", "APP_DEBUG = false");
        }

        $security_mode = $_ENV['SECURITY_MODE'] ?? 'permissive';
        if ($security_mode !== 'strict') {
            $this->warn("⚠️  WARN", "SECURITY_MODE should be 'strict', is: '$security_mode'");
        } else {
            $this->pass("✅ PASS", "SECURITY_MODE = strict");
        }
    }

    private function validate_security_headers() {
        echo "\n[2/15] Security Headers\n";
        echo str_repeat("-", 60) . "\n";

        $checks = [
            'SECURITY_HEADERS_HSTS' => 'HSTS (HTTP Strict Transport Security)',
            'CSRF_ENABLED' => 'CSRF Protection',
            'SECURITY_HEADERS_X_FRAME_OPTIONS' => 'X-Frame-Options',
            'SECURITY_HEADERS_X_CONTENT_TYPE' => 'X-Content-Type-Options',
        ];

        foreach ($checks as $key => $label) {
            $value = $_ENV[$key] ?? 'false';
            if ($value === 'true' || $value === '1') {
                $this->pass("✅ PASS", "$label enabled");
            } else {
                $this->fail("❌ FAIL", "$label is disabled");
            }
        }
    }

    private function validate_database() {
        echo "\n[3/15] Database Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $required_vars = [
            'DB_HOST' => 'Database Host',
            'DB_NAME' => 'Database Name',
            'DB_USER' => 'Database User',
            'DB_PASS' => 'Database Password',
        ];

        foreach ($required_vars as $key => $label) {
            $value = $_ENV[$key] ?? '';
            if (empty($value)) {
                $this->fail("❌ FAIL", "$label ($key) is not set");
            } elseif ($value === 'CHANGE_ME' || $value === 'troque-esta-senha') {
                $this->fail("❌ FAIL", "$label ($key) has placeholder value");
            } else {
                $masked = strlen($value) > 3 ? substr($value, 0, 3) . '***' : '***';
                $this->pass("✅ PASS", "$label is configured ($masked)");
            }
        }

        // Try to connect
        $this->test_database_connection();
    }

    private function test_database_connection() {
        try {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $db = $_ENV['DB_NAME'] ?? '';
            $user = $_ENV['DB_USER'] ?? '';
            $pass = $_ENV['DB_PASS'] ?? '';

            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            $result = $pdo->query('SELECT 1');
            $this->pass("✅ PASS", "Database connection successful");
            
        } catch (PDOException $e) {
            $this->fail("❌ FAIL", "Database connection failed: " . $e->getMessage());
        }
    }

    private function validate_sessions() {
        echo "\n[4/15] Session Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $checks = [
            'SESSION_SECURE' => 'Session must be transmitted over HTTPS only',
            'SESSION_HTTPONLY' => 'Session must not be accessible from JavaScript',
            'SESSION_SAMESITE' => 'SameSite cookie attribute protects against CSRF',
        ];

        foreach ($checks as $key => $label) {
            $value = $_ENV[$key] ?? '';
            if ($value === 'true' || $value === 'Strict' || $value === '1') {
                $this->pass("✅ PASS", $label);
            } else {
                $this->warn("⚠️  WARN", "$label - Current: $value");
            }
        }

        $lifetime = $_ENV['SESSION_LIFETIME'] ?? 0;
        if ($lifetime < 1800) {
            $this->fail("❌ FAIL", "SESSION_LIFETIME too short: $lifetime seconds (min 30 min)");
        } else {
            $this->pass("✅ PASS", "SESSION_LIFETIME = $lifetime seconds");
        }
    }

    private function validate_cache() {
        echo "\n[5/15] Cache Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $driver = $_ENV['CACHE_DRIVER'] ?? 'file';
        
        if ($driver === 'redis') {
            $this->pass("✅ PASS", "Cache driver: Redis (optimal)");
        } elseif ($driver === 'file') {
            $this->warn("⚠️  WARN", "Cache driver: File (not optimal for production)");
        } else {
            $this->fail("❌ FAIL", "Cache driver invalid: $driver");
        }

        $cache_enabled = $_ENV['QUERY_CACHE_ENABLED'] ?? 'false';
        if ($cache_enabled === 'true') {
            $this->pass("✅ PASS", "Query caching enabled");
        } else {
            $this->warn("⚠️  WARN", "Query caching disabled - may impact performance");
        }
    }

    private function validate_redis() {
        echo "\n[6/15] Redis Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $redis_enabled = $_ENV['SESSION_DRIVER'] ?? '';
        
        if ($redis_enabled === 'redis') {
            $this->pass("✅ PASS", "Redis enabled for sessions");
            
            $host = $_ENV['SESSION_REDIS_HOST'] ?? 'localhost';
            $port = $_ENV['SESSION_REDIS_PORT'] ?? 6379;
            
            if ($this->test_redis_connection($host, $port)) {
                $this->pass("✅ PASS", "Redis connection successful ($host:$port)");
            } else {
                $this->warn("⚠️  WARN", "Redis connection failed - will use fallback");
            }
        } else {
            $this->warn("⚠️  WARN", "Redis not configured for sessions");
        }
    }

    private function test_redis_connection($host, $port) {
        try {
            $redis = @fsockopen($host, $port, $errno, $errstr, 2);
            if ($redis) {
                fclose($redis);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function validate_email() {
        echo "\n[7/15] Email Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $provider = $_ENV['MAIL_PROVIDER'] ?? '';
        
        if (empty($provider)) {
            $this->fail("❌ FAIL", "MAIL_PROVIDER not set");
            return;
        }

        $this->pass("✅ PASS", "Mail provider: $provider");

        if ($provider === 'sendgrid') {
            $api_key = $_ENV['MAIL_SENDGRID_API_KEY'] ?? '';
            if (strpos($api_key, 'CHANGE_ME') !== false || empty($api_key)) {
                $this->fail("❌ FAIL", "SendGrid API key not configured");
            } else {
                $this->pass("✅ PASS", "SendGrid API key configured");
            }
        } elseif ($provider === 'smtp') {
            $host = $_ENV['MAIL_HOST'] ?? '';
            $port = $_ENV['MAIL_PORT'] ?? '';
            
            if (empty($host) || empty($port)) {
                $this->fail("❌ FAIL", "SMTP credentials incomplete");
            } else {
                $this->pass("✅ PASS", "SMTP configured ($host:$port)");
            }
        }

        $from = $_ENV['MAIL_FROM_ADDRESS'] ?? '';
        if (empty($from) || strpos($from, '@') === false) {
            $this->fail("❌ FAIL", "Invalid MAIL_FROM_ADDRESS");
        } else {
            $this->pass("✅ PASS", "Mail from: $from");
        }
    }

    private function validate_billing() {
        echo "\n[8/15] Billing & Payment Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $provider = $_ENV['VIABIX_BILLING_PROVIDER'] ?? '';
        
        if (empty($provider)) {
            $this->fail("❌ FAIL", "VIABIX_BILLING_PROVIDER not set");
            return;
        }

        $this->pass("✅ PASS", "Billing provider: $provider");

        if ($provider === 'asaas') {
            $api_key = $_ENV['VIABIX_ASAAS_API_KEY'] ?? '';
            if (strpos($api_key, 'CHANGE_ME') !== false || empty($api_key)) {
                $this->fail("❌ FAIL", "Asaas API key not configured");
            } else {
                $this->pass("✅ PASS", "Asaas API key configured");
            }

            $env = $_ENV['VIABIX_ASAAS_ENV'] ?? 'sandbox';
            if ($env !== 'production') {
                $this->warn("⚠️  WARN", "Asaas environment is $env (should be 'production')");
            } else {
                $this->pass("✅ PASS", "Asaas running in production mode");
            }
        }
    }

    private function validate_sentry() {
        echo "\n[9/15] Error Tracking (Sentry)\n";
        echo str_repeat("-", 60) . "\n";

        $sentry_enabled = $_ENV['SENTRY_ENABLED'] ?? 'false';
        
        if ($sentry_enabled === 'false') {
            $this->warn("⚠️  WARN", "Sentry error tracking is disabled");
            return;
        }

        $dsn = $_ENV['SENTRY_DSN'] ?? '';
        
        if (empty($dsn) || strpos($dsn, 'CHANGE_ME') !== false) {
            $this->fail("❌ FAIL", "SENTRY_DSN not configured");
        } else {
            $this->pass("✅ PASS", "Sentry DSN configured");
        }

        $env = $_ENV['SENTRY_ENVIRONMENT'] ?? '';
        if ($env === 'production') {
            $this->pass("✅ PASS", "Sentry environment: production");
        } else {
            $this->warn("⚠️  WARN", "Sentry environment: $env (should be 'production')");
        }
    }

    private function validate_backup() {
        echo "\n[10/15] Backup Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $backup_enabled = $_ENV['BACKUP_ENABLED'] ?? 'false';
        
        if ($backup_enabled !== 'true') {
            $this->fail("❌ FAIL", "BACKUP_ENABLED is disabled - backups critical for production");
            return;
        }

        $this->pass("✅ PASS", "Backups enabled");

        $retention = $_ENV['BACKUP_RETENTION_DAYS'] ?? 0;
        if ($retention < 7) {
            $this->fail("❌ FAIL", "BACKUP_RETENTION_DAYS too short: $retention (min 90 days)");
        } else {
            $this->pass("✅ PASS", "Backup retention: $retention days");
        }

        $driver = $_ENV['BACKUP_DRIVER'] ?? '';
        if ($driver === 's3') {
            $bucket = $_ENV['BACKUP_S3_BUCKET'] ?? '';
            if (empty($bucket)) {
                $this->fail("❌ FAIL", "S3 bucket not configured");
            } else {
                $this->pass("✅ PASS", "S3 backup configured: $bucket");
            }
        } else {
            $this->warn("⚠️  WARN", "Backup driver not S3: $driver");
        }
    }

    private function validate_encryption() {
        echo "\n[11/15] Encryption\n";
        echo str_repeat("-", 60) . "\n";

        $encryption_enabled = $_ENV['ENCRYPTION_ENABLED'] ?? 'false';
        
        if ($encryption_enabled !== 'true') {
            $this->fail("❌ FAIL", "ENCRYPTION_ENABLED is disabled");
            return;
        }

        $this->pass("✅ PASS", "Encryption enabled");

        $key = $_ENV['ENCRYPTION_KEY'] ?? '';
        if (empty($key) || strlen($key) < 32) {
            $this->fail("❌ FAIL", "ENCRYPTION_KEY is too short or missing");
        } else {
            $this->pass("✅ PASS", "Encryption key configured");
        }

        $algo = $_ENV['ENCRYPTION_ALGORITHM'] ?? 'AES-128-CBC';
        if ($algo !== 'AES-256-CBC') {
            $this->warn("⚠️  WARN", "Encryption algorithm: $algo (AES-256-CBC recommended)");
        } else {
            $this->pass("✅ PASS", "Encryption: AES-256-CBC");
        }
    }

    private function validate_file_permissions() {
        echo "\n[12/15] File Permissions\n";
        echo str_repeat("-", 60) . "\n";

        $env_file = __DIR__ . '/../.env';
        $perms = fileperms($env_file) & 0777;
        
        if ($perms !== 0644 && $perms !== 0600) {
            $this->warn("⚠️  WARN", ".env permissions: " . decoct($perms) . " (should be 0600)");
        } else {
            $this->pass("✅ PASS", ".env permissions secure: " . decoct($perms));
        }

        $dirs = [
            __DIR__ . '/../',
            __DIR__ . '/../api',
        ];

        foreach ($dirs as $dir) {
            if (!is_writable($dir)) {
                $this->warn("⚠️  WARN", "Directory not writable: $dir");
            }
        }
    }

    private function validate_ssl_certificate() {
        echo "\n[13/15] SSL/TLS Certificate\n";
        echo str_repeat("-", 60) . "\n";

        $url = $_ENV['APP_URL'] ?? 'https://localhost';
        
        if (strpos($url, 'https://') === false) {
            $this->fail("❌ FAIL", "APP_URL does not use HTTPS: $url");
            return;
        }

        $this->pass("✅ PASS", "APP_URL uses HTTPS");

        // Extract hostname
        $hostname = parse_url($url, PHP_URL_HOST);
        
        if ($this->test_ssl_certificate($hostname)) {
            $this->pass("✅ PASS", "SSL certificate valid for $hostname");
        } else {
            $this->warn("⚠️  WARN", "Could not verify SSL certificate for $hostname");
        }
    }

    private function test_ssl_certificate($hostname) {
        try {
            $stream = @stream_context_create(['ssl' => ['verify_peer' => true]]);
            $handle = @fsockopen('ssl://' . $hostname, 443, $errno, $errstr, 5);
            if ($handle) {
                fclose($handle);
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    private function validate_rate_limiting() {
        echo "\n[14/15] Rate Limiting\n";
        echo str_repeat("-", 60) . "\n";

        $enabled = $_ENV['RATE_LIMIT_ENABLED'] ?? 'false';
        
        if ($enabled !== 'true') {
            $this->fail("❌ FAIL", "RATE_LIMIT_ENABLED is disabled");
            return;
        }

        $this->pass("✅ PASS", "Rate limiting enabled");

        $checks = [
            'RATE_LIMIT_LOGIN_MAX' => 5,
            'RATE_LIMIT_SIGNUP_MAX' => 3,
            'RATE_LIMIT_API_MAX' => 100,
        ];

        foreach ($checks as $key => $min) {
            $value = $_ENV[$key] ?? 0;
            if ($value < $min) {
                $this->warn("⚠️  WARN", "$key is too restrictive: $value (min recommended: $min)");
            } else {
                $this->pass("✅ PASS", "$key = $value");
            }
        }
    }

    private function validate_cors() {
        echo "\n[15/15] CORS Configuration\n";
        echo str_repeat("-", 60) . "\n";

        $enabled = $_ENV['CORS_ENABLED'] ?? 'false';
        
        if ($enabled !== 'true') {
            $this->fail("❌ FAIL", "CORS_ENABLED is disabled");
            return;
        }

        $this->pass("✅ PASS", "CORS enabled");

        $origins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        if (empty($origins) || strpos($origins, 'localhost') !== false) {
            $this->warn("⚠️  WARN", "CORS_ALLOWED_ORIGINS may contain development URLs");
        } else {
            $this->pass("✅ PASS", "CORS origins configured");
        }

        $methods = $_ENV['CORS_ALLOWED_METHODS'] ?? '';
        if (strpos($methods, 'DELETE') !== false) {
            $this->pass("✅ PASS", "DELETE method allowed for CORS");
        }
    }

    private function pass($status, $message) {
        $this->results[] = ['status' => $status, 'message' => $message];
        $this->passes++;
        echo "$status $message\n";
    }

    private function warn($status, $message) {
        $this->results[] = ['status' => $status, 'message' => $message];
        $this->warnings++;
        echo "$status $message\n";
    }

    private function fail($status, $message) {
        $this->results[] = ['status' => $status, 'message' => $message];
        $this->critical_failures++;
        echo "$status $message\n";
    }

    private function print_results() {
        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════════════╗\n";
        echo "║                         VALIDATION RESULTS                    ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n\n";

        printf("✅ PASSED:  %d checks\n", $this->passes);
        printf("⚠️  WARNED:  %d checks\n", $this->warnings);
        printf("❌ FAILED:  %d checks\n\n", $this->critical_failures);

        if ($this->critical_failures === 0) {
            echo "🚀 READY FOR PRODUCTION DEPLOYMENT\n\n";
            echo "Next steps:\n";
            echo "1. Review all warnings above\n";
            echo "2. Run: systemctl restart apache2 php8.2-fpm\n";
            echo "3. Monitor: tail -f /var/log/viabix/*.log\n";
            echo "4. Test: curl -I https://app.viabix.com.br\n\n";
        } else {
            echo "❌ FIX THE ABOVE ERRORS BEFORE DEPLOYING TO PRODUCTION\n\n";
        }

        echo str_repeat("=", 64) . "\n";
    }
}

// Run validator
$validator = new ProductionValidator();
$validator->run();
