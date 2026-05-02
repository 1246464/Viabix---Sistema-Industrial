<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$admin = viabixRequireAdminSession();
viabixEnsureBillingSchema();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

function adminSaasTenantSummary() {
    global $pdo;

    try {
        $stmt = $pdo->query(
            "SELECT
                t.id,
                t.slug,
                t.nome_fantasia,
                t.email_financeiro,
                t.status AS tenant_status,
                t.trial_ate,
                t.ativado_em,
                t.created_at,
                s.id AS subscription_id,
                s.status AS subscription_status,
                s.ciclo,
                s.valor_contratado,
                s.fim_vigencia,
                p.codigo AS plan_code,
                p.nome AS plan_name
             FROM tenants t
             LEFT JOIN subscriptions s ON s.id = (
                SELECT s2.id
                FROM subscriptions s2
                WHERE s2.tenant_id = t.id
                ORDER BY
                    CASE s2.status
                        WHEN 'ativa' THEN 1
                        WHEN 'trial' THEN 2
                        WHEN 'inadimplente' THEN 3
                        WHEN 'suspensa' THEN 4
                        WHEN 'cancelada' THEN 5
                        ELSE 6
                    END,
                    s2.updated_at DESC,
                    s2.created_at DESC
                LIMIT 1
             )
             LEFT JOIN plans p ON p.id = s.plan_id
             ORDER BY t.created_at DESC"
        );

        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        logError('adminSaasTenantSummary', ['error' => $e->getMessage()]);
        return [];
    }
}

function adminSaasTenantInvoices($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT i.id, i.numero, i.status, i.valor_total, i.valor_pago, i.moeda, i.url_cobranca,
                i.vencimento_em, i.pago_em, i.created_at,
                p.codigo AS plan_code, p.nome AS plan_name
         FROM invoices i
         INNER JOIN subscriptions s ON s.id = i.subscription_id
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE i.tenant_id = ?
         ORDER BY i.created_at DESC, i.id DESC
         LIMIT 50"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetchAll();
}

function adminSaasTenantPayments($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id, invoice_id, gateway_payment_id, metodo, status, valor, pago_em, created_at
         FROM payments
         WHERE tenant_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 20"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetchAll();
}

function adminSaasTenantWebhooks($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id, provider, event_id, event_type, processado, processado_em, erro_processamento, created_at
         FROM webhook_events
         WHERE tenant_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 20"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetchAll();
}

