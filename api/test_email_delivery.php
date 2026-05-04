<?php
/**
 * EMAIL DELIVERY TEST
 * Teste o sistema de envio de emails
 * 
 * Execute: php api/test_email_delivery.php
 */

require_once __DIR__ . '/../bootstrap_env.php';

echo "\n📧 EMAIL DELIVERY TEST\n";
echo "====================\n\n";

// ========================================
// TEST 1: Verificar configuração
// ========================================
echo "TEST 1: Email Configuration\n";
echo "---------------------------\n";

$mailProvider = viabix_env('MAIL_PROVIDER', 'smtp');
$mailFrom = viabix_env('MAIL_FROM_ADDRESS', 'noreply@viabix.com');
$mailFromName = viabix_env('MAIL_FROM_NAME', 'Viabix');

echo "Provider: {$mailProvider}\n";
echo "From: {$mailFromName} <{$mailFrom}>\n";

// Verificar configuração do provider específico
switch ($mailProvider) {
    case 'sendgrid':
        $sendgridKey = viabix_env('MAIL_SENDGRID_API_KEY');
        echo "SendGrid API Key: " . ($sendgridKey ? "✅ CONFIGURED" : "❌ MISSING") . "\n";
        if (!$sendgridKey) {
            echo "\n⚠️  CONFIGURE MAIL_SENDGRID_API_KEY in .env.production\n";
            echo "   Get free API key from: https://sendgrid.com/\n";
        }
        break;
    
    case 'mailgun':
        $mailgunKey = viabix_env('MAIL_MAILGUN_API_KEY');
        $mailgunDomain = viabix_env('MAIL_MAILGUN_DOMAIN');
        echo "Mailgun API Key: " . ($mailgunKey ? "✅ CONFIGURED" : "❌ MISSING") . "\n";
        echo "Mailgun Domain: " . ($mailgunDomain ? "✅ CONFIGURED" : "❌ MISSING") . "\n";
        break;
    
    case 'smtp':
    default:
        $smtpHost = viabix_env('MAIL_HOST', 'localhost');
        $smtpPort = viabix_env('MAIL_PORT', '587');
        echo "SMTP Host: {$smtpHost}:{$smtpPort}\n";
        echo "SMTP User: " . (viabix_env('MAIL_USERNAME') ? "✅ CONFIGURED" : "❌ MISSING") . "\n";
        break;
}

echo "\n";

// ========================================
// TEST 2: Test email functions exist
// ========================================
echo "TEST 2: Email Functions\n";
echo "-----------------------\n";

$functions = [
    'viabixSendEmail',
    'viabixSendWelcomeEmail',
    'viabixSendPasswordResetEmail',
    'viabixLoadEmailTemplate'
];

$allExist = true;
foreach ($functions as $func) {
    $exists = function_exists($func);
    echo "Function {$func}: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    $allExist = $allExist && $exists;
}

echo "\n";

if (!$allExist) {
    echo "❌ Some email functions are missing. Make sure config.php requires email.php\n\n";
    exit(1);
}

// ========================================
// TEST 3: Load email templates
// ========================================
echo "TEST 3: Email Templates\n";
echo "----------------------\n";

$templates = ['welcome', 'password_reset', 'email_verification', 'payment_confirmation', 'invoice'];
$templateDir = __DIR__ . '/../templates/email';

foreach ($templates as $template) {
    $path = "{$templateDir}/{$template}.php";
    
    if (file_exists($path)) {
        echo "Template {$template}.php: ✅ EXISTS\n";
        
        // Try to load template
        try {
            $template_data = include $path;
            
            if (isset($template_data['subject']) && isset($template_data['html'])) {
                echo "  - Subject: OK\n";
                echo "  - HTML: " . strlen($template_data['html']) . " bytes\n";
            } else {
                echo "  - ❌ Invalid template format\n";
            }
        } catch (Exception $e) {
            echo "  - ❌ Error loading template: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Template {$template}.php: ❌ MISSING ({$path})\n";
    }
}

echo "\n";

// ========================================
// TEST 4: Send test email (OPTIONAL)
// ========================================
echo "TEST 4: Send Test Email (Optional)\n";
echo "-----------------------------------\n";

echo "To send a test email, provide your email address as argument:\n";
echo "  php api/test_email_delivery.php your-email@example.com\n\n";

if ($argc > 1) {
    $testEmail = $argv[1];
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        echo "❌ Invalid email address: {$testEmail}\n";
        exit(1);
    }
    
    echo "Sending test welcome email to: {$testEmail}\n";
    
    // Require config to initialize email system
    require_once __DIR__ . '/config.php';
    
    $result = viabixSendWelcomeEmail(
        $testEmail,
        'Test User',
        (viabix_env('APP_URL') ?: 'https://app.viabix.com') . '/login.html'
    );
    
    if ($result['success']) {
        echo "✅ Email sent successfully!\n";
        echo "Message ID: " . $result['message_id'] . "\n";
        echo "\nCheck your email for the welcome message.\n";
    } else {
        echo "❌ Failed to send email\n";
        echo "Error: " . $result['message'] . "\n";
        
        // More detailed error for SendGrid
        if ($mailProvider === 'sendgrid' && !viabix_env('MAIL_SENDGRID_API_KEY')) {
            echo "\n⚠️  SendGrid API key not configured!\n";
            echo "Steps to fix:\n";
            echo "1. Create free account at https://sendgrid.com/\n";
            echo "2. Generate API key in Settings\n";
            echo "3. Add to .env.production: MAIL_SENDGRID_API_KEY=your_key_here\n";
            echo "4. Run: sudo systemctl restart php8.2-fpm\n";
        }
    }
    
    echo "\n";
}

// ========================================
// SUMMARY
// ========================================
echo "SUMMARY\n";
echo "=======\n";

if ($mailProvider === 'sendgrid' && !viabix_env('MAIL_SENDGRID_API_KEY')) {
    echo "⚠️  Email system NOT READY\n";
    echo "\n📋 Setup Steps:\n";
    echo "1. Create SendGrid account (free): https://sendgrid.com/\n";
    echo "2. Generate API key in Settings → API Keys\n";
    echo "3. Copy API key\n";
    echo "4. SSH to DigitalOcean:\n";
    echo "   ssh root@your_ip\n";
    echo "5. Edit .env.production:\n";
    echo "   nano /var/www/viabix/.env.production\n";
    echo "6. Find line: MAIL_SENDGRID_API_KEY=CHANGE_ME_SENDGRID_API_KEY\n";
    echo "7. Replace with your API key\n";
    echo "8. Save (Ctrl+X → Y → Enter)\n";
    echo "9. Restart PHP:\n";
    echo "   sudo systemctl restart php8.2-fpm\n";
    echo "10. Test again:\n";
    echo "   php api/test_email_delivery.php your-email@example.com\n";
} else if ($allExist && file_exists($templateDir)) {
    echo "✅ Email system is READY\n";
    echo "\n🚀 Features enabled:\n";
    echo "- Welcome emails on signup\n";
    echo "- Password reset emails\n";
    echo "- Payment confirmation emails\n";
    echo "- Invoice emails\n";
    echo "- Email verification\n";
} else {
    echo "⚠️  Email system INCOMPLETE\n";
    echo "   Check errors above\n";
}

echo "\n📚 See EMAIL_DELIVERY_SETUP.md for full documentation\n\n";

?>
