# Swagger/OpenAPI 3.0 Documentation

## Overview

Complete API documentation system using OpenAPI 3.0 specification. Automatically generated from route definitions with interactive Swagger UI, code examples, and API explorer.

**Key Features:**
- ✅ OpenAPI 3.0.0 compliant specification
- ✅ Interactive Swagger UI with try-it-out capabilities
- ✅ Auto-generated from `routes.php` (single source of truth)
- ✅ Code examples (cURL, JavaScript, PHP)
- ✅ Advanced filtering and search
- ✅ Export to JSON and YAML formats
- ✅ Client SDK generation ready

**Location:** 
- Routes definition: `api/routes.php` (1,000+ lines)
- Spec generator: `api/swagger.php` (500+ lines)
- Interactive interface: `api/test_swagger.php` (1,000+ lines)
- Generated specs: Available dynamically

**Integration:** Auto-loaded via `api/config.php`

---

## 1. Quick Start

### 1.1 Access the Documentation

```
http://localhost/api/test_swagger.php
```

**Features:**
- Live API documentation
- Endpoint filtering by category or HTTP method
- Detailed request/response examples
- Code snippets in multiple languages
- Download OpenAPI spec

### 1.2 Get OpenAPI Spec

**JSON Format:**
```
http://localhost/api/test_swagger.php?format=json
```

**YAML Format:**
```
http://localhost/api/test_swagger.php?format=yaml
```

### 1.3 Query Specific Routes

Filter by category:
```
http://localhost/api/test_swagger.php?tag=Authentication
```

Filter by method:
```
http://localhost/api/test_swagger.php?method=POST
```

---

## 2. Architecture

### 2.1 Component Overview

```
┌─────────────────────────────────────────┐
│         routes.php                      │
│   Define ALL API routes with metadata   │
│   (25+ endpoints registered)            │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│         swagger.php                     │
│   Generate OpenAPI 3.0 spec from routes │
│   (ViabixOpenAPIGenerator class)        │
└────────────┬────────────────────────────┘
             │
             ├─────────────► JSON output
             ├─────────────► YAML output
             │
             ▼
┌─────────────────────────────────────────┐
│      test_swagger.php                   │
│   Interactive documentation interface   │
│   (Bootstrap 5.3, Swagger UI)           │
└─────────────────────────────────────────┘
```

### 2.2 Single Source of Truth

All API documentation is maintained in **`routes.php`**:

```php
viabixRegisterRoute('POST', '/api/login.php', 'login', [
    'summary' => 'User Login',
    'description' => 'Authenticate user with email and password',
    'tags' => ['Authentication'],
    'requestBody' => [...],
    'responses' => [...],
    'example' => [...]
]);
```

The specification is **auto-generated** from these definitions. No manual spec updates needed!

---

## 3. Routes Definition (routes.php)

### 3.1 Register New Route

```php
viabixRegisterRoute(
    'POST',                    // HTTP method
    '/api/endpoint.php',       // Path
    'handler_name',            // Handler identifier
    [                          // Metadata
        'summary' => 'Short description',
        'description' => 'Longer description',
        'tags' => ['Category'],
        'parameters' => [...],
        'requestBody' => [...],
        'responses' => [...]
    ]
);
```

### 3.2 Complete Example

```php
viabixRegisterRoute('POST', '/api/invoices.php', 'create_invoice', [
    'summary' => 'Create Invoice',
    'description' => 'Create new invoice with line items',
    'tags' => ['Billing'],
    'security' => [['sessionCookie' => []]],
    'requestBody' => [
        'required' => true,
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['user_id', 'amount'],
                    'properties' => [
                        'user_id' => ['type' => 'integer'],
                        'amount' => ['type' => 'number', 'format' => 'decimal'],
                        'description' => ['type' => 'string'],
                        'due_date' => ['type' => 'string', 'format' => 'date']
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '201' => ['description' => 'Invoice created successfully'],
        '400' => ['description' => 'Validation error'],
        '403' => ['description' => 'Not authorized']
    ],
    'example' => [
        'request' => [
            'user_id' => 123,
            'amount' => 199.99,
            'description' => 'Monthly subscription',
            'due_date' => '2026-05-09'
        ],
        'response' => [
            'success' => true,
            'invoice_id' => 456,
            'status' => 'pending'
        ]
    ]
]);
```

