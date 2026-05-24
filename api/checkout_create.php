<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$user = viabixRequireAuthenticatedSession();
viabixEnsureBillingSchema();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();
if (!$tenantId) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Tenant atual não identificado.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$planIdentifier = trim($input['plan_code'] ?? $input['plan_id'] ?? '');
$cycle = trim($input['cycle'] ?? 'mensal');
$requestedProvider = trim($input['provider'] ?? 'auto');

if ($planIdentifier === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Plano é obrigatório para gerar checkout.']);
    exit;
}

if (!in_array($cycle, ['mensal', 'anual'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Ciclo inválido. Use mensal ou anual.']);
    exit;
}

$plan = viabixFindPlan($planIdentifier);
if (!$plan) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Plano não encontrado ou inativo.']);
    exit;
}

$currentSubscription = viabixGetCurrentSubscriptionRecord($tenantId);
if (!$currentSubscription) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Nenhuma assinatura foi encontrada para este tenant.']);
    exit;
}

$amount = $cycle === 'anual' ? (float) $plan['preco_anual'] : (float) $plan['preco_mensal'];

try {
    $provider = viabixResolveCheckoutProvider($requestedProvider);

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE subscriptions
         SET plan_id = ?, ciclo = ?, valor_contratado = ?, gateway = ?, updated_at = NOW()
         WHERE id = ?'
    );
    $stmt->execute([
        $plan['id'],
        $cycle,
        $amount,
        $provider,
        $currentSubscription['id'],
    ]);

    $invoice = viabixCreateInvoiceForSubscription($tenantId, $currentSubscription['id'], $amount, $cycle, $provider);

    $gatewayData = null;
    if ($provider === 'asaas') {
        $gatewayData = viabixCreateAsaasPayment($tenantId, $currentSubscription, $plan, $amount, $cycle, $invoice['id']);

        $stmt = $pdo->prepare(
            'UPDATE invoices
             SET gateway_invoice_id = ?, numero = COALESCE(?, numero), url_cobranca = COALESCE(?, url_cobranca), vencimento_em = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $gatewayData['gateway_invoice_id'],
            $gatewayData['numero'] ?? null,
            $gatewayData['url_cobranca'] ?? null,
            $gatewayData['vencimento_em'],
            $invoice['id'],
        ]);

        $stmt = $pdo->prepare(
            'UPDATE subscriptions
             SET gateway_customer_id = COALESCE(?, gateway_customer_id), updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $gatewayData['gateway_customer_id'] ?? null,
            $currentSubscription['id'],
        ]);

        $invoice['gateway_invoice_id'] = $gatewayData['gateway_invoice_id'];
        $invoice['numero'] = $gatewayData['numero'] ?? $invoice['numero'];
        $invoice['url_cobranca'] = $gatewayData['url_cobranca'] ?? $invoice['url_cobranca'];
        $invoice['vencimento_em'] = $gatewayData['vencimento_em'];
    }

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $currentSubscription['id'],
        $tenantId,
        'checkout_created',
        'sistema',
        json_encode([
            'plan_code' => $plan['codigo'],
            'plan_name' => $plan['nome'],
            'cycle' => $cycle,
            'provider' => $provider,
            'invoice_id' => $invoice['id'],
            'gateway_invoice_id' => $invoice['gateway_invoice_id'] ?? null,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    $pdo->commit();

    viabixLogActivity($user['id'], 'checkout_create', 'Checkout gerado para troca/ativação de plano', 'invoice', (string) $invoice['id']);

    $tenantContext = viabixGetTenantContext($tenantId);
    viabixPopulateSession([
        'id' => $user['id'],
        'login' => $_SESSION['user_login'] ?? $user['login'],
        'nome' => $_SESSION['user_nome'] ?? $user['nome'],
        'nivel' => $_SESSION['user_role_raw'] ?? $_SESSION['user_level'] ?? 'admin',
        'tenant_id' => $tenantId,
    ], $tenantContext);

    echo json_encode([
        'success' => true,
        'message' => $provider === 'asaas'
            ? 'Checkout gerado com sucesso no Asaas.'
            : 'Checkout gerado com sucesso.',
        'provider' => $provider,
        'plan' => [
            'id' => $plan['id'],
            'code' => $plan['codigo'],
            'name' => $plan['nome'],
            'cycle' => $cycle,
            'amount' => $amount,
        ],
        'invoice' => $invoice,
        'checkout' => [
            'mode' => 'redirect',
            'url' => $invoice['url_cobranca'],
            'instructions' => $provider === 'asaas'
                ? [
                    'A cobrança foi criada no Asaas.',
                    'Abra a URL oficial para concluir o pagamento.',
                    'O webhook do Asaas atualizará assinatura e fatura automaticamente.'
                ]
                : [
                    'Gateway ainda em modo manual/mock.',
                    'Use o endpoint api/webhook_billing.php para simular confirmação e fechamento da cobrança.',
                    'Quando o gateway real estiver disponível, esta rota retorna a URL oficial do provedor.'
                ]
        ]
    ]);
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logError('Erro de integração no checkout', ['error' => $e->getMessage(), 'tenant_id' => $tenantId, 'provider' => $requestedProvider]);
    http_response_code(502);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logError('Erro ao gerar checkout', ['error' => $e->getMessage(), 'tenant_id' => $tenantId]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível gerar o checkout.']);
}
?>