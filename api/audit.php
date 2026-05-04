<?php
/**
 * Audit Logging System
 * 
 * Tracks all user activities for compliance, security, and investigation:
 * - Login/logout events
 * - CRUD operations (create, read, update, delete)
 * - Permission changes
 * - Data access patterns
 * - Failed security checks (2FA, CSRF, rate limit)
 * 
 * Location: api/audit.php
 * Integrated: api/config.php
 */

if (!defined('VIABIX_APP')) {
    die('Direct access not allowed');
}

/**
 * Audit Logger Class
 */
class ViabixAuditLogger {
    
    private $pdo;
    private $user_id;
    private $tenant_id;
    private $ip_address;
    private $user_agent;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->user_id = $_SESSION['user_id'] ?? null;
        // SECURITY: Get tenant_id for audit log filtering
        $this->tenant_id = viabixCurrentTenantId();
        $this->ip_address = $this->getClientIp();
        $this->user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    /**
     * Get client IP address
     * @return string IP address
     */
    private function getClientIp() {
        // Check for IP from shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        // Check for remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        // Validate IP
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return trim($ip);
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Log authentication event
     * @param int $user_id User ID
     * @param string $action 'login', 'logout', 'login_failed', 'session_expired'
     * @param array $details Additional context
     * @return bool Success
     */
    public function logAuthEvent($user_id, $action, $details = []) {
        return $this->log('AUTH', $action, $user_id, $details);
    }
    
    /**
     * Log CRUD operation
     * @param string $resource Resource type (e.g., 'usuario', 'projeto', 'invoice')
     * @param string $action 'create', 'read', 'update', 'delete'
     * @param int $resource_id ID of resource
     * @param array $details Changes made (old/new values)
     * @return bool Success
     */
    public function logCrudOperation($resource, $action, $resource_id, $details = []) {
        $category = 'CRUD_' . strtoupper($resource);
        return $this->log($category, $action, $this->user_id, [
            'resource' => $resource,
            'resource_id' => $resource_id,
            'changes' => $details
        ]);
    }
    
    /**
     * Log security event
     * @param string $action Event type (e.g., 'csrf_failed', '2fa_attempt', 'rate_limit')
     * @param array $details Context
     * @return bool Success
     */
    public function logSecurityEvent($action, $details = []) {
        return $this->log('SECURITY', $action, $this->user_id, $details);
    }
    
    /**
     * Log permission change
     * @param int $target_user_id User whose permissions changed
     * @param array $old_permissions Old permissions
     * @param array $new_permissions New permissions
     * @return bool Success
     */
    public function logPermissionChange($target_user_id, $old_permissions, $new_permissions) {
        return $this->log('PERMISSIONS', 'change', $this->user_id, [
            'target_user_id' => $target_user_id,
            'old' => $old_permissions,
            'new' => $new_permissions
        ]);
    }
    
    /**
     * Log data access
     * @param int $accessed_user_id User whose data was accessed
     * @param string $data_type Type of data (e.g., 'personal_info', 'financial_data')
     * @param string $access_type 'read', 'export', 'download'
     * @return bool Success
     */
    public function logDataAccess($accessed_user_id, $data_type, $access_type = 'read') {
        return $this->log('DATA_ACCESS', $access_type, $this->user_id, [
            'accessed_user_id' => $accessed_user_id,
            'data_type' => $data_type
        ]);
    }
    
    /**
     * Log API call
     * @param string $endpoint API endpoint
     * @param string $method HTTP method
     * @param int $response_code HTTP response code
     * @param array $details Parameters, response time, etc
     * @return bool Success
     */
    public function logApiCall($endpoint, $method, $response_code, $details = []) {
        return $this->log('API', $method, $this->user_id, [
            'endpoint' => $endpoint,
            'response_code' => $response_code,
            'response_time_ms' => $details['response_time_ms'] ?? 0,
            'parameters' => $details['parameters'] ?? []
        ]);
    }
    
    /**
     * Log error/exception
     * @param string $error_type Error type
     * @param string $message Error message
     * @param array $context Additional context
     * @return bool Success
     */
    public function logError($error_type, $message, $context = []) {
        return $this->log('ERROR', $error_type, $this->user_id, [
            'message' => $message,
            'context' => $context
        ]);
    }
    
    /**
     * Main logging function
     * @param string $category Category (AUTH, CRUD, SECURITY, etc)
     * @param string $action Specific action
     * @param int $user_id User ID (can be null for system events)
     * @param array $details Event details as JSON
     * @return bool Success
     */
    private function log($category, $action, $user_id = null, $details = []) {
        try {
            // Ensure table exists
            if (!viabixHasTable('audit_logs')) {
                return false;
            }
            
            $stmt = $this->pdo->prepare("
                INSERT INTO audit_logs 
                (user_id, category, action, ip_address, user_agent, details, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $details_json = json_encode($details);
            
            $stmt->execute([
                $user_id,
                $category,
                $action,
                $this->ip_address,
                $this->user_agent,
                $details_json
            ]);
            
            return true;
            
        } catch (Exception $e) {
            // Fail silently to not interrupt main application
            viabixLogError("Audit logging failed", ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get audit logs filtered by criteria
     * @param array $filters Filter criteria
     * @param int $limit Results limit
     * @param int $offset Pagination offset
     * @return array Array of audit records
     */
    public function getLogs($filters = [], $limit = 100, $offset = 0) {
        try {
            // SECURITY: Check if audit_logs has tenant_id column
            $tenantAware = false;
            if ($this->tenant_id) {
                try {
                    $check = $this->pdo->query("DESCRIBE audit_logs");
                    while ($col = $check->fetch(PDO::FETCH_ASSOC)) {
                        if ($col['Field'] === 'tenant_id') {
                            $tenantAware = true;
                            break;
                        }
                    }
                } catch (Exception $e) {
                    // Column check failed, continue without tenant filtering
                }
            }
            
            // Build base query with tenant isolation
            if ($tenantAware && $this->tenant_id) {
                $query = "SELECT * FROM audit_logs WHERE tenant_id = ?";
                $params = [$this->tenant_id];
            } else if ($this->tenant_id) {
                // Fallback: Filter via user's tenant through JOIN with usuarios
                $query = "SELECT al.* FROM audit_logs al "
                       . "JOIN usuarios u ON al.user_id = u.id "
                       . "WHERE u.tenant_id = ?";
                $params = [$this->tenant_id];
            } else {
                $query = "SELECT * FROM audit_logs WHERE 1=1";
                $params = [];
            }
            
            // Filter by user
            if (!empty($filters['user_id'])) {
                $query .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            // Filter by category
            if (!empty($filters['category'])) {
                $query .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            // Filter by action
            if (!empty($filters['action'])) {
                $query .= " AND action = ?";
                $params[] = $filters['action'];
            }
            
            // Filter by date range
            if (!empty($filters['start_date'])) {
                $query .= " AND created_at >= ?";
                $params[] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (!empty($filters['end_date'])) {
                $query .= " AND created_at <= ?";
                $params[] = $filters['end_date'] . ' 23:59:59';
            }
            
            // Filter by IP
            if (!empty($filters['ip_address'])) {
                $query .= " AND ip_address = ?";
                $params[] = $filters['ip_address'];
            }
            
            // Filter by resource
            if (!empty($filters['resource'])) {
                $query .= " AND details LIKE ?";
                $params[] = '%"resource":"' . $filters['resource'] . '"%';
            }
            
            // Order by latest first
            $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            viabixLogError("Failed to fetch audit logs", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Get total count of logs
     * @param array $filters Filter criteria
     * @return int Total count
     */
    public function getLogsCount($filters = []) {
        try {
            // SECURITY: Check if audit_logs has tenant_id column
            $tenantAware = false;
            if ($this->tenant_id) {
                try {
                    $check = $this->pdo->query("DESCRIBE audit_logs");
                    while ($col = $check->fetch(PDO::FETCH_ASSOC)) {
                        if ($col['Field'] === 'tenant_id') {
                            $tenantAware = true;
                            break;
                        }
                    }
                } catch (Exception $e) {
                    // Column check failed, continue without tenant filtering
                }
            }
            
            // Build base query with tenant isolation
            if ($tenantAware && $this->tenant_id) {
                $query = "SELECT COUNT(*) as total FROM audit_logs WHERE tenant_id = ?";
                $params = [$this->tenant_id];
            } else if ($this->tenant_id) {
                // Fallback: Filter via user's tenant through JOIN with usuarios
                $query = "SELECT COUNT(*) as total FROM audit_logs al "
                       . "JOIN usuarios u ON al.user_id = u.id "
                       . "WHERE u.tenant_id = ?";
                $params = [$this->tenant_id];
            } else {
                $query = "SELECT COUNT(*) as total FROM audit_logs WHERE 1=1";
                $params = [];
            }
            
            if (!empty($filters['user_id'])) {
                $query .= " AND user_id = ?";
                $params[] = $filters['user_id'];
            }
            
            if (!empty($filters['category'])) {
                $query .= " AND category = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['start_date'])) {
                $query .= " AND created_at >= ?";
                $params[] = $filters['start_date'] . ' 00:00:00';
            }
            
            if (!empty($filters['end_date'])) {
                $query .= " AND created_at <= ?";
                $params[] = $filters['end_date'] . ' 23:59:59';
            }
            
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?? 0;
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Export audit logs as CSV
     * @param array $filters Filter criteria
     * @param string $filename Output filename
     * @return string CSV content
     */
    public function exportCsv($filters = [], $filename = 'audit_logs.csv') {
        try {
            $logs = $this->getLogs($filters, 10000, 0);
            
            if (empty($logs)) {
                return '';
            }
            
            $csv = "ID,User ID,Category,Action,IP Address,User Agent,Details,Created At\n";
            
            foreach ($logs as $log) {
                $csv .= sprintf(
                    "%d,%s,%s,%s,%s,%s,\"%s\",%s\n",
                    $log['id'],
                    $log['user_id'] ?? 'N/A',
                    $log['category'],
                    $log['action'],
                    $log['ip_address'],
                    str_replace('"', '""', substr($log['user_agent'], 0, 100)),
                    str_replace('"', '""', $log['details']),
                    $log['created_at']
                );
            }
            
            return $csv;
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    /**
     * Get activity summary for user
     * @param int $user_id User ID
     * @param string $period 'today', 'week', 'month', 'all'
     * @return array Summary statistics
     */
    public function getUserActivitySummary($user_id, $period = 'week') {
        try {
            $date_query = '';
            
            switch ($period) {
                case 'today':
                    $date_query = "AND created_at >= CURDATE()";
                    break;
                case 'week':
                    $date_query = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $date_query = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
            }
            
            $stmt = $this->pdo->prepare("
                SELECT 
                    category,
                    COUNT(*) as count,
                    MIN(created_at) as first_activity,
                    MAX(created_at) as last_activity
                FROM audit_logs
                WHERE user_id = ? $date_query
                GROUP BY category
                ORDER BY count DESC
            ");
            
            $stmt->execute([$user_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get suspicious activity patterns
     * @return array Suspicious activities
     */
    public function getSuspiciousActivity() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    user_id,
                    ip_address,
                    COUNT(*) as failed_attempts,
                    MAX(created_at) as last_attempt
                FROM audit_logs
                WHERE category = 'AUTH' 
                AND action IN ('login_failed', 'csrf_failed', 'rate_limit_exceeded')
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY user_id, ip_address
                HAVING COUNT(*) >= 3
                ORDER BY failed_attempts DESC
            ");
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Generate compliance report
     * @param string $report_type 'gdpr', 'pci_dss', 'general'
     * @param array $filters Additional filters
     * @return array Report data
     */
    public function generateComplianceReport($report_type, $filters = []) {
        try {
            $report = [
                'type' => $report_type,
                'generated_at' => date('Y-m-d H:i:s'),
                'period' => $filters['period'] ?? 'all',
            ];
            
            switch ($report_type) {
                case 'gdpr':
                    // Data access patterns
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            user_id,
                            action,
                            COUNT(*) as count,
                            MIN(created_at) as first_time,
                            MAX(created_at) as last_time
                        FROM audit_logs
                        WHERE category IN ('DATA_ACCESS', 'AUTH', 'CRUD_USUARIO')
                        GROUP BY user_id, action
                        ORDER BY count DESC
                    ");
                    $stmt->execute();
                    $report['data_access'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                case 'pci_dss':
                    // Security events
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            action,
                            COUNT(*) as count,
                            MIN(created_at) as first_occurrence,
                            MAX(created_at) as last_occurrence
                        FROM audit_logs
                        WHERE category IN ('SECURITY', 'AUTH', 'ERROR')
                        GROUP BY action
                        ORDER BY count DESC
                    ");
                    $stmt->execute();
                    $report['security_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    break;
                    
                default:
                    // General statistics
                    $stmt = $this->pdo->prepare("
                        SELECT 
                            category,
                            COUNT(*) as count
                        FROM audit_logs
                        GROUP BY category
                        ORDER BY count DESC
                    ");
                    $stmt->execute();
                    $report['by_category'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return $report;
            
        } catch (Exception $e) {
            viabixLogError("Failed to generate compliance report", ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Purge old audit logs (GDPR data retention)
     * @param int $days_to_keep Number of days to retain
     * @return int Rows deleted
     */
    public function purgeOldLogs($days_to_keep = 90) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM audit_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            
            $stmt->execute([$days_to_keep]);
            
            $deleted = $stmt->rowCount();
            viabixLogInfo("Purged $deleted old audit logs");
            
            return $deleted;
            
        } catch (Exception $e) {
            viabixLogError("Failed to purge audit logs", ['error' => $e->getMessage()]);
            return 0;
        }
    }
}

/**
 * Global audit logger instance
 */
$viabix_audit = null;

/**
 * Get audit logger instance
 */
function viabixAudit() {
    global $viabix_audit;
    
    if (!$viabix_audit) {
        $viabix_audit = new ViabixAuditLogger();
    }
    
    return $viabix_audit;
}

/**
 * Quick logging functions
 */
function viabixLogAuthEvent($user_id, $action, $details = []) {
    return viabixAudit()->logAuthEvent($user_id, $action, $details);
}

function viabixLogCrud($resource, $action, $resource_id, $details = []) {
    return viabixAudit()->logCrudOperation($resource, $action, $resource_id, $details);
}

function viabixLogSecurityEvent($action, $details = []) {
    return viabixAudit()->logSecurityEvent($action, $details);
}

function viabixLogDataAccess($accessed_user_id, $data_type, $access_type = 'read') {
    return viabixAudit()->logDataAccess($accessed_user_id, $data_type, $access_type);
}

function viabixLogApiCall($endpoint, $method, $response_code, $details = []) {
    return viabixAudit()->logApiCall($endpoint, $method, $response_code, $details);
}

?>