### 3.3 Available Route Properties

| Property | Required | Type | Purpose |
|----------|----------|------|---------|
| `method` | Yes | string | HTTP method (GET, POST, PUT, DELETE, PATCH) |
| `path` | Yes | string | API endpoint path |
| `handler` | Yes | string | Handler identifier for routing |
| `summary` | No | string | Short description (50 chars) |
| `description` | No | string | Detailed description |
| `tags` | No | array | Categorization for grouping |
| `parameters` | No | array | Query/path parameters |
| `requestBody` | No | object | Request body schema |
| `responses` | No | object | Response schemas by status code |
| `security` | No | array | Security requirements |
| `deprecated` | No | bool | Mark endpoint as deprecated |
| `example` | No | object | Request/response examples |

---

## 4. OpenAPI Spec Generator (swagger.php)

### 4.1 Main Class: ViabixOpenAPIGenerator

```php
$generator = new ViabixOpenAPIGenerator();
$spec = $generator->getSpec();        // Array
$json = $generator->toJSON();         // JSON string
$yaml = $generator->toYAML();         // YAML string
```

### 4.2 Helper Functions

**Get OpenAPI spec:**
```php
$spec = viabixGetOpenAPISpec();      // Array
```

**Get as JSON:**
```php
$json = viabixGetOpenAPIJSON();      // Download/output
```

**Get as YAML:**
```php
$yaml = viabixGetOpenAPIYAML();      // Download/output
```

**Get specific route:**
```php
$operation = viabixGetRouteOperation('POST', '/api/login.php');
```

**Get routes by tag:**
```php
$auth_routes = viabixGetRoutesByTag('Authentication');
```

**Get Swagger UI HTML:**
```php
$html = viabixGetSwaggerUI();
```

### 4.3 Spec Structure

OpenAPI 3.0.0 document includes:

```json
{
  "openapi": "3.0.0",
  "info": {
    "title": "Viabix SaaS API",
    "version": "1.0.0",
    "contact": {...},
    "license": {...}
  },
  "servers": [
    {
      "url": "https://viabix.com",
      "description": "Production"
    }
  ],
  "tags": [
    {
      "name": "Authentication",
      "description": "..."
    }
  ],
  "paths": {
    "/api/login.php": {
      "post": {
        "summary": "User Login",
        "requestBody": {...},
        "responses": {...}
      }
    }
  },
  "components": {
    "schemas": {...},
    "securitySchemes": {...},
    "responses": {...}
  }
}
```

---

## 5. Generated Components

### 5.1 Schemas

Reusable data structures defined in components:

```
- Error: Standard error response
- User: User account object
- AuditLog: Audit entry object
- Invoice: Invoice object
- SuspiciousActivity: Threat detection object
```

**Usage in routes:**
```php
'responses' => [
    '400' => [
        'description' => 'Validation error',
        'content' => [
            'application/json' => [
                'schema' => ['$ref' => '#/components/schemas/Error']
            ]
        ]
    ]
]
```

### 5.2 Security Schemes

Two authentication methods supported:

**Session Cookie:**
```php
'security' => [['sessionCookie' => []]]
```

**API Key:**
```php
'security' => [['apiKey' => []]]
```

### 5.3 Standard Responses

Reusable error responses:

```
- 401 Unauthorized
- 403 Forbidden
- 404 NotFound
- 400 ValidationError
- 429 TooManyRequests
```

---

## 6. Interactive Documentation Interface

### 6.1 Features in test_swagger.php

**Dashboard Statistics:**
- Total endpoints count
- API categories
- OpenAPI version
- Documentation coverage

**Advanced Filtering:**
- Filter by category (tag)
- Filter by HTTP method (GET/POST/PUT/DELETE)
- Live results update

**Endpoint Details:**
- Path and method
- Summary and description
- Tags and categories
- Parameters documentation
- Request/response schemas
- Status codes
- Code examples

**Code Examples:**
- cURL command
- JavaScript fetch
- PHP curl

**Export Options:**
- Download OpenAPI JSON
- Download OpenAPI YAML

### 6.2 URL Parameters

```
?tag=Authentication         # Filter by category
?method=POST               # Filter by HTTP method
?format=json               # Get OpenAPI JSON
?format=yaml               # Get OpenAPI YAML
```

