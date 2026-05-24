<?php
/**
 * Viabix Swagger/OpenAPI 3.0 Generator
 * 
 * Automatic API documentation generation from routes.php
 * Creates OpenAPI 3.0.0 spec for API documentation, client SDKs, and testing
 * 
 * Usage:
 *   - JSON spec: viabixGetOpenAPISpec()
 *   - YAML export: viabixExportOpenAPIYAML()
 *   - HTML docs: See test_swagger.php
 */

require_once __DIR__ . '/routes.php';

class ViabixOpenAPIGenerator {
    
    private $spec;
    private $host;
    private $basePath;
    private $schemes;
    
    public function __construct() {
        $this->host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->basePath = '/';
        $this->schemes = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? ['https'] : ['http'];
        
        $this->spec = [
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'Viabix SaaS API',
                'description' => 'Production-ready SaaS platform with security, compliance, and multi-tenant support',
                'version' => '1.0.0',
                'contact' => [
                    'name' => 'Viabix Support',
                    'email' => 'support@viabix.com'
                ],
                'license' => [
                    'name' => 'Proprietary'
                ],
                'x-logo' => [
                    'url' => '/assets/viabix-logo.png'
                ]
            ],
            'servers' => [
                [
                    'url' => 'https://' . $this->host,
                    'description' => 'Production Server',
                    'variables' => []
                ],
                [
                    'url' => 'http://localhost',
                    'description' => 'Development Server'
                ]
            ],
            'tags' => $this->generateTags(),
            'paths' => [],
            'components' => $this->generateComponents(),
            'security' => [
                ['sessionCookie' => []],
                ['apiKey' => []]
            ],
            'externalDocs' => [
                'description' => 'Find out more about Viabix',
                'url' => 'https://viabix.com/docs'
            ]
        ];
    }
    
    /**
     * Generate API tags from routes
     */
    private function generateTags() {
        $routes = viabixGetRoutes();
        $tags = [];
        $seen = [];
        
        foreach ($routes as $route) {
            foreach ($route['tags'] as $tag) {
                if (!isset($seen[$tag])) {
                    $seen[$tag] = true;
                    $tags[] = [
                        'name' => $tag,
                        'description' => $this->getTagDescription($tag)
                    ];
                }
            }
        }
        
        usort($tags, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $tags;
    }
    
    /**
     * Get description for tag
     */
    private function getTagDescription($tag) {
        $descriptions = [
            'Authentication' => 'User login, signup, and session management',
            '2FA' => 'Two-factor authentication (TOTP, Email OTP, Backup Codes)',
            'Users' => 'User account management',
            'Audit' => 'Activity logs and audit trails',
            'Security' => 'Security-related operations',
            'Email' => 'Email sending and templates',
            'Billing' => 'Invoices and subscription management',
            'System' => 'System health and diagnostics',
            'Validation' => 'Input validation utilities',
            'Export' => 'Data export and reporting',
            'GDPR' => 'GDPR compliance endpoints',
            'General' => 'General API endpoints'
        ];
        
        return $descriptions[$tag] ?? 'API operations';
    }
    
    /**
     * Generate components (schemas, security schemes, responses)
     */
    private function generateComponents() {
        return [
            'schemas' => [
                'Error' => [
                    'type' => 'object',
                    'properties' => [
                        'success' => ['type' => 'boolean', 'example' => false],
                        'error' => ['type' => 'string'],
                        'code' => ['type' => 'integer'],
                        'details' => ['type' => 'object']
                    ]
                ],
                'User' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'name' => ['type' => 'string'],
                        'email' => ['type' => 'string', 'format' => 'email'],
                        'role' => ['type' => 'string', 'enum' => ['admin', 'user', 'support']],
                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                        'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        'updated_at' => ['type' => 'string', 'format' => 'date-time']
                    ]
                ],
                'AuditLog' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'user_id' => ['type' => 'integer', 'nullable' => true],
                        'category' => ['type' => 'string'],
                        'action' => ['type' => 'string'],
                        'ip_address' => ['type' => 'string'],
                        'user_agent' => ['type' => 'string'],
                        'details' => ['type' => 'object'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time']
                    ]
                ],
                'Invoice' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => ['type' => 'integer'],
                        'user_id' => ['type' => 'integer'],
                        'amount' => ['type' => 'number', 'format' => 'decimal'],
                        'status' => ['type' => 'string', 'enum' => ['pending', 'paid', 'overdue', 'cancelled']],
                        'description' => ['type' => 'string'],
                        'due_date' => ['type' => 'string', 'format' => 'date'],
                        'created_at' => ['type' => 'string', 'format' => 'date-time']
                    ]
                ],
                'SuspiciousActivity' => [
                    'type' => 'object',
                    'properties' => [
                        'user_id' => ['type' => 'integer'],
                        'ip_address' => ['type' => 'string'],
                        'failed_attempts' => ['type' => 'integer'],
                        'last_attempt' => ['type' => 'string', 'format' => 'date-time']
                    ]
                ]
            ],
            'securitySchemes' => [
                'sessionCookie' => [
                    'type' => 'apiKey',
                    'in' => 'cookie',
                    'name' => 'PHPSESSID'
                ],
                'apiKey' => [
                    'type' => 'apiKey',
                    'in' => 'header',
                    'name' => 'X-API-Key'
                ]
            ],
            'responses' => [
                'Unauthorized' => [
                    'description' => 'Authentication required',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Error']
                        ]
                    ]
                ],
                'Forbidden' => [
                    'description' => 'Insufficient permissions',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Error']
                        ]
                    ]
                ],
                'NotFound' => [
                    'description' => 'Resource not found',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Error']
                        ]
                    ]
                ],
                'ValidationError' => [
                    'description' => 'Input validation failed',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Error']
                        ]
                    ]
                ],
                'TooManyRequests' => [
                    'description' => 'Rate limit exceeded',
                    'content' => [
                        'application/json' => [
                            'schema' => ['$ref' => '#/components/schemas/Error']
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Build paths from routes
     */
    public function generatePaths() {
        $routes = viabixGetRoutes();
        $paths = [];
        
        foreach ($routes as $key => $route) {
            if (!isset($paths[$route['path']])) {
                $paths[$route['path']] = [];
            }
            
            $method = strtolower($route['method']);
            
            $operation = [
                'summary' => $route['summary'],
                'description' => $route['description'],
                'tags' => $route['tags'],
                'operationId' => $route['handler'],
                'deprecated' => $route['deprecated']
            ];
            
            // Add parameters
            if (!empty($route['parameters'])) {
                $operation['parameters'] = $route['parameters'];
            }
            
            // Add request body
            if ($route['requestBody']) {
                $operation['requestBody'] = $route['requestBody'];
            }
            
            // Add responses
            $operation['responses'] = $route['responses'];
            
            // Add security
            if (!empty($route['security'])) {
                $operation['security'] = $route['security'];
            }
            
            // Add example
            if ($route['example']) {
                $operation['x-example'] = $route['example'];
            }
            
            $paths[$route['path']][$method] = $operation;
        }
        
        // Sort paths
        ksort($paths);
        
        return $paths;
    }
    
    /**
     * Get complete OpenAPI spec
     */
    public function getSpec() {
        $this->spec['paths'] = $this->generatePaths();
        return $this->spec;
    }
    
    /**
     * Export as JSON
     */
    public function toJSON() {
        return json_encode($this->getSpec(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Export as YAML
     */
    public function toYAML() {
        return $this->arrayToYAML($this->getSpec());
    }
    
    /**
     * Simple YAML converter
     */
    private function arrayToYAML($array, $indent = 0) {
        $yaml = '';
        $spaces = str_repeat(' ', $indent);
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric(array_keys($value)[0] ?? null)) {
                    // Indexed array
                    $yaml .= "$spaces- " . $this->arrayToYAML($value, $indent) . "\n";
                } else {
                    // Associative array
                    $yaml .= "$spaces$key:\n";
                    $yaml .= $this->arrayToYAML($value, $indent + 2);
                }
            } else {
                $yaml .= "$spaces$key: " . (is_string($value) ? "\"$value\"" : var_export($value, true)) . "\n";
            }
        }
        
        return $yaml;
    }
}

/**
 * Get OpenAPI spec as array
 */
function viabixGetOpenAPISpec() {
    $generator = new ViabixOpenAPIGenerator();
    return $generator->getSpec();
}

/**
 * Get OpenAPI spec as JSON
 */
function viabixGetOpenAPIJSON() {
    $generator = new ViabixOpenAPIGenerator();
    return $generator->toJSON();
}

/**
 * Get OpenAPI spec as YAML
 */
function viabixGetOpenAPIYAML() {
    $generator = new ViabixOpenAPIGenerator();
    return $generator->toYAML();
}

/**
 * Get specific route by path and method
 */
function viabixGetRouteOperation($method, $path) {
    $spec = viabixGetOpenAPISpec();
    return $spec['paths'][$path][strtolower($method)] ?? null;
}

/**
 * Get all routes grouped by tag
 */
function viabixGetRoutesByTag($tag) {
    $spec = viabixGetOpenAPISpec();
    $routes = [];
    
    foreach ($spec['paths'] as $path => $methods) {
        foreach ($methods as $method => $operation) {
            if (in_array($tag, $operation['tags'] ?? [])) {
                $routes[$path][$method] = $operation;
            }
        }
    }
    
    return $routes;
}

/**
 * Generate Swagger UI HTML
 */
function viabixGetSwaggerUI() {
    $spec_url = 'swagger.php?format=json';
    
    return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <title>Viabix API Documentation</title>
        <meta charset="utf-8"/>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui.css" >
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@3/swagger-ui.js"></script>
        <script>
            SwaggerUIBundle({
                url: "$spec_url",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "StandaloneLayout",
                deepLinking: true,
                defaultModelsExpandDepth: 1
            });
        </script>
    </body>
    </html>
    HTML;
}

?>
