# Input Validation Framework Documentation

## Overview

The Input Validation Framework provides a centralized, secure system for validating and sanitizing all user inputs in the Viabix SaaS application.

**Key Features:**
- ✅ XSS Prevention (HTML/script injection protection)
- ✅ SQL Injection Prevention (prepared statements + validation)
- ✅ Data Type Validation (email, number, date, CPF, CNPJ, phone)
- ✅ Business Logic Validation (min/max length, uniqueness, password strength)
- ✅ Flexible Rule System (pipe-separated rules, custom messages)
- ✅ Multi-language Support (PT-BR defaults, easily customizable)
- ✅ Performance Optimized (minimal overhead, early termination)

**Location:** `api/validation.php`

**Integration:** Auto-loaded via `api/config.php`

---

## 1. Core Classes

### ViabixValidator

The main validation engine for multi-field validation with custom rules and messages.

**Constructor:**
```php
$validator = new ViabixValidator($data = []);
```

**Example:**
```php
$data = [
    'email' => 'user@example.com',
    'password' => 'SecurePass123',
    'cpf' => '123.456.789-09'
];

$validator = new ViabixValidator($data);
```

---

## 2. Validation Rules

### Basic Rules

#### required
Field is mandatory (not empty).

```php
$validator->rule('email', 'required');
$validator->rule('name', 'required');
```

**Error Message:** "Este campo é obrigatório"

---

#### email
Valid email format using PHP's FILTER_VALIDATE_EMAIL.

```php
$validator->rule('email', 'required|email');
```

**Error Message:** "Por favor, forneça um email válido"

**Examples:**
- ✅ user@example.com
- ✅ john.doe+tag@example.co.uk
- ❌ invalid@
- ❌ @example.com

---

#### email_unique
Email must not exist in `usuarios` table.

```php
$validator->rule('email', 'required|email|email_unique');
```

**Error Message:** "Este email já está registrado"

**Database Check:**
```sql
SELECT COUNT(*) FROM usuarios WHERE email = ?
```

---

#### login_unique
Login must not exist in `usuarios` table.

```php
$validator->rule('login', 'required|login_unique');
```

**Error Message:** "Este login já está em uso"

---

### Length Rules

#### min_length:N
Minimum N characters (uses strlen).

```php
$validator->rule('password', 'required|min_length:8');
$validator->rule('name', 'required|min_length:3');
```

**Error Message:** "Mínimo {{min}} caracteres"

**Note:** Only validates if field is not empty.

---

#### max_length:N
Maximum N characters (uses strlen).

```php
$validator->rule('description', 'required|max_length:500');
$validator->rule('phone', 'required|max_length:15');
```

**Error Message:** "Máximo {{max}} caracteres"

---

### Numeric Rules

#### numeric
Value must be numeric (integer or float).

```php
$validator->rule('age', 'required|numeric');
$validator->rule('price', 'required|numeric');
```

**Error Message:** "Deve ser um número"

**Valid Examples:**
- 123
- 123.45
- -45.67

**Invalid Examples:**
- "abc"
- "123abc"

---

#### integer
Value must be an integer (no decimals).

```php
$validator->rule('count', 'required|integer');
$validator->rule('quantity', 'required|integer');
```

**Error Message:** "Deve ser um número inteiro"

---

#### min:N
Value must be >= N (for numbers).

```php
$validator->rule('age', 'required|integer|min:18');
$validator->rule('price', 'required|numeric|min:0.01');
```

**Error Message:** "Mínimo {{min}}"

---

#### max:N
Value must be <= N (for numbers).

```php
$validator->rule('age', 'required|integer|max:120');
$validator->rule('discount', 'required|numeric|max:100');
```

**Error Message:** "Máximo {{max}}"

---

### Type Rules

#### string
Value must be text (not array/object).

```php
$validator->rule('name', 'required|string');
```

**Error Message:** "Deve ser um texto"

---

#### array
Value must be an array.

```php
$validator->rule('items', 'required|array');
```

**Error Message:** "Deve ser um array"

---

#### boolean
Value must be true/false or equivalent.

```php
$validator->rule('agree_terms', 'required|boolean');
```

**Error Message:** "Deve ser verdadeiro ou falso"

---

### Brazilian Document Rules

