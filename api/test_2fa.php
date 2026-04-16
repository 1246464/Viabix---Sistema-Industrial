<?php
/**
 * Two-Factor Authentication Test Interface
 * Interactive testing UI for TOTP, Email OTP, Backup codes
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Simulate user authentication for testing
$_SESSION['user_id'] = 1;

$twofa = new ViabixTwoFactorAuth(1);
$test_results = [];

// Handle test actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$test_message = null;
$test_status = null;

if ($action === 'generate_secret') {
    $secret = $twofa->generateTotpSecret();
    $qr_code = $twofa->generateQrCodeUrl('test@example.com', $secret);
    $_SESSION['test_secret'] = $secret;
    $test_message = "Secret generated: " . $secret;
    $test_status = "success";
}

if ($action === 'verify_totp') {
    $code = $_POST['totp_code'] ?? '';
    $secret = $_SESSION['test_secret'] ?? '';
    
    if (!$secret) {
        $test_message = "First generate a secret";
        $test_status = "error";
    } elseif (!$code) {
        $test_message = "Enter a code";
        $test_status = "error";
    } else {
        $verified = $twofa->verifyTotp($secret, $code);
        $test_message = $verified ? "✓ TOTP code is valid!" : "✗ TOTP code is invalid";
        $test_status = $verified ? "success" : "error";
    }
}

if ($action === 'generate_backup') {
    $codes = $twofa->generateBackupCodes();
    $_SESSION['test_backup_codes'] = $codes;
    $test_message = "Backup codes generated";
    $test_status = "success";
}

if ($action === 'verify_backup') {
    $code = $_POST['backup_code'] ?? '';
    $codes = $_SESSION['test_backup_codes'] ?? [];
    
    if (empty($codes)) {
        $test_message = "First generate backup codes";
        $test_status = "error";
    } elseif (!$code) {
        $test_message = "Enter a backup code";
        $test_status = "error";
    } else {
        $verified = in_array($code, $codes);
        $test_message = $verified ? "✓ Backup code is valid!" : "✗ Backup code is invalid";
        $test_status = $verified ? "success" : "error";
        
        if ($verified) {
            // Remove used code
            unset($codes[array_search($code, $codes)]);
            $_SESSION['test_backup_codes'] = array_values($codes);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication Test Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            color: #666;
            margin: 0;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        
        .card-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            transition: transform 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: scale(1.02);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-box p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .code-display {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            word-break: break-all;
            border: 1px solid #e0e0e0;
        }
        
        .qr-code-container {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .qr-code-container img {
            max-width: 300px;
            height: auto;
        }
        
        .totp-timer {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
            line-height: 30px;
            font-weight: bold;
        }
        
        .backup-codes-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .backup-code-item {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            border: 1px solid #e0e0e0;
            word-break: break-all;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .icon {
            font-size: 1.3rem;
        }
        
        .tab-section {
            margin-top: 20px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-button {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 2px solid white;
            padding: 10px 20px;
            margin-right: 5px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: white;
            color: #667eea;
        }
        
        .tab-button:hover {
            background: white;
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-shield-alt icon"></i>
                Two-Factor Authentication
            </h1>
            <p><i class="fas fa-info-circle"></i> Test TOTP, Email OTP, and Backup Codes</p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box">
                <h3>3</h3>
                <p>Authentication Methods</p>
            </div>
            <div class="stat-box">
                <h3>30s</h3>
                <p>TOTP Validity</p>
            </div>
            <div class="stat-box">
                <h3>10</h3>
                <p>Backup Codes</p>
            </div>
            <div class="stat-box">
                <h3>10m</h3>
                <p>OTP Expiry</p>
            </div>
        </div>

        <?php if ($test_message): ?>
        <div class="alert alert-<?php echo $test_status === 'success' ? 'success' : 'danger'; ?>">
            <i class="fas fa-<?php echo $test_status === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($test_message); ?>
        </div>
        <?php endif; ?>

        <!-- TOTP Section -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-clock icon"></i> Time-Based One-Time Password (TOTP)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">TOTP is compatible with Google Authenticator, Authy, Microsoft Authenticator, and other TOTP apps.</p>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="generate_secret">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sync"></i> Generate New Secret
                    </button>
                </form>

                <?php if (isset($_SESSION['test_secret'])): ?>
                <div class="alert alert-info">
                    <h6><i class="fas fa-key"></i> Setup Steps:</h6>
                    <ol>
                        <li>Scan the QR code with your authenticator app</li>
                        <li>Or enter the secret manually: <code><?php echo htmlspecialchars($_SESSION['test_secret']); ?></code></li>
                        <li>Enter the 6-digit code below to verify</li>
                    </ol>
                </div>

                <div class="qr-code-container">
                    <img src="<?php echo $twofa->generateQrCodeUrl('test@example.com', $_SESSION['test_secret']); ?>" alt="QR Code">
                    <p class="text-muted mt-2">Scan with Google Authenticator or Authy</p>
                </div>

                <div class="code-display mb-3">
                    <strong>Secret Key (Base32):</strong> <br>
                    <?php echo htmlspecialchars($_SESSION['test_secret']); ?>
                </div>

                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="verify_totp">
                    <div class="input-group">
                        <input type="text" class="form-control" name="totp_code" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]{6}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Verify Code
                        </button>
                    </div>
                </form>

                <div class="alert alert-secondary">
                    <h6>How it Works:</h6>
                    <ul class="mb-0">
                        <li>Secret is stored and time-synchronized with your device</li>
                        <li>Code changes every 30 seconds</li>
                        <li>Codes within ±30 seconds are accepted (tolerance window)</li>
                        <li>No internet required ✓</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Backup Codes Section -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-shield-alt icon"></i> Backup Codes</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">Backup codes allow account recovery if you lose access to your authenticator app.</p>

                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="generate_backup">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-wrench"></i> Generate Backup Codes
                    </button>
                </form>

                <?php if (isset($_SESSION['test_backup_codes']) && !empty($_SESSION['test_backup_codes'])): ?>
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> Important:</h6>
                    <ul class="mb-0">
                        <li>Store these codes in a safe place</li>
                        <li>Each code can only be used once</li>
                        <li>Remaining codes: <strong><?php echo count($_SESSION['test_backup_codes']); ?>/10</strong></li>
                    </ul>
                </div>

                <h6>Your Backup Codes:</h6>
                <div class="backup-codes-list mb-3">
                    <?php foreach ($_SESSION['test_backup_codes'] as $code): ?>
                    <div class="backup-code-item">
                        <?php echo htmlspecialchars($code); ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="verify_backup">
                    <div class="input-group">
                        <input type="text" class="form-control" name="backup_code" placeholder="Enter backup code (e.g., XXXX-XXXX-XXXX)">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Verify Code
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Integration Examples -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code icon"></i> Setup Example</h5>
                    </div>
                    <div class="card-body">
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>$twofa = new ViabixTwoFactorAuth();
$secret = $twofa->generateTotpSecret();
$qr_url = $twofa->generateQrCodeUrl(
    'user@example.com',
    $secret
);
$backup_codes = $twofa->generateBackupCodes();

// Enable 2FA
$twofa->enableTwoFactor(
    $user_id,
    $secret,
    'totp',
    $backup_codes
);</code></pre>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code icon"></i> Verification Example</h5>
                    </div>
                    <div class="card-body">
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>// After user enters code
$user_id = viabixGetTwoFactorMethod($user_id);

// Verify code
if ($twofa->verifyCode($user_id, $code)) {
    $twofa->completeTwoFactorAuth(
        $user_id
    );
    // User is now fully authenticated
}

// Or quick helper
if (viabixVerifyTwoFactorCode(
    $user_id,
    $code
)) {
    // Valid!
}</code></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features List -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-check-circle icon"></i> Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>TOTP Implementation</h6>
                        <ul>
                            <li>✓ HMAC-SHA1 algorithm (RFC 4226)</li>
                            <li>✓ 30-second time window</li>
                            <li>✓ ±1 window tolerance</li>
                            <li>✓ Base32 encoding</li>
                            <li>✓ Google Authenticator compatible</li>
                            <li>✓ Authy compatible</li>
                            <li>✓ Microsoft Authenticator compatible</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Security Features</h6>
                        <ul>
                            <li>✓ Backup codes (10 codes)</li>
                            <li>✓ Hashed code storage (Bcrypt)</li>
                            <li>✓ Single-use enforcement</li>
                            <li>✓ Expiring OTP (10 min)</li>
                            <li>✓ Partial auth sessions</li>
                            <li>✓ Session regeneration</li>
                            <li>✓ Rate limiting compatible</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 mb-3">
            <small class="text-white">
                <i class="fas fa-info-circle"></i>
                Two-Factor Authentication v1.0 | 
                <a href="/api/" class="text-white">Back to API</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update timer for TOTP
        function updateTotpTimer() {
            const remaining = 30 - (Math.floor(Date.now() / 1000) % 30);
            const timer = document.querySelector('.totp-timer');
            if (timer) {
                timer.textContent = remaining;
            }
        }
        
        setInterval(updateTotpTimer, 100);
        updateTotpTimer();
    </script>
</body>
</html>
