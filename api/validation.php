<?php
/**
 * Input Validation & Sanitization Framework
 * 
 * Provides centralized validation and sanitization for all user inputs.
 * Prevents: SQL Injection, XSS, Data Type Attacks, Business Logic Violations
 * 
 * Location: api/validation.php
 * Integrated: api/config.php
 */

if (!defined('VIABIX_APP')) {
    die('Direct access not allowed');
}

// Validation error messages
define('VIABIX_VALIDATION_ERRORS', [
    'required' => 'Este campo é obrigatório',
    'email' => 'Por favor, forneça um email válido',
    'email_unique' => 'Este email já está registrado',
    'login_unique' => 'Este login já está em uso',
    'min_length' => 'Mínimo {{min}} caracteres',
    'max_length' => 'Máximo {{max}} caracteres',
    'min' => 'Mínimo {{min}}',
    'max' => 'Máximo {{max}}',
    'regex' => 'Formato inválido',
    'date' => 'Data inválida',
    'numeric' => 'Deve ser um número',
    'integer' => 'Deve ser um número inteiro',
    'string' => 'Deve ser um texto',
    'boolean' => 'Deve ser verdadeiro ou falso',
    'array' => 'Deve ser um array',
    'enum' => 'Valor não permitido: {{values}}',
    'cpf' => 'CPF inválido',
    'cnpj' => 'CNPJ inválido',
    'phone' => 'Telefone inválido',
    'url' => 'URL inválida',
    'password_strength' => 'Senha fraca (mín. 8 caracteres, 1 maiúscula, 1 número)',
    'password_confirm' => 'As senhas não coincidem',
    'custom' => 'Validação falhou'
]);

/**
 * Main validator class
 */
class ViabixValidator {
    private $data = [];
    private $errors = [];
    private $rules = [];
    private $messages = [];
    
    public function __construct($data = []) {
        $this->data = $data;
        $this->messages = VIABIX_VALIDATION_ERRORS;
    }
    
    /**
     * Add validation rule
     * @param string $field - Field name
     * @param string|array $rules - Validation rules
     * @param array $messages - Custom messages
     */
    public function rule($field, $rules, $messages = []) {
        if (is_string($rules)) {
            $rules = array_map('trim', explode('|', $rules));
        }
        
        $this->rules[$field] = $rules;
        if (!empty($messages)) {
            $this->messages = array_merge($this->messages, $messages);
        }
        
        return $this;
    }
    
    /**
     * Validate data
     * @return bool - True if valid
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            foreach ($rules as $rule) {
                $this->validateRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Validate single rule
     */
    private function validateRule($field, $rule) {
        $value = $this->data[$field] ?? null;
        
        // Parse rule with parameters
        if (strpos($rule, ':') !== false) {
            [$rule, $params] = explode(':', $rule, 2);
            $params = array_map('trim', explode(',', $params));
        } else {
            $params = [];
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->addError($field, 'required');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, 'email');
                }
                break;
                
            case 'email_unique':
                if (!empty($value) && !$this->isEmailUnique($value)) {
                    $this->addError($field, 'email_unique');
                }
                break;
                
            case 'login_unique':
                if (!empty($value) && !$this->isLoginUnique($value)) {
                    $this->addError($field, 'login_unique');
                }
                break;
                
            case 'min_length':
            case 'minlength':
                $min = intval($params[0] ?? 0);
                if (!empty($value) && strlen($value) < $min) {
                    $this->addError($field, 'min_length', ['min' => $min]);
                }
                break;
                
            case 'max_length':
            case 'maxlength':
                $max = intval($params[0] ?? 999999);
                if (!empty($value) && strlen($value) > $max) {
                    $this->addError($field, 'max_length', ['max' => $max]);
                }
                break;
                
            case 'min':
                $min = floatval($params[0] ?? 0);
                if (!empty($value) && floatval($value) < $min) {
                    $this->addError($field, 'min', ['min' => $min]);
                }
                break;
                
