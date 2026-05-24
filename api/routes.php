<?php
/**
 * Viabix Routes Definition
 * Central route registry with OpenAPI metadata
 * 
 * All API endpoints defined here with request/response schemas
 * Used by: swagger.php (OpenAPI generation), validation, routing
 */

// Global routes registry
$VIABIX_ROUTES = [];

/**
 * Register a route with OpenAPI metadata
 */
function viabixRegisterRoute($method, $path, $handler, $metadata = []) {
    global $VIABIX_ROUTES;
    
    $key = strtoupper($method) . ' ' . $path;
    
    $VIABIX_ROUTES[$key] = array_merge([
        'method' => strtoupper($method),
        'path' => $path,
        'handler' => $handler,
        'summary' => 'API Endpoint',
        'description' => '',
        'tags' => ['General'],
        'parameters' => [],
        'requestBody' => null,
        'responses' => [
            '200' => ['description' => 'Success'],
            '400' => ['description' => 'Bad Request'],
            '401' => ['description' => 'Unauthorized'],
            '500' => ['description' => 'Server Error']
        ],
        'security' => [],
        'deprecated' => false,
        'example' => null
    ], $metadata);
    
    return $VIABIX_ROUTES[$key];
}

/**
 * Get all registered routes
 */
function viabixGetRoutes() {
    global $VIABIX_ROUTES;
    return $VIABIX_ROUTES;
}

/**
 * Get route by path and method
 */
function viabixGetRoute($method, $path) {
    global $VIABIX_ROUTES;
    $key = strtoupper($method) . ' ' . $path;
    return $VIABIX_ROUTES[$key] ?? null;
}

// ============================================================================
//  AUTHENTICATION ROUTES
// ============================================================================

viabixRegisterRoute('POST', '/api/login.php', 'login', [
    'summary' => 'User Login',
    'description' => 'Authenticate user with email and password',
    'tags' => ['Authentication'],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['email', 'password'],
                    'properties' => [
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'user@example.com'],
                        'password' => ['type' => 'string', 'minLength' => 8, 'example' => 'SecurePass123!']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => [
            'description' => 'Login successful',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean', 'example' => true],
                            'message' => ['type' => 'string', 'example' => 'Login successful'],
                            'user_id' => ['type' => 'integer', 'example' => 1],
                            'requires_2fa' => ['type' => 'boolean', 'example' => false],
                            'session_token' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ],
        '401' => ['description' => 'Invalid credentials'],
        '429' => ['description' => 'Too many attempts (rate limited)']
    ],
    'example' => [
        'request' => ['email' => 'admin@example.com', 'password' => 'SecurePass123!'],
        'response' => ['success' => true, 'user_id' => 1, 'requires_2fa' => true]
    ]
]);

viabixRegisterRoute('POST', '/api/signup.php', 'signup', [
    'summary' => 'User Registration',
    'description' => 'Create new user account',
    'tags' => ['Authentication'],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['name', 'email', 'password', 'password_confirm'],
                    'properties' => [
                        'name' => ['type' => 'string', 'minLength' => 2, 'example' => 'John Doe'],
                        'email' => ['type' => 'string', 'format' => 'email', 'example' => 'john@example.com'],
                        'password' => ['type' => 'string', 'minLength' => 8, 'example' => 'SecurePass123!'],
                        'password_confirm' => ['type' => 'string', 'minLength' => 8],
                        'cpf' => ['type' => 'string', 'pattern' => '^\d{11}$', 'example' => '12345678901'],
                        'phone' => ['type' => 'string', 'example' => '+5511999887766']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '201' => ['description' => 'User created successfully'],
        '400' => ['description' => 'Validation error or user already exists'],
        '429' => ['description' => 'Too many signup attempts']
    ],
    'example' => [
        'request' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'cpf' => '12345678901'
        ],
        'response' => ['success' => true, 'user_id' => 5, 'message' => 'User created']
    ]
]);

