<?php
/**
 * Email System Test Interface
 * 
 * Purpose: Test email sending and template rendering
 * Access: http://localhost/ANVI/api/test_email.php
 * 
 * THIS IS A DEVELOPMENT/TESTING FILE - DO NOT USE IN PRODUCTION
 */

require_once 'config.php';

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'send_test':
            $to = trim($_POST['to'] ?? '');
            $template = trim($_POST['template'] ?? 'welcome');
            
            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['success' => false, 'message' => 'Invalid email address']);
                exit;
            }
            
            $result = viabixSendEmail($to, $template, [
                'name' => $_POST['name'] ?? 'Test User',
                'login_url' => $_POST['login_url'] ?? (getenv('APP_URL') ?: 'https://app.viabix.com') . '/login',
                'reset_url' => $_POST['reset_url'] ?? (getenv('APP_URL') ?: 'https://app.viabix.com') . '/reset-password',
                'verify_url' => $_POST['verify_url'] ?? (getenv('APP_URL') ?: 'https://app.viabix.com') . '/verify-email',
                'token' => $_POST['token'] ?? uniqid(),
                'amount' => $_POST['amount'] ?? '99.90',
                'currency' => $_POST['currency'] ?? 'BRL',
                'invoice_id' => $_POST['invoice_id'] ?? 'INV-2024-001'
            ]);
            
            echo json_encode($result);
            exit;
            
        case 'render_template':
            $template = trim($_POST['template'] ?? 'welcome');
            
            try {
                $template_data = viabixLoadEmailTemplate($template, [
                    'name' => $_POST['name'] ?? 'John Doe',
                    'login_url' => $_POST['login_url'] ?? 'https://app.viabix.com/login',
                    'reset_url' => $_POST['reset_url'] ?? 'https://app.viabix.com/reset',
                    'verify_url' => $_POST['verify_url'] ?? 'https://app.viabix.com/verify',
                    'token' => $_POST['token'] ?? 'abc123xyz',
                    'amount' => $_POST['amount'] ?? '99.90',
                    'currency' => $_POST['currency'] ?? 'BRL',
                    'invoice_id' => $_POST['invoice_id'] ?? 'INV-001',
                    'date' => date('d/m/Y H:i'),
                    'company_name' => getenv('MAIL_FROM_NAME') ?: 'Viabix'
                ]);
                
                echo json_encode([
                    'success' => true,
                    'subject' => $template_data['subject'],
                    'html' => $template_data['html'],
                    'text' => $template_data['text'] ?? ''
                ]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit;
            
        case 'get_config':
            echo json_encode([
                'success' => true,
                'config' => [
                    'provider' => getenv('MAIL_PROVIDER') ?: 'smtp',
                    'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'noreply@viabix.com',
                    'from_name' => getenv('MAIL_FROM_NAME') ?: 'Viabix',
                    'app_url' => getenv('APP_URL') ?: 'https://app.viabix.com',
                    'smtp_configured' => !empty(getenv('MAIL_HOST')),
                    'sendgrid_configured' => !empty(getenv('MAIL_SENDGRID_API_KEY')),
                    'mailgun_configured' => !empty(getenv('MAIL_MAILGUN_API_KEY'))
                ]
            ]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email System Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 1000px;
            width: 100%;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        @media (max-width: 1000px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
        
        .header {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 30px;
            text-align: center;
            grid-column: 1 / -1;
        }
        
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .section {
            padding: 30px;
            border-right: 1px solid #e0e0e0;
        }
        
        .section:last-child {
            border-right: none;
        }
        
        @media (max-width: 1000px) {
            .section {
                border-right: none;
                border-bottom: 1px solid #e0e0e0;
            }
            
            .section:last-child {
                border-bottom: none;
            }
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3em;
            border-bottom: 2px solid #11998e;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
            font-size: 0.9em;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9em;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #11998e;
            box-shadow: 0 0 5px rgba(17, 153, 142, 0.3);
        }
        
        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary {
            background: #11998e;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0d7a71;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(17, 153, 142, 0.4);
        }
        
        .btn-secondary {
            background: #38ef7d;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #2cd969;
            transform: translateY(-2px);
        }
        
        .result-box {
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-top: 20px;
            display: none;
            max-height: 300px;
            overflow-y: auto;
            font-size: 0.85em;
        }
        
        .result-box.show {
            display: block;
        }
        
        .result-box.success {
            background: #dcfce7;
            border-color: #16a34a;
        }
        
        .result-box.error {
            background: #fee2e2;
            border-color: #dc2626;
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        
        .result-content {
            font-family: 'Courier New', monospace;
            word-break: break-all;
            white-space: pre-wrap;
        }
        
        .config-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 3px;
            margin: 5px 5px 5px 0;
            font-size: 0.85em;
        }
        
        .config-badge.active {
            background: #dcfce7;
            color: #16a34a;
        }
        
        .preview-container {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .status {
            padding: 10px;
            border-radius: 3px;
            margin: 10px 0;
            font-size: 0.9em;
        }
        
        .status.success {
            background: #dcfce7;
            border-left: 3px solid #16a34a;
            color: #16a34a;
        }
        
        .status.error {
            background: #fee2e2;
            border-left: 3px solid #dc2626;
            color: #dc2626;
        }
        
        .status.info {
            background: #d4edda;
            border-left: 3px solid #11998e;
            color: #11998e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email System Test</h1>
            <p>Test email sending and template rendering</p>
        </div>
        
        <!-- Left side: Send Email -->
        <div class="section">
            <h2>✉️ Send Test Email</h2>
            
            <div class="form-group">
                <label for="send-to">Recipient Email:</label>
                <input type="email" id="send-to" placeholder="recipient@example.com">
            </div>
            
            <div class="form-group">
                <label for="send-template">Template:</label>
                <select id="send-template">
                    <option value="welcome">Welcome Email</option>
                    <option value="password_reset">Password Reset</option>
                    <option value="email_verification">Email Verification</option>
                    <option value="payment_confirmation">Payment Confirmation</option>
                    <option value="invoice">Invoice Email</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="send-name">User Name:</label>
                <input type="text" id="send-name" value="John Doe">
            </div>
            
            <div class="form-group">
                <label for="send-amount">Amount (for payment template):</label>
                <input type="text" id="send-amount" value="99.90">
            </div>
            
            <div class="form-group">
                <label for="send-invoice">Invoice ID (for invoice template):</label>
                <input type="text" id="send-invoice" value="INV-2024-001">
            </div>
            
            <button class="btn-primary" onclick="sendTestEmail()">Send Test Email</button>
            
            <div id="send-result" class="result-box">
                <div class="result-title">Result:</div>
                <div class="result-content" id="send-result-content"></div>
            </div>
        </div>
        
        <!-- Right side: Template Preview -->
        <div class="section">
            <h2>👁️ Template Preview</h2>
            
            <div class="form-group">
                <label for="preview-template">Template:</label>
                <select id="preview-template" onchange="previewTemplate()">
                    <option value="welcome">Welcome Email</option>
                    <option value="password_reset">Password Reset</option>
                    <option value="email_verification">Email Verification</option>
                    <option value="payment_confirmation">Payment Confirmation</option>
                    <option value="invoice">Invoice Email</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="preview-name">Name:</label>
                <input type="text" id="preview-name" value="John Doe" onchange="previewTemplate()">
            </div>
            
            <div class="form-group">
                <label for="preview-token">Token/Code:</label>
                <input type="text" id="preview-token" value="abc123xyz" onchange="previewTemplate()">
            </div>
            
            <button class="btn-secondary" onclick="previewTemplate()">Render Preview</button>
            
            <div id="preview-result" class="result-box show">
                <div class="result-title">Template Preview:</div>
                <div id="preview-content" style="background: white; padding: 15px; border-radius: 3px;"></div>
            </div>
        </div>
        
        <!-- Configuration Info -->
        <div style="grid-column: 1 / -1; padding: 30px; border-top: 1px solid #ddd; background: #f9f9f9;">
            <h3 style="margin-bottom: 15px;">📋 Email Configuration</h3>
            <div id="config-info"></div>
        </div>
    </div>
    
    <script>
        function sendTestEmail() {
            const to = document.getElementById('send-to').value.trim();
            const template = document.getElementById('send-template').value;
            const name = document.getElementById('send-name').value;
            const amount = document.getElementById('send-amount').value;
            const invoice = document.getElementById('send-invoice').value;
            
            if (!to) {
                alert('Please enter recipient email');
                return;
            }
            
            const resultBox = document.getElementById('send-result');
            const resultContent = document.getElementById('send-result-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Sending email...';
            
            const formData = new FormData();
            formData.append('action', 'send_test');
            formData.append('to', to);
            formData.append('template', template);
            formData.append('name', name);
            formData.append('amount', amount);
            formData.append('invoice_id', invoice);
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                const text = data.success 
                    ? `✓ Email sent successfully!\nMessage ID: ${data.message_id}`
                    : `✗ Failed to send email\nError: ${data.message}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('success', data.success);
                resultBox.classList.toggle('error', !data.success);
            })
            .catch(err => {
                resultContent.textContent = '✗ Error: ' + err.message;
                resultBox.classList.add('error');
            });
        }
        
        function previewTemplate() {
            const template = document.getElementById('preview-template').value;
            const name = document.getElementById('preview-name').value;
            const token = document.getElementById('preview-token').value;
            
            const formData = new FormData();
            formData.append('action', 'render_template');
            formData.append('template', template);
            formData.append('name', name);
            formData.append('token', token);
            formData.append('amount', '99.90');
            formData.append('currency', 'BRL');
            formData.append('invoice_id', 'INV-2024-001');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const previewContent = document.getElementById('preview-content');
                    previewContent.innerHTML = `<strong>Subject:</strong> ${data.subject}<br><br><iframe style="width: 100%; height: 400px; border: none; border-radius: 5px;" srcdoc="${escapeHtml(data.html)}"></iframe>`;
                } else {
                    document.getElementById('preview-content').textContent = 'Error: ' + data.message;
                }
            })
            .catch(err => {
                document.getElementById('preview-content').textContent = 'Error: ' + err.message;
            });
        }
        
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
        
        function loadConfig() {
            const formData = new FormData();
            formData.append('action', 'get_config');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const config = data.config;
                    let html = `<strong>Provider:</strong> ${config.provider}<br>`;
                    html += `<strong>From:</strong> ${config.from_name} &lt;${config.from_address}&gt;<br><br>`;
                    html += `<strong>Configured Providers:</strong><br>`;
                    
                    if (config.smtp_configured) {
                        html += '<span class="config-badge active">✓ SMTP Configured</span>';
                    } else {
                        html += '<span class="config-badge">✗ SMTP Not Configured</span>';
                    }
                    
                    if (config.sendgrid_configured) {
                        html += '<span class="config-badge active">✓ SendGrid Configured</span>';
                    } else {
                        html += '<span class="config-badge">✗ SendGrid Not Configured</span>';
                    }
                    
                    if (config.mailgun_configured) {
                        html += '<span class="config-badge active">✓ Mailgun Configured</span>';
                    } else {
                        html += '<span class="config-badge">✗ Mailgun Not Configured</span>';
                    }
                    
                    document.getElementById('config-info').innerHTML = html;
                }
            });
        }
        
        // Load config and initial template on page load
        document.addEventListener('DOMContentLoaded', () => {
            loadConfig();
            previewTemplate();
        });
    </script>
</body>
</html>
