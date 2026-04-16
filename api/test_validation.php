<?php
/**
 * Input Validation Framework Test Interface
 * Interactive testing UI for validation & sanitization
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Test cases
$test_results = [];
$sanitization_samples = [
    'XSS Attack' => '<script>alert("xss")</script>',
    'SQL Injection' => "'; DROP TABLE usuarios; --",
    'Email' => 'user@example.com',
    'URL' => 'https://example.com/page?id=123',
    'Phone' => '(11) 99999-8888',
    'CPF' => '123.456.789-09',
    'CNPJ' => '11.222.333/0001-81',
    'Password' => 'SecurePass123!',
    'Number' => '$1,234.56',
    'HTML Content' => '<div class="alert">Test</div>',
];

// Validation test cases
$validation_tests = [
    [
        'name' => 'Valid Email',
        'data' => ['email' => 'user@example.com'],
        'rules' => ['email' => 'required|email'],
        'should_pass' => true
    ],
    [
        'name' => 'Invalid Email',
        'data' => ['email' => 'not-an-email'],
        'rules' => ['email' => 'required|email'],
        'should_pass' => false
    ],
    [
        'name' => 'Valid CPF',
        'data' => ['cpf' => '123.456.789-09'],
        'rules' => ['cpf' => 'required|cpf'],
        'should_pass' => true
    ],
    [
        'name' => 'Strong Password',
        'data' => ['password' => 'StrongPass123'],
        'rules' => ['password' => 'required|password'],
        'should_pass' => true
    ],
    [
        'name' => 'Weak Password',
        'data' => ['password' => 'weak'],
        'rules' => ['password' => 'required|password'],
        'should_pass' => false
    ],
    [
        'name' => 'Min Length Validation',
        'data' => ['name' => 'John'],
        'rules' => ['name' => 'required|min_length:5'],
        'should_pass' => false
    ],
    [
        'name' => 'Max Length Validation',
        'data' => ['description' => 'This is a very long text that exceeds the maximum allowed length'],
        'rules' => ['description' => 'required|max_length:50'],
        'should_pass' => false
    ],
];

// Run validation tests
foreach ($validation_tests as &$test) {
    $result = viabixValidate($test['data'], $test['rules']);
    $test['passed'] = $result['success'] === $test['should_pass'];
    $test['errors'] = $result['errors'];
}

// Handle form submissions
$form_action = $_POST['action'] ?? null;
$form_results = null;

if ($form_action === 'sanitize') {
    $value = $_POST['value'] ?? '';
    $type = $_POST['sanitize_type'] ?? 'string';
    
    $form_results = [
        'type' => 'sanitize',
        'original' => $value,
        'type' => $type,
        'sanitized' => viabixSanitize($value, $type),
        'escaped' => viabixEscape($value, 'html')
    ];
}

if ($form_action === 'validate') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $cpf = $_POST['cpf'] ?? '';
    
    $data = [
        'email' => $email,
        'password' => $password,
        'password_confirm' => $password_confirm,
        'cpf' => $cpf
    ];
    
    $rules = [
        'email' => 'required|email|email_unique',
        'password' => 'required|password|min_length:8',
        'password_confirm' => 'required|password_confirm:password',
        'cpf' => 'cpf'
    ];
    
    $result = viabixValidate($data, $rules);
    $form_results = [
        'type' => 'validate',
        'success' => $result['success'],
        'errors' => $result['errors'],
        'data' => $data
    ];
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Validation Framework - Test Interface</title>
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
        
        .badge-success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .sanitization-table {
            font-size: 0.9rem;
        }
        
        .sanitization-table td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .sanitization-table .original {
            color: #d32f2f;
            font-family: monospace;
            word-break: break-all;
        }
        
        .sanitization-table .sanitized {
            color: #388e3c;
            font-family: monospace;
            word-break: break-all;
        }
        
        .test-result {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #ccc;
        }
        
        .test-result.pass {
            background: #f1f8f6;
            border-left-color: #4caf50;
        }
        
        .test-result.fail {
            background: #fff3f0;
            border-left-color: #d32f2f;
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
        
        .code-block {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            border: 1px solid #e0e0e0;
        }
        
        .icon {
            font-size: 1.3rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-shield-alt icon"></i>
                Input Validation Framework
            </h1>
            <p><i class="fas fa-info-circle"></i> Test sanitization, validation rules, and security features</p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo count($sanitization_samples); ?></h3>
                <p>Sanitization Types</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($validation_tests); ?></h3>
                <p>Validation Tests</p>
            </div>
            <div class="stat-box">
                <h3><?php echo array_sum(array_map(fn($t) => $t['passed'] ? 1 : 0, $validation_tests)); ?>/<?php echo count($validation_tests); ?></h3>
                <p>Tests Passed</p>
            </div>
        </div>

        <div class="row mt-4">
            <!-- Sanitization -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-broom icon"></i> Sanitization Testing</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="sanitize">
                            <div class="row">
                                <div class="col-md-8">
                                    <textarea class="form-control" name="value" placeholder="Enter text to sanitize..." style="height: 100px;"><?php echo htmlspecialchars($_POST['value'] ?? '', ENT_QUOTES); ?></textarea>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select mb-3" name="sanitize_type">
                                        <option value="string">String (Default)</option>
                                        <option value="email">Email</option>
                                        <option value="url">URL</option>
                                        <option value="number">Number</option>
                                        <option value="integer">Integer</option>
                                        <option value="html">HTML Safe</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-play"></i> Sanitize
                                    </button>
                                </div>
                            </div>
                        </form>

                        <?php if ($form_results && $form_results['type'] === 'sanitize'): ?>
                        <div class="alert alert-info">
                            <h6><i class="fas fa-flask"></i> Sanitization Result</h6>
                            <table class="sanitization-table w-100 mt-3">
                                <tr>
                                    <td><strong>Original:</strong></td>
                                    <td class="original"><?php echo htmlspecialchars($form_results['original'], ENT_QUOTES); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Type:</strong></td>
                                    <td><code><?php echo $form_results['type']; ?></code></td>
                                </tr>
                                <tr>
                                    <td><strong>Sanitized:</strong></td>
                                    <td class="sanitized"><?php echo htmlspecialchars($form_results['sanitized'], ENT_QUOTES); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>HTML Escaped:</strong></td>
                                    <td><code><?php echo htmlspecialchars($form_results['escaped'], ENT_QUOTES); ?></code></td>
                                </tr>
                            </table>
                        </div>
                        <?php endif; ?>

                        <h6 class="mt-4"><i class="fas fa-shield-alt"></i> Sample Sanitizations</h6>
                        <div class="table-responsive">
                            <table class="table table-sm sanitization-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Original</th>
                                        <th>Sanitized</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanitization_samples as $name => $sample): ?>
                                    <tr>
                                        <td><strong><?php echo $name; ?></strong></td>
                                        <td class="original"><?php echo htmlspecialchars($sample, ENT_QUOTES); ?></td>
                                        <td class="sanitized"><?php echo htmlspecialchars(viabixSanitize($sample, 'string'), ENT_QUOTES); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Validation -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks icon"></i> Validation Testing</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="validate">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                        <input type="email" class="form-control" name="email" placeholder="user@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-id-card"></i> CPF (optional)</label>
                                        <input type="text" class="form-control" name="cpf" placeholder="123.456.789-09" value="<?php echo htmlspecialchars($_POST['cpf'] ?? '', ENT_QUOTES); ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                                        <input type="password" class="form-control" name="password" placeholder="Min 8 chars: 1 uppercase, 1 number">
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> 
                                            Requirements: Minimum 8 characters, 1 uppercase letter, 1 number
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                                        <input type="password" class="form-control" name="password_confirm" placeholder="Repeat password">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-play"></i> Validate Form
                            </button>
                        </form>

                        <?php if ($form_results && $form_results['type'] === 'validate'): ?>
                        <div class="mt-4">
                            <?php if ($form_results['success']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <strong>Validation Successful!</strong>
                                All fields passed validation.
                            </div>
                            <?php else: ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <strong>Validation Failed</strong>
                                Please fix the following errors:
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($form_results['errors'] as $field => $errors): ?>
                                        <?php foreach ($errors as $error): ?>
                                        <li>
                                            <strong><?php echo ucfirst($field); ?>:</strong> 
                                            <?php echo htmlspecialchars($error); ?>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <h6 class="mt-4"><i class="fas fa-flask"></i> Automated Tests</h6>
                        <?php foreach ($validation_tests as $test): ?>
                        <div class="test-result <?php echo $test['passed'] ? 'pass' : 'fail'; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo $test['name']; ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Expected: <?php echo $test['should_pass'] ? '✓ Pass' : '✗ Fail'; ?> |
                                        Got: <?php echo $test['passed'] ? '✓ Pass' : '✗ Fail'; ?>
                                    </small>
                                </div>
                                <span class="badge <?php echo $test['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                    <?php echo $test['passed'] ? '✓' : '✗'; ?>
                                </span>
                            </div>
                            <?php if (!empty($test['errors'])): ?>
                            <small class="text-muted d-block mt-2">
                                Errors: <?php echo implode(', ', array_reduce($test['errors'], 'array_merge', [])); ?>
                            </small>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documentation -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code icon"></i> Usage Examples</h5>
                    </div>
                    <div class="card-body">
                        <h6>Quick Validation</h6>
                        <div class="code-block">
$validator = new ViabixValidator($data);
$validator->rule('email', 'required|email');
$validator->rule('password', 'required|password');

if ($validator->validate()) {
    // Valid
} else {
    echo $validator->errors();
}</div>

                        <h6 class="mt-3">Sanitization</h6>
                        <div class="code-block">
$email = viabixSanitize($_POST['email'], 'email');
$text = viabixSanitize($_POST['text'], 'string');
$phone = viabixSanitize($_POST['phone'], 'number');</div>

                        <h6 class="mt-3">Custom Rules</h6>
                        <div class="code-block">
$validator->rule('age', 'required|integer|min:18|max:120');
$validator->rule('cpf', 'required|cpf');
$validator->rule('terms', 'required'); // checkbox</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-list icon"></i> Available Rules</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><code>required</code> - Field is mandatory</li>
                            <li><code>email</code> - Valid email format</li>
                            <li><code>email_unique</code> - Email not in database</li>
                            <li><code>min_length:N</code> - Minimum N characters</li>
                            <li><code>max_length:N</code> - Maximum N characters</li>
                            <li><code>numeric</code> - Is a number</li>
                            <li><code>integer</code> - Is an integer</li>
                            <li><code>string</code> - Is text</li>
                            <li><code>cpf</code> - Valid CPF</li>
                            <li><code>cnpj</code> - Valid CNPJ</li>
                            <li><code>phone</code> - Valid phone number</li>
                            <li><code>url</code> - Valid URL</li>
                            <li><code>password</code> - Strong password (8+ chars, 1 upper, 1 number)</li>
                            <li><code>password_confirm:field</code> - Match another field</li>
                            <li><code>regex:pattern</code> - Matches regex</li>
                            <li><code>enum:val1,val2</code> - One of specified values</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 mb-3">
            <small class="text-white">
                <i class="fas fa-info-circle"></i>
                Input Validation Framework v1.0 | 
                <a href="/api/" class="text-white">Back to API</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
