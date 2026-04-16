<?php
// Desabilita output buffering (deve vir antes de headers)
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
ob_implicit_flush(1);

require_once 'config.php';

// Configurar CORS seguro (com whitelist de domínios)
viabixConfigureCors(['GET', 'POST'], ['Content-Type']);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive')

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
    $conn = getConnection();
    $tenantId = getCurrentTenantId();
    $tenantAwareChanges = tenantFilterEnabled($conn, 'mudancas');
    
    // ID da última mudança que o cliente viu
    $lastChangeId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;
    
    // Loop para manter a conexão ativa
    $startTime = time();
    $maxDuration = 60; // 60 segundos, depois reconecta
    
    while (time() - $startTime < $maxDuration) {
        
        // Busca mudanças novas
        if ($tenantAwareChanges) {
            $stmt = $conn->prepare("SELECT * FROM mudancas WHERE tenant_id = ? AND id > ? ORDER BY id ASC LIMIT 10");
            $stmt->bind_param("si", $tenantId, $lastChangeId);
        } else {
            $stmt = $conn->prepare("SELECT * FROM mudancas WHERE id > ? ORDER BY id ASC LIMIT 10");
            $stmt->bind_param("i", $lastChangeId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
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
        
        $stmt->close();
        
        // Aguarda 2 segundos antes de verificar novamente
        sleep(2);
    }
    
    $conn->close();
    
} catch (Exception $e) {
    sendSSE(['type' => 'error', 'message' => 'Erro na sincronização']);
}
?>
