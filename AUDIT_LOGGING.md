# Audit Logging System Documentation

## Overview

The Audit Logging System provides comprehensive activity tracking for compliance, security investigation, and user behavior analysis.

**Key Features:**
- ✅ Multi-category logging (AUTH, SECURITY, CRUD, DATA_ACCESS, API, ERROR)
- ✅ JSON-based detailed context storage
- ✅ Advanced filtering and search capabilities
- ✅ Suspicious activity detection
- ✅ Compliance reporting (GDPR, PCI DSS)
- ✅ Automatic log retention and archival
- ✅ Database partitioning for performance at scale
- ✅ Pre-aggregated statistics for fast analytics
- ✅ Zero-configuration setup (auto-creates tables)

**Compliance Ready:**
- ✅ GDPR (data retention policies, access logs)
- ✅ PCI DSS (security event tracking)
- ✅ SOC 2 (audit trail requirements)
- ✅ HIPAA (activity monitoring)

**Location:** `api/audit.php`

**Integration:** Auto-loaded via `api/config.php`

**Database Schema:** `BD/migration_audit_logs.sql`

---

## 1. Core Concepts

### Log Categories

Logs are organized by category for easy filtering and analysis:

| Category | Purpose | Examples |
|----------|---------|----------|
| **AUTH** | Authentication events | login, logout, login_failed, session_expired, 2fa_verified |
| **SECURITY** | Security-related events | csrf_failed, rate_limit_exceeded, unauthorized_access |
| **CRUD_USUARIO** | User management operations | create_user, update_user, delete_user |
| **CRUD_INVOICE** | Invoice operations | create_invoice, update_invoice, approve_invoice |
| **CRUD_PROJETO** | Project operations | create_project, archive_project |
| **DATA_ACCESS** | Data access patterns | export_data, download_report, access_personal_info |
| **PERMISSIONS** | Permission changes | grant_admin, revoke_permission, change_role |
| **API** | API endpoint calls | POST, GET, PUT, DELETE requests |
| **ERROR** | Application errors | exception_thrown, validation_failed, database_error |

---

### Log Entry Structure

Each log entry contains:

```
{
  "id": 12345,
  "user_id": 1,                    // null for system events
  "category": "AUTH",
  "action": "login",
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "details": {...},                // JSON context
  "created_at": "2026-04-09 14:30:00"
}
```

---

## 2. Database Schema

### audit_logs Table

Main audit trail storage with automatic partitioning by month.

```sql
CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT,
  category VARCHAR(50),
  action VARCHAR(100),
  ip_address VARCHAR(45),
  user_agent VARCHAR(1000),
  details JSON,
  created_at TIMESTAMP,
  
  PARTITION BY RANGE (YEAR_MONTH(created_at))
);
```

**Partitioning Benefit:** Large tables split by month for faster queries

**Indexes:**
- `idx_user_id` - Quick user lookup
- `idx_category` - Category filtering
- `idx_created_at` - Date range queries
- `idx_user_category_date` - Complex queries

---

### audit_events_summary Table

Pre-calculated daily statistics for fast reporting.

```sql
CREATE TABLE audit_events_summary (
  id INT,
  date DATE,
  category VARCHAR(50),
  action VARCHAR(100),
  count INT,
  unique_users INT,
  unique_ips INT,
  
  UNIQUE KEY (date, category, action)
);
```

**Purpose:** Avoid slow aggregations on large audit_logs table

---

### audit_retention_policy Table

GDPR-compliant data retention configuration.

```sql
CREATE TABLE audit_retention_policy (
  id INT,
  category VARCHAR(50),           -- null = default
  retention_days INT,             -- How long to keep active
  archive_after_days INT,         -- When to move to archive
  updated_at TIMESTAMP
);
```

**Default Policies:**
- AUTH: 365 days retention (compliance)
- SECURITY: 365 days retention (compliance)
- PERMISSIONS: 2555 days / 7 years (regulatory)
- DATA_ACCESS: 90 days (GDPR)
- Others: 90 days active, 180 days archive

---

## 3. Class Reference

### ViabixAuditLogger

Main audit logging class.

```php
$audit = viabixAudit();
```

---

## 4. Logging Methods

### logAuthEvent($user_id, $action, $details = [])

Log authentication and session events.

**Syntax:**
```php
viabixLogAuthEvent($user_id, $action, $details);
```

**Parameters:**
- `$user_id` - User ID
- `$action` - Event type: 'login', 'logout', 'login_failed', 'session_expired', '2fa_verified'
- `$details` - Array of additional context

