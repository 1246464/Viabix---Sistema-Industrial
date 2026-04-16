<?php
/**
 * CORS Security Test Interface
 * 
 * Purpose: Test CORS configuration with different origins and methods
 * Access: http://localhost/ANVI/api/test_cors.php
 * 
 * THIS IS A DEVELOPMENT/TESTING FILE - DO NOT USE IN PRODUCTION
 */

require_once 'config.php';

// Parse configuration
$allowed_origins = viabixGetAllowedCorsOrigins();
$allowed_origins_str = implode(', ', $allowed_origins);

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $test_origin = $_POST['test_origin'] ?? '';
    
    switch ($action) {
        case 'test_preflight':
            // Simulate preflight request
            $_SERVER['HTTP_ORIGIN'] = $test_origin;
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'preflight_test',
                'origin' => $test_origin,
                'allowed' => viabixIsOriginAllowed($test_origin),
                'currently_configured_origins' => $allowed_origins
            ]);
            exit;
            
        case 'test_direct':
            // Test direct request with CORS
            $_SERVER['HTTP_ORIGIN'] = $test_origin;
            header('Content-Type: application/json');
            
            if (viabixIsOriginAllowed($test_origin)) {
                viabixConfigureCors(['GET', 'POST', 'OPTIONS']);
                echo json_encode([
                    'status' => 'allowed',
                    'origin' => $test_origin,
                    'message' => 'CORS headers applied'
                ]);
            } else {
                echo json_encode([
                    'status' => 'blocked',
                    'origin' => $test_origin,
                    'message' => 'Origin not in whitelist'
                ]);
            }
            exit;
            
        case 'check_config':
            // Show current config
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'config',
                'allowed_origins' => $allowed_origins,
                'env_variable' => getenv('CORS_ALLOWED_ORIGINS'),
                'app_env' => APP_ENV,
                'https_enabled' => viabix_request_is_https()
            ]);
            exit;
    }
}