function adminSaasIntegrationHealth() {
    global $pdo;

    $providerAvailability = viabixGetBillingProviderAvailability();
    $asaasConfig = viabixAsaasConfig();

    $summary = [
        'default_provider' => $providerAvailability['default'] ?? 'manual',
        'asaas_enabled' => !empty($providerAvailability['asaas']['enabled']),
        'asaas_environment' => $asaasConfig['environment'] ?? 'sandbox',
        'asaas_webhook_token_configured' => trim((string) ($asaasConfig['webhook_token'] ?? '')) !== '',
        'webhooks_last_24h' => 0,
        'webhooks_pending' => 0,
        'webhooks_with_error' => 0,
        'failed_payments' => 0,
        'pending_invoices' => 0,
        'last_webhook_at' => null,
        'last_paid_invoice_at' => null,
    ];

    $stmt = $pdo->query(
        "SELECT
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS webhooks_last_24h,
            SUM(CASE WHEN processado = 0 THEN 1 ELSE 0 END) AS webhooks_pending,
            SUM(CASE WHEN erro_processamento IS NOT NULL AND erro_processamento <> '' THEN 1 ELSE 0 END) AS webhooks_with_error,
            MAX(created_at) AS last_webhook_at
         FROM webhook_events"
    );
    $webhookStats = $stmt->fetch() ?: [];

    $summary['webhooks_last_24h'] = (int) ($webhookStats['webhooks_last_24h'] ?? 0);
    $summary['webhooks_pending'] = (int) ($webhookStats['webhooks_pending'] ?? 0);
    $summary['webhooks_with_error'] = (int) ($webhookStats['webhooks_with_error'] ?? 0);
    $summary['last_webhook_at'] = $webhookStats['last_webhook_at'] ?? null;

    $stmt = $pdo->query(
        "SELECT
            SUM(CASE WHEN status = 'falhou' THEN 1 ELSE 0 END) AS failed_payments,
            MAX(CASE WHEN status = 'confirmado' THEN COALESCE(pago_em, created_at) ELSE NULL END) AS last_paid_invoice_at
         FROM payments"
    );
    $paymentStats = $stmt->fetch() ?: [];
    $summary['failed_payments'] = (int) ($paymentStats['failed_payments'] ?? 0);
    $summary['last_paid_invoice_at'] = $paymentStats['last_paid_invoice_at'] ?? null;

    $stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'pendente'");
    $summary['pending_invoices'] = (int) $stmt->fetchColumn();

    $stmt = $pdo->query(
        "SELECT w.id, w.provider, w.event_id, w.event_type, w.processado, w.processado_em, w.erro_processamento, w.created_at,
                t.id AS tenant_id, t.nome_fantasia, t.slug
         FROM webhook_events w
         LEFT JOIN tenants t ON t.id = w.tenant_id
         ORDER BY w.created_at DESC, w.id DESC
         LIMIT 20"
    );
    $recentWebhooks = $stmt->fetchAll();

    $stmt = $pdo->query(
        "SELECT p.id, p.invoice_id, p.gateway_payment_id, p.metodo, p.status, p.valor, p.pago_em, p.created_at,
            s.gateway AS provider,
                t.id AS tenant_id, t.nome_fantasia,
                i.numero AS invoice_number
         FROM payments p
         INNER JOIN invoices i ON i.id = p.invoice_id
         INNER JOIN subscriptions s ON s.id = i.subscription_id
         INNER JOIN tenants t ON t.id = p.tenant_id
         ORDER BY p.created_at DESC, p.id DESC
         LIMIT 20"
    );
    $recentPayments = $stmt->fetchAll();

    return [
        'summary' => $summary,
        'providers' => $providerAvailability,
        'recent_webhooks' => $recentWebhooks,
        'recent_payments' => $recentPayments,
    ];
}