viabixRegisterRoute('POST', '/api/logout.php', 'logout', [
    'summary' => 'User Logout',
    'description' => 'End user session',
    'tags' => ['Authentication'],
    'security' => [['sessionCookie' => []]],
    'responses' => [
        '200' => ['description' => 'Logged out successfully'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

// ============================================================================
//  TWO-FACTOR AUTHENTICATION ROUTES
// ============================================================================

viabixRegisterRoute('POST', '/api/2fa_enable.php', '2fa_enable', [
    'summary' => 'Enable Two-Factor Authentication',
    'description' => 'Setup 2FA for user account (TOTP or Email)',
    'tags' => ['2FA'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['method'],
                    'properties' => [
                        'method' => ['type' => 'string', 'enum' => ['totp', 'email'], 'example' => 'totp']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'Setup initialized - QR code provided for TOTP'],
        '401' => ['description' => 'Not authenticated'],
        '409' => ['description' => '2FA already enabled']
    ]
]);

viabixRegisterRoute('POST', '/api/2fa_verify.php', '2fa_verify', [
    'summary' => 'Verify 2FA Code',
    'description' => 'Verify TOTP or OTP code during login or setup',
    'tags' => ['2FA'],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['code', 'method'],
                    'properties' => [
                        'code' => ['type' => 'string', 'minLength' => 4, 'example' => '123456'],
                        'method' => ['type' => 'string', 'enum' => ['totp', 'email'], 'example' => 'totp']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'Code verified - authentication complete'],
        '400' => ['description' => 'Invalid or expired code'],
        '429' => ['description' => 'Too many verification attempts']
    ]
]);

viabixRegisterRoute('POST', '/api/2fa_disable.php', '2fa_disable', [
    'summary' => 'Disable Two-Factor Authentication',
    'description' => 'Remove 2FA from user account',
    'tags' => ['2FA'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['code'],
                    'properties' => [
                        'code' => ['type' => 'string', 'description' => 'Current 2FA code for confirmation']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => '2FA disabled'],
        '401' => ['description' => 'Not authenticated or invalid code']
    ]
]);

// ============================================================================
//  USER MANAGEMENT ROUTES
// ============================================================================

viabixRegisterRoute('GET', '/api/usuarios.php', 'list_users', [
    'summary' => 'List Users',
    'description' => 'Retrieve list of users (admin only)',
    'tags' => ['Users'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'offset', 'in' => 'query', 'schema' => ['type' => 'integer', 'example' => 0]],
        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'example' => 50]],
        ['name' => 'role', 'in' => 'query', 'schema' => ['type' => 'string', 'example' => 'admin']],
        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'example' => 'active']]
    ],
    'responses' => [
        '200' => ['description' => 'User list returned'],
        '401' => ['description' => 'Not authenticated'],
        '403' => ['description' => 'Insufficient permissions']
    ]
]);