// Handle preflight for this test file itself
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    viabixHandleCorsPreflight(['GET', 'POST', 'OPTIONS'], ['Content-Type']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORS Security Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 900px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            font-size: 1.5em;
        }
        
        .section h3 {
            color: #555;
            margin: 20px 0 10px 0;
            font-size: 1.1em;
        }
        
        .config-box {
            background: #f5f7fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.95em;
            word-break: break-all;
        }
        
        .test-group {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #e0e0e0;
        }
        
        .test-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #333;
        }
        
        .test-group input,
        .test-group select {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
            font-family: 'Courier New', monospace;
        }
        
        .test-group input:focus,
        .test-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.3);
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.3s ease;
            flex: 1;
            min-width: 150px;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #48bb78;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #38a169;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(72, 187, 120, 0.4);
        }
        
        .btn-danger {
            background: #f56565;
            color: white;
        }
        
        .btn-danger:hover {
            background: #e53e3e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 101, 101, 0.4);
        }
        
        .result-box {
            background: #e8f4f8;
            border-left: 4px solid #0891b2;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            display: none;
        }
        
        .result-box.show {
            display: block;
        }
        
        .result-box.success {
            background: #dcfce7;
            border-left-color: #16a34a;
        }
        
        .result-box.error {
            background: #fee2e2;
            border-left-color: #dc2626;
        }
        
        .result-title {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        
        .result-content {
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            white-space: pre-wrap;
            word-break: break-all;
        }
        
        .info-box {
            background: #ede9fe;
            border-left: 4px solid #a855f7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .info-box strong {
            color: #7e22ce;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .status-success {
            background: #16a34a;
        }
        
        .status-error {
            background: #dc2626;
        }
        
        .status-warning {
            background: #f59e0b;
        }
        
        .header-info {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        .header-info div {
            margin: 5px 0;
        }
        
        .code {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        table tr:hover {
            background: #f5f7fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 CORS Security Test</h1>
            <p>Test Cross-Origin Resource Sharing Configuration</p>
        </div>
        
        <div class="content">
            <!-- Current Configuration -->
            <div class="section">
                <h2>📋 Current CORS Configuration</h2>
                
                <div class="header-info">
                    <div><strong>Application Environment:</strong> <?php echo APP_ENV; ?></div>
                    <div><strong>HTTPS Enabled:</strong> <?php echo viabix_request_is_https() ? '✅ Yes' : '❌ No'; ?></div>
                    <div><strong>.env Variable Set:</strong> <?php echo !empty(getenv('CORS_ALLOWED_ORIGINS')) ? '✅ Yes' : '❌ No'; ?></div>
                </div>
                
                <h3>Allowed Origins</h3>
                <div class="config-box">
                    <?php 
                    if (!empty($allowed_origins)) {
                        echo implode("\n", $allowed_origins);
                        echo "\n\n✅ CORS is configured";
                    } else {
                        echo "❌ NO ORIGINS CONFIGURED\n";
                        echo "Set CORS_ALLOWED_ORIGINS in .env file";
                    }
                    ?>
                </div>
                
                <button class="btn-secondary" onclick="checkConfig()">🔍 Verify Configuration</button>
            </div>
            
            <!-- Test Preflight -->
            <div class="section">
                <h2>🧪 Test 1: Preflight Request (OPTIONS)</h2>
                <p style="color: #666; margin-bottom: 15px;">Test if a CORS preflight request would be allowed from a specific origin.</p>
                
                <div class="test-group">
                    <label for="origin1">Test Origin:</label>
                    <input type="text" id="origin1" placeholder="e.g., https://app.viabix.com" value="https://app.viabix.com">
                    <div class="button-group">
                        <button class="btn-primary" onclick="testPreflight()">Test Preflight</button>
                        <button class="btn-secondary" onclick="addCommonOrigin('origin1')">+ Add Common</button>
                    </div>
                </div>
                
                <div id="result1" class="result-box">
                    <div class="result-title">Result:</div>
                    <div class="result-content" id="result1-content"></div>
                </div>
            </div>
            
            <!-- Test Direct Request -->
            <div class="section">
                <h2>🧪 Test 2: Direct CORS Request</h2>
                <p style="color: #666; margin-bottom: 15px;">Test if a direct request with CORS headers would be allowed.</p>
                
                <div class="test-group">
                    <label for="origin2">Test Origin:</label>
                    <input type="text" id="origin2" placeholder="e.g., https://malicious.com" value="https://malicious.com">
                    <div class="button-group">
                        <button class="btn-danger" onclick="testDirect()">Test Request (Blocked?)</button>
                        <button class="btn-secondary" onclick="addCommonOrigin('origin2')">+ Add Common</button>
                    </div>
                </div>
                
                <div id="result2" class="result-box">
                    <div class="result-title">Result:</div>
                    <div class="result-content" id="result2-content"></div>
                </div>
            </div>
            
            <!-- Test Cases -->
            <div class="section">
                <h2>📚 Pre-configured Test Cases</h2>
                
                <h3>Allowed Origins (Should Pass ✅)</h3>
                <div class="test-group">
                    <button class="btn-primary" onclick="runTestCase('allowed')">Run Allowed Origins Tests</button>
                </div>
                
                <h3>Blocked Origins (Should Fail ❌)</h3>
                <div class="test-group">
                    <button class="btn-danger" onclick="runTestCase('blocked')">Run Blocked Origins Tests</button>
                </div>
                
                <div id="result3" class="result-box">
                    <div class="result-title">Results:</div>
                    <div class="result-content" id="result3-content"></div>
                </div>
            </div>
            
            <!-- Security Headers Check -->
            <div class="section">
                <h2>🛡️ Security Headers Check</h2>
                <p style="color: #666; margin-bottom: 15px;">Verify that all security headers are properly configured.</p>
                
                <div class="info-box">
                    <strong>Expected Security Headers:</strong><br>
                    ✓ X-Frame-Options<br>
                    ✓ X-Content-Type-Options<br>
                    ✓ X-XSS-Protection<br>
                    ✓ Referrer-Policy<br>
                    ✓ Permissions-Policy<br>
                    ✓ Strict-Transport-Security (HTTPS only)
                </div>
            </div>
            
            <!-- Common Origins List -->
            <div class="section">
                <h2>📌 Common Test Origins</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Environment</th>
                            <th>Origins</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Local Development</strong></td>
                            <td>http://localhost, http://localhost:3000, http://127.0.0.1</td>
                        </tr>
                        <tr>
                            <td><strong>Viabix Staging</strong></td>
                            <td>https://staging.viabix.com, https://staging-admin.viabix.com</td>
                        </tr>
                        <tr>
                            <td><strong>Viabix Production</strong></td>
                            <td>https://app.viabix.com, https://www.viabix.com, https://admin.viabix.com</td>
                        </tr>
                        <tr>
                            <td><strong>Malicious Examples</strong></td>
                            <td>https://malicious.com, https://evil.org, https://phishing.net</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Help & Troubleshooting -->
            <div class="section">
                <h2>❓ Troubleshooting</h2>
                
                <h3>No CORS headers returned?</h3>
                <div class="info-box">
                    1. Verify origin is in <code>CORS_ALLOWED_ORIGINS</code> in .env file<br>
                    2. Restart PHP/web server after changing .env<br>
                    3. Check that <code>api/cors.php</code> is correctly included in <code>api/config.php</code><br>
                    4. Review error logs for PHP errors
                </div>
                
                <h3>Test shows origin as blocked but should be allowed?</h3>
                <div class="info-box">
                    1. Ensure origin URL exactly matches .env (protocol, domain, port)<br>
                    2. No trailing slashes in origin URL<br>
                    3. Use <code>http://</code> for localhost, <code>https://</code> for production<br>
                    4. Clear browser cache and reload
                </div>
                
                <h3>CORS errors in third-party integrations?</h3>
                <div class="info-box">
                    1. Add the integration's domain to <code>CORS_ALLOWED_ORIGINS</code><br>
                    2. If using webhooks, they may not need CORS (different API pattern)<br>
                    3. Request webhook provider to add your domain to their whitelist
                </div>
            </div>
            
            <!-- Footer -->
            <div class="section">
                <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                    <strong>⚠️ Important:</strong> This is a development testing interface. In production, restrict access to this file or remove it. For details, see CORS_PROTECTION.md documentation.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Add common origin to input
        function addCommonOrigin(inputId) {
            const origins = {
                'origin1': ['https://app.viabix.com', 'http://localhost:3000', 'https://staging.viabix.com'],
                'origin2': ['https://malicious.com', 'https://evil.org', 'https://phishing.net']
            };
            
            const options = origins[inputId] || [];
            const selected = prompt('Select an origin:\n\n' + options.map((o, i) => (i+1) + '. ' + o).join('\n'));
            
            if (selected && !isNaN(selected)) {
                const idx = parseInt(selected) - 1;
                if (idx >= 0 && idx < options.length) {
                    document.getElementById(inputId).value = options[idx];
                }
            }
        }
        
        // Test preflight
        function testPreflight() {
            const origin = document.getElementById('origin1').value.trim();
            if (!origin) {
                alert('Please enter an origin');
                return;
            }
            
            const resultBox = document.getElementById('result1');
            const resultContent = document.getElementById('result1-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Testing...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_preflight&test_origin=' + encodeURIComponent(origin)
            })
            .then(r => r.json())
            .then(data => {
                const allowed = data.allowed ? '✅ ALLOWED' : '❌ BLOCKED';
                const text = `Origin: ${origin}
Status: ${allowed}

Allowed Origins:
${data.currently_configured_origins.join('\n')}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('success', data.allowed);
                resultBox.classList.toggle('error', !data.allowed);
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('error');
            });
        }
        
        // Test direct request
        function testDirect() {
            const origin = document.getElementById('origin2').value.trim();
            if (!origin) {
                alert('Please enter an origin');
                return;
            }
            
            const resultBox = document.getElementById('result2');
            const resultContent = document.getElementById('result2-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Testing...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_direct&test_origin=' + encodeURIComponent(origin)
            })
            .then(r => r.json())
            .then(data => {
                const text = `Origin: ${origin}
Status: ${data.status}
Message: ${data.message}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('success', data.status === 'allowed');
                resultBox.classList.toggle('error', data.status !== 'allowed');
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('error');
            });
        }
        
        // Check config
        function checkConfig() {
            const resultBox = document.getElementById('result3');
            const resultContent = document.getElementById('result3-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Checking configuration...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_config'
            })
            .then(r => r.json())
            .then(data => {
                let text = 'Configuration Status:\n\n';
                text += '📋 App Environment: ' + data.app_env + '\n';
                text += '🔒 HTTPS Enabled: ' + (data.https_enabled ? '✅ Yes' : '❌ No') + '\n';
                text += '🔧 Configured Origins: ' + data.allowed_origins.length + '\n\n';
                text += 'Allowed Origins:\n' + data.allowed_origins.join('\n');
                
                resultContent.textContent = text;
                resultBox.classList.add('success');
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('error');
            });
        }
        
        // Run test cases
        async function runTestCase(type) {
            const resultBox = document.getElementById('result3');
            const resultContent = document.getElementById('result3-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Running tests...';
            
            const origins = type === 'allowed' 
                ? ['https://app.viabix.com', 'https://www.viabix.com', 'http://localhost']
                : ['https://malicious.com', 'https://evil.org', 'https://phishing.net'];
            
            let results = type === 'allowed' ? '✅ ALLOWED ORIGINS (Should Pass)\n\n' : '❌ BLOCKED ORIGINS (Should Fail)\n\n';
            
            for (const origin of origins) {
                try {
                    const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'action=test_preflight&test_origin=' + encodeURIComponent(origin)
                    });
                    
                    const data = await response.json();
                    const status = data.allowed ? '✅' : '❌';
                    results += `${status} ${origin}\n`;
                } catch (err) {
                    results += `⚠️ ${origin} - Error\n`;
                }
            }
            
            resultContent.textContent = results;
            resultBox.classList.add(type === 'allowed' ? 'success' : 'error');
        }
        
        // Auto-check config on load
        document.addEventListener('DOMContentLoaded', checkConfig);
    </script>
</body>
</html>
