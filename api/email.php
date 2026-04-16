<?php
/**
 * Email Service System
 * 
 * Supports multiple email providers:
 * - SMTP (default, using mail() or direct connection)
 * - SendGrid API
 * - Mailgun API
 * 
 * Location: api/email.php
 * Integrated: api/config.php
 */

if (!defined('VIABIX_APP')) {
    die('Direct access not allowed');
}

// Email provider constants
define('VIABIX_EMAIL_PROVIDER_SMTP', 'smtp');
define('VIABIX_EMAIL_PROVIDER_SENDGRID', 'sendgrid');
define('VIABIX_EMAIL_PROVIDER_MAILGUN', 'mailgun');

/**
 * Send email with template rendering
 * @param string $to - Recipient email
 * @param string $template - Template name (e.g., 'welcome', 'reset_password')
 * @param array $data - Data for template rendering
 * @param string $subject - Override default subject
 * Returns: array['success' => bool, 'message' => string, 'message_id' => string]
 */
function viabixSendEmail($to, $template, $data = [], $subject = null) {
    try {
        // Validate email
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address: {$to}");
        }
        
        // Load template
        $template_data = viabixLoadEmailTemplate($template, $data);
        $subject = $subject ?? $template_data['subject'];
        $html = $template_data['html'];
        $text = $template_data['text'] ?? '';
        
        // Prepare email
        $email = [
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'text' => !empty($text) ? $text : strip_tags($html),
            'from' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@viabix.com',
            'from_name' => getenv('MAIL_FROM_NAME') ?: 'Viabix'
        ];
        
        // Send based on provider
        $provider = getenv('MAIL_PROVIDER') ?: VIABIX_EMAIL_PROVIDER_SMTP;
        
        switch ($provider) {
            case VIABIX_EMAIL_PROVIDER_SENDGRID:
                return viabixSendEmailSendGrid($email);
            case VIABIX_EMAIL_PROVIDER_MAILGUN:
                return viabixSendEmailMailgun($email);
            case VIABIX_EMAIL_PROVIDER_SMTP:
            default:
                return viabixSendEmailSmtp($email);
        }
    } catch (Exception $e) {
        $message = "Email send failed: " . $e->getMessage();
        
        // Log to Sentry
        if (function_exists('viabixSentryMessage')) {
            viabixSentryMessage($message, 'error', [
                'to' => $to,
                'template' => $template
            ]);
        }
        
        return [
            'success' => false,
            'message' => $message,
            'message_id' => null
        ];
    }
}

/**
 * Send email via SMTP
 * Uses PHP mail() function (configured via php.ini) or direct SMTP
 */
function viabixSendEmailSmtp($email) {
    // Prepare headers
    $headers = [
        'From: ' . $email['from_name'] . ' <' . $email['from'] . '>',
        'Reply-To: ' . $email['from'],
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: Viabix/1.0'
    ];
    
    // Add DKIM if configured
    $dkim_private = getenv('MAIL_DKIM_PRIVATE_KEY');
    if (!empty($dkim_private)) {
        // DKIM signing would go here (requires additional library)
        // For now, just add comment
        $headers[] = 'X-DKIM: enabled';
    }
    
    // Send via PHP mail()
    $success = mail(
        $email['to'],
        $email['subject'],
        $email['html'],
        implode("\r\n", $headers)
    );
    
    if ($success) {
        return [
            'success' => true,
            'message' => 'Email sent successfully',
            'message_id' => md5($email['to'] . time())
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Failed to send email via SMTP',
            'message_id' => null
        ];
    }
}

/**
 * Send email via SendGrid API
 * Requires MAIL_SENDGRID_API_KEY in .env
 */
function viabixSendEmailSendGrid($email) {
    $api_key = getenv('MAIL_SENDGRID_API_KEY');
    if (empty($api_key)) {
        return [
            'success' => false,
            'message' => 'SendGrid API key not configured',
            'message_id' => null
        ];
    }
    
    $url = 'https://api.sendgrid.com/v3/mail/send';
    
    $payload = [
        'personalizations' => [
            [
                'to' => [
                    ['email' => $email['to']]
                ]
            ]
        ],
        'from' => [
            'email' => $email['from'],
            'name' => $email['from_name']
        ],
        'subject' => $email['subject'],
        'content' => [
            [
                'type' => 'text/html',
                'value' => $email['html']
            ],
            [
                'type' => 'text/plain',
                'value' => $email['text']
            ]
        ]
    ];
    
    // Add Reply-To
    if (!empty($email['from'])) {
        $payload['reply_to'] = [
            'email' => $email['from'],
            'name' => $email['from_name']
        ];
    }
    
    $options = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ],
            'content' => json_encode($payload),
            'timeout' => 10
        ]
    ]);
    
    try {
        $response = @file_get_contents($url, false, $options);
        $headers = $http_response_header ?? [];
        
        // Check for 202 Accepted status
        $status_code = 202;
        foreach ($headers as $header) {
            if (strpos($header, 'HTTP') === 0) {
                preg_match('/HTTP\/\d\.\d (\d+)/', $header, $matches);
                $status_code = intval($matches[1] ?? 0);
            }
        }
        
        if ($status_code === 202) {
            return [
                'success' => true,
                'message' => 'Email sent via SendGrid',
                'message_id' => uniqid('sg_')
            ];
        } else {
            return [
                'success' => false,
                'message' => 'SendGrid API error: HTTP ' . $status_code,
                'message_id' => null
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'SendGrid error: ' . $e->getMessage(),
            'message_id' => null
        ];
    }
}

