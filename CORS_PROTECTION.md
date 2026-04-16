# CORS (Cross-Origin Resource Sharing) Security Implementation

## Table of Contents
1. [Overview](#overview)
2. [What is CORS?](#what-is-cors)
3. [Security Vulnerability Fixed](#security-vulnerability-fixed)
4. [Implementation Architecture](#implementation-architecture)
5. [Configuration](#configuration)
6. [Usage Patterns](#usage-patterns)
7. [Testing & Validation](#testing--validation)
8. [Deployment Checklist](#deployment-checklist)
9. [Troubleshooting](#troubleshooting)
10. [API Reference](#api-reference)

---

## Overview

This document describes the comprehensive CORS (Cross-Origin Resource Sharing) protection implemented for the Viabix SaaS platform. CORS is a security mechanism that controls which external domains can access your API endpoints.

### Key Points
- **Status**: ✅ Implemented & Integrated
- **Version**: 1.0
- **Last Updated**: 2024
- **Enforces**: Secure origin validation
- **Pattern**: Whitelist-based (not wildcard)

---

## What is CORS?

### Background
When a web browser makes a request to a different domain, it enforces CORS restrictions for security. For example:
- Your main app: `https://app.viabix.com`
- API endpoint: `https://api.viabix.com`

These are "different origins" (different domains), so the browser requires CORS headers.

### The Problem
A common (but insecure) approach is to allow ALL origins:
```php
// ❌ DANGEROUS - NEVER DO THIS IN PRODUCTION
header('Access-Control-Allow-Origin: *');
```

This is equivalent to leaving your API open to the entire internet.

### The Solution
Viabix now uses **whitelist-based CORS**:
- Only configured origins can access the API
- All other cross-origin requests are blocked
- Suspicious origins are logged to Sentry
- Helps prevent: XSS attacks, credential theft, API abuse

---

## Security Vulnerability Fixed

### Critical Issue Found
**File**: `Controle_de_projetos/sse.php`  
**Previous Code**:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
```

### Attack Vector
The wildcard origin policy allowed:
1. Any malicious website to call your API
2. Credential theft via cross-site requests
3. Unauthorized data access
4. API endpoint abuse

### Current Fix
```php
// ✅ SECURE - Whitelist-based validation
viabixConfigureCors(['GET', 'POST'], ['Content-Type']);
```

Now only configured origins can access this endpoint.

---

## Implementation Architecture

### Core Components

#### 1. `api/cors.php` - CORS Security Module
**Purpose**: Centralized CORS logic  
**Size**: ~180 lines  
**Functions**:

| Function | Purpose |
|----------|---------|
| `viabixGetAllowedCorsOrigins()` | Returns whitelist from `.env` |
| `viabixIsOriginAllowed($origin)` | Validates origin against whitelist |
| `viabixConfigureCors($methods, $headers)` | Applies CORS headers |
| `viabixHandleCorsPreflight()` | Handles OPTIONS preflight |
| `viabixLogSuspiciousOrigin($origin)` | Logs to Sentry |

**Key Design**:
- No external dependencies
- Works with `.env` configuration
- Integrates with Sentry for logging suspicious requests
- Handles CORS preflight automatically

#### 2. Integration Points

| File | Change | Purpose |
|------|--------|---------|
| `api/config.php` | Added `require 'cors.php'` | Auto-initialize CORS |
| `Controle_de_projetos/sse.php` | Replaced wildcard with `viabixConfigureCors()` | Secure SSE endpoint |
| Other API files | Can use `viabixConfigureCors()` | Optional per-endpoint config |

#### 3. Global Security Headers
Added to `api/config.php`:
```php
header('X-Frame-Options: SAMEORIGIN', true);
header('X-Content-Type-Options: nosniff', true);
header('X-XSS-Protection: 1; mode=block', true);
header('Referrer-Policy: strict-origin-when-cross-origin', true);
header('Permissions-Policy: geolocation=(), microphone=(), camera=()', true);
```

These work alongside CORS to prevent:
- **X-Frame-Options**: Clickjacking attacks
- **X-Content-Type-Options**: MIME type sniffing
- **X-XSS-Protection**: Reflected XSS attacks
- **Referrer-Policy**: Information leakage
- **Permissions-Policy**: Unauthorized browser feature access

---

## Configuration

### Environment Variable Setup

#### `.env` File
```env
# CORS Configuration (comma-separated origins)
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://www.yourdomain.com,http://localhost:3000
```

### Configuring Allowed Origins

**Development (local testing)**:
```env
CORS_ALLOWED_ORIGINS=http://localhost,http://localhost:3000,http://localhost:8080,http://127.0.0.1:3000
```

**Staging**:
```env
CORS_ALLOWED_ORIGINS=https://staging.viabix.com,https://staging-admin.viabix.com
```

**Production**:
```env
CORS_ALLOWED_ORIGINS=https://app.viabix.com,https://www.viabix.com,https://admin.viabix.com
```

### Important Notes
- Separate multiple origins with **commas** (no spaces)
- Use **full protocol** (`https://` or `http://`)
- No trailing slashes
- For localhost during development: use without protocol as fallback
- Review before each environment deployment

---

## Usage Patterns

### Pattern 1: Global CORS (Already Configured)
```php
// In api/config.php - automatically applied to all requests
require_once 'cors.php';
```

**Result**: Every API endpoint has basic CORS headers

### Pattern 2: Custom Per-Endpoint CORS
```php
// In an API endpoint (e.g., api/webhook_billing.php)
viabixConfigureCors(
    ['POST'],                    // Allow POST only
    ['Content-Type', 'X-Signature'] // Allow custom headers
);
```

**Use Case**: More restrictive endpoints need extra headers

### Pattern 3: Handling Preflight Requests
```php
// Browser sends OPTIONS before actual request
// viabixHandleCorsPreflight() handles this automatically
// OR manually:
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    viabixHandleCorsPreflight(['POST'], ['X-Custom-Header']);
    exit(0);
}
```

**When**: Before processing POST/PUT/DELETE with custom headers

### Pattern 4: Logging Suspicious Origins
```php
// Automatically logged when non-whitelisted origin detected
// Check in Sentry dashboard or logs
```

---

## Testing & Validation

### Web-Based Test Interface
**File**: `api/test_cors.php`

Open in browser: `http://localhost/ANVI/api/test_cors.php`

**Test Cases Included**:
1. **Allowed Origin**: Returns 200 + CORS headers
2. **Denied Origin**: Returns 403 + logged to Sentry
3. **Preflight (OPTIONS)**: Returns 204 + preflight headers
4. **Custom Headers**: Validates header allowlist
5. **Missing Origin**: Handled gracefully

### Manual Testing with cURL

#### Test 1: Valid Origin
```bash
curl -H "Origin: https://yourdomain.com" \
     -H "Access-Control-Request-Method: GET" \
     -X OPTIONS \
     http://localhost/ANVI/api/check_session.php
```

**Expected Response**:
```
Access-Control-Allow-Origin: https://yourdomain.com
Access-Control-Allow-Methods: GET, POST
Access-Control-Allow-Credentials: true
```

#### Test 2: Invalid Origin
```bash
curl -H "Origin: https://malicious.com" \
     -H "Access-Control-Request-Method: GET" \
     -X OPTIONS \
     http://localhost/ANVI/api/check_session.php
```

**Expected**: No CORS headers returned

#### Test 3: Check Security Headers
```bash
curl -i http://localhost/ANVI/api/check_session.php | grep -E "X-.*:|Strict-Transport"
```

**Expected Response Headers**:
```
X-Frame-Options: SAMEORIGIN
X-Content-Type-Options: nosniff
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Strict-Transport-Security: max-age=31536000 (HTTPS only)
```

### Browser DevTools Testing

1. Open browser DevTools (F12)
2. Network tab
3. Load a page that makes cross-origin requests
4. Check request headers: `Origin: ...`
5. Check response headers: `Access-Control-Allow-*`

**Success Indicators**:
- ✅ Requests from allowed origins have CORS headers
- ✅ Requests from blocked origins show CORS error
- ✅ Browser console shows no CORS violations for legitimate requests

### Node.js Testing Script
```javascript
// test-cors.js
const https = require('https');

const origins = [
    'https://app.viabix.com',
    'https://malicious.com',
    'http://localhost:3000'
];

origins.forEach(origin => {
    const options = {
        hostname: 'api.viabix.com',
        port: 443,
        path: '/api/check_session.php',
        method: 'OPTIONS',
        headers: {
            'Origin': origin,
            'Access-Control-Request-Method': 'POST'
        }
    };

    const req = https.request(options, (res) => {
        console.log(`\n${origin}:`);
        console.log(`Status: ${res.statusCode}`);
        console.log(`CORS Header: ${res.headers['access-control-allow-origin']}`);
    });

    req.end();
});
```

Run: `node test-cors.js`

---

## Deployment Checklist

### Pre-Deployment
- [ ] Verify `.env` has all allowed origins configured
- [ ] Test each origin in development/staging
- [ ] Run `api/test_cors.php` to validate setup
- [ ] Check Sentry receives suspicious origin attempts
- [ ] Audit API endpoints for removed security issues
- [ ] Document all allowed origins for team reference

### Deployment Commands
```bash
# 1. Backup current config
cp api/config.php api/config.php.backup

# 2. Verify CORS module exists
ls -la api/cors.php

# 3. Test CORS preflight
curl -X OPTIONS -H "Origin: https://app.viabix.com" \
  https://yourdomain.com/api/check_session.php

# 4. Verify no access-control-allow-origin: * exists
grep -r "Access-Control-Allow-Origin: \*" ./api/
# Should return NO results
```

### Post-Deployment
- [ ] Monitor Sentry for blocked origins
- [ ] Test from valid client applications
- [ ] Confirm no CORS errors in browser console
- [ ] Review access logs for suspicious patterns
- [ ] Get stakeholder confirmation of working integrations
- [ ] Document any newly added origins

---

## Troubleshooting

### Issue 1: "No 'Access-Control-Allow-Origin' header"
**Symptom**: Browser console shows CORS error  
**Causes**:
- Origin not in `CORS_ALLOWED_ORIGINS` 
- `.env` not loaded by config.php
- API endpoint not using `viabixConfigureCors()`

**Solution**:
```php
// Verify in api/config.php
if (!function_exists('viabixConfigureCors')) {
    die('CORS module not loaded');
}

// Check .env is parsed
echo getenv('CORS_ALLOWED_ORIGINS');
```

### Issue 2: "Request blocked by CORS policy"
**Symptom**: Preflight (OPTIONS) succeeds but actual request fails  
**Causes**:
- Missing custom headers in `Access-Control-Allow-Headers`
- Credentials not allowed

**Solution**:
```php
// In endpoint that uses custom headers
viabixConfigureCors(
    ['POST', 'PUT', 'DELETE'],
    ['Content-Type', 'X-Custom-Header']  // Add missing header
);
```

### Issue 3: Sentry Logging Unclear
**Symptom**: Suspicious origins not being logged  
**Causes**:
- Sentry not initialized
- Log level filters warning messages

**Solution**:
```php
// In api/cors.php, verify Sentry is available
if (function_exists('viabixSentryMessage')) {
    viabixSentryMessage('Suspicious CORS origin: ' . $origin, 'warning');
}
```

### Issue 4: Localhost Testing Fails
**Symptom**: localhost origin blocked during development  
**Causes**:
- Missing localhost in `CORS_ALLOWED_ORIGINS`

**Solution**:
```env
# .env for development
CORS_ALLOWED_ORIGINS=http://localhost,http://localhost:3000,http://127.0.0.1:3000,http://localhost:8080
```

### Debug Mode
Enable debug logging in `api/cors.php`:
```php
// Add to cors.php temporarily
define('VIABIX_CORS_DEBUG', true);

// In viabixConfigureCors()
if (defined('VIABIX_CORS_DEBUG')) {
    error_log('CORS Request Origin: ' . $_SERVER['HTTP_ORIGIN']);
    error_log('Allowed Origins: ' . getenv('CORS_ALLOWED_ORIGINS'));
}
```

---

## API Reference

### `viabixGetAllowedCorsOrigins()`
**Returns**: Array of allowed origin strings  
**Example**:
```php
$origins = viabixGetAllowedCorsOrigins();
// ['https://app.viabix.com', 'https://www.viabix.com', ...]
```

### `viabixIsOriginAllowed($origin)`
**Parameter**: `$origin` (string) - Origin from request header  
**Returns**: Boolean  
**Example**:
```php
if (viabixIsOriginAllowed($_SERVER['HTTP_ORIGIN'] ?? '')) {
    echo 'Origin is allowed';
}
```

### `viabixConfigureCors($methods = [], $headers = [])`
**Parameters**:
- `$methods` (array) - Allowed HTTP methods (e.g., `['GET', 'POST']`)
- `$headers` (array) - Allowed request headers (e.g., `['Content-Type']`)

**Example**:
```php
viabixConfigureCors(
    ['GET', 'POST', 'PUT', 'DELETE'],
    ['Content-Type', 'Authorization', 'X-Custom']
);
```

### `viabixHandleCorsPreflight($methods = [], $headers = [])`
**Purpose**: Handle OPTIONS preflight request  
**Example**:
```php
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    viabixHandleCorsPreflight(['POST'], ['Content-Type']);
    exit(0);
}
```

### `viabixLogSuspiciousOrigin($origin)`
**Purpose**: Log attempt from non-whitelisted origin  
**Automatically Called**: When origin is not in whitelist  
**Manual Usage**:
```php
viabixLogSuspiciousOrigin('https://unknown.com');
```

---

## Additional Security Considerations

### 1. Credentials & Cookies
CORS now includes:
```php
header('Access-Control-Allow-Credentials: true', true);
```

This allows persistent sessions across origins. Ensure:
- Cookies use `Secure` flag (HTTPS only)
- Cookies use `SameSite=Strict`
- Session validation happens server-side

### 2. Limiting Preflight Cache
```php
header('Access-Control-Max-Age: 3600', true);  // Cache preflight 1 hour
```

Browsers cache preflight responses to reduce requests.

### 3. Combining with Rate Limiting
CORS + Rate Limiting + Authentication = Defense in depth
- CORS blocks unknown origins
- Rate limiting prevents abuse from known origins
- Session validation prevents credential theft

### 4. Monitoring in Sentry
Check Sentry dashboard for:
1. Volume of blocked cross-origin requests
2. Repeated attempts from same IP/origin
3. New/unknown origins attempting access

---

## Related Documentation

- [MONITORING.md](MONITORING.md) - Sentry error tracking
- [CSRF_PROTECTION.md](CSRF_PROTECTION.md) - CSRF token protection
- [Security Headers Explained](https://securityheaders.com) - External reference
- [OWASP CORS Guide](https://owasp.org/www-community/misconfigurations/CORS) - External reference

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024 | Initial implementation with whitelist-based CORS |

---

## Support & Questions

For issues or questions about CORS implementation:
1. Check [Troubleshooting](#troubleshooting) section
2. Review test interface: `api/test_cors.php`
3. Check Sentry dashboard for errors
4. Consult existing documentation in `/CORS_PROTECTION.md`

---

**Last Updated**: 2024  
**Maintainer**: Viabix Development Team  
**Status**: ✅ Production Ready