#### cpf
Valid CPF (Cadastro de Pessoa Física).

```php
$validator->rule('cpf', 'required|cpf');
```

**Error Message:** "CPF inválido"

**Algorithm:** Validates check digits using official algorithm.

**Formats Accepted:**
- 123.456.789-09
- 12345678909
- 123 456 789-09

**Example:**
```php
$validator->rule('taxpayer_id', 'cpf');
```

---

#### cnpj
Valid CNPJ (Cadastro Nacional da Pessoa Jurídica).

```php
$validator->rule('cnpj', 'required|cnpj');
```

**Error Message:** "CNPJ inválido"

**Algorithm:** Validates check digits using official algorithm.

**Formats Accepted:**
- 11.222.333/0001-81
- 11222333000181
- 11 222 333/0001-81

---

### Communication Rules

#### phone
Valid phone number (10-11 digits).

```php
$validator->rule('phone', 'required|phone');
$validator->rule('mobile', 'required|phone');
```

**Error Message:** "Telefone inválido"

**Formats Accepted:**
- (11) 99999-8888
- (11) 3333-4444
- 11999998888
- 1133334444

**Validation:** 10-11 digits (after removing non-numeric)

---

#### url
Valid URL format.

```php
$validator->rule('website', 'required|url');
$validator->rule('callback_url', 'required|url');
```

**Error Message:** "URL inválida"

**Examples:**
- ✅ https://example.com
- ✅ http://sub.example.co.uk/path?query=1
- ❌ not a url
- ❌ htp://example.com

---

### Security Rules

#### password
Strong password (8+ chars, 1 uppercase, 1 number).

```php
$validator->rule('password', 'required|password');
```

**Error Message:** "Senha fraca (mín. 8 caracteres, 1 maiúscula, 1 número)"

**Requirements:**
- Minimum 8 characters
- At least 1 uppercase letter (A-Z)
- At least 1 number (0-9)

**Examples:**
- ✅ SecurePass123
- ✅ MyPassword1
- ❌ weakpass (no uppercase, no number)
- ❌ SevenCh1 (only 8 chars, acceptable but verify format)

---

#### password_confirm:field
Password confirmation (must match another field).

```php
$validator->rule('password', 'required|password');
$validator->rule('password_confirm', 'required|password_confirm:password');
```

**Error Message:** "As senhas não coincidem"

**Usage:**
```php
$data = [
    'password' => 'SecurePass123',
    'password_confirm' => 'SecurePass123'  // Must match
];

$validator = new ViabixValidator($data);
$validator->rule('password_confirm', 'required|password_confirm:password');
```

---

### Pattern Matching

#### regex:pattern
Match custom regex pattern.

```php
$validator->rule('code', 'required|regex:/^[A-Z]{3}\d{3}$/');
```

**Error Message:** "Formato inválido"

**Pattern Format:** Full regex with delimiters (e.g., `/pattern/i`)

**Examples:**
```php
// Alphanumeric only
$validator->rule('username', 'required|regex:/^[a-zA-Z0-9_]+$/');

// Hex color code
$validator->rule('color', 'required|regex:/^#[0-9A-F]{6}$/i');

// Custom format
$validator->rule('reference', 'required|regex:/^REF-\d{6}$/');
```

---

### Enum Validation

#### enum:val1,val2,...
Value must be one of specified values.

```php
$validator->rule('status', 'required|enum:active,inactive,pending');
$validator->rule('plan', 'required|enum:free,starter,pro,enterprise');
```

**Error Message:** "Valor não permitido: {{values}}"

**Usage:**
```php
$data = ['role' => 'admin'];
$validator = new ViabixValidator($data);
$validator->rule('role', 'enum:admin,user,guest');  // Valid

$data = ['role' => 'superuser'];
// Result: "Valor não permitido: admin, user, guest"
```

---

### Date Rules

#### date
Valid date format (Y-m-d, d/m/Y, d-m-Y, Y/m/d).

```php
$validator->rule('birth_date', 'required|date');
$validator->rule('contract_date', 'required|date');
```

**Error Message:** "Data inválida"

**Accepted Formats:**
- 2025-12-31
- 31/12/2025
- 31-12-2025
- 2025/12/31