function adminSaasListPayments($filters = []) {
    global $pdo;

    $provider = strtolower(trim((string) ($filters['provider'] ?? '')));
    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $period = strtolower(trim((string) ($filters['period'] ?? '7d')));
    $limit = isset($filters['limit']) ? max(1, min(200, (int) $filters['limit'])) : 100;

    $where = [];
    $params = [];

    if ($provider !== '' && $provider !== 'all') {
        $where[] = 's.gateway = ?';
        $params[] = $provider;
    }

    if ($status !== '' && $status !== 'all') {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }

    if ($period === '24h') {
        $where[] = 'COALESCE(p.pago_em, p.created_at) >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
    } elseif ($period === '7d') {
        $where[] = 'COALESCE(p.pago_em, p.created_at) >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($period === '30d') {
        $where[] = 'COALESCE(p.pago_em, p.created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }

    $sql =
        "SELECT p.id, p.invoice_id, p.gateway_payment_id, p.metodo, p.status, p.valor, p.pago_em, p.created_at,
                s.gateway AS provider,
                t.id AS tenant_id, t.nome_fantasia,
                i.numero AS invoice_number
         FROM payments p
         INNER JOIN invoices i ON i.id = p.invoice_id
         INNER JOIN subscriptions s ON s.id = i.subscription_id
         INNER JOIN tenants t ON t.id = p.tenant_id";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY COALESCE(p.pago_em, p.created_at) DESC, p.id DESC LIMIT ?';

    $stmt = $pdo->prepare($sql);

    $index = 1;
    foreach ($params as $param) {
        $stmt->bindValue($index++, $param);
    }
    $stmt->bindValue($index, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'filters' => [
            'provider' => $provider ?: 'all',
            'status' => $status ?: 'all',
            'period' => $period ?: '7d',
            'limit' => $limit,
        ],
    ];
}

function adminSaasListWebhooks($filters = []) {
    global $pdo;

    $provider = strtolower(trim((string) ($filters['provider'] ?? '')));
    $status = strtolower(trim((string) ($filters['status'] ?? '')));
    $period = strtolower(trim((string) ($filters['period'] ?? '7d')));
    $limit = isset($filters['limit']) ? max(1, min(200, (int) $filters['limit'])) : 100;

    $where = [];
    $params = [];

    if ($provider !== '' && $provider !== 'all') {
        $where[] = 'w.provider = ?';
        $params[] = $provider;
    }

    if ($status === 'error') {
        $where[] = "w.erro_processamento IS NOT NULL AND w.erro_processamento <> ''";
    } elseif ($status === 'processed') {
        $where[] = "w.processado = 1 AND (w.erro_processamento IS NULL OR w.erro_processamento = '')";
    } elseif ($status === 'pending') {
        $where[] = "w.processado = 0 AND (w.erro_processamento IS NULL OR w.erro_processamento = '')";
    }

    if ($period === '24h') {
        $where[] = 'w.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
    } elseif ($period === '7d') {
        $where[] = 'w.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
    } elseif ($period === '30d') {
        $where[] = 'w.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
    }

    $sql =
        "SELECT w.id, w.provider, w.event_id, w.event_type, w.processado, w.processado_em, w.erro_processamento, w.created_at,
                t.id AS tenant_id, t.nome_fantasia, t.slug
         FROM webhook_events w
         LEFT JOIN tenants t ON t.id = w.tenant_id";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY w.created_at DESC, w.id DESC LIMIT ?';

    $stmt = $pdo->prepare($sql);

    $index = 1;
    foreach ($params as $param) {
        $stmt->bindValue($index++, $param);
    }
    $stmt->bindValue($index, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return [
        'items' => $stmt->fetchAll(),
        'filters' => [
            'provider' => $provider ?: 'all',
            'status' => $status ?: 'all',
            'period' => $period ?: '7d',
            'limit' => $limit,
        ],
    ];
}

function adminSaasReprocessWebhook($webhookId, $admin) {
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT id, provider, event_id, event_type, tenant_id, payload, processado, erro_processamento
         FROM webhook_events
         WHERE id = ?
         LIMIT 1'
    );
    $stmt->execute([$webhookId]);
    $webhook = $stmt->fetch();

    if (!$webhook) {
        throw new RuntimeException('Webhook não encontrado.');
    }

    if (empty($webhook['erro_processamento'])) {
        throw new RuntimeException('Esse webhook não está marcado com erro para reprocessamento.');
    }

    $payload = json_decode($webhook['payload'] ?? 'null', true);
    if (!is_array($payload)) {
        throw new RuntimeException('Payload salvo do webhook está inválido.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE webhook_events
         SET processado = 0, processado_em = NULL, erro_processamento = NULL
         WHERE id = ?'
    );
    $stmt->execute([$webhookId]);

    $result = viabixApplyBillingEvent($webhook['provider'], $webhook['event_type'], $payload);

    $stmt = $pdo->prepare(
        'UPDATE webhook_events
         SET processado = 1,
             processado_em = NOW(),
             tenant_id = COALESCE(tenant_id, ?)
         WHERE id = ?'
    );
    $stmt->execute([$result['tenant_id'] ?? $webhook['tenant_id'], $webhookId]);

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $result['subscription_id'] ?? null,
        $result['tenant_id'] ?? $webhook['tenant_id'],
        'admin_webhook_reprocessed',
        'admin',
        json_encode([
            'webhook_id' => (int) $webhookId,
            'provider' => $webhook['provider'],
            'event_id' => $webhook['event_id'],
            'event_type' => $webhook['event_type'],
            'admin_user' => $admin['login'],
        ], JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

    viabixLogActivity($admin['id'], 'admin_webhook_reprocess', 'Reprocessou webhook com erro', 'webhook', (string) $webhookId);

    return [
        'webhook_id' => (int) $webhookId,
        'event_id' => $webhook['event_id'],
        'event_type' => $webhook['event_type'],
        'provider' => $webhook['provider'],
        'tenant_id' => $result['tenant_id'] ?? $webhook['tenant_id'],
    ];
}

function adminSaasTenantDetail($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT t.*, s.id AS subscription_id, s.status AS subscription_status, s.ciclo,
                s.valor_contratado, s.inicio_vigencia, s.fim_vigencia, s.cancelada_em,
                p.codigo AS plan_code, p.nome AS plan_name,
                p.preco_mensal, p.preco_anual
         FROM tenants t
         LEFT JOIN subscriptions s ON s.id = (
            SELECT s2.id
            FROM subscriptions s2
            WHERE s2.tenant_id = t.id
            ORDER BY
                CASE s2.status
                    WHEN 'ativa' THEN 1
                    WHEN 'trial' THEN 2
                    WHEN 'inadimplente' THEN 3
                    WHEN 'suspensa' THEN 4
                    WHEN 'cancelada' THEN 5
                    ELSE 6
                END,
                s2.updated_at DESC,
                s2.created_at DESC
            LIMIT 1
         )
         LEFT JOIN plans p ON p.id = s.plan_id
         WHERE t.id = ?
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        return null;
    }

    $users = [];
    if (viabixHasTable('usuarios')) {
        $stmt = $pdo->prepare(
            'SELECT id, login, email, nome, nivel, ativo, ultimo_acesso, data_criacao
             FROM usuarios
             WHERE tenant_id = ?
             ORDER BY data_criacao DESC
             LIMIT 20'
        );
        $stmt->execute([$tenantId]);
        $users = $stmt->fetchAll();
    }

    $stmt = $pdo->prepare(
        'SELECT id, tipo_evento, origem, created_at
         FROM subscription_events
         WHERE tenant_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT 20'
    );
    $stmt->execute([$tenantId]);
    $events = $stmt->fetchAll();

    return [
        'tenant' => $tenant,
        'users' => $users,
        'events' => $events,
        'invoices' => adminSaasTenantInvoices($tenantId),
        'payments' => adminSaasTenantPayments($tenantId),
        'webhooks' => adminSaasTenantWebhooks($tenantId),
    ];
}

try {
    switch ($action) {
        case 'overview':
            $tenants = adminSaasTenantSummary();
            $plans = viabixListActivePlans();
            $integration = adminSaasIntegrationHealth();

            echo json_encode([
                'success' => true,
                'summary' => [
                    'tenants_total' => count($tenants),
                    'tenants_ativos' => count(array_filter($tenants, static fn($row) => ($row['tenant_status'] ?? null) === 'ativo')),
                    'tenants_trial' => count(array_filter($tenants, static fn($row) => ($row['tenant_status'] ?? null) === 'trial')),
                    'tenants_inadimplentes' => count(array_filter($tenants, static fn($row) => ($row['tenant_status'] ?? null) === 'inadimplente')),
                ],
                'plans' => $plans,
                'tenants' => $tenants,
                'integration' => $integration,
            ]);
            break;

        case 'detail':
            $tenantId = trim($_GET['tenant_id'] ?? '');
            if ($tenantId === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'tenant_id é obrigatório.']);
                exit;
            }

            $detail = adminSaasTenantDetail($tenantId);
            if (!$detail) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Tenant não encontrado.']);
                exit;
            }

            echo json_encode(['success' => true] + $detail);
            break;

        case 'webhooks':
            $result = adminSaasListWebhooks([
                'provider' => $_GET['provider'] ?? '',
                'status' => $_GET['status'] ?? '',
                'period' => $_GET['period'] ?? '7d',
                'limit' => $_GET['limit'] ?? 100,
            ]);

            echo json_encode([
                'success' => true,
                'webhooks' => $result['items'],
                'filters' => $result['filters'],
            ]);
            break;

        case 'payments':
            $result = adminSaasListPayments([
                'provider' => $_GET['provider'] ?? '',
                'status' => $_GET['status'] ?? '',
                'period' => $_GET['period'] ?? '7d',
                'limit' => $_GET['limit'] ?? 100,
            ]);

            echo json_encode([
                'success' => true,
                'payments' => $result['items'],
                'filters' => $result['filters'],
            ]);
            break;

        case 'update_status':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $tenantId = trim($input['tenant_id'] ?? '');
            $tenantStatus = trim($input['tenant_status'] ?? '');
            $subscriptionStatus = trim($input['subscription_status'] ?? '');

            if ($tenantId === '' || $tenantStatus === '' || $subscriptionStatus === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'tenant_id, tenant_status e subscription_status são obrigatórios.']);
                exit;
            }

            $allowedTenant = ['trial', 'ativo', 'suspenso', 'inadimplente', 'cancelado'];
            $allowedSubscription = ['trial', 'ativa', 'inadimplente', 'suspensa', 'cancelada', 'expirada'];

            if (!in_array($tenantStatus, $allowedTenant, true) || !in_array($subscriptionStatus, $allowedSubscription, true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Status informado é inválido.']);
                exit;
            }

            $subscription = viabixGetCurrentSubscriptionRecord($tenantId);
            if (!$subscription) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Assinatura do tenant não encontrada.']);
                exit;
            }

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE tenants SET status = ?, updated_at = NOW(), cancelado_em = CASE WHEN ? = "cancelado" THEN NOW() ELSE cancelado_em END WHERE id = ?');
            $stmt->execute([$tenantStatus, $tenantStatus, $tenantId]);

            $stmt = $pdo->prepare('UPDATE subscriptions SET status = ?, updated_at = NOW(), cancelada_em = CASE WHEN ? = "cancelada" THEN NOW() ELSE cancelada_em END WHERE id = ?');
            $stmt->execute([$subscriptionStatus, $subscriptionStatus, $subscription['id']]);

            $stmt = $pdo->prepare('INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $subscription['id'],
                $tenantId,
                'admin_status_update',
                'admin',
                json_encode([
                    'tenant_status' => $tenantStatus,
                    'subscription_status' => $subscriptionStatus,
                    'admin_user' => $admin['login'],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $pdo->commit();

            viabixLogActivity($admin['id'], 'admin_tenant_status_update', 'Atualizou status operacional de tenant', 'tenant', $tenantId);

            echo json_encode(['success' => true, 'message' => 'Status do tenant atualizado com sucesso.']);
            break;

        case 'change_plan':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $tenantId = trim($input['tenant_id'] ?? '');
            $planIdentifier = trim($input['plan_code'] ?? $input['plan_id'] ?? '');
            $cycle = trim($input['cycle'] ?? 'mensal');

            if ($tenantId === '' || $planIdentifier === '') {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'tenant_id e plano são obrigatórios.']);
                exit;
            }

            if (!in_array($cycle, ['mensal', 'anual'], true)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Ciclo inválido.']);
                exit;
            }

            $subscription = viabixGetCurrentSubscriptionRecord($tenantId);
            $plan = viabixFindPlan($planIdentifier);

            if (!$subscription || !$plan) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Assinatura ou plano não encontrado.']);
                exit;
            }

            $amount = $cycle === 'anual' ? (float) $plan['preco_anual'] : (float) $plan['preco_mensal'];

            $pdo->beginTransaction();

            $stmt = $pdo->prepare('UPDATE subscriptions SET plan_id = ?, ciclo = ?, valor_contratado = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([$plan['id'], $cycle, $amount, $subscription['id']]);

            $stmt = $pdo->prepare('INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $subscription['id'],
                $tenantId,
                'admin_plan_change',
                'admin',
                json_encode([
                    'plan_code' => $plan['codigo'],
                    'plan_name' => $plan['nome'],
                    'cycle' => $cycle,
                    'admin_user' => $admin['login'],
                ], JSON_UNESCAPED_UNICODE),
            ]);

            $pdo->commit();

            viabixLogActivity($admin['id'], 'admin_plan_change', 'Alterou plano do tenant', 'tenant', $tenantId);

            echo json_encode(['success' => true, 'message' => 'Plano alterado com sucesso.']);
            break;

        case 'reprocess_webhook':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $webhookId = isset($input['webhook_id']) ? (int) $input['webhook_id'] : 0;

            if ($webhookId <= 0) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'webhook_id é obrigatório.']);
                exit;
            }

            try {
                $result = adminSaasReprocessWebhook($webhookId, $admin);
                echo json_encode([
                    'success' => true,
                    'message' => 'Webhook reprocessado com sucesso.',
                    'result' => $result,
                ]);
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $stmt = $pdo->prepare('UPDATE webhook_events SET erro_processamento = ? WHERE id = ?');
                $stmt->execute([$e->getMessage(), $webhookId]);

                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Ação inválida.']);
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logError('Erro em admin_saas.php', ['action' => $action, 'error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do painel admin.']);
}
?>