### 6.3 Example URLs

```
# View all endpoints
http://localhost/api/test_swagger.php

# View authentication endpoints
http://localhost/api/test_swagger.php?tag=Authentication

# View all POST endpoints
http://localhost/api/test_swagger.php?method=POST

# Download OpenAPI spec
http://localhost/api/test_swagger.php?format=json
```

---

## 7. Integration with Existing API

### 7.1 Routes in Production

All defined routes in `routes.php` should match actual endpoints:

```
Define in routes.php:  POST /api/login.php
Implement in:          api/login.php
Test via:              api/test_swagger.php
```

### 7.2 Automatic Route Discovery

Routes are auto-discovered from `routes.php`:

```php
// In config.php - loaded first
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/swagger.php';

// All routes are now registered globally
$all_routes = viabixGetRoutes();
```

### 7.3 Use with Middleware

Swagger spec can be used for request validation:

```php
// Validate request against spec
$operation = viabixGetRouteOperation('POST', $_SERVER['PHP_SELF']);

if ($operation && !empty($operation['parameters'])) {
    viabixValidateRequestParameters($operation['parameters']);
}
```

---

## 8. Client SDK Generation

### 8.1 Using OpenAPI Spec

Download OpenAPI JSON and generate client SDKs:

```bash
# Download spec
curl http://localhost/api/test_swagger.php?format=json > openapi.json

# Generate TypeScript client (using openapi-generator-cli)
openapi-generator-cli generate -i openapi.json -g typescript-axios -o ./client

# Generate Python client
openapi-generator-cli generate -i openapi.json -g python -o ./python-client

# Generate Go client
openapi-generator-cli generate -i openapi.json -g go -o ./go-client
```

### 8.2 API Client Libraries Support

Spec is compatible with:
- Swagger Codegen
- OpenAPI Generator
- API clients: Postman, Insomnia, Thunder Client
- Documentation tools: ReDoc, Swagger UI, OpenAPI Explorer

---

## 9. Examples

### 9.1 Authentication Endpoints

**API Definition:**
```php
viabixRegisterRoute('POST', '/api/login.php', 'login', [
    'summary' => 'User Login',
    'tags' => ['Authentication'],
    'requestBody' => [
        'required' => true,
        'content' => ['application/json' => [
            'schema' => [
                'type' => 'object',
                'required' => ['email', 'password'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'password' => ['type' => 'string', 'minLength' => 8]
                ]
            ]
        ]]
    ],
    'responses' => [...]
]);
```

**cURL Example:**
```bash
curl -X POST http://localhost/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"SecurePass123!"}'
```