viabixRegisterRoute('POST', '/api/usuarios.php', 'create_user', [
    'summary' => 'Create User',
    'description' => 'Create new user (admin only)',
    'tags' => ['Users'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['name', 'email', 'role'],
                    'properties' => [
                        'name' => ['type' => 'string', 'example' => 'John Admin'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'role' => ['type' => 'string', 'enum' => ['admin', 'user', 'support']],
                        'password' => ['type' => 'string', 'description' => 'Auto-generated if empty']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '201' => ['description' => 'User created successfully'],
        '400' => ['description' => 'Validation error'],
        '403' => ['description' => 'Not an admin']
    ]
]);

viabixRegisterRoute('PUT', '/api/usuarios.php', 'update_user', [
    'summary' => 'Update User',
    'description' => 'Update user profile or settings',
    'tags' => ['Users'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'phone' => ['type' => 'string'],
                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive']]
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'User updated'],
        '400' => ['description' => 'Validation error'],
        '404' => ['description' => 'User not found']
    ]
]);

viabixRegisterRoute('DELETE', '/api/usuarios.php', 'delete_user', [
    'summary' => 'Delete User',
    'description' => 'Delete user account (admin only, soft delete)',
    'tags' => ['Users'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['user_id'],
                    'properties' => [
                        'user_id' => ['type' => 'integer'],
                        'export_data' => ['type' => 'boolean', 'description' => 'Export user data before deletion']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'User deleted'],
        '403' => ['description' => 'Not an admin'],
        '404' => ['description' => 'User not found']
    ]
]);

// ============================================================================
//  AUDIT & LOGGING ROUTES
// ============================================================================

viabixRegisterRoute('GET', '/api/audit_logs.php', 'get_audit', [
    'summary' => 'Retrieve Audit Logs',
    'description' => 'Query audit logs with filtering and pagination',
    'tags' => ['Audit'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'category', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['AUTH', 'SECURITY', 'CRUD_USUARIO', 'API', 'ERROR']]],
        ['name' => 'action', 'in' => 'query', 'schema' => ['type' => 'string', 'example' => 'login']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'ip_address', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'ipv4']],
        ['name' => 'offset', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 0]],
        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 100]]
    ],
    'responses' => [
        '200' => ['description' => 'Audit logs returned'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

viabixRegisterRoute('GET', '/api/audit_suspicious.php', 'get_suspicious', [
    'summary' => 'Get Suspicious Activity',
    'description' => 'Detect and retrieve suspicious patterns (3+ failures per IP in 1 hour)',
    'tags' => ['Audit', 'Security'],
    'security' => [['sessionCookie' => []]],
    'responses' => [
        '200' => ['description' => 'Suspicious activity list'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

// ============================================================================
//  EMAIL ROUTES
// ============================================================================

viabixRegisterRoute('POST', '/api/send_email.php', 'send_email', [
    'summary' => 'Send Email',
    'description' => 'Send email via configured providers (SMTP, SendGrid, Mailgun)',
    'tags' => ['Email'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['to', 'subject', 'html'],
                    'properties' => [
                        'to' => ['type' => 'string', 'format' => 'email'],
                        'subject' => ['type' => 'string'],
                        'html' => ['type' => 'string'],
                        'text' => ['type' => 'string'],
                        'template' => ['type' => 'string', 'enum' => ['welcome', 'password_reset', 'invoice', 'payment_confirmation']],
                        'template_data' => ['type' => 'object']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'Email sent or queued'],
        '400' => ['description' => 'Invalid email or validation error'],
        '500' => ['description' => 'Email provider error']
    ]
]);

// ============================================================================
//  INVOICE/BILLING ROUTES
// ============================================================================

viabixRegisterRoute('GET', '/api/billing_invoices.php', 'list_invoices', [
    'summary' => 'List Invoices',
    'description' => 'Retrieve user invoices with filtering and pagination',
    'tags' => ['Billing'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'status', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['pending', 'paid', 'overdue']]],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'offset', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 0]],
        ['name' => 'limit', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 50]]
    ],
    'responses' => [
        '200' => ['description' => 'Invoices list returned'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

viabixRegisterRoute('POST', '/api/billing_invoices.php', 'create_invoice', [
    'summary' => 'Create Invoice',
    'description' => 'Create new invoice (admin only)',
    'tags' => ['Billing'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['user_id', 'amount', 'description'],
                    'properties' => [
                        'user_id' => ['type' => 'integer'],
                        'amount' => ['type' => 'number', 'format' => 'decimal', 'example' => 99.99],
                        'description' => ['type' => 'string'],
                        'due_date' => ['type' => 'string', 'format' => 'date'],
                        'items' => ['type' => 'array', 'description' => 'Line items']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '201' => ['description' => 'Invoice created'],
        '400' => ['description' => 'Validation error'],
        '403' => ['description' => 'Insufficient permissions']
    ]
]);

// ============================================================================
//  HEALTH & DIAGNOSTIC ROUTES
// ============================================================================

viabixRegisterRoute('GET', '/api/healthcheck.php', 'healthcheck', [
    'summary' => 'Health Check',
    'description' => 'System health status - database, cache, services',
    'tags' => ['System'],
    'responses' => [
        '200' => [
            'description' => 'System healthy',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'status' => ['type' => 'string', 'enum' => ['healthy', 'degraded', 'critical']],
                            'timestamp' => ['type' => 'string', 'format' => 'date-time'],
                            'database' => ['type' => 'string', 'enum' => ['ok', 'error']],
                            'cache' => ['type' => 'string', 'enum' => ['ok', 'unavailable']],
                            'services' => ['type' => 'object']
                        ]
                    ]
                ]
            ]
        ]
    ]
]);

viabixRegisterRoute('GET', '/api/diagnostico.php', 'diagnostics', [
    'summary' => 'System Diagnostics',
    'description' => 'Detailed system diagnostics - PHP version, extensions, configuration',
    'tags' => ['System'],
    'security' => [['sessionCookie' => []]],
    'responses' => [
        '200' => ['description' => 'System diagnostics'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

// ============================================================================
//  VALIDATION HELPER ROUTES
// ============================================================================

viabixRegisterRoute('POST', '/api/validate.php', 'validate', [
    'summary' => 'Validate Data',
    'description' => 'Validate form/API input data against rules',
    'tags' => ['Validation'],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['data', 'rules'],
                    'properties' => [
                        'data' => ['type' => 'object', 'description' => 'Data to validate'],
                        'rules' => ['type' => 'object', 'description' => 'Validation rules']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '200' => ['description' => 'Validation result'],
        '400' => ['description' => 'Validation failed']
    ]
]);

// ============================================================================
//  EXPORT & REPORTING ROUTES
// ============================================================================

viabixRegisterRoute('GET', '/api/export_data.php', 'export_data', [
    'summary' => 'Export User Data',
    'description' => 'Export all user data (GDPR compliance)',
    'tags' => ['Export', 'GDPR'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'format', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['json', 'csv', 'pdf']]]
    ],
    'responses' => [
        '200' => ['description' => 'Data exported'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

viabixRegisterRoute('GET', '/api/export_audit.php', 'export_audit', [
    'summary' => 'Export Audit Logs',
    'description' => 'Export audit logs as CSV or JSON',
    'tags' => ['Export', 'Audit'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'format', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['csv', 'json']]],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']]
    ],
    'responses' => [
        '200' => ['description' => 'Audit logs exported'],
        '401' => ['description' => 'Not authenticated']
    ]
]);

?>
