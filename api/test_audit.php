<?php
/**
 * Audit Logging Test Interface
 * Interactive testing UI for audit logs, reports, and monitoring
 */

require_once __DIR__ . '/config.php';

header('Content-Type: text/html; charset=utf-8');

// Require authentication for audit viewing
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;  // For testing only
}

$audit = viabixAudit();
$page = $_GET['page'] ?? 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get filter parameters
$filters = [
    'user_id' => $_GET['user_id'] ?? null,
    'category' => $_GET['category'] ?? null,
    'action' => $_GET['action'] ?? null,
    'start_date' => $_GET['start_date'] ?? null,
    'end_date' => $_GET['end_date'] ?? null,
    'ip_address' => $_GET['ip_address'] ?? null,
];

// Remove empty filters
$filters = array_filter($filters);

// Get logs
$logs = $audit->getLogs($filters, $per_page, $offset);
$total_logs = $audit->getLogsCount($filters);
$total_pages = ceil($total_logs / $per_page);

// Get suspicious activity
$suspicious = $audit->getSuspiciousActivity();

// Get activity summary for current user (if logged in)
$user_summary = isset($_SESSION['user_id']) ? $audit->getUserActivitySummary($_SESSION['user_id'], 'month') : [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logging - Test Interface</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1400px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header p {
            color: #666;
            margin: 0;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0;
            padding: 20px;
        }
        
        .card-header h5 {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 25px;
            transition: transform 0.2s ease;
        }
        
        .btn-primary:hover {
            transform: scale(1.02);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-box p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .log-entry {
            padding: 15px;
            border-left: 4px solid #ccc;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        
        .log-entry.auth {
            border-left-color: #4CAF50;
        }
        
        .log-entry.security {
            border-left-color: #f44336;
        }
        
        .log-entry.crud {
            border-left-color: #2196F3;
        }
        
        .log-entry.error {
            border-left-color: #ff9800;
        }
        
        .log-category {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .log-category.auth {
            background: #d4edda;
            color: #155724;
        }
        
        .log-category.security {
            background: #f8d7da;
            color: #721c24;
        }
        
        .log-category.crud {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .log-category.error {
            background: #ffe0b2;
            color: #bf360c;
        }
        
        .badge-success {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .table {
            background: white;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .table th {
            background: #f5f5f5;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .filter-section {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        .icon {
            font-size: 1.3rem;
        }
        
        .pagination {
            margin-top: 20px;
        }
        
        .suspicious-item {
            padding: 15px;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            margin-bottom: 10px;
            border-radius: 5px;
        }
        
        .json-display {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.85rem;
            max-height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>
                <i class="fas fa-clipboard-list icon"></i>
                Audit Logging System
            </h1>
            <p><i class="fas fa-info-circle"></i> Monitor all user activities, security events, and compliance data</p>
        </div>

        <!-- Stats -->
        <div class="stats">
            <div class="stat-box">
                <h3><?php echo number_format($total_logs); ?></h3>
                <p>Total Logs</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($suspicious); ?></h3>
                <p>Suspicious Activities</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $total_pages; ?></h3>
                <p>Pages</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($user_summary); ?></h3>
                <p>Activity Categories</p>
            </div>
        </div>

        <!-- Suspicious Activity Alert -->
        <?php if (!empty($suspicious)): ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading">
                <i class="fas fa-exclamation-triangle"></i> Suspicious Activity Detected
            </h4>
            <?php foreach ($suspicious as $activity): ?>
            <div class="suspicious-item">
                <strong>User ID:</strong> <?php echo $activity['user_id'] ?? 'N/A'; ?> | 
                <strong>IP:</strong> <?php echo htmlspecialchars($activity['ip_address']); ?> | 
                <strong>Failed Attempts:</strong> <?php echo $activity['failed_attempts']; ?> | 
                <strong>Last Attempt:</strong> <?php echo $activity['last_attempt']; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-filter icon"></i> Filter Logs</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-section">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="form-label">User ID</label>
                            <input type="number" class="form-control" name="user_id" value="<?php echo htmlspecialchars($filters['user_id'] ?? ''); ?>" placeholder="All users">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <option value="AUTH" <?php echo $filters['category'] === 'AUTH' ? 'selected' : ''; ?>>Authentication</option>
                                <option value="SECURITY" <?php echo $filters['category'] === 'SECURITY' ? 'selected' : ''; ?>>Security</option>
                                <option value="CRUD_USUARIO" <?php echo $filters['category'] === 'CRUD_USUARIO' ? 'selected' : ''; ?>>User CRUD</option>
                                <option value="DATA_ACCESS" <?php echo $filters['category'] === 'DATA_ACCESS' ? 'selected' : ''; ?>>Data Access</option>
                                <option value="API" <?php echo $filters['category'] === 'API' ? 'selected' : ''; ?>>API Calls</option>
                                <option value="ERROR" <?php echo $filters['category'] === 'ERROR' ? 'selected' : ''; ?>>Errors</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">IP Address</label>
                            <input type="text" class="form-control" name="ip_address" value="<?php echo htmlspecialchars($filters['ip_address'] ?? ''); ?>" placeholder="e.g., 192.168.1.1">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="?" class="btn btn-secondary" style="width: 100%;">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-list icon"></i> Recent Audit Logs (Page <?php echo $page; ?> of <?php echo $total_pages; ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No audit logs found matching your filters.
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Category</th>
                                <th>Action</th>
                                <th>IP Address</th>
                                <th>Details</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): 
                                $category_class = strtolower($log['category']);
                                if (strpos($category_class, 'crud') !== false) $category_class = 'crud';
                            ?>
                            <tr>
                                <td><small><?php echo $log['id']; ?></small></td>
                                <td><?php echo $log['user_id'] ?? 'System'; ?></td>
                                <td>
                                    <span class="log-category <?php echo $category_class; ?>">
                                        <?php echo htmlspecialchars($log['category']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><small><code><?php echo htmlspecialchars($log['ip_address']); ?></code></small></td>
                                <td>
                                    <?php if ($log['details']): ?>
                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#detailsModal<?php echo $log['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <div class="modal fade" id="detailsModal<?php echo $log['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Log Details - ID <?php echo $log['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="json-display"><?php echo htmlspecialchars(json_encode(json_decode($log['details']), JSON_PRETTY_PRINT)); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo date('M d, H:i:s', strtotime($log['created_at'])); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?php echo http_build_query(array_filter($filters), '', '&'); ?>">First</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo http_build_query(array_filter($filters), '', '&'); ?>">Previous</a>
                        </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo http_build_query(array_filter($filters), '', '&'); ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo http_build_query(array_filter($filters), '', '&'); ?>">Next</a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo http_build_query(array_filter($filters), '', '&'); ?>">Last</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- User Activity Summary -->
        <?php if (!empty($user_summary)): ?>
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar icon"></i> Your Activity (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Count</th>
                                <th>First Activity</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_summary as $activity): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($activity['category']); ?></strong></td>
                                <td><span class="badge badge-success"><?php echo $activity['count']; ?></span></td>
                                <td><small><?php echo date('M d, H:i', strtotime($activity['first_activity'])); ?></small></td>
                                <td><small><?php echo date('M d, H:i', strtotime($activity['last_activity'])); ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Documentation -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-code icon"></i> Usage Examples</h5>
                    </div>
                    <div class="card-body">
                        <h6>Log Authentication Event</h6>
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>viabixLogAuthEvent($user_id, 'login', [
  'email' => 'user@example.com'
]);</code></pre>

                        <h6 class="mt-3">Log CRUD Operation</h6>
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>viabixLogCrud('usuario', 'update', $user_id, [
  'old' => ['status' => 'active'],
  'new' => ['status' => 'inactive']
]);</code></pre>

                        <h6 class="mt-3">Log Security Event</h6>
                        <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto;"><code>viabixLogSecurityEvent('failed_login_attempt', [
  'email' => 'user@example.com',
  'attempts' => 3
]);</code></pre>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-book icon"></i> Log Categories</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><strong>AUTH:</strong> Login, logout, session events</li>
                            <li><strong>SECURITY:</strong> CSRF, rate limiting, 2FA events</li>
                            <li><strong>CRUD_*:</strong> Create, read, update, delete operations</li>
                            <li><strong>DATA_ACCESS:</strong> Data retrieval and export</li>
                            <li><strong>PERMISSIONS:</strong> Role and permission changes</li>
                            <li><strong>API:</strong> API endpoint calls</li>
                            <li><strong>ERROR:</strong> Application errors and exceptions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-5 mb-3">
            <small class="text-white">
                <i class="fas fa-info-circle"></i>
                Audit Logging System v1.0 | 
                <a href="/api/" class="text-white">Back to API</a>
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
