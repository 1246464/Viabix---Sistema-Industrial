<?php
// CLI-only initial provisioning; secrets are never printed.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$target = dirname(__DIR__) . '/.env';
if (file_exists($target)) { throw new RuntimeException('Refusing to overwrite environment.'); }
$password = bin2hex(random_bytes(32));
$pdo = new PDO('mysql:unix_socket=/var/run/mysqld/mysqld.sock', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec("CREATE USER 'viabix_app'@'localhost' IDENTIFIED BY " . $pdo->quote($password));
$pdo->exec("GRANT ALL PRIVILEGES ON viabix_db.* TO 'viabix_app'@'localhost'");
$config = [
 'APP_ENV'=>'production', 'APP_DEBUG'=>'false', 'APP_URL'=>'https://app.viabix.com.br',
 'DB_HOST'=>'127.0.0.1', 'DB_PORT'=>'3306', 'DB_NAME'=>'viabix_db', 'DB_USER'=>'viabix_app', 'DB_PASS'=>$password, 'DB_CHARSET'=>'utf8mb4',
 'JWT_SECRET'=>bin2hex(random_bytes(32)), 'SESSION_NAME'=>'viabix_session', 'SESSION_LIFETIME'=>'28800', 'SESSION_SECURE'=>'true', 'SESSION_SAMESITE'=>'Strict',
 'REDIS_ENABLED'=>'true', 'REDIS_HOST'=>'127.0.0.1', 'REDIS_PORT'=>'6379', 'REDIS_DB'=>'1',
 'CORS_ALLOWED_ORIGINS'=>'https://viabix.com.br,https://www.viabix.com.br,https://app.viabix.com.br',
 'VIABIX_BILLING_PROVIDER'=>'asaas', 'VIABIX_ASAAS_ENV'=>'production',
];
$content=''; foreach($config as $key=>$value) { $content .= "$key=$value\n"; }
file_put_contents($target, $content, LOCK_EX);
chmod($target, 0640);
echo "Production environment created.\n";
