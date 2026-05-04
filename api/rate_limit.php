<?php
/**
 * Rate Limiting & Throttling System
 * 
 * Protects against:
 * - Brute force login attacks
 * - API abuse
 * - DDoS attacks
 * 
 * Supports:
 * - Redis (PRIMARY) - Persistent, distributed, fast
 * - Session fallback - Lightweight, development-only
 * 
 * Location: api/rate_limit.php
 * Integrated: api/config.php
 */

if (!defined('VIABIX_APP')) {
    die('Direct access not allowed');
}

/**
 * Global Redis connection (initialized in config.php)
 */
$redis = null;

/**
 * Initialize Redis connection for rate limiting
 * Called automatically from config.php
 */
function viabixInitializeRedis() {
    global $redis;
    
    if ($redis !== null) {
        return $redis; // Already initialized
    }
    
    $redisEnabled = viabix_env_bool('REDIS_ENABLED', true);
    $redisHost = viabix_env('REDIS_HOST', 'localhost');
    $redisPort = (int) viabix_env('REDIS_PORT', 6379);
    $redisPassword = viabix_env('REDIS_PASSWORD', '');
    $redisDb = (int) viabix_env('REDIS_DB', 0);
    
    // Try to initialize Redis if enabled
    if ($redisEnabled && extension_loaded('redis')) {
        try {
            $redis = new Redis();
            
            // Set connection timeout
            $redis->settimeout(5000); // 5 seconds
            
            // Connect
            if ($redisPassword) {
                $redis->connect($redisHost, $redisPort, 5, null, 0, 5000);
                $redis->auth($redisPassword);
            } else {
                $redis->connect($redisHost, $redisPort, 5);
            }
            
            // Select database
            $redis->select($redisDb);
            
            // Ping to verify connection
            if ($redis->ping()) {
                error_log('[RATE_LIMIT] Redis initialized successfully: ' . $redisHost . ':' . $redisPort);
                return $redis;
            }
        } catch (Exception $e) {
            error_log('[RATE_LIMIT] Redis connection failed: ' . $e->getMessage());
            $redis = null;
        }
    }
    
    // Fallback to session if Redis not available
    error_log('[RATE_LIMIT] Using fallback: Session-based rate limiting (NOT PERSISTENT!)');
    return null;
}

/**
 * Get rate limit key for IP-based limiting
 * Returns: string hash for this IP
 */
function viabixGetIpLimitKey() {
    $ip = viabixGetClientIp();
    return 'rl_ip_' . md5($ip);
}

/**
 * Get rate limit key for user-based limiting
 * @param int $user_id - User ID
 * Returns: string hash for this user
 */
function viabixGetUserLimitKey($user_id) {
    return 'rl_user_' . intval($user_id);
}

/**
 * Get rate limit key for endpoint
 * @param string $endpoint - API endpoint name
 * @param mixed $user_id - User ID (optional for per-user limits)
 * Returns: string hash for this endpoint
 */
function viabixGetEndpointLimitKey($endpoint, $user_id = null) {
    $key = 'rl_ep_' . md5($endpoint);
    if ($user_id) {
        $key .= '_u' . intval($user_id);
    }
    return $key;
}

/**
 * Check if IP is rate limited (brute force protection)
 * @param string $action - Action type (login, signup, api)
 * @param int $max_attempts - Maximum attempts allowed
 * @param int $window_seconds - Time window in seconds
 * Returns: array ['allowed' => bool, 'attempts' => int, 'reset_in' => seconds]
 */
