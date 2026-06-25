<?php
/**
 * Helpers de planos, assinatura, limites e faturas.
 *
 * Este arquivo depende de `api/config.php` para conexão PDO, helpers de schema
 * e `generateUUID()`. Ele é carregado pelo próprio config no fim da inicialização.
 */

if (!defined('VIABIX_APP')) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

/**
 * Busca o plano por código ou id.
 */
function viabixFindPlan($identifier) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT *
         FROM plans
         WHERE (id = ? OR codigo = ?)
           AND status = 'ativo'
         LIMIT 1"
    );
    $stmt->execute([$identifier, $identifier]);

    return $stmt->fetch() ?: null;
}

/**
 * Busca a assinatura mais relevante do tenant atual.
 */
function viabixGetCurrentSubscriptionRecord($tenantId) {
    global $pdo;

    if (!$tenantId || !viabixHasTable('subscriptions')) {
        return null;
    }

    $stmt = $pdo->prepare(
        "SELECT s.*, p.codigo AS plan_code, p.nome AS plan_name,
                p.preco_mensal, p.preco_anual,
                p.limite_usuarios, p.limite_anvis_mensal, p.limite_projetos_ativos,
                p.permite_modulo_anvi, p.permite_modulo_projetos,
                p.permite_exportacao, p.permite_api, p.permite_sso
         FROM subscriptions s
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE s.tenant_id = ?
         ORDER BY
            CASE s.status
                WHEN 'ativa' THEN 1
                WHEN 'trial' THEN 2
                WHEN 'inadimplente' THEN 3
                WHEN 'suspensa' THEN 4
                WHEN 'cancelada' THEN 5
                ELSE 6
            END,
            s.updated_at DESC,
            s.created_at DESC
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetch() ?: null;
}

/**
 * Valida limites comerciais do plano atual para recursos que crescem por tenant.
 * Retorna allowed=true quando o plano não tem limite definido para o recurso.
 */
function viabixCheckPlanQuota($tenantId, $resource) {
    global $pdo;

    $resource = (string) $resource;
    $subscription = viabixGetCurrentSubscriptionRecord($tenantId);
    if (!$tenantId || !$subscription) {
        return [
            'allowed' => true,
            'limit' => null,
            'used' => 0,
            'resource' => $resource,
            'message' => null,
        ];
    }

    $limitColumn = null;
    $table = null;
    $where = 'tenant_id = ?';

    if ($resource === 'anvis_monthly') {
        $limitColumn = 'limite_anvis_mensal';
        $table = 'anvis';
        $dateColumn = viabixHasColumn('anvis', 'data_criacao')
            ? 'data_criacao'
            : (viabixHasColumn('anvis', 'created_at') ? 'created_at' : null);
        if ($dateColumn) {
            $where .= " AND {$dateColumn} >= DATE_FORMAT(NOW(), '%Y-%m-01')";
        }
    } elseif ($resource === 'active_projects') {
        $limitColumn = 'limite_projetos_ativos';
        $table = 'projetos';
        if (viabixHasColumn('projetos', 'status')) {
            $where .= " AND status NOT IN ('concluido', 'concluído', 'cancelado', 'cancelada', 'arquivado', 'arquivada')";
        }
    } elseif ($resource === 'users') {
        $limitColumn = 'limite_usuarios';
        $table = 'usuarios';
        if (viabixHasColumn('usuarios', 'ativo')) {
            $where .= ' AND ativo = 1';
        }
    }

    if (!$limitColumn || !$table || !viabixHasTable($table) || !viabixHasColumn($table, 'tenant_id')) {
        return [
            'allowed' => true,
            'limit' => null,
            'used' => 0,
            'resource' => $resource,
            'message' => null,
        ];
    }

    $limit = $subscription[$limitColumn] !== null ? (int) $subscription[$limitColumn] : null;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
    $stmt->execute([$tenantId]);
    $used = (int) $stmt->fetchColumn();
    $allowed = $limit === null || $used < $limit;

    return [
        'allowed' => $allowed,
        'limit' => $limit,
        'used' => $used,
        'resource' => $resource,
        'plan_code' => $subscription['plan_code'] ?? null,
        'plan_name' => $subscription['plan_name'] ?? null,
        'message' => $allowed ? null : 'Limite do plano atingido.',
    ];
}

/**
 * Busca faturas do tenant.
 */
function viabixGetInvoicesForTenant($tenantId, $limit = 20) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT i.*, s.status AS subscription_status, p.codigo AS plan_code, p.nome AS plan_name
         FROM invoices i
         INNER JOIN subscriptions s ON s.id = i.subscription_id
         INNER JOIN plans p ON p.id = s.plan_id
         WHERE i.tenant_id = ?
         ORDER BY i.created_at DESC, i.id DESC
         LIMIT ?"
    );
    $stmt->bindValue(1, $tenantId);
    $stmt->bindValue(2, (int) $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

/**
 * Cria uma fatura manual/mocada para permitir integração progressiva do checkout.
 */
function viabixCreateInvoiceForSubscription($tenantId, $subscriptionId, $amount, $billingCycle, $gateway = 'manual', array $gatewayData = []) {
    global $pdo;

    $dueDate = !empty($gatewayData['vencimento_em'])
        ? new DateTimeImmutable($gatewayData['vencimento_em'])
        : new DateTimeImmutable('now +' . ($billingCycle === 'anual' ? '3 days' : '2 days'));
    $invoiceNumber = $gatewayData['numero'] ?? ('INV-' . date('Ymd') . '-' . strtoupper(substr(str_replace('-', '', generateUUID()), 0, 8)));

    $stmt = $pdo->prepare(
        'INSERT INTO invoices (tenant_id, subscription_id, gateway_invoice_id, numero, status, valor_total, valor_pago, moeda, url_cobranca, vencimento_em)
         VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?)'
    );
    $gatewayInvoiceId = $gatewayData['gateway_invoice_id'] ?? ($gateway . '_' . str_replace('-', '', generateUUID()));
    $billingUrl = $gatewayData['url_cobranca'] ?? ('../billing.html?invoice=' . rawurlencode($invoiceNumber));
    $stmt->execute([
        $tenantId,
        $subscriptionId,
        $gatewayInvoiceId,
        $invoiceNumber,
        'pendente',
        $amount,
        'BRL',
        $billingUrl,
        $dueDate->format('Y-m-d H:i:s'),
    ]);

    return [
        'id' => (int) $pdo->lastInsertId(),
        'gateway_invoice_id' => $gatewayInvoiceId,
        'numero' => $invoiceNumber,
        'status' => 'pendente',
        'valor_total' => $amount,
        'moeda' => 'BRL',
        'url_cobranca' => $billingUrl,
        'vencimento_em' => $dueDate->format('Y-m-d H:i:s'),
    ];
}
