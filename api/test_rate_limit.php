<?php
/**
 * Rate Limiting Test Interface
 * 
 * Purpose: Test rate limiting configuration
 * Access: http://localhost/ANVI/api/test_rate_limit.php
 * 
 * THIS IS A DEVELOPMENT/TESTING FILE - DO NOT USE IN PRODUCTION
 */

require_once 'config.php';

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'simulate_login':
            header('Content-Type: application/json');
            $check = viabixCheckIpRateLimit('test_login', 5, 300);
            echo json_encode([
                'status' => 'simulated_login',
                'allowed' => $check['allowed'],
                'attempts' => $check['attempts'],
                'max_attempts' => 5,
                'reset_in' => $check['reset_in']
            ]);
            exit;
            
        case 'simulate_signup':
            header('Content-Type: application/json');
            $check = viabixCheckIpRateLimit('test_signup', 3, 300);
            echo json_encode([
                'status' => 'simulated_signup',
                'allowed' => $check['allowed'],
                'attempts' => $check['attempts'],
                'max_attempts' => 3,
                'reset_in' => $check['reset_in']
            ]);
            exit;
            
        case 'simulate_api':
            header('Content-Type: application/json');
            $user_id = intval($_POST['user_id'] ?? 1);
            $check = viabixCheckUserRateLimit($user_id, 'test_api', 100, 60);
            echo json_encode([
                'status' => 'simulated_api',
                'user_id' => $user_id,
                'allowed' => $check['allowed'],
                'requests' => $check['requests'],
                'max_requests' => 100,
                'reset_in' => $check['reset_in']
            ]);
            exit;
            
        case 'get_status':
            header('Content-Type: application/json');
            $ip = viabixGetClientIp();
            echo json_encode([
                'status' => viabixGetRateLimitStatus(),
                'client_ip' => $ip
            ]);
            exit;
            
        case 'clear_all':
            header('Content-Type: application/json');
            $_SESSION['rate_limit'] = [];
            echo json_encode([
                'status' => 'cleared',
                'message' => 'All rate limits cleared'
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
    <title>Rate Limiting Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            border-bottom: 3px solid #f5576c;
            font-size: 1.5em;
        }
        
        .section h3 {
            color: #555;
            margin: 20px 0 10px 0;
            font-size: 1.1em;
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
            background: #f5576c;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e03d52;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245, 87, 108, 0.4);
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
            background: #dc2626;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220, 38, 38, 0.4);
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
        
        .result-box.blocked {
            background: #fee2e2;
            border-left-color: #dc2626;
        }
        
        .result-box.allowed {
            background: #dcfce7;
            border-left-color: #16a34a;
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
        }
        
        .counter {
            display: inline-block;
            background: #f5576c;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            margin: 0 5px;
        }
        
        .counter.blocked {
            background: #dc2626;
        }
        
        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #48bb78 0%, #f5576c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.85em;
            transition: width 0.3s ease;
        }
        
        .info-box {
            background: #ede9fe;
            border-left: 4px solid #a855f7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table th {
            background: #f5576c;
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
        
        .status-timer {
            font-size: 2em;
            text-align: center;
            color: #f5576c;
            font-weight: 600;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ Rate Limiting Test</h1>
            <p>Test Brute Force & API Throttling Protection</p>
        </div>
        
        <div class="content">
            <!-- Test 1: Login Simulation -->
            <div class="section">
                <h2>🔐 Test 1: Login Rate Limiting</h2>
                <p style="color: #666; margin-bottom: 15px;">Simulates 5 login attempts per 5 minutes. Click the button repeatedly to see when it blocks.</p>
                
                <div class="test-group">
                    <p style="margin-bottom: 10px;">Current Progress:</p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="login-progress" style="width: 0%;">0/5</div>
                    </div>
                    <div class="button-group">
                        <button class="btn-primary" onclick="testLogin()">Attempt Login</button>
                        <button class="btn-secondary" onclick="resetLoginTest()">Reset Test</button>
                    </div>
                </div>
                
                <div id="login-result" class="result-box">
                    <div class="result-title">Result:</div>
                    <div class="result-content" id="login-result-content"></div>
                </div>
            </div>
            
            <!-- Test 2: Signup Simulation -->
            <div class="section">
                <h2>📝 Test 2: Signup Rate Limiting</h2>
                <p style="color: #666; margin-bottom: 15px;">Simulates 3 signup attempts per 5 minutes. Tests account creation spam protection.</p>
                
                <div class="test-group">
                    <p style="margin-bottom: 10px;">Current Progress:</p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="signup-progress" style="width: 0%;">0/3</div>
                    </div>
                    <div class="button-group">
                        <button class="btn-primary" onclick="testSignup()">Attempt Signup</button>
                        <button class="btn-secondary" onclick="resetSignupTest()">Reset Test</button>
                    </div>
                </div>
                
                <div id="signup-result" class="result-box">
                    <div class="result-title">Result:</div>
                    <div class="result-content" id="signup-result-content"></div>
                </div>
            </div>
            
            <!-- Test 3: API Throttling -->
            <div class="section">
                <h2>🔌 Test 3: API Throttling</h2>
                <p style="color: #666; margin-bottom: 15px;">Simulates 100 API requests per minute per user. Tests authenticated endpoint throttling.</p>
                
                <div class="test-group">
                    <label for="user-id">User ID:</label>
                    <input type="number" id="user-id" value="1" min="1">
                    <p style="margin-bottom: 10px;">Current Progress:</p>
                    <div class="progress-bar">
                        <div class="progress-fill" id="api-progress" style="width: 0%;">0/100</div>
                    </div>
                    <div class="button-group">
                        <button class="btn-primary" onclick="testApi()">Attempt API Call</button>
                        <button class="btn-secondary" onclick="bulkTestApi(10)">Bulk x10</button>
                        <button class="btn-danger" onclick="resetApiTest()">Reset Test</button>
                    </div>
                </div>
                
                <div id="api-result" class="result-box">
                    <div class="result-title">Result:</div>
                    <div class="result-content" id="api-result-content"></div>
                </div>
            </div>
            
            <!-- Configuration Info -->
            <div class="section">
                <h2>📋 Rate Limiting Configuration</h2>
                
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Max Attempts</th>
                            <th>Window</th>
                            <th>Threat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Login</strong></td>
                            <td>5 attempts</td>
                            <td>5 minutes</td>
                            <td>Brute force password attacks</td>
                        </tr>
                        <tr>
                            <td><strong>Signup</strong></td>
                            <td>3 attempts</td>
                            <td>5 minutes</td>
                            <td>Account creation spam</td>
                        </tr>
                        <tr>
                            <td><strong>API</strong></td>
                            <td>100 requests</td>
                            <td>1 minute</td>
                            <td>Unauthorized data harvesting</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Management -->
            <div class="section">
                <h2>🛠️ Test Management</h2>
                <div class="test-group">
                    <p style="margin-bottom: 15px;">Clear all test limits to start fresh:</p>
                    <button class="btn-danger" onclick="clearAllLimits()">Clear All Rate Limits</button>
                </div>
                
                <div id="status-result" class="result-box show info-box">
                    <div class="result-title">System Status:</div>
                    <div class="result-content" id="status-content">Loading...</div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="section">
                <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                    <strong>⚠️ Important:</strong> This is a development testing interface. In production, remove or restrict access to this file. See RATE_LIMITING.md documentation for details.
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let loginAttempts = 0;
        let signupAttempts = 0;
        let apiRequests = 0;
        
        function testLogin() {
            const resultBox = document.getElementById('login-result');
            const resultContent = document.getElementById('login-result-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Testing...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=simulate_login'
            })
            .then(r => r.json())
            .then(data => {
                loginAttempts = data.attempts;
                updateLoginProgress();
                
                const status = data.allowed ? '✅ ALLOWED' : '❌ BLOCKED';
                const text = `Attempt: ${data.attempts}/${data.max_attempts}
Status: ${status}
Reset In: ${data.reset_in} seconds
Message: ${data.allowed ? 'Login request would succeed' : 'Rate limit exceeded - login blocked'}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('allowed', data.allowed);
                resultBox.classList.toggle('blocked', !data.allowed);
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('blocked');
            });
        }
        
        function testSignup() {
            const resultBox = document.getElementById('signup-result');
            const resultContent = document.getElementById('signup-result-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Testing...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=simulate_signup'
            })
            .then(r => r.json())
            .then(data => {
                signupAttempts = data.attempts;
                updateSignupProgress();
                
                const status = data.allowed ? '✅ ALLOWED' : '❌ BLOCKED';
                const text = `Attempt: ${data.attempts}/${data.max_attempts}
Status: ${status}
Reset In: ${data.reset_in} seconds
Message: ${data.allowed ? 'Signup request would succeed' : 'Rate limit exceeded - signup blocked'}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('allowed', data.allowed);
                resultBox.classList.toggle('blocked', !data.allowed);
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('blocked');
            });
        }
        
        function testApi() {
            const userId = document.getElementById('user-id').value;
            const resultBox = document.getElementById('api-result');
            const resultContent = document.getElementById('api-result-content');
            
            resultBox.classList.add('show');
            resultContent.textContent = 'Testing...';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=simulate_api&user_id=' + userId
            })
            .then(r => r.json())
            .then(data => {
                apiRequests = data.requests;
                updateApiProgress();
                
                const status = data.allowed ? '✅ ALLOWED' : '❌ BLOCKED';
                const text = `User ID: ${data.user_id}
Request: ${data.requests}/${data.max_requests}
Status: ${status}
Reset In: ${data.reset_in} seconds
Message: ${data.allowed ? 'API request would succeed' : 'Rate limit exceeded - request blocked'}`;
                
                resultContent.textContent = text;
                resultBox.classList.toggle('allowed', data.allowed);
                resultBox.classList.toggle('blocked', !data.allowed);
            })
            .catch(err => {
                resultContent.textContent = '❌ Error: ' + err.message;
                resultBox.classList.add('blocked');
            });
        }
        
        async function bulkTestApi(count) {
            for (let i = 0; i < count; i++) {
                testApi();
                await new Promise(resolve => setTimeout(resolve, 100));
            }
        }
        
        function updateLoginProgress() {
            const progress = (loginAttempts / 5) * 100;
            document.getElementById('login-progress').style.width = progress + '%';
            document.getElementById('login-progress').textContent = loginAttempts + '/5';
        }
        
        function updateSignupProgress() {
            const progress = (signupAttempts / 3) * 100;
            document.getElementById('signup-progress').style.width = progress + '%';
            document.getElementById('signup-progress').textContent = signupAttempts + '/3';
        }
        
        function updateApiProgress() {
            const progress = (apiRequests / 100) * 100;
            document.getElementById('api-progress').style.width = Math.min(progress, 100) + '%';
            document.getElementById('api-progress').textContent = apiRequests + '/100';
        }
        
        function resetLoginTest() {
            loginAttempts = 0;
            updateLoginProgress();
            document.getElementById('login-result').classList.remove('show');
        }
        
        function resetSignupTest() {
            signupAttempts = 0;
            updateSignupProgress();
            document.getElementById('signup-result').classList.remove('show');
        }
        
        function resetApiTest() {
            apiRequests = 0;
            updateApiProgress();
            document.getElementById('api-result').classList.remove('show');
        }
        
        function clearAllLimits() {
            if (!confirm('Clear all rate limits? This resets all test progress.')) return;
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear_all'
            })
            .then(r => r.json())
            .then(data => {
                resetLoginTest();
                resetSignupTest();
                resetApiTest();
                updateStatus();
                alert('All rate limits cleared!');
            });
        }
        
        function updateStatus() {
            const resultContent = document.getElementById('status-content');
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_status'
            })
            .then(r => r.json())
            .then(data => {
                const text = `Client IP: ${data.client_ip}
Session ID: ${data.status.timestamp}
Active Limits: ${Object.keys(data.status.limits).length}`;
                
                resultContent.textContent = text;
            });
        }
        
        // Auto-update status on load
        document.addEventListener('DOMContentLoaded', updateStatus);
    </script>
</body>
</html>
