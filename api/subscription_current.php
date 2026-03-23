<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$user = viabixRequireAuthenticatedSession();
viabixEnsureBillingSchema();

$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();
$subscription = viabixGetCurrentSubscriptionRecord($tenantId);

if (!$subscription) {
    echo json_encode([
        'success' => true,
        'tenant_id' => $tenantId,
        'billing_providers' => viabixGetBillingProviderAvailability(),
        'subscription' => null,
        'message' => 'Nenhuma assinatura encontrada para a conta atual.'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'tenant_id' => $tenantId,
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