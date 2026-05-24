<?php
// Desabilita output buffering (compatível com PHP-FPM e mod_php)
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
ob_implicit_flush(1);
while (ob_get_level() > 0) { ob_end_clean(); }

require_once 'config.php';

// CORS: permitir apenas origens confiáveis
$allowed_origins = [
    'https://viabix.com.br',
    'https://www.viabix.com.br',
    'http://localhost',
    'http://localhost:80',
    'http://localhost:8080'
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

if (session_status() === PHP_SESSION_NONE) {
    session_name('viabix_session');
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "data: " . json_encode(['type' => 'error', 'message' => 'Não autenticado']) . "\n\n";
    exit;
}

// Função para enviar evento SSE
function sendSSE($data) {
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

try {
    $pdo = getConnection();
    $tenantId = getCurrentTenantId();
    $tenantAwareChanges = tenantFilterEnabled($pdo, 'mudancas');

    // ID da última mudança que o cliente viu
    $lastChangeId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

    // Loop para manter a conexão ativa
    $startTime = time();
    $maxDuration = 60; // 60 segundos, depois reconecta

    while (time() - $startTime < $maxDuration) {

        // Busca mudanças novas
        if ($tenantAwareChanges) {
            $stmt = $pdo->prepare("SELECT * FROM mudancas WHERE tenant_id = ? AND id > ? ORDER BY id ASC LIMIT 10");
            $stmt->execute([$tenantId, $lastChangeId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM mudancas WHERE id > ? ORDER BY id ASC LIMIT 10");
            $stmt->execute([$lastChangeId]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            foreach ($rows as $row) {
                sendSSE([
                    'id' => (int)$row['id'],
                    'type' => $row['tipo'],
                    'itemId' => (int)$row['item_id'],
                    'timestamp' => $row['data_hora']
                ]);
                $lastChangeId = (int)$row['id'];
            }
        } else {
            // Envia ping para manter conexão viva
            sendSSE(['type' => 'ping']);
        }

        // Aguarda 0.5s antes de verificar novamente
        usleep(500000);
    }

} catch (Exception $e) {
    sendSSE(['type' => 'error', 'message' => 'Erro na sincronização']);
}
?>