**Validation:** Uses DateTime::createFromFormat for strict validation.

---

## 3. Usage Patterns

### Basic Form Validation

**HTML Form:**
```html
<form method="POST">
    <input type="text" name="email">
    <input type="password" name="password">
    <input type="password" name="password_confirm">
    <button type="submit">Register</button>
</form>
```

**PHP Backend:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ];
    
    $validator = new ViabixValidator($data);
    $validator->rule('email', 'required|email|email_unique');
    $validator->rule('password', 'required|password');
    $validator->rule('password_confirm', 'required|password_confirm:password');
    
    if ($validator->validate()) {
        // Valid - proceed with registration
        $email = viabixSanitize($data['email'], 'email');
        $password = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        // Save to database...
    } else {
        // Invalid - show errors
        $errors = $validator->errors();
        // Display $errors to user
    }
}
```

---

### API JSON Validation

**Request:**
```json
{
    "email": "user@example.com",
    "cpf": "123.456.789-09",
    "phone": "(11) 99999-8888",
    "plan": "pro"
}
```

**PHP Backend:**
```php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$validator = new ViabixValidator($data);
$validator->rule('email', 'required|email');
$validator->rule('cpf', 'required|cpf');
$validator->rule('phone', 'required|phone');
$validator->rule('plan', 'required|enum:free,starter,pro,enterprise');

if ($validator->validate()) {
    echo json_encode(['success' => true, 'message' => 'Valid data']);
} else {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $validator->errors()]);
}
```

---

### Quick Validation Helper

For fast validation without needing to instantiate ViabixValidator:

```php
$result = viabixValidate([
    'email' => 'user@example.com',
    'password' => 'SecurePass123'
], [
    'email' => 'required|email',
    'password' => 'required|password'
]);

if ($result['success']) {
    // Valid
} else {
    // Show errors: $result['errors']
}
```

---

## 4. Sanitization

### Overview

Sanitization removes or escapes potentially dangerous input to prevent injection attacks. Applied BEFORE validation for defense-in-depth.

### Functions

#### viabixSanitize($value, $type = 'string')

Sanitize a single value based on type.

**Syntax:**
```php
$sanitized = viabixSanitize($value, $type);
```

**Types:**

| Type | Function | Example |
|------|----------|---------|
| `string` | HTML escape, trim | `<div>Test</div>` → `&lt;div&gt;Test&lt;/div&gt;` |
| `email` | Filter email format | `user@example.com` (unchanged if valid) |
| `url` | Filter URL format | `https://example.com?q=1` |
| `number` | Remove non-numeric | `$1,234.56` → `1234.56` |
| `integer` | Cast to int | `"123abc"` → `123` |
| `float` | Cast to float | `"123.45"` → `123.45` |
| `boolean` | Cast to bool | `"true"` → `true`, `"0"` → `false` |
| `json` | Decode JSON | `'{"key":"value"}'` → PHP array |
| `html` | HTML escape | `<script>` → `&lt;script&gt;` |
| `raw` | No sanitization | `"<b>test</b>"` → `"<b>test</b>"` (use carefully!) |

---

#### viabixSanitizeArray($data, $types = [])

Sanitize multiple values with specific types.

**Syntax:**
```php
$sanitized = viabixSanitizeArray($data, $types);
```

**Example:**
```php
$data = [
    'name' => '<script>alert(1)</script>John',
    'email' => 'user@example.com',
    'age' => '25abc',
    'website' => 'https://example.com'
];

$types = [
    'name' => 'string',
    'email' => 'email',
    'age' => 'integer',
    'website' => 'url'
];

$clean = viabixSanitizeArray($data, $types);

// Result:
// [
//     'name' => '&lt;script&gt;alert(1)&lt;/script&gt;John',
//     'email' => 'user@example.com',
//     'age' => 25,
//     'website' => 'https://example.com'
// ]
```

---

### Escaping for Output

#### viabixEscape($value, $type = 'html')

Escape value for safe display in different contexts.

**Types:**

| Type | Use Case | Example |
|------|----------|---------|
| `html` | HTML content | `<script>` → `&lt;script&gt;` |
| `attr` | HTML attributes | Same as HTML |
| `js` | JavaScript strings | `"string"` → `"\"string\""` |
| `url` | URL parameters | `hello world` → `hello%20world` |
| `csv` | CSV export | `test"value` → `"test""value"` |