function viabixCheckIpRateLimit($action = 'login', $max_attempts = 5, $window_seconds = 300) {
    global $redis;
    
    $cache_key = viabixGetIpLimitKey() . '_' . $action;
    $current_time = time();
    
    // ===== TRY REDIS FIRST =====
    if ($redis !== null) {
        try {
            $current = $redis->get($cache_key);
            
            if ($current === false) {
                // First attempt in this window
                $redis->setex($cache_key, $window_seconds, 1);
                return [
                    'allowed' => true,
                    'attempts' => 1,
                    'reset_in' => $window_seconds
                ];
            }
            
            // Get current count and TTL
            $attempts = intval($current);
            $ttl = $redis->ttl($cache_key);
            $reset_in = max($ttl, 1);
            
            // Increment counter
            $attempts++;
            $redis->incr($cache_key);
            
            // Check if limit exceeded
            $allowed = $attempts <= $max_attempts;
            
            // Log suspicious activity
            if (!$allowed && function_exists('viabixSentryMessage')) {
                viabixSentryMessage(
                    "Rate limit exceeded for action '{$action}': " . viabixGetClientIp() . 
                    " attempted {$attempts} times in {$window_seconds}s",
                    'warning'
                );
            }
            
            return [
                'allowed' => $allowed,
                'attempts' => $attempts,
                'reset_in' => $reset_in
            ];
        } catch (Exception $e) {
            error_log('[RATE_LIMIT] Redis error in IP check: ' . $e->getMessage());
            // Fallthrough to session backup
        }
    }
    
    // ===== FALLBACK TO SESSION =====
    $session_data = $_SESSION['rate_limit'] ?? [];
    
    if (!isset($session_data[$cache_key])) {
        // First attempt in this window
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'action' => $action
        ];
        return [
            'allowed' => true,
            'attempts' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    $data = $session_data[$cache_key];
    $elapsed = $current_time - $data['window_start'];
    
    // Reset window if expired
    if ($elapsed >= $window_seconds) {
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'action' => $action
        ];
        return [
            'allowed' => true,
            'attempts' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    // Increment counter
    $_SESSION['rate_limit'][$cache_key]['count']++;
    $attempts = $_SESSION['rate_limit'][$cache_key]['count'];
    $reset_in = $window_seconds - $elapsed;
    
    $allowed = $attempts <= $max_attempts;
    
    // Log suspicious activity if limit exceeded
    if (!$allowed && function_exists('viabixSentryMessage')) {
        viabixSentryMessage(
            "Rate limit exceeded for action '{$action}': " . viabixGetClientIp() . 
            " attempted {$attempts} times in {$window_seconds}s",
            'warning'
        );
    }
    
    return [
        'allowed' => $allowed,
        'attempts' => $attempts,
        'reset_in' => $reset_in
    ];
}

/**
 * Check if user is rate limited (API throttling)
 * @param int $user_id - User ID
 * @param string $endpoint - Endpoint name
 * @param int $max_requests - Maximum requests allowed
 * @param int $window_seconds - Time window in seconds
 * Returns: array ['allowed' => bool, 'requests' => int, 'reset_in' => seconds]
 */
function viabixCheckUserRateLimit($user_id, $endpoint = 'api', $max_requests = 100, $window_seconds = 60) {
    if (!$user_id) {
        return ['allowed' => true, 'requests' => 0, 'reset_in' => 0];
    }
    
    $cache_key = viabixGetUserLimitKey($user_id) . '_' . $endpoint;
    $current_time = time();
    $session_data = $_SESSION['rate_limit'] ?? [];
    
    if (!isset($session_data[$cache_key])) {
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'endpoint' => $endpoint
        ];
        return [
            'allowed' => true,
            'requests' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    $data = $session_data[$cache_key];
    $elapsed = $current_time - $data['window_start'];
    
    // Reset window if expired
    if ($elapsed >= $window_seconds) {
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'endpoint' => $endpoint
        ];
        return [
            'allowed' => true,
            'requests' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    // Increment counter
    $_SESSION['rate_limit'][$cache_key]['count']++;
    $requests = $_SESSION['rate_limit'][$cache_key]['count'];
    $reset_in = $window_seconds - $elapsed;
    
    $allowed = $requests <= $max_requests;
    
    // Log if limit exceeded
    if (!$allowed && function_exists('viabixSentryMessage')) {
        viabixSentryMessage(
            "User rate limit exceeded: User {$user_id} at endpoint '{$endpoint}' " .
            "made {$requests} requests in {$window_seconds}s",
            'warning'
        );
    }
    
    return [
        'allowed' => $allowed,
        'requests' => $requests,
        'reset_in' => $reset_in
    ];
}

/**
 * Check if user is rate limited - Redis version
 * @param int $user_id - User ID
 * @param string $endpoint - Endpoint name
 * @param int $max_requests - Maximum requests allowed
 * @param int $window_seconds - Time window in seconds
 * Returns: array ['allowed' => bool, 'requests' => int, 'reset_in' => seconds]
 */
function viabixCheckUserRateLimitRedis($user_id, $endpoint = 'api', $max_requests = 100, $window_seconds = 60) {
    global $redis;
    
    if (!$user_id) {
        return ['allowed' => true, 'requests' => 0, 'reset_in' => 0];
    }
    
    $cache_key = viabixGetUserLimitKey($user_id) . '_' . $endpoint;
    $current_time = time();
    
    // ===== TRY REDIS FIRST =====
    if ($redis !== null) {
        try {
            $current = $redis->get($cache_key);
            
            if ($current === false) {
                // First request in this window
                $redis->setex($cache_key, $window_seconds, 1);
                return [
                    'allowed' => true,
                    'requests' => 1,
                    'reset_in' => $window_seconds
                ];
            }
            
            // Get current count and TTL
            $requests = intval($current);
            $ttl = $redis->ttl($cache_key);
            $reset_in = max($ttl, 1);
            
            // Increment counter
            $requests++;
            $redis->incr($cache_key);
            
            // Check if limit exceeded
            $allowed = $requests <= $max_requests;
            
            // Log if limit exceeded
            if (!$allowed && function_exists('viabixSentryMessage')) {
                viabixSentryMessage(
                    "User rate limit exceeded: User {$user_id} at endpoint '{$endpoint}' " .
                    "made {$requests} requests in {$window_seconds}s",
                    'warning'
                );
            }
            
            return [
                'allowed' => $allowed,
                'requests' => $requests,
                'reset_in' => $reset_in
            ];
        } catch (Exception $e) {
            error_log('[RATE_LIMIT] Redis error in user check: ' . $e->getMessage());
            // Fallthrough to session backup
        }
    }
    
    // ===== FALLBACK TO SESSION =====
    $session_data = $_SESSION['rate_limit'] ?? [];
    
    if (!isset($session_data[$cache_key])) {
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'endpoint' => $endpoint
        ];
        return [
            'allowed' => true,
            'requests' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    $data = $session_data[$cache_key];
    $elapsed = $current_time - $data['window_start'];
    
    // Reset window if expired
    if ($elapsed >= $window_seconds) {
        $_SESSION['rate_limit'][$cache_key] = [
            'count' => 1,
            'window_start' => $current_time,
            'endpoint' => $endpoint
        ];
        return [
            'allowed' => true,
            'requests' => 1,
            'reset_in' => $window_seconds
        ];
    }
    
    // Increment counter
    $_SESSION['rate_limit'][$cache_key]['count']++;
    $requests = $_SESSION['rate_limit'][$cache_key]['count'];
    $reset_in = $window_seconds - $elapsed;
    
    $allowed = $requests <= $max_requests;
    
    // Log if limit exceeded
    if (!$allowed && function_exists('viabixSentryMessage')) {
        viabixSentryMessage(
            "User rate limit exceeded: User {$user_id} at endpoint '{$endpoint}' " .
            "made {$requests} requests in {$window_seconds}s",
            'warning'
        );
    }
    
    return [
        'allowed' => $allowed,
        'requests' => $requests,
        'reset_in' => $reset_in
    ];
}

/**
 * Add rate limit response headers
 * Informs client about remaining requests
 * @param int $limit - Total request limit
 * @param int $remaining - Remaining requests
 * @param int $reset - Unix timestamp when limit resets
 */
function viabixAddRateLimitHeaders($limit, $remaining, $reset) {
    header('X-RateLimit-Limit: ' . intval($limit), true);
    header('X-RateLimit-Remaining: ' . intval($remaining), true);
    header('X-RateLimit-Reset: ' . intval($reset), true);
}

/**
 * Enforce rate limit and return error if exceeded
 * Automatically sends 429 status and headers if limit exceeded
 * @param array $limit_check - Result from viabixCheckIpRateLimit() or viabixCheckUserRateLimit()
 * @param string $message - Custom error message
 * Returns: void (exits if limit exceeded, continues if allowed)
 */
function viabixEnforceRateLimit($limit_check, $message = 'Too many requests. Please try again later.') {
    if (!$limit_check['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . intval($limit_check['reset_in']), true);
        header('Content-Type: application/json', true);
        
        echo json_encode([
            'error' => 'rate_limit_exceeded',
            'message' => $message,
            'retry_after_seconds' => $limit_check['reset_in']
        ]);
        exit;
    }
}

/**
 * Get client IP address
 * Handles proxies and load balancers
 * Returns: string IP address
 */
function viabixGetClientIp() {
    // Check for shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Handle multiple IPs (take first)
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Default to remote address
    else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    // Validate IP format
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = '127.0.0.1';
    }
    
    return $ip;
}

/**
 * Get rate limit status for dashboard/monitoring
 * Returns: array with current limits info
 */
function viabixGetRateLimitStatus($user_id = null) {
    $ip = viabixGetClientIp();
    $session_data = $_SESSION['rate_limit'] ?? [];
    
    return [
        'ip' => $ip,
        'limits' => $session_data,
        'user_id' => $user_id,
        'timestamp' => time()
    ];
}

/**
 * Clear rate limit for specific action
 * @param string $action - Action type (login, signup, api, etc.)
 * @param mixed $user_id - Optional user ID to clear specific user limits
 */
function viabixClearRateLimit($action, $user_id = null) {
    if (!isset($_SESSION['rate_limit'])) {
        return;
    }
    
    $session_data = $_SESSION['rate_limit'];
    $keys_to_remove = [];
    
    foreach (array_keys($session_data) as $key) {
        if ($user_id) {
            // Clear specific user limit
            if (strpos($key, '_u' . intval($user_id)) !== false) {
                $keys_to_remove[] = $key;
            }
        } else {
            // Clear action limit for all IPs
            if (strpos($key, '_' . $action) !== false) {
                $keys_to_remove[] = $key;
            }
        }
    }
    
    foreach ($keys_to_remove as $key) {
        unset($_SESSION['rate_limit'][$key]);
    }
}

// Initialize session storage for rate limits if needed
if (session_status() === PHP_SESSION_ACTIVE) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
}

?>
