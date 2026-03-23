<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

viabixEnsureBillingSchema();

$rawPayload = file_get_contents('php://input');
$payload = json_decode($rawPayload, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$normalized = viabixNormalizeBillingWebhook($payload['provider'] ?? 'auto', $payload);

$provider = $normalized['provider'];
$eventId = $normalized['event_id'];
$eventType = $normalized['event_type'];
$tenantId = $normalized['tenant_id'];
$payload = $normalized['payload'];
$ignored = !empty($normalized['ignored']);

if ($eventType === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'event_type é obrigatório.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, processado FROM webhook_events WHERE provider = ? AND event_id = ? LIMIT 1');
    $stmt->execute([$provider, $eventId]);
    $existing = $stmt->fetch();

    if ($existing && (int) $existing['processado'] === 1) {
        echo json_encode(['success' => true, 'message' => 'Evento já processado.', 'event_id' => $eventId]);
        exit;
    }

    $pdo->beginTransaction();

    if ($existing) {
        $stmt = $pdo->prepare('UPDATE webhook_events SET payload = ?, processado = 0, erro_processamento = NULL WHERE id = ?');
        $stmt->execute([json_encode($payload, JSON_UNESCAPED_UNICODE), $existing['id']]);
        $webhookRowId = (int) $existing['id'];
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO webhook_events (provider, event_id, event_type, tenant_id, payload, processado)
             VALUES (?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([$provider, $eventId, $eventType, $tenantId, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        $webhookRowId = (int) $pdo->lastInsertId();
    }

    if ($ignored) {
        $result = [
            'tenant_id' => $tenantId,
            'event_type' => $eventType,
            'ignored' => true,
            'reason' => $normalized['ignore_reason'] ?? 'Evento ignorado por não exigir ação operacional.',
        ];
    } else {
        $result = viabixApplyBillingEvent($provider, $eventType, $payload);
    }

    $stmt = $pdo->prepare('UPDATE webhook_events SET processado = 1, processado_em = NOW(), tenant_id = COALESCE(tenant_id, ?) WHERE id = ?');
    $stmt->execute([$result['tenant_id'] ?? $tenantId, $webhookRowId]);

    $pdo->commit();

    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    if (!empty($_SESSION['tenant_id']) && !empty($result['tenant_id']) && $_SESSION['tenant_id'] === $result['tenant_id']) {
        $tenantContext = viabixGetTenantContext($result['tenant_id']);
        viabixPopulateSession([
            'id' => $_SESSION['user_id'],
            'login' => $_SESSION['user_login'] ?? null,
            'nome' => $_SESSION['user_nome'] ?? null,
            'nivel' => $_SESSION['user_role_raw'] ?? $_SESSION['user_level'] ?? 'admin',
            'tenant_id' => $_SESSION['tenant_id'],
        ], $tenantContext);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Webhook processado com sucesso.',
        'event_id' => $eventId,
        'result' => $result,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if (!empty($webhookRowId)) {
        try {
            $stmt = $pdo->prepare('UPDATE webhook_events SET erro_processamento = ? WHERE id = ?');
            $stmt->execute([$e->getMessage(), $webhookRowId]);
        } catch (Throwable $inner) {
            // Ignorar erro secundário ao registrar falha do webhook.
        }
    }

    logError('Erro ao processar webhook de billing', ['error' => $e->getMessage(), 'event_type' => $eventType, 'provider' => $provider]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Falha ao processar webhook.', 'error' => $e->getMessage()]);
}
?>