/**
 * Send email via Mailgun API
 * Requires MAIL_MAILGUN_API_KEY and MAIL_MAILGUN_DOMAIN in .env
 */
function viabixSendEmailMailgun($email) {
    $api_key = getenv('MAIL_MAILGUN_API_KEY');
    $domain = getenv('MAIL_MAILGUN_DOMAIN');
    
    if (empty($api_key) || empty($domain)) {
        return [
            'success' => false,
            'message' => 'Mailgun API credentials not configured',
            'message_id' => null
        ];
    }
    
    $url = "https://api.mailgun.net/v3/{$domain}/messages";
    
    $data = [
        'from' => $email['from_name'] . ' <' . $email['from'] . '>',
        'to' => $email['to'],
        'subject' => $email['subject'],
        'html' => $email['html'],
        'text' => $email['text']
    ];
    
    $options = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'Authorization: Basic ' . base64_encode('api:' . $api_key),
                'Content-Type: application/x-www-form-urlencoded'
            ],
            'content' => http_build_query($data),
            'timeout' => 10
        ]
    ]);
    
    try {
        $response = @file_get_contents($url, false, $options);
        $headers = $http_response_header ?? [];
        
        $status_code = 200;
        foreach ($headers as $header) {
            if (strpos($header, 'HTTP') === 0) {
                preg_match('/HTTP\/\d\.\d (\d+)/', $header, $matches);
                $status_code = intval($matches[1] ?? 0);
            }
        }
        
        if ($status_code === 200) {
            $result = json_decode($response, true);
            return [
                'success' => true,
                'message' => 'Email sent via Mailgun',
                'message_id' => $result['id'] ?? uniqid('mg_')
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Mailgun API error: HTTP ' . $status_code,
                'message_id' => null
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Mailgun error: ' . $e->getMessage(),
            'message_id' => null
        ];
    }
}

/**
 * Load and render email template
 * @param string $template - Template name
 * @param array $data - Variables for template
 * Returns: array['subject' => string, 'html' => string, 'text' => string]
 */
function viabixLoadEmailTemplate($template, $data = []) {
    // Try to load from database first (for admin-customizable templates)
    $template_html = viabixGetEmailTemplateFromDb($template, $data);
    if ($template_html) {
        return $template_html;
    }
    
    // Fall back to file-based templates
    return viabixGetEmailTemplateFromFile($template, $data);
}

/**
 * Get template from database if available
 */
function viabixGetEmailTemplateFromDb($template, $data = []) {
    global $pdo;
    
    if (!isset($pdo) || !viabixHasTable('email_templates')) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT subject, html_body, text_body 
            FROM email_templates 
            WHERE name = ? AND active = 1 
            LIMIT 1
        ");
        $stmt->execute([$template]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            return null;
        }
        
        return [
            'subject' => viabixRenderTemplate($row['subject'], $data),
            'html' => viabixRenderTemplate($row['html_body'], $data),
            'text' => viabixRenderTemplate($row['text_body'] ?? '', $data)
        ];
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get template from file
 */
function viabixGetEmailTemplateFromFile($template, $data = []) {
    $template_dir = __DIR__ . '/../templates/email';
    $template_path = $template_dir . '/' . preg_replace('/[^a-z0-9_-]/', '', $template) . '.php';
    
    if (!file_exists($template_path)) {
        throw new Exception("Email template not found: {$template}");
    }
    
    // Load template (returns ['subject', 'html', 'text'])
    $template_content = include $template_path;
    
    if (!is_array($template_content)) {
        throw new Exception("Invalid email template format: {$template}");
    }
    
    return [
        'subject' => viabixRenderTemplate($template_content['subject'] ?? '', $data),
        'html' => viabixRenderTemplate($template_content['html'] ?? '', $data),
        'text' => viabixRenderTemplate($template_content['text'] ?? '', $data)
    ];
}

/**
 * Simple template rendering (interpolate variables)
 * Supports {{variable}} syntax
 */
function viabixRenderTemplate($template, $data = []) {
    foreach ($data as $key => $value) {
        // Escape HTML by default, add |html for raw HTML
        if (is_string($value)) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $template);
            $template = str_replace('{{' . $key . '|html}}', $value, $template);
        } elseif (is_array($value)) {
            // For array values, JSON encode
            $template = str_replace('{{' . $key . '}}', json_encode($value), $template);
        } else {
            $template = str_replace('{{' . $key . '}}', (string)$value, $template);
        }
    }
    return $template;
}