            case 'max':
                $max = floatval($params[0] ?? 999999);
                if (!empty($value) && floatval($value) > $max) {
                    $this->addError($field, 'max', ['max' => $max]);
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, 'numeric');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !is_int($value) && !ctype_digit((string)$value)) {
                    $this->addError($field, 'integer');
                }
                break;
                
            case 'string':
                if (!empty($value) && !is_string($value)) {
                    $this->addError($field, 'string');
                }
                break;
                
            case 'regex':
                $pattern = $params[0] ?? '';
                if (!empty($value) && !preg_match($pattern, $value)) {
                    $this->addError($field, 'regex');
                }
                break;
                
            case 'date':
                if (!empty($value) && !$this->isValidDate($value)) {
                    $this->addError($field, 'date');
                }
                break;
                
            case 'cpf':
                if (!empty($value) && !$this->isValidCpf($value)) {
                    $this->addError($field, 'cpf');
                }
                break;
                
            case 'cnpj':
                if (!empty($value) && !$this->isValidCnpj($value)) {
                    $this->addError($field, 'cnpj');
                }
                break;
                
            case 'phone':
                if (!empty($value) && !$this->isValidPhone($value)) {
                    $this->addError($field, 'phone');
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    $this->addError($field, 'url');
                }
                break;
                
            case 'password':
                if (!empty($value) && !$this->isStrongPassword($value)) {
                    $this->addError($field, 'password_strength');
                }
                break;
                
            case 'password_confirm':
                $confirm_field = $params[0] ?? 'password_confirm';
                if (!empty($value) && $value !== ($this->data[$confirm_field] ?? null)) {
                    $this->addError($field, 'password_confirm');
                }
                break;
                
            case 'enum':
                $allowed = $params;
                if (!empty($value) && !in_array($value, $allowed)) {
                    $this->addError($field, 'enum', ['values' => implode(', ', $allowed)]);
                }
                break;
        }
    }
    
    /**
     * Check if email is unique
     */
    private function isEmailUnique($email) {
        global $pdo;
        
        if (!isset($pdo) || !viabixHasTable('usuarios')) {
            return true;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetchColumn() === 0;
        } catch (Exception $e) {
            return true;
        }
    }
    
    /**
     * Check if login is unique
     */
    private function isLoginUnique($login) {
        global $pdo;
        
        if (!isset($pdo) || !viabixHasTable('usuarios')) {
            return true;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE login = ?");
            $stmt->execute([$login]);
            return $stmt->fetchColumn() === 0;
        } catch (Exception $e) {
            return true;
        }
    }
    
    /**
     * Validate CPF
     */
    private function isValidCpf($cpf) {
        $cpf = preg_replace('/\D/', '', $cpf);
        
        if (strlen($cpf) != 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }
        
        for ($i = 0; $i < 2; $i++) {
            $sum = 0;
            $multiplier = ($i === 0) ? 10 : 11;
            
            for ($j = 0; $j < $multiplier - 1; $j++) {
                $sum += intval($cpf[$j]) * ($multiplier - $j);
            }
            
            $remainder = $sum % 11;
            $digit = ($remainder < 2) ? 0 : (11 - $remainder);
            
            if (intval($cpf[$multiplier - 1]) != $digit) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate CNPJ
     */
    private function isValidCnpj($cnpj) {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        
        if (strlen($cnpj) != 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        
        for ($i = 0; $i < 2; $i++) {
            $sum = 0;
            $multiplier = ($i === 0) ? 5 : 6;
            $j = 0;
            
            for ($start = 0; $start < $multiplier; $start++) {
                $sum += intval($cnpj[$j]) * ($multiplier + 1 - $start);
                $j++;
            }
            
            for ($start = 0; $start < 8; $start++) {
                $sum += intval($cnpj[$j]) * (9 - $start);
                $j++;
            }
            
            $remainder = $sum % 11;
            $digit = ($remainder < 2) ? 0 : (11 - $remainder);
            
            if (intval($cnpj[$multiplier + 1]) != $digit) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    private function isValidPhone($phone) {
        $phone = preg_replace('/\D/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
    
    /**
     * Validate date
     */
    private function isValidDate($date) {
        $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date);
            if ($d && $d->format($format) === $date) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check password strength
     * Min 8 chars, at least 1 uppercase, 1 number
     */
    private function isStrongPassword($password) {
        if (strlen($password) < 8) {
            return false;
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Add validation error
     */
    private function addError($field, $key, $replace = []) {
        $message = $this->messages[$key] ?? $key;
        
        foreach ($replace as $placeholder => $value) {
            $message = str_replace('{{' . $placeholder . '}}', $value, $message);
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get all errors
     */
    public function errors() {
        return $this->errors;
    }
    
    /**
     * Get errors for specific field
     */
    public function errors_for($field) {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if field has error
     */
    public function has_error($field) {
        return !empty($this->errors[$field]);
    }
    
    /**
     * Get first error message for field
     */
    public function first_error($field) {
        $errors = $this->errors[$field] ?? [];
        return $errors[0] ?? null;
    }
}

/**
 * Sanitize a single value
 */
function viabixSanitize($value, $type = 'string') {
    if ($value === null) {
        return null;
    }
    
    switch ($type) {
        case 'string':
        case 'text':
            return trim(htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8'));
            
        case 'email':
            return filter_var($value, FILTER_SANITIZE_EMAIL);
            
        case 'url':
            return filter_var($value, FILTER_SANITIZE_URL);
            
        case 'number':
        case 'numeric':
            return preg_replace('/[^0-9\.\-]/', '', $value);
            
        case 'integer':
            return intval($value);
            
        case 'float':
            return floatval($value);
            
        case 'boolean':
        case 'bool':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
        case 'json':
            return json_decode($value, true);
            
        case 'html':
            // Escape HTML but preserve formatting
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
        case 'raw':
            // No sanitization (use carefully!)
            return $value;
            
        default:
            return trim(htmlspecialchars(stripslashes($value), ENT_QUOTES, 'UTF-8'));
    }
}

/**
 * Sanitize array of values
 */
function viabixSanitizeArray($data, $types = []) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        $type = $types[$key] ?? 'string';
        $sanitized[$key] = viabixSanitize($value, $type);
    }
    
    return $sanitized;
}

/**
 * Quick validation helper
 */
function viabixValidate($data, $rules) {
    $validator = new ViabixValidator($data);
    
    foreach ($rules as $field => $field_rules) {
        $validator->rule($field, $field_rules);
    }
    
    if ($validator->validate()) {
        return ['success' => true, 'errors' => []];
    } else {
        return ['success' => false, 'errors' => $validator->errors()];
    }
}

/**
 * Escape value for safe output
 */
function viabixEscape($value, $type = 'html') {
    switch ($type) {
        case 'html':
        case 'text':
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
        case 'attr':
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            
        case 'js':
            return json_encode($value);
            
        case 'url':
            return urlencode($value);
            
        case 'csv':
            return '"' . str_replace('"', '""', $value) . '"';
            
        default:
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

?>