**JavaScript Example:**
```javascript
fetch('/api/login.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    email: 'user@example.com',
    password: 'SecurePass123!'
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

### 9.2 Audit Endpoints

**API Definition:**
```php
viabixRegisterRoute('GET', '/api/audit_logs.php', 'get_audit', [
    'summary' => 'Retrieve Audit Logs',
    'tags' => ['Audit'],
    'security' => [['sessionCookie' => []]],
    'parameters' => [
        ['name' => 'user_id', 'in' => 'query', 'schema' => ['type' => 'integer']],
        ['name' => 'category', 'in' => 'query', 'schema' => ['type' => 'string']],
        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']]
    ],
    'responses' => [...]
]);
```

**PHP Example:**
```php
$ch = curl_init('http://localhost/api/audit_logs.php?user_id=1&category=AUTH');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=' . $_COOKIE['PHPSESSID']]
]);
$logs = json_decode(curl_exec($ch));
```

---

## 10. Maintenance

### 10.1 Adding New Endpoints

1. **Register in routes.php:**
```php
viabixRegisterRoute('GET', '/api/new_endpoint.php', 'handler', [
    'summary' => 'Endpoint summary',
    'tags' => ['Category'],
    'responses' => [...]
]);
```

2. **Implement in api/new_endpoint.php**

3. **Test via:**
```
http://localhost/api/test_swagger.php?tag=Category
```

4. **Export new spec:**
```
http://localhost/api/test_swagger.php?format=json
```

### 10.2 Updating Existing Endpoints

Edit in `routes.php`, spec updates automatically:

```php
viabixRegisterRoute('POST', '/api/login.php', 'login', [
    // Update metadata here
    'description' => 'Updated description',
    'responses' => [
        '429' => ['description' => 'Rate limited (new)']
    ]
]);
```

### 10.3 Deprecating Endpoints

Mark as deprecated:

```php
viabixRegisterRoute('POST', '/api/old_endpoint.php', 'deprecated_handler', [
    'summary' => 'Old endpoint (use /api/new_endpoint.php instead)',
    'deprecated' => true,
    'responses' => [...]
]);
```

---

## 11. Best Practices

### 11.1 Route Definition

✅ **DO:**
```php
viabixRegisterRoute('POST', '/api/users.php', 'create_user', [
    'summary' => 'Create User',                    // Clear, concise
    'description' => 'Create new user account',    // Detailed
    'tags' => ['Users'],                           // Categorized
    'requestBody' => [...],                        // Well-defined schema
    'responses' => [...]                           // All status codes
]);
```

❌ **DON'T:**
```php
viabixRegisterRoute('POST', '/api/users.php', 'handler1', [
    'summary' => 'Do stuff',                       // Too vague
    // No description
    // No tags
    'responses' => ['200' => [...]]                // Missing error codes
]);
```

### 11.2 Security Documentation

Always document security:

```php
viabixRegisterRoute('DELETE', '/api/users.php', 'delete_user', [
    'summary' => 'Delete User',
    'security' => [['sessionCookie' => []]],       // Document auth
    'requestBody' => [...],
    'responses' => [
        '403' => ['description' => 'Admin only']   // Document restrictions
    ]
]);
```

### 11.3 Response Documentation

Document all responses:

```php
'responses' => [
    '200' => ['description' => 'Success'],
    '201' => ['description' => 'Created'],
    '400' => ['description' => 'Validation failed'],
    '401' => ['description' => 'Not authenticated'],
    '403' => ['description' => 'Not authorized'],
    '404' => ['description' => 'Not found'],
    '429' => ['description' => 'Rate limited'],
    '500' => ['description' => 'Server error']
]
```

---

## 12. Troubleshooting

### 12.1 Routes Not Appearing

**Issue:** Routes not showing in test_swagger.php

**Solution:**
1. Verify route registered in `routes.php`
2. Clear browser cache
3. Check browser console for errors
4. Verify `viabixRegisterRoute()` is called

### 12.2 OpenAPI Validation Error

**Issue:** "OpenAPI spec is invalid"

**Solution:**
1. Validate JSON at https://validator.swagger.io/
2. Check schema references: `$ref: '#/components/schemas/User'`
3. Verify required fields in schema
4. Check status code format (must be string: "200" not 200)

### 12.3 Download Spec as File

**Issue:** JSON/YAML opening in browser instead of downloading

**Solution:** Use appropriate headers:
```php
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="openapi.json"');
echo viabixGetOpenAPIJSON();
```

---

## 13. Integration Checklist

- [x] Routes defined in `routes.php`
- [x] Swagger generator in `api/swagger.php`
- [x] Test interface at `api/test_swagger.php`
- [x] Integration in `api/config.php`
- [x] OpenAPI spec accessible via HTTP
- [x] Code examples generated
- [x] Components/schemas defined
- [x] Security schemes documented
- [x] All endpoints tested
- [x] Documentation complete

---

## Summary

The Swagger/OpenAPI system provides:

✅ **Single Source of Truth:** Routes defined once in `routes.php`  
✅ **Auto-Generated Specs:** No manual spec updates needed  
✅ **Interactive Documentation:** User-friendly web interface  
✅ **Multiple Formats:** JSON and YAML exports  
✅ **Code Examples:** cURL, JavaScript, PHP  
✅ **Easy Maintenance:** Add/update routes in one place  
✅ **Client SDK Ready:** Generate clients in any language  
✅ **Swagger Compatible:** Works with all OpenAPI tools  

**Access Now:**
- Documentation: `http://localhost/api/test_swagger.php`
- OpenAPI JSON: `http://localhost/api/test_swagger.php?format=json`
- OpenAPI YAML: `http://localhost/api/test_swagger.php?format=yaml`

---

**Version:** 1.0  
**Last Updated:** 2026-04-09  
**OpenAPI Version:** 3.0.0  
**Status:** Production Ready