**Usage Example:**
```php
// Successful login
viabixLogAuthEvent($user['id'], 'login', [
    'email' => $user['email'],
    'method' => 'password',
    'ip_forwarded' => false
]);

// Failed login attempt
viabixLogAuthEvent($user['id'], 'login_failed', [
    'email' => $email,
    'reason' => 'invalid_password',
    'attempt_count' => 3
]);

// Session timeout
viabixLogAuthEvent($user_id, 'session_expired', [
    'session_duration_minutes' => 480
]);

// 2FA verification
viabixLogAuthEvent($user_id, '2fa_verified', [
    'method' => 'totp',
    'verification_time_ms' => 145
]);
```

---

### logCrudOperation($resource, $action, $resource_id, $details = [])

Log create, read, update, delete operations.

**Syntax:**
```php
viabixLogCrud($resource, $action, $resource_id, $details);
```

**Parameters:**
- `$resource` - Resource type: 'usuario', 'invoice', 'projeto', etc
- `$action` - 'create', 'read', 'update', 'delete', 'export'
- `$resource_id` - ID of modified resource
- `$details` - Changes made, data exported, etc

**Usage Example:**
```php
// Create user
viabixLogCrud('usuario', 'create', $new_user_id, [
    'email' => 'new@example.com',
    'role' => 'user',
    'tenant_id' => $tenant_id
]);

// Update user
viabixLogCrud('usuario', 'update', $user_id, [
    'old' => ['status' => 'active', 'role' => 'user'],
    'new' => ['status' => 'inactive', 'role' => 'admin'],
    'updated_by' => $_SESSION['user_id']
]);

// Delete user
viabixLogCrud('usuario', 'delete', $user_id, [
    'email' => $user['email'],
    'data_exported' => true,
    'soft_delete' => false
]);

// Read/Export
viabixLogCrud('usuario', 'export', $user_id, [
    'format' => 'csv',
    'fields' => ['id', 'email', 'name'],
    'rows_exported' => 500
]);
```

---

### logSecurityEvent($action, $details = [])

Log security-related events (not tied to single user action).

**Syntax:**
```php
viabixLogSecurityEvent($action, $details);
```

**Parameters:**
- `$action` - Event type: 'csrf_failed', 'rate_limit_exceeded', 'unauthorized_access', etc
- `$details` - Additional context

**Usage Example:**
```php
// CSRF token validation failed
viabixLogSecurityEvent('csrf_failed', [
    'endpoint' => '/api/update_profile.php',
    'method' => 'POST',
    'expected_token' => 'abc123...',
    'provided_token' => 'invalid'
]);

// Rate limit exceeded
viabixLogSecurityEvent('rate_limit_exceeded', [
    'endpoint' => '/api/login.php',
    'limit_type' => 'ip',
    'limit_value' => 5,
    'time_window' => 300,
    'attempts_made' => 6
]);

// Unauthorized access attempt
viabixLogSecurityEvent('unauthorized_access', [
    'resource' => 'admin_panel',
    'user_role' => 'user',
    'required_role' => 'admin',
    'attempt_time' => date('Y-m-d H:i:s')
]);
```

---

### logDataAccess($accessed_user_id, $data_type, $access_type = 'read')

Log data access for compliance (GDPR Article 32).

**Syntax:**
```php
viabixLogDataAccess($accessed_user_id, $data_type, $access_type);
```

**Parameters:**
- `$accessed_user_id` - Whose data was accessed
- `$data_type` - Type of data: 'personal_info', 'financial_data', 'audit_logs', etc
- `$access_type` - 'read', 'export', 'download'

**Usage Example:**
```php
// Admin views user profile
viabixLogDataAccess($profile_user_id, 'personal_info', 'read');

// User exports their own data (GDPR right to access)
viabixLogDataAccess($_SESSION['user_id'], 'personal_info', 'export');

// Download financial report
viabixLogDataAccess($tenant_id, 'financial_data', 'download');

// Access audit logs
viabixLogDataAccess(null, 'audit_logs', 'read');  // null = system data
```

---

### logApiCall($endpoint, $method, $response_code, $details = [])

Log API endpoint calls for performance and security analysis.

**Syntax:**
```php
viabixLogApiCall($endpoint, $method, $response_code, $details);
```

**Parameters:**
- `$endpoint` - API path (e.g., '/api/users.php')
- `$method` - HTTP method: GET, POST, PUT, DELETE
- `$response_code` - HTTP status code (200, 401, 404, 500, etc)
- `$details` - Response time, parameters, cached/not cached

