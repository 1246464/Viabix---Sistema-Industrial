<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$user = viabixRequireAuthenticatedSession();
viabixEnsureBillingSchema();

$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();
$subscription = viabixGetCurrentSubscriptionRecord($tenantId);
$tenantContext = viabixGetTenantContext($tenantId);

function subscriptionDaysUntil($dateValue) {
    if (!$dateValue) {
        return null;
    }

    try {
        $target = new DateTimeImmutable($dateValue);
        $today = new DateTimeImmutable('today');
        return (int) $today->diff($target)->format('%r%a');
    } catch (Throwable $e) {
        return null;
    }
}

if (!$subscription) {
    echo json_encode([
        'success' => true,
        'tenant_id' => $tenantId,
        'tenant' => [
            'id' => $tenantId,
            'nome' => $tenantContext['tenant_nome'] ?? null,
            'status' => $tenantContext['tenant_status'] ?? null,
        ],
        'billing_providers' => viabixGetBillingProviderAvailability(),
        'subscription' => null,
        'message' => 'Nenhuma assinatura encontrada para a conta atual.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'tenant_id' => $tenantId,
    'tenant' => [
        'id' => $tenantId,
        'nome' => $tenantContext['tenant_nome'] ?? null,
        'status' => $tenantContext['tenant_status'] ?? null,
    ],
    'billing_providers' => viabixGetBillingProviderAvailability(),
    'subscription' => [
        'id' => $subscription['id'],
        'status' => $subscription['status'],
        'gateway' => $subscription['gateway'],
        'cycle' => $subscription['ciclo'],
        'quantity_users' => $subscription['quantidade_usuarios_contratados'],
        'amount' => (float) $subscription['valor_contratado'],
        'trial_until' => $subscription['trial_ate'],
        'starts_at' => $subscription['inicio_vigencia'],
        'ends_at' => $subscription['fim_vigencia'],
        'days_remaining' => subscriptionDaysUntil($subscription['status'] === 'trial' ? $subscription['trial_ate'] : $subscription['fim_vigencia']),
        'commercial_state' => $subscription['status'] === 'trial'
            ? 'trial'
            : (in_array($subscription['status'], ['inadimplente', 'suspensa', 'expirada'], true) ? 'payment_required' : 'active'),
        'plan' => [
            'id' => $subscription['plan_id'],
            'code' => $subscription['plan_code'],
            'name' => $subscription['plan_name'],
            'monthly_price' => (float) $subscription['preco_mensal'],
            'annual_price' => (float) $subscription['preco_anual'],
            'features' => [
                'modulo_anvi' => (bool) $subscription['permite_modulo_anvi'],
                'modulo_projetos' => (bool) $subscription['permite_modulo_projetos'],
                'exportacao' => (bool) $subscription['permite_exportacao'],
                'api' => (bool) $subscription['permite_api'],
                'sso' => (bool) $subscription['permite_sso'],
            ]
        ]
    ]
]);
?>