**Example:**
```php
<?php
$user_input = '<script>alert("xss")</script>';

// Safe for HTML output
echo viabixEscape($user_input, 'html');
// Output: &lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;

// Safe for JavaScript
echo "var name = " . viabixEscape($user_input, 'js') . ";";
// Output: var name = "<script>alert(\"xss\")</script>";

// Safe for URL
echo "<a href='?search=" . viabixEscape($user_input, 'url') . "'>Link</a>";
?>
```

---

## 5. Error Handling

### Getting Errors

#### errors()
Get all validation errors as associative array.

```php
$validator->rule('email', 'required|email');
$validator->rule('password', 'required|password');

if (!$validator->validate()) {
    $errors = $validator->errors();
    // $errors = [
    //     'email' => ['Por favor, forneça um email válido'],
    //     'password' => ['Senha fraca (mín. 8 caracteres, 1 maiúscula, 1 número)']
    // ]
}
```

---

#### errors_for($field)
Get errors for specific field as array.

```php
$email_errors = $validator->errors_for('email');
// ['Por favor, forneça um email válido']
```

---

#### first_error($field)
Get first error for specific field.

```php
$first = $validator->first_error('email');
// 'Por favor, forneça um email válido'
```

---

#### has_error($field)
Check if field has any errors.

```php
if ($validator->has_error('email')) {
    echo 'Email has errors';
}
```

---

### Custom Error Messages

Override default messages during validation:

```php
$validator = new ViabixValidator($data);

$custom_messages = [
    'required' => 'Campo obrigatório!',
    'email' => 'Email inválido!',
    'password' => 'Senha deve ter 8+ caracteres, maiúscula e número'
];

$validator->rule('email', 'required|email', $custom_messages);
$validator->rule('password', 'required|password', $custom_messages);

$validator->validate();
```

---

## 6. Frontend Integration

### HTML5 Validation + PHP Backend

**HTML** (client-side validation):
```html
<form method="POST">
    <input type="email" name="email" required>
    <input type="password" name="password" required minlength="8">
    <input type="password" name="password_confirm" required>
    <button type="submit">Register</button>
</form>
```

**PHP** (server-side validation - always required!):
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Always validate server-side, even if HTML5 validation passed
    $validator = new ViabixValidator($_POST);
    $validator->rule('email', 'required|email');
    $validator->rule('password', 'required|password');
    $validator->rule('password_confirm', 'required|password_confirm:password');
    
    if ($validator->validate()) {
        // Process valid data
    } else {
        // Display errors using:
        $errors = $validator->errors();
    }
}
```

---

### AJAX Form Submission

**JavaScript (client-side):**
```javascript
document.getElementById('registerForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    const response = await fetch('/api/signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
        alert('Registration successful!');
        window.location.href = '/dashboard';
    } else {
        // Display validation errors
        result.errors.forEach((field, errors) => {
            console.log(`${field}: ${errors.join(', ')}`);
        });
    }
});
```

**PHP (server-side):**
```php
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?? [];

$validator = new ViabixValidator($data);
$validator->rule('email', 'required|email|email_unique');
$validator->rule('password', 'required|password');
$validator->rule('password_confirm', 'required|password_confirm:password');

if ($validator->validate()) {
    // Process registration...
    echo json_encode(['success' => true]);
} else {
    http_response_code(422);
    echo json_encode(['success' => false, 'errors' => $validator->errors()]);
}
```

---

## 7. Integration with Logging

Validation errors are automatically logged to Sentry:

```php
if (!$validator->validate()) {
    // Automatically captured by Sentry
    viabixLogError('Validation failed', $validator->errors());
}
```

---

## 8. Performance Considerations

### Optimization Tips

1. **Group rules** - Combine multiple rules for same field
   ```php
   // Good
   $validator->rule('email', 'required|email|email_unique');
   
   // Avoid
   $validator->rule('email', 'required');
   $validator->rule('email', 'email');
   $validator->rule('email', 'email_unique');
   ```

2. **Order rules** - Put quick checks first
   ```php
   // Good (quick checks first)
   $validator->rule('cpf', 'required|string|regex:/^\d{11}$|cpf');
   
   // Avoid (expensive check first)
   $validator->rule('cpf', 'cpf|required');
   ```

3. **Lazy validation** - Optional fields only validated if provided
   ```php
   // phone is optional -> only validated if provided
   $validator->rule('phone', 'phone');  // Won't error if empty
   
   // name is required -> always validated
   $validator->rule('name', 'required|string');
   ```

4. **Batch validation** - Use viabixValidate for quick checks
   ```php
   $result = viabixValidate($data, $rules);  // Single call, cleaner code
   ```

---

## 9. Common Patterns

### User Registration

```php
$validator = new ViabixValidator($_POST);