**Usage Example:**
```php
// At start of API endpoint
$start_time = microtime(true);

// ... API logic ...

$response_time_ms = (microtime(true) - $start_time) * 1000;

viabixLogApiCall('/api/invoices.php', 'GET', 200, [
    'response_time_ms' => $response_time_ms,
    'offset' => $_GET['offset'] ?? 0,
    'limit' => $_GET['limit'] ?? 50,
    'filters_applied' => count($filters),
    'cached' => false,
    'results_count' => $invoice_count
]);
```

---

### logError($error_type, $message, $context = [])

Log errors and exceptions systematically.

**Syntax:**
```php
viabixLogError($error_type, $message, $context);
```

**Parameters:**
- `$error_type` - Error classification: 'validation_error', 'database_error', 'payment_error'
- `$message` - Error description
- `$context` - Stack trace, variables involved, etc

**Usage Example:**
```php
try {
    $pdo->query('SELECT ...');
} catch (Exception $e) {
    viabixLogError('database_error', 'Query failed', [
        'query' => 'SELECT ...',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
}

// Validation error
try {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
} catch (Exception $e) {
    viabixLogError('validation_error', 'Email validation failed', [
        'email_provided' => $email,
        'validator' => 'FILTER_VALIDATE_EMAIL'
    ]);
}
```

---

## 5. Query and Reporting Methods

### getLogs($filters = [], $limit = 100, $offset = 0)

Retrieve audit logs with filtering.

**Syntax:**
```php
$logs = viabixAudit()->getLogs($filters, $limit, $offset);
```

**Parameters:**
- `$filters` - Array of filter criteria
- `$limit` - Number of results (default 100)
- `$offset` - Pagination offset (default 0)

**Available Filters:**
```php
$filters = [
    'user_id' => 1,                      // Specific user
    'category' => 'AUTH',                // Log category
    'action' => 'login',                 // Specific action
    'start_date' => '2026-04-01',        // Date range start (YYYY-MM-DD)
    'end_date' => '2026-04-09',          // Date range end
    'ip_address' => '192.168.1.100',     // Source IP
    'resource' => 'usuario'              // CRUD resource type
];

$logs = viabixAudit()->getLogs($filters, 50, 0);
```

**Returns:** Array of audit log entries

**Usage Example:**
```php
// Get user's activity last 7 days
$logs = viabixAudit()->getLogs([
    'user_id' => $user_id,
    'start_date' => date('Y-m-d', strtotime('-7 days'))
], 100, 0);

// Get failed logins in last 24 hours
$logs = viabixAudit()->getLogs([
    'category' => 'AUTH',
    'action' => 'login_failed',
    'start_date' => date('Y-m-d', strtotime('-1 day'))
], 1000, 0);

// Get all API calls with errors
$logs = viabixAudit()->getLogs([
    'category' => 'API',
    'action' => 'error'
], 500, 0);
```

---

### getLogsCount($filters = [])

Get total count of logs matching filters (faster than counting results).

**Syntax:**
```php
$total = viabixAudit()->getLogsCount($filters);
```

**Usage Example:**
```php
$suspicious_logins = viabixAudit()->getLogsCount([
    'category' => 'AUTH',
    'action' => 'login_failed',
    'start_date' => date('Y-m-d')
]);

echo "Failed login attempts today: $suspicious_logins";
```

---

### getSuspiciousActivity()

Find suspicious activity patterns (3+ failed attempts per IP in last hour).

**Syntax:**
```php
$suspicious = viabixAudit()->getSuspiciousActivity();
```

**Returns:** Array of suspicious patterns
```php
[
    [
        'user_id' => 1,
        'ip_address' => '192.168.1.100',
        'failed_attempts' => 5,
        'last_attempt' => '2026-04-09 14:30:00'
    ],
    // ...
]
```

**Usage Example:**
```php
$suspicious = viabixAudit()->getSuspiciousActivity();

foreach ($suspicious as $pattern) {
    // Send alert
    viabixSendEmail(
        'security@company.com',
        'Suspicious Activity Detected',
        "User {$pattern['user_id']} from IP {$pattern['ip_address']} "
        . "has {$pattern['failed_attempts']} failed attempts"
    );
}
```

---

### getUserActivitySummary($user_id, $period = 'week')

Get activity breakdown by category for user.

**Syntax:**
```php
$summary = viabixAudit()->getUserActivitySummary($user_id, $period);
```

**Parameters:**
- `$user_id` - User ID
- `$period` - 'today', 'week', 'month', 'all'

**Returns:**
```php
[
    ['category' => 'AUTH', 'count' => 5, 'first_activity' => '...', 'last_activity' => '...'],
    ['category' => 'CRUD_USUARIO', 'count' => 2, ...],
    // ...
]
```

---

