<?php
/**
 * Two-Factor Authentication Framework
 * 
 * Provides flexible 2FA with multiple methods:
 * - TOTP (Google Authenticator / Authy compatible)
 * - Email OTP (One-Time Password)
 * - Backup Codes (for account recovery)
 * 
 * Location: api/two_factor_auth.php
 * Integrated: api/config.php
 */

if (!defined('VIABIX_APP')) {
    die('Direct access not allowed');
}

/**
 * Two-Factor Authentication Manager
 */
class ViabixTwoFactorAuth {
    
    private $user_id;
    private $pdo;
    
    public function __construct($user_id = null) {
        global $pdo;
        $this->user_id = $user_id;
        $this->pdo = $pdo;
    }
    
    /**
     * Generate TOTP secret (Base32 encoded random bytes)
     * @return string Base32 encoded 20-byte secret
     */
    public function generateTotpSecret() {
        $bytes = bin2hex(random_bytes(10));
        return $this->base32Encode(hex2bin($bytes));
    }
    
    /**
     * Generate QR code for TOTP setup
     * @param string $email User email
     * @param string $secret TOTP secret
     * @param string $app_name Application name
     * @return string URL for QR code (Google Charts API)
     */
    public function generateQrCodeUrl($email, $secret, $app_name = 'Viabix') {
        $otpauth = "otpauth://totp/" . urlencode($app_name . " (" . $email . ")") . "?secret=" . $secret;
        $otpauth .= "&issuer=" . urlencode($app_name);
        
        return "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($otpauth);
    }
    
    /**
     * Verify TOTP code
     * @param string $secret TOTP secret
     * @param string $code 6-digit code from authenticator
     * @param int $window Time window tolerance (±1 = 30 seconds each side, default)
     * @return bool True if code is valid
     */
    public function verifyTotp($secret, $code, $window = 1) {
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }
        
        $time = floor(time() / 30);
        