$validator
    ->rule('email', 'required|email|email_unique')
    ->rule('name', 'required|string|min_length:3|max_length:100')
    ->rule('password', 'required|password|min_length:8')
    ->rule('password_confirm', 'required|password_confirm:password')
    ->rule('terms', 'required')  // checkbox
    ->rule('cpf', 'cpf');  // optional but validate if provided

if ($validator->validate()) {
    // Safe to use data
    $user = [
        'email' => viabixSanitize($_POST['email'], 'email'),
        'name' => viabixSanitize($_POST['name'], 'string'),
        'password' => password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        'cpf' => viabixSanitize($_POST['cpf'] ?? '', 'string')
    ];
    // Save user...
} else {
    return json_encode(['success' => false, 'errors' => $validator->errors()]);
}
```

---

### API Request Validation

```php
$data = json_decode(file_get_contents('php://input'), true) ?? [];

$rules = [
    'plan' => 'required|enum:free,starter,pro,enterprise',
    'quantity' => 'required|integer|min:1|max:1000',
    'coupon_code' => 'regex:/^[A-Z0-9]{10}$/',  // optional pattern
];

$result = viabixValidate($data, $rules);

if (!$result['success']) {
    http_response_code(422);
    echo json_encode(['errors' => $result['errors']]);
    exit;
}

// Process order...
```

---

### Data Upload

```php
$file_data = [
    'invoice_date' => $_POST['invoice_date'] ?? '',
    'amount' => $_POST['amount'] ?? '',
    'supplier_cnpj' => $_POST['supplier_cnpj'] ?? '',
];

$validator = new ViabixValidator($file_data);
$validator
    ->rule('invoice_date', 'required|date')
    ->rule('amount', 'required|numeric|min:0.01')
    ->rule('supplier_cnpj', 'required|cnpj');

if ($validator->validate()) {
    // Process validated data...
} else {
    // Show validation errors
}
```

---

## 10. Security Best Practices

### Always Validate on Server

> **Critical:** HTML5 and JavaScript validation is for UX only. Always validate on server!

```php
// ❌ BAD - Only trusting browser
if (typeof email === 'string') {
    // Process...
}

// ✅ GOOD - Server-side validation always
$validator = new ViabixValidator($_POST);
$validator->rule('email', 'required|email');
if ($validator->validate()) {
    // Process...
}
```

---

### Sanitize Then Validate

```php
// ✅ GOOD Order
$dirty = $_POST['data'] ?? '';
$clean = viabixSanitize($dirty, 'string');  // Sanitize first
$validator = new ViabixValidator(['field' => $clean]);
$validator->rule('field', 'required|min_length:3');  // Then validate

// Store clean data
$safe_data = $clean;
```

---

### Never Trust User Input

```php
// ❌ BAD
$name = $_POST['name'];
echo "Hello $name!";

// ✅ GOOD
$name = viabixSanitize($_POST['name'] ?? '', 'string');
$safe_output = viabixEscape($name, 'html');
echo "Hello $safe_output!";
```

---

### Whitelist, Don't Blacklist

```php
// ❌ BAD - Trying to block bad patterns
if (strpos($_POST['email'], 'script') === false) {
    // Accept...
}

// ✅ GOOD - Accept only known good pattern
$validator->rule('role', 'enum:admin,user,guest');
```

---

## 11. Testing

### Test Interface

Access the interactive test interface:
```
http://localhost/api/test_validation.php
```

**Features:**
- Real-time sanitization testing
- Interactive validation form
- Automated test suite (10+ tests)
- Error message display
- Usage examples

---

### Manual Testing

```php
// Test sanitization
$xss = '<script>alert("xss")</script>';
echo viabixSanitize($xss, 'string');  // Safe