### generateComplianceReport($report_type, $filters = [])

Generate GDPR/PCI DSS compliance reports.

**Syntax:**
```php
$report = viabixAudit()->generateComplianceReport($report_type, $filters);
```

**Parameters:**
- `$report_type` - 'gdpr', 'pci_dss', 'general'
- `$filters` - Additional search filters

**Usage Example:**

**GDPR Report** (Data access patterns):
```php
$report = viabixAudit()->generateComplianceReport('gdpr');
// Shows: Who accessed what data, when, and why

echo "Report generated: " . $report['generated_at'];
foreach ($report['data_access'] as $access) {
    echo $access['user_id'] . " accessed " . $access['action'];
}
```

**PCI DSS Report** (Security events):
```php
$report = viabixAudit()->generateComplianceReport('pci_dss');
// Shows: Login attempts, failed authentication, unauthorized access

foreach ($report['security_events'] as $event) {
    echo $event['action'] . ": " . $event['count'] . " occurrences";
}
```

---

### exportCsv($filters = [], $filename = 'audit_logs.csv')

Export audit logs as CSV for external reporting.

**Syntax:**
```php
$csv_content = viabixAudit()->exportCsv($filters, $filename);
```

**Returns:** CSV string

**Usage Example:**
```php
// Get logs and prepare for download
$csv = viabixAudit()->exportCsv([
    'start_date' => '2026-04-01',
    'end_date' => '2026-04-09'
]);

// Download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_logs_april.csv"');
echo $csv;
```

---

## 6. Integration Examples

### Login Endpoint with Audit

```php
<?php
// api/login.php
require_once 'config.php';

$email = viabixSanitize($_POST['email'], 'email');
$password = $_POST['password'] ?? '';

// Find user
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user['password'])) {
    // Log failed attempt
    viabixLogAuthEvent(-1, 'login_failed', [
        'email' => $email,
        'reason' => 'invalid_credentials'
    ]);
    
    json_error('Invalid email or password', 401);
}

// Log successful login
viabixLogAuthEvent($user['id'], 'login', [
    'email' => $email,
    'verification_method' => '2fa' // or 'password'
]);

// Continue login process...
```

---

### User Update with Change Tracking

```php
<?php
// Update user
$old_user = $pdo->query("SELECT * FROM usuarios WHERE id = ?");

$updates = [
    'name' => $new_name,
    'role' => $new_role,
    'status' => $new_status
];

$stmt = $pdo->prepare("UPDATE usuarios SET ... WHERE id = ?");
$stmt->execute([...]);

// Log changes
viabixLogCrud('usuario', 'update', $user_id, [
    'old' => [
        'name' => $old_user['name'],
        'role' => $old_user['role'],
        'status' => $old_user['status']
    ],
    'new' => $updates,
    'updated_by_id' => $_SESSION['user_id']
]);
```

---

### Suspicious Activity Alerts

```php
<?php
// Security monitoring endpoint
$suspicious = viabixAudit()->getSuspiciousActivity();

foreach ($suspicious as $pattern) {
    // Block further attempts from this IP
    viabixAddToBlocklist($pattern['ip_address']);
    
    // Send security alert
    Send email/SMS/Slack notification
    
    // Log the alert action
    viabixLogSecurityEvent('suspicious_activity_detected', [
        'user_id' => $pattern['user_id'],
        'ip_address' => $pattern['ip_address'],
        'failed_attempts' => $pattern['failed_attempts'],
        'action_taken' => 'ip_blocked'
    ]);
}
```

---

## 7. Security Best Practices

### Always Log on Server

> **Critical:** Never rely on client-side logging!

```php
// ❌ BAD - Client logging only
console.log("User ID: " + user_id);

// ✅ GOOD - Always server-side
viabixLogAuthEvent($user_id, 'login', [...]);
```

---

### Don't Log Sensitive Data

```php
// ❌ BAD - Logging passwords
viabixLogAuthEvent($user_id, 'login', [
    'password' => $password  // NEVER!
]);

// ✅ GOOD - Log only non-sensitive context
viabixLogAuthEvent($user_id, 'login', [
    'authentication_method' => 'password',
    'password_strength_score' => 8  // if needed
]);
```

---

### Hash Sensitive Fields

```php
// ❌ BAD - Plain email in logs
viabixLogCrud('usuario', 'create', $user_id, [
    'email' => $email
]);

// ✅ GOOD - Hash or mask sensitive fields
viabixLogCrud('usuario', 'create', $user_id, [
    'email_hash' => hash('sha256', $email),
    'email_domain' => substr($email, strpos($email, '@'))
]);
```

---

## 8. Performance Optimization