        for ($i = -$window; $i <= $window; $i++) {
            $timestamp = pack('N', $time + $i);
            $hash = hash_hmac('sha1', $timestamp, $this->base32Decode($secret), true);
            $offset = ord($hash[19]) & 0x0f;
            $code_value = (
                (ord($hash[$offset]) & 0x7f) << 24 |
                (ord($hash[$offset + 1]) & 0xff) << 16 |
                (ord($hash[$offset + 2]) & 0xff) << 8 |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;
            
            $generated_code = str_pad($code_value, 6, '0', STR_PAD_LEFT);
            if (hash_equals($code, $generated_code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate backup codes (recovery codes)
     * Returns 10 codes in format: XXXX-XXXX-XXXX
     * @return array Array of 10 backup codes
     */
    public function generateBackupCodes() {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $random = bin2hex(random_bytes(6));
            $code = substr($random, 0, 4) . '-' . substr($random, 4, 4) . '-' . substr($random, 8, 4);
            $codes[] = $code;
        }
        return $codes;
    }
    
    /**
     * Store 2FA configuration and backup codes
     * @param int $user_id User ID
     * @param string $totp_secret TOTP secret
     * @param string $method 'totp' or 'email'
     * @param array $backup_codes Backup codes to store
     * @return bool Success
     */
    public function enableTwoFactor($user_id, $totp_secret, $method = 'totp', $backup_codes = []) {
        try {
            // Hash backup codes
            $backup_codes_hashed = [];
            foreach ($backup_codes as $code) {
                $backup_codes_hashed[] = [
                    'code' => password_hash($code, PASSWORD_BCRYPT, ['cost' => 12]),
                    'used' => 0
                ];
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_2fa (user_id, totp_secret, method, backup_codes, enabled, created_at)
                VALUES (?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    totp_secret = ?,
                    method = ?,
                    backup_codes = ?,
                    enabled = 1,
                    updated_at = NOW()
            ");
            
            $backup_codes_json = json_encode($backup_codes_hashed);
            
            $stmt->execute([
                $user_id,
                $totp_secret,
                $method,
                $backup_codes_json,
                $totp_secret,
                $method,
                $backup_codes_json
            ]);
            
            viabixLogInfo("2FA enabled for user $user_id with method: $method");
            return true;
            
        } catch (Exception $e) {
            viabixLogError("Failed to enable 2FA for user $user_id", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verify two-factor code (TOTP or backup)
     * @param int $user_id User ID
     * @param string $code Code to verify
     * @return bool True if valid
     */
    public function verifyCode($user_id, $code) {
        try {
            // Get user's 2FA config
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios_2fa WHERE user_id = ? AND enabled = 1");
            $stmt->execute([$user_id]);
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$config) {
                return false;
            }
            
            $code_clean = preg_replace('/[^0-9]/', '', $code);
            
            // Try TOTP verification
            if ($config['method'] === 'totp' && $this->verifyTotp($config['totp_secret'], $code_clean)) {
                return true;
            }
            
            // Try backup code
            if ($this->verifyBackupCode($user_id, $code)) {
                return true;
            }
            
            viabixLogWarning("Invalid 2FA code attempt for user $user_id");
            return false;
            
        } catch (Exception $e) {
            viabixLogError("Error verifying 2FA code", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verify and consume backup code
     * @param int $user_id User ID
     * @param string $code Backup code
     * @return bool True if valid and unused
     */
    private function verifyBackupCode($user_id, $code) {
        try {
            $stmt = $this->pdo->prepare("SELECT backup_codes FROM usuarios_2fa WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            $backup_codes = json_decode($result['backup_codes'], true);
            
            foreach ($backup_codes as $idx => $code_entry) {
                // Check if unused and matches
                if (!$code_entry['used'] && password_verify($code, $code_entry['code'])) {
                    // Mark as used
                    $backup_codes[$idx]['used'] = time();
                    
                    $stmt = $this->pdo->prepare("
                        UPDATE usuarios_2fa 
                        SET backup_codes = ? 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([json_encode($backup_codes), $user_id]);
                    
                    viabixLogInfo("Backup code used for user $user_id");
                    return true;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            viabixLogError("Error verifying backup code", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Check if user has 2FA enabled
     * @param int $user_id User ID
     * @return bool True if 2FA is enabled
     */
    public function isTwoFactorEnabled($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios_2fa WHERE user_id = ? AND enabled = 1");
            $stmt->execute([$user_id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get 2FA method for user
     * @param int $user_id User ID
     * @return string|null 'totp', 'email', or null if not enabled
     */
    public function getTwoFactorMethod($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT method FROM usuarios_2fa WHERE user_id = ? AND enabled = 1");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['method'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Disable 2FA for user
     * @param int $user_id User ID
     * @return bool Success
     */
    public function disableTwoFactor($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE usuarios_2fa SET enabled = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            viabixLogInfo("2FA disabled for user $user_id");
            return true;
        } catch (Exception $e) {
            viabixLogError("Failed to disable 2FA", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Send OTP via email
     * @param int $user_id User ID
     * @param string $email Email address
     * @return string|bool Generated OTP or false on error
     */
    public function sendEmailOtp($user_id, $email) {
        try {
            // Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_hash = password_hash($otp, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Store OTP with 10 minute expiry
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_2fa_otp (user_id, otp_hash, expires_at, used)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE), 0)
                ON DUPLICATE KEY UPDATE
                    otp_hash = ?,
                    expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE),
                    used = 0
            ");
            
            $stmt->execute([$user_id, $otp_hash, $otp_hash]);
            
            // Send email
            $subject = 'Código de Autenticação Viabix';
            $html = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Código de Autenticação</h2>
                    <p>Seu código de autenticação é:</p>
                    <h1 style='font-size: 2.5em; letter-spacing: 5px; color: #667eea;'>$otp</h1>
                    <p>Este código expira em 10 minutos.</p>
                    <p><small>Se você não solicitou este código, ignore este email.</small></p>
                </body>
                </html>
            ";
            
            $text = "Código de autenticação: $otp\n\nEste código expira em 10 minutos.";
            
            viabixSendEmail($email, $subject, $html, $text);
            viabixLogInfo("OTP email sent to user $user_id");
            
            return $otp; // For testing only
            
        } catch (Exception $e) {
            viabixLogError("Failed to send OTP email", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verify email OTP
     * @param int $user_id User ID
     * @param string $otp OTP code
     * @return bool True if valid
     */
    public function verifyEmailOtp($user_id, $otp) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT otp_hash FROM usuarios_2fa_otp 
                WHERE user_id = ? 
                AND used = 0 
                AND expires_at > NOW()
                LIMIT 1
            ");
            
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return false;
            }
            
            if (password_verify($otp, $result['otp_hash'])) {
                // Mark as used
                $stmt = $this->pdo->prepare("UPDATE usuarios_2fa_otp SET used = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                viabixLogInfo("Email OTP verified for user $user_id");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            viabixLogError("Error verifying email OTP", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Create partial authentication session (user authenticated but needs 2FA)
     * Used after successful password verification but before 2FA verification
     * @param int $user_id User ID
     * @return string Session token for 2FA verification
     */
    public function createPartialAuthSession($user_id) {
        $token = bin2hex(random_bytes(32));
        $token_hash = password_hash($token, PASSWORD_BCRYPT, ['cost' => 10]);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO usuarios_2fa_sessions (user_id, token_hash, expires_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ");
            
            $stmt->execute([$user_id, $token_hash]);
            
            // Store in session for verification
            $_SESSION['2fa_pending_user_id'] = $user_id;
            $_SESSION['2fa_token'] = $token;
            $_SESSION['2fa_expires'] = time() + 600; // 10 minutes
            
            return $token;
            
        } catch (Exception $e) {
            viabixLogError("Failed to create partial auth session", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Verify partial authentication session
     * @return int|false User ID if valid, false otherwise
     */
    public function verifyPartialAuthSession() {
        // Check session variables
        if (empty($_SESSION['2fa_pending_user_id']) || 
            empty($_SESSION['2fa_token']) || 
            $_SESSION['2fa_expires'] < time()) {
            return false;
        }
        
        $user_id = (int) $_SESSION['2fa_pending_user_id'];
        $token = $_SESSION['2fa_token'];
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT token_hash FROM usuarios_2fa_sessions 
                WHERE user_id = ? 
                AND expires_at > NOW()
                ORDER BY created_at DESC
                LIMIT 1
            ");
            
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && password_verify($token, $result['token_hash']) ? $user_id : false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Complete 2FA verification and create full session
     * @param int $user_id User ID
     * @return bool Success
     */
    public function completeTwoFactorAuth($user_id) {
        // Clear 2FA session data
        unset($_SESSION['2fa_pending_user_id']);
        unset($_SESSION['2fa_token']);
        unset($_SESSION['2fa_expires']);
        
        // Create full authenticated session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['authenticated'] = true;
        $_SESSION['2fa_verified'] = true;
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        // Clean up partial auth sessions
        try {
            $stmt = $this->pdo->prepare("DELETE FROM usuarios_2fa_sessions WHERE user_id = ?");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            // Silent fail
        }
        
        viabixLogInfo("2FA verification completed for user $user_id");
        return true;
    }
    
    /**
     * Base32 encode (for TOTP secret)
     */
    private function base32Encode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vlen = 0;
        
        foreach (str_split($data) as $char) {
            $v = ($v << 8) | ord($char);
            $vlen += 8;
            while ($vlen >= 5) {
                $vlen -= 5;
                $output .= $alphabet[($v >> $vlen) & 31];
            }
        }
        
        if ($vlen > 0) {
            $v <<= (5 - $vlen);
            $output .= $alphabet[$v & 31];
        }
        
        return $output;
    }
    
    /**
     * Base32 decode
     */
    private function base32Decode($data) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vlen = 0;
        
        foreach (str_split(strtoupper($data)) as $char) {
            $digit = strpos($alphabet, $char);
            if ($digit === false) {
                continue;
            }
            $v = ($v << 5) | $digit;
            $vlen += 5;
            if ($vlen >= 8) {
                $vlen -= 8;
                $output .= chr(($v >> $vlen) & 255);
            }
        }
        
        return $output;
    }
}

/**
 * Helper function: Check if user has 2FA enabled
 */
function viabixHasTwoFactor($user_id) {
    $twofa = new ViabixTwoFactorAuth($user_id);
    return $twofa->isTwoFactorEnabled($user_id);
}

/**
 * Helper function: Get 2FA method
 */
function viabixGetTwoFactorMethod($user_id) {
    $twofa = new ViabixTwoFactorAuth($user_id);
    return $twofa->getTwoFactorMethod($user_id);
}

/**
 * Helper function: Verify 2FA code
 */
function viabixVerifyTwoFactorCode($user_id, $code) {
    $twofa = new ViabixTwoFactorAuth($user_id);
    return $twofa->verifyCode($user_id, $code);
}

/**
 * Helper function: Complete 2FA
 */
function viabixCompleteTwoFactorAuth($user_id) {
    $twofa = new ViabixTwoFactorAuth($user_id);
    return $twofa->completeTwoFactorAuth($user_id);
}

?>