// Test validation
$result = viabixValidate(['email' => 'invalid'], ['email' => 'required|email']);
// $result['success'] === false
// $result['errors']['email'] contains error message

// Test CPF
$valid_cpf = '123.456.789-09';
$result = viabixValidate(['cpf' => $valid_cpf], ['cpf' => 'cpf']);
// $result['success'] === true
```

---

## 12. Troubleshooting

### Validation Always Fails

**Issue:** Rule is too strict or data doesn't match expected format.

**Solution:**
1. Check data format (e.g., phone must have 10-11 digits)
2. Sanitize first, then validate
3. Use `first_error()` to see actual error message

```php
if (!$validator->validate()) {
    echo $validator->first_error('field');  // See actual error
}
```

---

### Email Uniqueness Check Fails

**Issue:** Database table doesn't exist or email logic reversed.

**Solution:**
1. Ensure `usuarios` table exists
2. Check that email_unique checks correctly
3. Verify PDO connection is available

```php
// Manually check
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
    $stmt->execute(['test@example.com']);
    $count = $stmt->fetchColumn();
    echo $count > 0 ? 'Exists' : 'Not exists';
} catch (Exception $e) {
    echo 'Database error: ' . $e->getMessage();
}
```

---

### Custom Messages Not Showing

**Issue:** Messages passed to wrong function or not merged correctly.

**Solution:**
```php
// Correct
$messages = ['required' => 'Custom message'];
$validator->rule('email', 'required|email', $messages);

// Won't work
$validator = new ViabixValidator($data);
// Then create custom messages (too late)
```

---

## 13. Migrating from Old Validation

If you have legacy validation code, migrate gradually:

**Before:**
```php
if (empty($_POST['email'])) {
    $errors['email'] = 'Email required';
}
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email';
}
```

**After:**
```php
$validator = new ViabixValidator($_POST);
$validator->rule('email', 'required|email');
$errors = $validator->validate() ? [] : $validator->errors();
```

---

## 14. Advanced Topics

### Custom Validators

Extend ViabixValidator for custom business logic:

```php
class CustomValidator extends ViabixValidator {
    public function validate() {
        parent::validate();
        
        // Custom validation
        if ($this->data['industry'] === 'finance' && empty($this->data['license'])) {
            $this->errors['license'][] = 'License required for finance industry';
        }
        
        return empty($this->errors);
    }
}

$validator = new CustomValidator($data);
$validator->rule('industry', 'required');
$validator->validate();
```

---

### Async Validation (API)

Validate in real-time as user types:

```javascript
// debounce function
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func(...args), wait);
    };
}

// Check email uniqueness
const checkEmail = debounce(async (email) => {
    const response = await fetch('/api/check_email.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email })
    });
    const result = await response.json();
    
    if (!result.available) {
        document.getElementById('email-error').textContent = 'Email already registered';
    }
}, 500);

document.getElementById('email').addEventListener('blur', (e) => {
    checkEmail(e.target.value);
});
```

**PHP Endpoint:** `api/check_email.php`
```php
$data = json_decode(file_get_contents('php://input'), true);
$validator = new ViabixValidator($data);
$validator->rule('email', 'email|email_unique');

echo json_encode([
    'available' => $validator->validate(),
    'email' => $data['email']
]);
```

---

## Summary

The Input Validation Framework provides:

✅ **Security:** XSS prevention, SQL injection prevention, strong password enforcement  
✅ **Flexibility:** 20+ built-in rules, custom rules support, pipe-separated syntax  
✅ **Usability:** Simple API, helpful error messages in Portuguese, sanitization helpers  
✅ **Performance:** Lazy validation, early termination, no external dependencies  
✅ **Testing:** Interactive test interface, easy manual testing  

**Start using it now:**

```php
$validator = new ViabixValidator($_POST);
$validator->rule('email', 'required|email');
if ($validator->validate()) {
    // Safely process data
}
```

---

**Version:** 1.0  
**Last Updated:** 2026-04-09  
**Integration:** `api/config.php`  
**Test Interface:** `api/test_validation.php`