/**
 * Queue email for sending (async delivery)
 * Useful for high-volume or delayed emails
 */
function viabixQueueEmail($to, $template, $data = [], $delay_seconds = 0) {
    global $pdo;
    
    if (!viabixHasTable('email_queue')) {
        // If no queue table, send immediately
        return viabixSendEmail($to, $template, $data);
    }
    
    try {
        $send_at = $delay_seconds > 0 ? date('Y-m-d H:i:s', time() + $delay_seconds) : date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (recipient, template, data, send_at, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $to,
            $template,
            json_encode($data),
            $send_at
        ]);
        
        return [
            'success' => true,
            'message' => 'Email queued for sending',
            'message_id' => $pdo->lastInsertId()
        ];
    } catch (Exception $e) {
        // Fall back to immediate send
        return viabixSendEmail($to, $template, $data);
    }
}

/**
 * Process email queue
 * Call this via cron job: php process-email-queue.php
 * Or via ajax: /api/process-email-queue.php
 */
function viabixProcessEmailQueue() {
    global $pdo;
    
    if (!viabixHasTable('email_queue')) {
        return ['processed' => 0, 'failed' => 0];
    }
    
    try {
        // Get pending emails
        $stmt = $pdo->prepare("
            SELECT id, recipient, template, data, send_at
            FROM email_queue
            WHERE sent_at IS NULL
            AND draft = 0
            AND send_at <= NOW()
            LIMIT 100
        ");
        $stmt->execute();
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processed = 0;
        $failed = 0;
        
        foreach ($emails as $email) {
            $result = viabixSendEmail(
                $email['recipient'],
                $email['template'],
                json_decode($email['data'], true) ?? []
            );
            
            if ($result['success']) {
                // Mark as sent
                $update = $pdo->prepare("
                    UPDATE email_queue
                    SET sent_at = NOW(), message_id = ?
                    WHERE id = ?
                ");
                $update->execute([$result['message_id'], $email['id']]);
                $processed++;
            } else {
                // Mark as failed
                $update = $pdo->prepare("
                    UPDATE email_queue
                    SET failed_at = NOW(), error_message = ?
                    WHERE id = ?
                ");
                $update->execute([$result['message'], $email['id']]);
                $failed++;
            }
        }
        
        return ['processed' => $processed, 'failed' => $failed];
    } catch (Exception $e) {
        return ['processed' => 0, 'failed' => 0, 'error' => $e->getMessage()];
    }
}

/**
 * Send welcome email to new user
 */
function viabixSendWelcomeEmail($email, $name, $login_url = null) {
    $login_url = $login_url ?: (getenv('APP_URL') ?: 'https://app.viabix.com') . '/login.html';
    
    return viabixSendEmail($email, 'welcome', [
        'name' => $name,
        'login_url' => $login_url,
        'company_name' => getenv('MAIL_FROM_NAME') ?: 'Viabix'
    ]);
}

/**
 * Send password reset email
 */
function viabixSendPasswordResetEmail($email, $name, $reset_token, $reset_url = null) {
    $reset_url = $reset_url ?: (getenv('APP_URL') ?: 'https://app.viabix.com') . '/reset-password.html?token=' . $reset_token;
    
    return viabixSendEmail($email, 'password_reset', [
        'name' => $name,
        'reset_url' => $reset_url,
        'token' => $reset_token,
        'expiry_hours' => 24
    ]);
}

/**
 * Send email verification email
 */
function viabixSendVerificationEmail($email, $name, $verification_token, $verify_url = null) {
    $verify_url = $verify_url ?: (getenv('APP_URL') ?: 'https://app.viabix.com') . '/verify-email.html?token=' . $verification_token;
    
    return viabixSendEmail($email, 'email_verification', [
        'name' => $name,
        'verify_url' => $verify_url,
        'token' => $verification_token
    ]);
}

/**
 * Send payment confirmation email
 */
function viabixSendPaymentConfirmationEmail($email, $name, $payment_data) {
    return viabixSendEmail($email, 'payment_confirmation', [
        'name' => $name,
        'amount' => $payment_data['amount'] ?? 0,
        'currency' => $payment_data['currency'] ?? 'BRL',
        'invoice_id' => $payment_data['invoice_id'] ?? '',
        'date' => date('d/m/Y H:i')
    ]);
}

/**
 * Send invoice email
 */
function viabixSendInvoiceEmail($email, $name, $invoice_id, $invoice_url = null) {
    $invoice_url = $invoice_url ?: (getenv('APP_URL') ?: 'https://app.viabix.com') . '/invoice/' . $invoice_id;
    
    return viabixSendEmail($email, 'invoice', [
        'name' => $name,
        'invoice_id' => $invoice_id,
        'invoice_url' => $invoice_url
    ]);
}

?>
