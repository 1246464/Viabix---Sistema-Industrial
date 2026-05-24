<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$user = viabixRequireAuthenticatedSession();
viabixEnsureBillingSchema();

$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();
$limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 20;
$invoices = viabixGetInvoicesForTenant($tenantId, $limit);

echo json_encode([
    'success' => true,
    'tenant_id' => $tenantId,
    'invoices' => array_map(static function ($invoice) {
        return [
            'id' => (int) $invoice['id'],
            'number' => $invoice['numero'],
            'status' => $invoice['status'],
            'amount_total' => (float) $invoice['valor_total'],
            'amount_paid' => (float) $invoice['valor_pago'],
            'currency' => $invoice['moeda'],
            'billing_url' => $invoice['url_cobranca'],
            'due_at' => $invoice['vencimento_em'],
            'paid_at' => $invoice['pago_em'],
            'created_at' => $invoice['created_at'],
            'subscription_status' => $invoice['subscription_status'],
            'plan_code' => $invoice['plan_code'],
            'plan_name' => $invoice['plan_name'],
        ];
    }, $invoices),
]);
?>