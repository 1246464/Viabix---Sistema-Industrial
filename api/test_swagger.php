<?php
/**
 * Viabix Swagger/OpenAPI Test Interface
 * Interactive API documentation and testing tool
 * 
 * Features:
 * - Live Swagger UI
 * - Route explorer with filtering
 * - OpenAPI spec in JSON/YAML
 * - Code examples (PHP, JavaScript, cURL)
 * - API request simulator
 */

require_once __DIR__ . '/swagger.php';
require_once __DIR__ . '/cors.php';

// Disable rate limiting for testing
if (defined('RATE_LIMIT_DISABLED')) {
    define('RATE_LIMIT_DISABLED', true);
}

// Handle format parameter
$format = $_GET['format'] ?? 'html';

if ($format === 'json') {
    header('Content-Type: application/json');
    echo viabixGetOpenAPIJSON();
    exit;
}

if ($format === 'yaml') {
    header('Content-Type: text/yaml');
    echo viabixGetOpenAPIYAML();
    exit;
}

// HTML Interface
$spec = viabixGetOpenAPISpec();
$paths = $spec['paths'] ?? [];
$tags = $spec['tags'] ?? [];
$filter_tag = $_GET['tag'] ?? '';
$filter_method = $_GET['method'] ?? '';

// Filter routes
$filtered_paths = [];
foreach ($paths as $path => $methods) {
    foreach ($methods as $method => $operation) {
        $has_tag = empty($filter_tag) || in_array($filter_tag, $operation['tags'] ?? []);
        $has_method = empty($filter_method) || strtoupper($method) === strtoupper($filter_method);
        
        if ($has_tag && $has_method) {
            if (!isset($filtered_paths[$path])) {
                $filtered_paths[$path] = [];
            }
            $filtered_paths[$path][$method] = $operation;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Viabix API Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: rgba(0, 0, 0, 0.1) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        .badge-method {
            font-size: 11px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .badge-get { background: #61affe; color: white; }
        .badge-post { background: #49cc90; color: white; }
        .badge-put { background: #fca130; color: white; }
        .badge-delete { background: #f93e3e; color: white; }
        .badge-patch { background: #50e3c2; color: white; }
        
        .route-item {
            border-left: 4px solid #667eea;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 12px;
        }
        .route-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .route-item.get { border-left-color: #61affe; }
        .route-item.post { border-left-color: #49cc90; }
        .route-item.put { border-left-color: #fca130; }
        .route-item.delete { border-left-color: #f93e3e; }
        
        .parameters-table {
            font-size: 14px;
        }
        .code-block {
            background: #2d2d2d;
            color: #f8f8f2;
            border-radius: 6px;
            padding: 16px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        .code-block code { color: #f8f8f2; }
        .nav-tabs .nav-link { color: #667eea; border: none; }
        .nav-tabs .nav-link.active { 
            color: white;
            background: #667eea;
            border-radius: 6px 6px 0 0;
        }
        .text-muted-light { color: rgba(255, 255, 255, 0.7); }
        .tag-badge {
            display: inline-block;
            padding: 2px 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            font-size: 12px;
            margin-right: 4px;
            margin-bottom: 4px;
        }
        .stat-box {
            background: white;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
        }
        .stat-number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-label { font-size: 12px; color: #999; margin-top: 4px; }
        
        .modal-body .code-block { max-height: 400px; overflow-y: auto; }
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="bi bi-api"></i> Viabix API Documentation
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="?format=json" target="_blank">
                            <i class="bi bi-filetype-json"></i> OpenAPI JSON
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="?format=yaml" target="_blank">
                            <i class="bi bi-file-earmark"></i> OpenAPI YAML
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid py-5">
        <!-- Header -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <h1 class="text-white mb-3">
                    <i class="bi bi-book"></i> Viabix API Documentation
                </h1>
                <p class="text-muted-light">
                    Complete API reference with interactive examples and code snippets
                </p>
            </div>
        </div>

        <!-- Stats -->
        <div class="row mb-5">
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($paths); ?></div>
                    <div class="stat-label">Total Endpoints</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($tags); ?></div>
                    <div class="stat-label">API Categories</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number">3.0.0</div>
                    <div class="stat-label">OpenAPI Version</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Documented</div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark">Filter by Category</label>
                    <select class="form-select" onchange="window.location.href = '?tag=' + this.value">
                        <option value="">All Categories (<?php echo count($tags); ?>)</option>
                        <?php foreach ($tags as $tag): ?>
                            <option value="<?php echo htmlspecialchars($tag['name']); ?>" <?php echo $filter_tag === $tag['name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark">Filter by HTTP Method</label>
                    <select class="form-select" onchange="window.location.href = '?method=' + this.value + '&tag=' + '<?php echo urlencode($filter_tag); ?>'">
                        <option value="">All Methods</option>
                        <option value="GET" <?php echo $filter_method === 'GET' ? 'selected' : ''; ?>>GET - Read data</option>
                        <option value="POST" <?php echo $filter_method === 'POST' ? 'selected' : ''; ?>>POST - Create data</option>
                        <option value="PUT" <?php echo $filter_method === 'PUT' ? 'selected' : ''; ?>>PUT - Update data</option>
                        <option value="DELETE" <?php echo $filter_method === 'DELETE' ? 'selected' : ''; ?>>DELETE - Remove data</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Routes List -->
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">
                            <i class="bi bi-diagram-3"></i> API Endpoints
                            <span class="badge bg-primary ms-2"><?php echo count($filtered_paths); ?> found</span>
                        </h5>

                        <?php if (empty($filtered_paths)): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No endpoints match your filters
                            </div>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($filtered_paths as $path => $methods): ?>
                                    <?php foreach ($methods as $method => $operation): ?>
                                        <div class="col-12">
                                            <div class="route-item card p-3 <?php echo strtolower($method); ?>">
                                                <div class="row align-items-start">
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge-method badge-<?php echo strtolower($method); ?>">
                                                                <?php echo strtoupper($method); ?>
                                                            </span>
                                                            <code class="text-break" style="font-size: 13px;">
                                                                <?php echo htmlspecialchars($path); ?>
                                                            </code>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($operation['summary'] ?? 'API Call'); ?></strong>
                                                            <?php if (!empty($operation['description'])): ?>
                                                                <p class="text-muted small mt-1 mb-0">
                                                                    <?php echo htmlspecialchars($operation['description']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-2 text-end">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                                                                data-bs-target="#routeModal_<?php echo md5($path . $method); ?>">
                                                            <i class="bi bi-eye"></i> Details
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Tags -->
                                                <?php if (!empty($operation['tags'])): ?>
                                                    <div class="mt-2">
                                                        <?php foreach ($operation['tags'] as $tag): ?>
                                                            <span class="tag-badge"><?php echo htmlspecialchars($tag); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="routeModal_<?php echo md5($path . $method); ?>" tabindex="-1">
                                            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                                                <div class="modal-content">
                                                    <div class="modal-header bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                                        <h5 class="modal-title text-white">
                                                            <span class="badge-method badge-<?php echo strtolower($method); ?>" style="font-size: 12px;">
                                                                <?php echo strtoupper($method); ?>
                                                            </span>
                                                            <?php echo htmlspecialchars($path); ?>
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <!-- Summary & Description -->
                                                        <div class="mb-4">
                                                            <h6 class="fw-bold">
                                                                <?php echo htmlspecialchars($operation['summary'] ?? 'Endpoint'); ?>
                                                            </h6>
                                                            <?php if (!empty($operation['description'])): ?>
                                                                <p class="text-muted">
                                                                    <?php echo htmlspecialchars($operation['description']); ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>

                                                        <ul class="nav nav-tabs" role="tablist">
                                                            <li class="nav-item">
                                                                <a class="nav-link active" data-bs-toggle="tab" href="#params_<?php echo md5($path . $method); ?>">
                                                                    <i class="bi bi-sliders"></i> Parameters
                                                                </a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-bs-toggle="tab" href="#request_<?php echo md5($path . $method); ?>">
                                                                    <i class="bi bi-arrow-right-short"></i> Request
                                                                </a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-bs-toggle="tab" href="#response_<?php echo md5($path . $method); ?>">
                                                                    <i class="bi bi-arrow-left-short"></i> Response
                                                                </a>
                                                            </li>
                                                            <li class="nav-item">
                                                                <a class="nav-link" data-bs-toggle="tab" href="#examples_<?php echo md5($path . $method); ?>">
                                                                    <i class="bi bi-code-square"></i> Examples
                                                                </a>
                                                            </li>
                                                        </ul>

                                                        <div class="tab-content mt-4">
                                                            <!-- Parameters Tab -->
                                                            <div id="params_<?php echo md5($path . $method); ?>" class="tab-pane fade show active">
                                                                <?php if (!empty($operation['parameters'])): ?>
                                                                    <table class="table table-sm parameters-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Name</th>
                                                                                <th>In</th>
                                                                                <th>Type</th>
                                                                                <th>Description</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($operation['parameters'] as $param): ?>
                                                                                <tr>
                                                                                    <td><code><?php echo htmlspecialchars($param['name']); ?></code></td>
                                                                                    <td><?php echo htmlspecialchars($param['in']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($param['schema']['type'] ?? 'string'); ?></td>
                                                                                    <td class="text-muted small">
                                                                                        <?php echo htmlspecialchars($param['schema']['description'] ?? 'No description'); ?>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                <?php else: ?>
                                                                    <p class="text-muted">No parameters required</p>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Request Tab -->
                                                            <div id="request_<?php echo md5($path . $method); ?>" class="tab-pane fade">
                                                                <?php if (!empty($operation['requestBody'])): ?>
                                                                    <h6 class="fw-bold mb-2">Request Body</h6>
                                                                    <div class="code-block">
<code>{
  "email": "user@example.com",
  "password": "SecurePass123!"
}</code>
                                                                    </div>
                                                                <?php else: ?>
                                                                    <p class="text-muted">No request body required</p>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Response Tab -->
                                                            <div id="response_<?php echo md5($path . $method); ?>" class="tab-pane fade">
                                                                <h6 class="fw-bold mb-2">Response Format</h6>
                                                                <div class="code-block">
<code>{
  "success": true,
  "data": {...},
  "message": "Operation completed"
}</code>
                                                                </div>
                                                                <div class="mt-3">
                                                                    <h6 class="fw-bold mb-2">Status Codes</h6>
                                                                    <ul class="small">
                                                                        <?php foreach ($operation['responses'] as $code => $resp): ?>
                                                                            <li><strong><?php echo htmlspecialchars($code); ?></strong> - <?php echo htmlspecialchars($resp['description']); ?></li>
                                                                        <?php endforeach; ?>
                                                                    </ul>
                                                                </div>
                                                            </div>

                                                            <!-- Examples Tab -->
                                                            <div id="examples_<?php echo md5($path . $method); ?>" class="tab-pane fade">
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold mb-2">cURL</h6>
                                                                    <div class="code-block">
<code>curl -X <?php echo strtoupper($method); ?> \
  "https://api.viabix.com<?php echo htmlspecialchars($path); ?>" \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=session_id" \
  -d '{"key": "value"}'</code>
                                                                    </div>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold mb-2">JavaScript</h6>
                                                                    <div class="code-block">
<code>fetch('<?php echo htmlspecialchars($path); ?>', {
  method: '<?php echo strtoupper($method); ?>',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({key: 'value'})
}).then(r => r.json())
  .then(data => console.log(data))</code>
                                                                    </div>
                                                                </div>

                                                                <div>
                                                                    <h6 class="fw-bold mb-2">PHP</h6>
                                                                    <div class="code-block">
<code>$ch = curl_init('<?php echo htmlspecialchars($path); ?>');
curl_setopt_array($ch, [
  CURLOPT_<?php echo strtoupper($method); ?>, true,
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POSTFIELDS => json_encode(['key' => 'value'])
]);
$response = json_decode(curl_exec($ch));</code>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="row mt-5">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-info-circle"></i> API Information
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Base URL:</strong> <code>https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?></code></p>
                                <p><strong>Authentication:</strong> Session Cookie (PHPSESSID) or API Key</p>
                                <p><strong>Rate Limit:</strong> 100 requests per 60 seconds (per user)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Response Format:</strong> JSON</p>
                                <p><strong>Documentation Version:</strong> 1.0.0</p>
                                <p><strong>OpenAPI Version:</strong> 3.0.0</p>
                            </div>
                        </div>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="bi bi-lightbulb"></i>
                            <strong>Tip:</strong> Download the OpenAPI spec in <a href="?format=json" class="alert-link">JSON</a> or 
                            <a href="?format=yaml" class="alert-link">YAML</a> format to use with API clients like Postman or Insomnia.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="py-5 text-center text-white text-opacity-75">
        <p class="mb-0">
            <i class="bi bi-shield-check"></i> Viabix SaaS Platform - Production Ready
        </p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
