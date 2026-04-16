# Rate Limiting & Throttling Protection

## Table of Contents
1. [Overview](#overview)
2. [What is Rate Limiting?](#what-is-rate-limiting)
3. [Security Threats Prevented](#security-threats-prevented)
4. [Implementation Architecture](#implementation-architecture)
5. [Configuration](#configuration)
6. [Usage Patterns](#usage-patterns)
7. [Testing & Validation](#testing--validation)
8. [Deployment Checklist](#deployment-checklist)
9. [Troubleshooting](#troubleshooting)
10. [API Reference](#api-reference)

---

## Overview

This document describes the comprehensive rate limiting and throttling system implemented for the Viabix SaaS platform. Rate limiting protects against brute-force attacks, API abuse, and denial-of-service (DoS) attacks.

### Key Points
- **Status**: ✅ Implemented & Integrated
- **Version**: 1.0
- **Last Updated**: 2024
- **Protection**: IP-based and user-based throttling
- **Storage**: Session-based (scalable to Redis/Database)

---

## What is Rate Limiting?

### Background
Rate limiting controls how many requests an IP address or user can make in a given time period. For example:
- Login: 5 attempts per 5 minutes per IP
- Signup: 3 attempts per 5 minutes per IP
- API: 100 requests per minute per user

### The Problem Without Rate Limiting
Attackers can:
1. **Brute Force Passwords**: Try thousands of password combinations automatically
2. **Account Enumeration**: Discover valid usernames by volume of requests
3. **DoS Attacks**: Overwhelm server with requests from single IP
4. **API Abuse**: Harvest data or perform unauthorized operations at scale

### The Solution: Viabix Rate Limiting
- Track requests by IP address (for unauthenticated endpoints)
- Track requests by user ID (for authenticated endpoints)
- Block requests when limits exceeded
- Return HTTP 429 (Too Many Requests) status
- Include `Retry-After` header so clients can retry appropriately
- Log suspicious activity to Sentry

---

## Security Threats Prevented

### 1. Brute Force Login Attacks
**Before**: Attacker can try unlimited password combinations  
**After**: Maximum 5 attempts per 5 minutes per IP
```
Scenario: Attacker from IP 192.168.1.1 tries 1000 password combinations
Result: Blocked after 5 attempts, must wait 5 minutes
```

### 2. Account Enumeration
**Before**: Attacker discovers valid usernames via response differences  
**After**: Same rate limit masks enumeration
```
Scenario: Attacker sends many requests to /api/login with different usernames
Result: Blocked after 5 attempts, cannot enumerate users
```

### 3. Credential Stuffing
**Before**: Attacker uses leaked password lists against millions of accounts  
**After**: Limited attempts per IP prevent large-scale attacks
```
Scenario: Attacker tries 10,000 leaked credential pairs
Result: Blocked after 5 attempts per IP, would need 2000 IPs (impractical)
```

### 4. Signup Abuse
**Before**: Attacker creates unlimited trial accounts for spam/abuse  
**After**: Maximum 3 signups per 5 minutes per IP
```
Scenario: Attacker tries to create 100 trial accounts from one IP
Result: Blocked after 3 attempts, must wait 5 minutes
```

### 5. API Resource Exhaustion
**Before**: Authenticated user makes unlimited API requests  
**After**: Limited to 100 requests per minute per user
```
Scenario: Malicious user exports entire database via repeated API calls
Result: Blocked after 100 requests, returns 429 status
```

---

## Implementation Architecture

### Core Components

#### 1. `api/rate_limit.php` - Rate Limiting Module
**Purpose**: Centralized rate limiting logic  
**Size**: ~280 lines  
**Storage**: PHP sessions (state preserved per session)  
**Scalability**: Can be extended to Redis/Database

**Key Functions**:

| Function | Purpose |
|----------|---------|
| `viabixCheckIpRateLimit($action, $max, $window)` | Check IP-based limits |
| `viabixCheckUserRateLimit($user_id, $endpoint, $max, $window)` | Check user-based limits |
| `viabixGetClientIp()` | Extract client IP (handles proxies) |
| `viabixEnforceRateLimit($check, $msg)` | Enforce limit and return 429 |
| `viabixClearRateLimit($action, $user_id)` | Clear limit on success |
| `viabixAddRateLimitHeaders($limit, $remaining, $reset)` | Add standard headers |

#### 2. Integration Points

| File | Change | Purpose |
|------|--------|---------|
| `api/config.php` | Added `require 'rate_limit.php'` | Auto-initialize |
| `api/login.php` | Added brute-force check | 5 attempts/5 min/IP |
| `api/signup.php` | Added signup spam check | 3 attempts/5 min/IP |
| `.env.example` | Added configuration options | Customizable limits |

#### 3. Session Storage Architecture
```
$_SESSION['rate_limit'] = [
    'rl_ip_[hash]_login' => [
        'count' => 3,                    // Current attempts
        'window_start' => 1234567890,    // When window started
        'action' => 'login'              // Type of action
    ],
    'rl_user_123_api' => [
        'count' => 45,
        'window_start' => 1234567890,
        'endpoint' => 'api'
    ]
]
```

#### 4. HTTP Headers & Status Codes
When limit exceeded, returns:
```
HTTP/1.1 429 Too Many Requests
Content-Type: application/json
Retry-After: 287
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1234568177
```

---

## Configuration

### Environment Variables

#### Login Rate Limit
```env
RATE_LIMIT_LOGIN_MAX=5           # Max attempts
RATE_LIMIT_LOGIN_WINDOW=300      # Time window in seconds (5 min)
```

#### Signup Rate Limit
```env
RATE_LIMIT_SIGNUP_MAX=3          # Max attempts
RATE_LIMIT_SIGNUP_WINDOW=300     # Time window in seconds (5 min)
```

#### API Rate Limit
```env
RATE_LIMIT_API_MAX=100           # Max requests per minute
RATE_LIMIT_API_WINDOW=60         # Time window in seconds (1 min)
```

### Recommended Configuration by Environment

**Development (Local)**:
```env
RATE_LIMIT_LOGIN_MAX=100
RATE_LIMIT_LOGIN_WINDOW=60
RATE_LIMIT_SIGNUP_MAX=100
RATE_LIMIT_SIGNUP_WINDOW=60
RATE_LIMIT_API_MAX=10000
RATE_LIMIT_API_WINDOW=60
```

**Staging**:
```env
RATE_LIMIT_LOGIN_MAX=10
RATE_LIMIT_LOGIN_WINDOW=300
RATE_LIMIT_SIGNUP_MAX=5
RATE_LIMIT_SIGNUP_WINDOW=300
RATE_LIMIT_API_MAX=500
RATE_LIMIT_API_WINDOW=60
```

**Production**:
```env
RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_WINDOW=300
RATE_LIMIT_SIGNUP_MAX=3
RATE_LIMIT_SIGNUP_WINDOW=300
RATE_LIMIT_API_MAX=100
RATE_LIMIT_API_WINDOW=60
```

---

## Usage Patterns

### Pattern 1: Login Endpoint (Already Integrated)
```php
// In api/login.php - Automatic, already implemented
$rate_limit_check = viabixCheckIpRateLimit('login', 5, 300);
if (!$rate_limit_check['allowed']) {
    viabixEnforceRateLimit($rate_limit_check, 'Too many login attempts');
}

// On successful login, clear the limit
viabixClearRateLimit('login');
```

### Pattern 2: Signup Endpoint (Already Integrated)
```php
// In api/signup.php - Automatic, already implemented
$rate_limit_check = viabixCheckIpRateLimit('signup', 3, 300);
if (!$rate_limit_check['allowed']) {
    viabixEnforceRateLimit($rate_limit_check, 'Too many signup attempts');
}

// On successful signup, clear the limit
viabixClearRateLimit('signup');
```

### Pattern 3: Adding to New Endpoints
```php
// In custom API endpoint
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current user ID
$user_id = $_SESSION['user_id'] ?? null;

// Check rate limit
$limit_check = viabixCheckUserRateLimit($user_id, 'export_data', 10, 60);
if (!$limit_check['allowed']) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

// Your endpoint logic here
// Add response headers to inform client
viabixAddRateLimitHeaders(10, $limit_check['requests'], time() + $limit_check['reset_in']);
```

### Pattern 4: Client-Side Handling
```javascript
// JavaScript client handling 429 responses
fetch('/api/endpoint', {method: 'POST', body: JSON.stringify(data)})
    .then(response => {
        if (response.status === 429) {
            const retryAfter = response.headers.get('Retry-After');
            const remaining = response.headers.get('X-RateLimit-Remaining');
            
            console.log(`Rate limited. Retry after ${retryAfter} seconds`);
            console.log(`${remaining} requests remaining`);
            
            // Wait and retry
            setTimeout(() => {
                // Retry the request
            }, retryAfter * 1000);
        }
        return response.json();
    });
```

---

## Testing & Validation

### Web-Based Test Interface
**File**: `api/test_rate_limit.php`

Open in browser: `http://localhost/ANVI/api/test_rate_limit.php`

**Test Cases Included**:
1. **Login Brute Force**: Simulate 5+ login attempts
2. **Signup Spam**: Simulate 3+ signup attempts
3. **API Throttling**: Simulate 100+ API requests
4. **Rate Limit Reset**: Test time window expiration
5. **Per-User Limits**: Test different users

### Manual Testing with cURL

#### Test 1: Simulate 5 Login Attempts
```bash
#!/bin/bash
# This script simulates 5 rapid login attempts

for i in {1..7}; do
    echo "Attempt $i:"
    curl -X POST http://localhost/ANVI/api/login.php \
        -H "Content-Type: application/json" \
        -d '{"login":"test","senha":"test"}' \
        -w "\nStatus: %{http_code}\n" \
        -s | jq '.message'
    sleep 1
done
```

**Expected**: First 5 succeed, 6th & 7th return 429

#### Test 2: Check Rate Limit Headers
```bash
curl -X POST http://localhost/ANVI/api/login.php \
    -H "Content-Type: application/json" \
    -d '{"login":"test","senha":"test"}' \
    -v 2>&1 | grep -E "X-RateLimit|Retry-After"
```

**Expected Response Headers**:
```
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 4
X-RateLimit-Reset: 1234568177
```

#### Test 3: Check Client IP Detection
```bash
# From different IP (if proxy configured)
curl -X POST http://localhost/ANVI/api/login.php \
    -H "X-Forwarded-For: 203.0.113.45" \
    -H "Content-Type: application/json" \
    -d '{"login":"test","senha":"test"}' \
    -w "\nStatus: %{http_code}\n"
```

### Browser DevTools Testing

1. Open browser DevTools (F12)
2. Network tab
3. Open login form
4. Submit invalid credentials 5+ times rapidly
5. Observe 429 response on 6th attempt

**Expected Behavior**:
- ✅ Attempts 1-5: 200/403 responses (auth errors)
- ✅ Attempt 6+: 429 responses (rate limited)
- ✅ Message includes retry time

### Performance Testing

```bash
# Measure impact of rate limiting on legitimate users
ab -n 100 -c 10 http://localhost/ANVI/api/check_session.php

# Should show:
# - No 429 responses if legitimate access pattern
# - Response times unchanged
# - Memory usage minimal (session storage only)
```

---

## Deployment Checklist

### Pre-Deployment
- [ ] Verify `rate_limit.php` module is in `api/` directory
- [ ] Confirm `.env` has all rate limit configuration
- [ ] Test rate limiting in staging environment
- [ ] Review Sentry for rate limit events being logged
- [ ] Test login/signup with rate limit enforcement
- [ ] Verify client applications handle 429 responses
- [ ] Document rate limits for API consumers

### Deployment Commands
```bash
# 1. Verify rate limit module exists
ls -la api/rate_limit.php

# 2. Verify integration in config.php
grep "rate_limit.php" api/config.php

# 3. Test rate limits are working
curl -X POST http://localhost/ANVI/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"login":"test","senha":"test"}' \
  -w "%{http_code}"

# 4. Verify no errors in logs
tail -f /var/log/php-fpm.log
```

### Post-Deployment
- [ ] Monitor Sentry for rate limit events
- [ ] Check login/signup success rates (should be normal)
- [ ] Verify 429 responses for abuse patterns
- [ ] Get user feedback on legitimate access
- [ ] Review logs for false positives
- [ ] Document any rate limit adjustments needed
- [ ] Create on-call runbook for rate limit incidents

### Monitoring & Alerting
Set up Sentry alerts for:
1. More than 20 rate limit events from single IP in 1 hour
2. Increase in rate limit events vs baseline
3. Rate limit events from unexpected IPs

---

## Troubleshooting

### Issue 1: "Legitimate users getting rate limited"
**Symptom**: Users report "Too many requests" error during normal use  
**Causes**:
- Rate limit too strict
- Shared IP (corporate proxy, NAT)
- Client making unexpected repeated requests

**Solution**:
```env
# Increase limits cautiously
RATE_LIMIT_API_MAX=500    # Was 100
RATE_LIMIT_API_WINDOW=60  # Keep same window

# OR whitelist IPs
# (Not yet implemented, would require custom logic)
```

### Issue 2: "Rate limits not being enforced"
**Symptom**: Attacker makes unlimited requests, no 429 responses  
**Causes**:
- Rate limit module not required in config.php
- Session not initialized
- Rate limit check removed from endpoint

**Solution**:
```php
// Verify in api/config.php
if (!function_exists('viabixCheckIpRateLimit')) {
    die('Rate limit module not loaded');
}

// Verify in endpoint (e.g., api/login.php)
$rate_limit_check = viabixCheckIpRateLimit('login', 5, 300);
if (!$rate_limit_check['allowed']) {
    viabixEnforceRateLimit($rate_limit_check);
}
```

### Issue 3: "After successful login, user still rate limited"
**Symptom**: User logs in successfully but subsequent requests blocked  
**Causes**:
- Rate limit not cleared after success
- Different session between requests

**Solution**:
```php
// Verify viabixClearRateLimit() is called on success
viabixClearRateLimit('login');  // In login.php

// Ensure session persistence across requests
// Session should be preserved in $_SESSION['rate_limit']
```

### Issue 4: "Proxy/NAT Behind Load Balancer Issues"
**Symptom**: Different users behind same IP all rate limited  
**Causes**:
- X-Forwarded-For header not trusted
- Rate limiting by shared proxy IP

**Solution**:
```php
// Verify viabixGetClientIp() includes:
// 1. Check HTTP_X_FORWARDED_FOR (from proxy)
// 2. Check HTTP_CLIENT_IP (from CDN)
// 3. Fall back to REMOTE_ADDR

// For load balancer, configure to pass real client IP:
// Nginx:
//   proxy_set_header X-Forwarded-For $remote_addr;
// Apache:
//   Header set X-Forwarded-For "%{HTTP:X-Forwarded-For}i"
```

### Debug Mode
Enable debug logging in `api/rate_limit.php`:
```php
// Add to rate_limit.php temporarily
define('VIABIX_RATE_LIMIT_DEBUG', true);

// In viabixCheckIpRateLimit()
if (defined('VIABIX_RATE_LIMIT_DEBUG')) {
    error_log('Rate Limit Check: ' . json_encode([
        'action' => $action,
        'ip' => viabixGetClientIp(),
        'attempts' => $attempts,
        'max' => $max_attempts,
        'allowed' => $allowed
    ]));
}
```

---

## API Reference

### `viabixCheckIpRateLimit($action, $max_attempts, $window_seconds)`
**Purpose**: Check if IP is rate limited  
**Parameters**:
- `$action` (string) - Action type (e.g., 'login', 'signup')
- `$max_attempts` (int) - Max attempts allowed (default: 5)
- `$window_seconds` (int) - Time window in seconds (default: 300)

**Returns**: Array
```php
[
    'allowed' => true,        // Can proceed?
    'attempts' => 1,          // Current attempt count
    'reset_in' => 300         // Seconds until reset
]
```

**Example**:
```php
$check = viabixCheckIpRateLimit('login', 5, 300);
if (!$check['allowed']) {
    die("Rate limited. Try again in {$check['reset_in']} seconds");
}
```

### `viabixCheckUserRateLimit($user_id, $endpoint, $max_requests, $window_seconds)`
**Purpose**: Check if user is rate limited  
**Parameters**:
- `$user_id` (int) - User ID
- `$endpoint` (string) - API endpoint name
- `$max_requests` (int) - Max requests allowed (default: 100)
- `$window_seconds` (int) - Time window in seconds (default: 60)

**Returns**: Array (same as IP limit)

**Example**:
```php
$user_id = $_SESSION['user_id'] ?? null;
$check = viabixCheckUserRateLimit($user_id, 'export', 10, 60);
```

### `viabixGetClientIp()`
**Purpose**: Extract client IP handling proxies/load balancers  
**Returns**: String (IP address)

**Example**:
```php
$ip = viabixGetClientIp();
error_log("Request from: $ip");
```

### `viabixEnforceRateLimit($limit_check, $message)`
**Purpose**: Send 429 response if limit exceeded  
**Parameters**:
- `$limit_check` (array) - Result from check function
- `$message` (string) - Error message for client

**Returns**: void (exits on enforcement)

**Example**:
```php
$check = viabixCheckIpRateLimit('login');
viabixEnforceRateLimit($check, 'Too many login attempts');
// Execution stops here if rate limited
```

### `viabixClearRateLimit($action, $user_id)`
**Purpose**: Clear rate limit on successful operation  
**Parameters**:
- `$action` (string) - Action type to clear
- `$user_id` (int, optional) - Specific user to clear

**Example**:
```php
// On successful login
viabixClearRateLimit('login');

// On specific user success
viabixClearRateLimit('api', $user_id);
```

### `viabixAddRateLimitHeaders($limit, $remaining, $reset)`
**Purpose**: Add standard rate limit headers to response  
**HTTP Headers Set**:
- `X-RateLimit-Limit`: Total requests allowed
- `X-RateLimit-Remaining`: Requests left
- `X-RateLimit-Reset`: Unix timestamp of reset

**Example**:
```php
viabixAddRateLimitHeaders(100, 95, time() + 60);
```

---

## Scaling & Future Enhancements

### Current Limitations
- Session-based storage (lost on session timeout)
- Only works for single server (no distribution)
- Memory usage grows with active sessions

### Future Improvements
1. **Redis Caching** (recommended)
   - Persist limits across server restarts
   - Distribute across server cluster
   - Better performance

2. **Database Storage**
   - Permanent audit trail
   - Historical analysis
   - Complex query patterns

3. **GeoIP Blocking**
   - Block requests from specific countries
   - Whitelist known office locations

4. **Adaptive Rate Limiting**
   - Machine learning to detect patterns
   - Dynamic thresholds based on traffic

5. **Whitelist Management**
   - Whitelist trusted IPs
   - Bypass for premium users
   - API key rate limits

---

## Related Documentation

- [MONITORING.md](MONITORING.md) - Sentry error tracking
- [CSRF_PROTECTION.md](CSRF_PROTECTION.md) - CSRF token protection
- [CORS_PROTECTION.md](CORS_PROTECTION.md) - CORS security headers
- [OWASP Rate Limiting Guide](https://owasp.org/www-community/articles/Rate_Limiting) - External reference
- [HTTP 429 Status Code](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/429) - External reference

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024 | Initial implementation with IP and user-based limiting |

---

## Support & Questions

For issues or questions about rate limiting:
1. Check [Troubleshooting](#troubleshooting) section
2. Review test interface: `api/test_rate_limit.php`
3. Check Sentry dashboard for rate limit events
4. Consult existing documentation in `/RATE_LIMITING.md`

---

**Last Updated**: 2024  
**Maintainer**: Viabix Development Team  
**Status**: ✅ Production Ready
