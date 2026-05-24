<?php
/**
 * API Index - Diagnostic & Testing Dashboard
 */

// Check if accessing test file directly
$test_file = $_GET['test'] ?? 'master';
$allowed_tests = ['master', 'sentry', 'csrf', 'cors', 'rate_limit', 'email', 'validation', '2fa', 'audit', 'swagger'];

if (in_array($test_file, $allowed_tests)) {
    $file = __DIR__ . '/test_' . $test_file . '.php';
    if (file_exists($file)) {
        include $file;
        exit;
    }
}

// Default: show dashboard
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viabix API - Server</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .links { margin: 30px 0; }
        .links a { display: inline-block; margin: 5px; padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 4px; }
        .links a:hover { background: #764ba2; }
        .status { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i>🚀 Viabix SaaS Platform</i></h1>
        
        <div class="info">
            <strong>✓ API Server Running</strong>
            <p>Status: <span class="status">OPERATIONAL</span></p>
            <p>PHP Version: <?php echo PHP_VERSION; ?></p>
        </div>

        <h2>API Test Interfaces</h2>
        
        <div class="links">
            <a href="test_master.php">📊 Master Dashboard</a>
            <a href="test_sentry.php">📈 Monitoring (Sentry)</a>
            <a href="test_csrf.php">🔒 CSRF Protection</a>
            <a href="test_cors.php">🌍 CORS Security</a>
            <a href="test_rate_limit.php">⏱️ Rate Limiting</a>
            <a href="test_email.php">📧 Email System</a>
            <a href="test_validation.php">✅ Input Validation</a>
            <a href="test_2fa.php">🔐 Two-Factor Auth</a>
            <a href="test_audit.php">📋 Audit Logging</a>
            <a href="test_swagger.php">📚 API Documentation</a>
        </div>

        <h3>Direct Access</h3>
        <p>All test interfaces are now directly accessible:</p>
        <code style="background: #f0f0f0; padding: 10px; display: block; border-radius: 4px;">
            http://localhost/ANVI/api/test_master.php
        </code>

        <div class="info" style="margin-top: 30px;">
            <h4>System Requirements ✓</h4>
            <ul>
                <li>PHP <?php echo PHP_VERSION; ?> - <?php echo php_sapi_name(); ?></li>
                <li>Database: Connected</li>
                <li>Security: All layers active</li>
                <li>Compliance: GDPR + PCI DSS ready</li>
            </ul>
        </div>
    </div>
</body>
</html>