### Use Filters to Reduce Data

```php
// ❌ BAD - Getting all logs
$all_logs = viabixAudit()->getLogs([], 100000, 0);

// ✅ GOOD - Filter by date
$recent_logs = viabixAudit()->getLogs([
    'start_date' => date('Y-m-d', strtotime('-7 days'))
], 1000, 0);
```

---

### Use Pre-aggregated Statistics

```php
// ✅ GOOD - Use summary table for fast analytics
SELECT * FROM audit_events_summary WHERE date = CURDATE();
```

---

### Partition by Date

Database automatically partitions by month for performance.

```sql
-- Fast queries only scan relevant partitions
SELECT * FROM audit_logs WHERE created_at >= '2026-04-01';
```

---

## 9. Data Retention & Privacy

### Automatic Cleanup

Scheduled event runs daily at 2 AM to:
1. Archive old logs (180+ days) to `audit_logs_archive`
2. Delete archived logs after retention period
3. Update pre-aggregated statistics

---

### GDPR Compliance

**Right to Erasure:**
```php
// When user requests deletion
// 1. Export their data
$user_data = viabixAudit()->getLogs(['user_id' => $user_id], 100000, 0);

// 2. Archive for compliance (7 years for some categories)
// 3. Mark user_id as NULL in old logs
// 4. Delete personal data

// Implementation handled by purgeOldLogs()
```

---

### Configurable Retention

Modify policies in `audit_retention_policy` table:

```sql
-- Keep AUTH logs for 2 years (regulatory requirement)
UPDATE audit_retention_policy 
SET retention_days = 730 
WHERE category = 'AUTH';
```

---

## 10. Testing

### Test Interface

Access the interactive test interface:
```
http://localhost/api/test_audit.php
```

**Features:**
- Recent logs with pagination
- Advanced filtering (user, category, date, IP)
- Suspicious activity detection
- User activity summary
- Integration examples
- Log categories reference

---

### Manual Testing

```php
// Generate test data
for ($i = 0; $i < 100; $i++) {
    viabixLogAuthEvent(rand(1, 10), 'login', [
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

// Query
$logs = viabixAudit()->getLogs([
    'category' => 'AUTH',
    'action' => 'login'
], 50, 0);

echo "Found " . count($logs) . " login events";
```

---

## 11. Troubleshooting

### Tables Not Creating

**Issue:** "audit_logs table doesn't exist"

**Solution:**
1. Run migration: `BD/migration_audit_logs.sql`
2. Check MySQL version (partitioning requires MySQL 5.7+)
3. Verify permissions: `GRANT ALTER, CREATE, INDEX ON viabix_db.*`

---

### Logs Not Appearing

**Issue:** viabixLogAuthEvent() called but logs not in database

**Solution:**
1. Check table exists: `SHOW TABLES LIKE 'audit_logs';`
2. Check database connection: `echo isset($pdo) ? 'OK' : 'NOT OK';`
3. Check permissions: User needs INSERT rights
4. Look for errors in debug log

---

### Slow Queries

**Issue:** Queries taking too long

**Solution:**
1. Use `getLogsCount()` instead of counting results
2. Add date filters: `'start_date' => date('Y-m-d')`
3. Use `audit_events_summary` for aggregation
4. Check partition alignment: `SELECT PARTITION_NAME FROM INFORMATION_SCHEMA.PARTITIONS WHERE TABLE_NAME='audit_logs';`

---

## Summary

The Audit Logging System provides:

✅ **Comprehensive Tracking:** All activities logged with context  
✅ **Compliance Ready:** GDPR, PCI DSS, SOC 2, HIPAA  
✅ **Performance Optimized:** Partitioning, pre-aggregation, indexing  
✅ **Easy Integration:** Simple helper functions  
✅ **Flexible Reporting:** Export, analytics, threat detection  
✅ **Data Privacy:** Automatic retention, archival, purging  

**Start logging now:**

```php
// Authentication
viabixLogAuthEvent($user_id, 'login', ['method' => 'password']);

// Operations
viabixLogCrud('usuario', 'update', $user_id, ['old' => [...], 'new' => [...]]);

// Security
viabixLogSecurityEvent('csrf_failed', ['endpoint' => '/api/update']);

// Query
$logs = viabixAudit()->getLogs([
    'user_id' => $user_id,
    'start_date' => date('Y-m-d', strtotime('-7 days'))
], 100, 0);
```

---

**Version:** 1.0  
**Last Updated:** 2026-04-09  
**Integration:** `api/config.php`  
**Test Interface:** `api/test_audit.php`  
**Database Migration:** `BD/migration_audit_logs.sql`
