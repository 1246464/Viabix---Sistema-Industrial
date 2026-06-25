<?php
/**
 * Checkout, Asaas, webhooks e aplicação de eventos de billing.
 */

if (!defined('VIABIX_APP')) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

function viabixListActivePlans() {
    global $pdo;

    $stmt = $pdo->query(
        "SELECT id, codigo, nome, descricao, preco_mensal, preco_anual,
                limite_usuarios, limite_anvis_mensal, limite_projetos_ativos,
                permite_modulo_anvi, permite_modulo_projetos,
                permite_exportacao, permite_api, permite_sso
         FROM plans
         WHERE status = 'ativo'
         ORDER BY preco_mensal ASC, nome ASC"
    );

    return $stmt->fetchAll();
}

/**
 * Retorna quais provedores de billing estão prontos no ambiente atual.
 */
function viabixGetBillingProviderAvailability() {
    $asaasEnabled = viabixAsaasEnabled();
    $configuredDefault = strtolower((string) viabixEnv('VIABIX_BILLING_PROVIDER', VIABIX_BILLING_PROVIDER));

    if (!in_array($configuredDefault, ['manual', 'asaas'], true)) {
        $configuredDefault = 'manual';
    }

    if ($configuredDefault === 'asaas' && !$asaasEnabled) {
        $configuredDefault = 'manual';
    }

    return [
        'default' => $configuredDefault,
        'manual' => [
            'enabled' => true,
            'mode' => 'local',
            'label' => 'Manual',
        ],
        'asaas' => [
            'enabled' => $asaasEnabled,
            'mode' => VIABIX_ASAAS_ENV,
            'label' => 'Asaas',
            'webhook_url' => viabixBuildAbsoluteUrl('webhook_billing.php'),
        ],
    ];
}

/**
 * Resolve o provedor de checkout, preservando fallback manual quando necessário.
 */
function viabixResolveCheckoutProvider($requestedProvider = null) {
    $requestedProvider = strtolower(trim((string) $requestedProvider));
    $availability = viabixGetBillingProviderAvailability();

    if ($requestedProvider === '' || $requestedProvider === 'auto') {
        return $availability['default'];
    }

    if (!isset($availability[$requestedProvider])) {
        throw new InvalidArgumentException('Provedor de billing não suportado: ' . $requestedProvider);
    }

    if (empty($availability[$requestedProvider]['enabled'])) {
        throw new RuntimeException('O provedor ' . $requestedProvider . ' não está configurado neste ambiente.');
    }

    return $requestedProvider;
}

/**
 * Configuração consolidada do Asaas.
 */
function viabixAsaasConfig() {
    $environment = strtolower((string) viabixEnv('VIABIX_ASAAS_ENV', VIABIX_ASAAS_ENV));
    $baseUrl = $environment === 'production'
        ? 'https://api.asaas.com/v3'
        : 'https://sandbox.asaas.com/api/v3';

    return [
        'environment' => $environment,
        'api_key' => (string) viabixEnv('VIABIX_ASAAS_API_KEY', VIABIX_ASAAS_API_KEY),
        'webhook_token' => (string) viabixEnv('VIABIX_ASAAS_WEBHOOK_TOKEN', VIABIX_ASAAS_WEBHOOK_TOKEN),
        'base_url' => $baseUrl,
    ];
}

/**
 * Indica se o Asaas está disponível para uso no checkout.
 */
function viabixAsaasEnabled() {
    $config = viabixAsaasConfig();

    return trim($config['api_key']) !== '';
}

/**
 * Cliente HTTP JSON simples para integrações externas.
 */
function viabixRequestJson($method, $url, array $headers = [], $payload = null, $timeout = 30) {
    if (!function_exists('curl_init')) {
        throw new RuntimeException('cURL não está disponível no PHP para integração externa.');
    }

    $curl = curl_init($url);
    $httpHeaders = ['Accept: application/json'];

    foreach ($headers as $name => $value) {
        $httpHeaders[] = is_string($name) ? ($name . ': ' . $value) : $value;
    }

    if ($payload !== null) {
        $body = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);
        $httpHeaders[] = 'Content-Type: application/json';
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    }

    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $httpHeaders,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $rawResponse = curl_exec($curl);
    $curlError = curl_error($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($rawResponse === false) {
        throw new RuntimeException('Falha na comunicação HTTP: ' . $curlError);
    }

    $decoded = json_decode($rawResponse, true);

    if ($httpCode >= 400) {
        $message = 'Erro HTTP ' . $httpCode;

        if (is_array($decoded)) {
            if (!empty($decoded['errors'][0]['description'])) {
                $message = $decoded['errors'][0]['description'];
            } elseif (!empty($decoded['message'])) {
                $message = $decoded['message'];
            }
        }

        throw new RuntimeException($message);
    }

    return is_array($decoded) ? $decoded : [];
}

/**
 * Requisição autenticada à API do Asaas.
 */
function viabixAsaasRequest($method, $path, array $payload = []) {
    $config = viabixAsaasConfig();

    if (trim($config['api_key']) === '') {
        throw new RuntimeException('A chave da API do Asaas não está configurada.');
    }

    $url = rtrim($config['base_url'], '/') . '/' . ltrim($path, '/');

    return viabixRequestJson($method, $url, [
        'access_token' => $config['api_key'],
        'User-Agent' => 'Viabix-Billing/1.0',
    ], $payload === [] ? null : $payload);
}

/**
 * Busca dados de cobrança do tenant e do admin principal.
 */
function viabixGetTenantBillingProfile($tenantId) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT
            t.id,
            t.slug,
            t.nome_fantasia,
            t.razao_social,
            t.cnpj,
            t.email_financeiro,
            t.telefone,
            u.id AS admin_user_id,
            u.nome AS admin_nome,
            u.email AS admin_email,
            u.login AS admin_login
         FROM tenants t
         LEFT JOIN usuarios u
           ON u.tenant_id = t.id
          AND u.ativo = 1
          AND u.nivel = 'admin'
         WHERE t.id = ?
         ORDER BY u.id ASC
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);

    return $stmt->fetch() ?: null;
}

/**
 * Garante que o tenant tenha um customer válido no Asaas.
 */
function viabixEnsureAsaasCustomer($tenantId, $subscriptionId = null) {
    global $pdo;

    $customerId = null;

    if ($subscriptionId) {
        $stmt = $pdo->prepare('SELECT gateway_customer_id FROM subscriptions WHERE id = ? LIMIT 1');
        $stmt->execute([$subscriptionId]);
        $customerId = $stmt->fetchColumn() ?: null;
    }

    if (!$customerId) {
        $stmt = $pdo->prepare(
            "SELECT gateway_customer_id
             FROM subscriptions
             WHERE tenant_id = ?
               AND gateway = 'asaas'
               AND gateway_customer_id IS NOT NULL
               AND gateway_customer_id <> ''
             ORDER BY updated_at DESC
             LIMIT 1"
        );
        $stmt->execute([$tenantId]);
        $customerId = $stmt->fetchColumn() ?: null;
    }

    if ($customerId) {
        return $customerId;
    }

    $profile = viabixGetTenantBillingProfile($tenantId);
    if (!$profile) {
        throw new RuntimeException('Tenant não encontrado para cadastro no Asaas.');
    }

    $payload = [
        'name' => $profile['razao_social'] ?: $profile['nome_fantasia'],
        'email' => $profile['email_financeiro'] ?: ($profile['admin_email'] ?: null),
        'phone' => viabixDigitsOnly($profile['telefone'] ?? ''),
        'mobilePhone' => viabixDigitsOnly($profile['telefone'] ?? ''),
        'cpfCnpj' => viabixDigitsOnly($profile['cnpj'] ?? ''),
        'notificationDisabled' => false,
        'externalReference' => $tenantId,
    ];

    $payload = array_filter($payload, static function ($value) {
        return $value !== null && $value !== '';
    });

    $response = viabixAsaasRequest('POST', '/customers', $payload);
    $customerId = $response['id'] ?? null;

    if (!$customerId) {
        throw new RuntimeException('Asaas não retornou o identificador do cliente.');
    }

    if ($subscriptionId) {
        $stmt = $pdo->prepare('UPDATE subscriptions SET gateway = ?, gateway_customer_id = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(['asaas', $customerId, $subscriptionId]);
    }

    return $customerId;
}

/**
 * Cria uma cobrança avulsa no Asaas para o ciclo/plano atual.
 */
function viabixCreateAsaasPayment($tenantId, $subscription, $plan, $amount, $cycle, $localInvoiceId) {
    $customerId = viabixEnsureAsaasCustomer($tenantId, $subscription['id'] ?? null);
    $dueDate = new DateTimeImmutable('now +' . ($cycle === 'anual' ? '3 days' : '2 days'));
    $description = sprintf('Plano %s (%s) - Viabix', $plan['nome'], $cycle);

    $payload = [
        'customer' => $customerId,
        'billingType' => 'UNDEFINED',
        'value' => round((float) $amount, 2),
        'dueDate' => $dueDate->format('Y-m-d'),
        'description' => $description,
        'externalReference' => 'invoice:' . $localInvoiceId . '|tenant:' . $tenantId . '|subscription:' . ($subscription['id'] ?? ''),
    ];

    $response = viabixAsaasRequest('POST', '/payments', $payload);

    if (empty($response['id'])) {
        throw new RuntimeException('Asaas não retornou o identificador da cobrança.');
    }

    return [
        'gateway_customer_id' => $customerId,
        'gateway_invoice_id' => $response['id'],
        'numero' => $response['invoiceNumber'] ?? null,
        'url_cobranca' => $response['invoiceUrl'] ?? ($response['bankSlipUrl'] ?? null),
        'vencimento_em' => !empty($response['dueDate']) ? ($response['dueDate'] . ' 23:59:59') : $dueDate->format('Y-m-d 23:59:59'),
        'raw' => $response,
    ];
}

/**
 * Valida o token opcional de webhook do Asaas, quando configurado.
 */
function viabixValidateAsaasWebhook() {
    $config = viabixAsaasConfig();
    $expectedToken = trim((string) $config['webhook_token']);

    if ($expectedToken === '') {
        return;
    }

    $providedToken = viabixGetRequestHeader('asaas-access-token');
    if ($providedToken === null) {
        $providedToken = viabixGetRequestHeader('x-asaas-access-token');
    }

    if (!hash_equals($expectedToken, (string) $providedToken)) {
        throw new RuntimeException('Token do webhook do Asaas inválido.');
    }
}

/**
 * Converte webhooks externos para o formato interno de billing.
 */
function viabixNormalizeBillingWebhook($provider, array $payload) {
    $provider = strtolower(trim((string) $provider));

    if (($provider === '' || $provider === 'auto') && isset($payload['event'], $payload['payment'])) {
        $provider = 'asaas';
    }

    if ($provider !== 'asaas') {
        return [
            'provider' => $provider ?: 'manual',
            'event_id' => trim((string) ($payload['event_id'] ?? (($provider ?: 'manual') . '_' . str_replace('-', '', generateUUID())))),
            'event_type' => trim((string) ($payload['event_type'] ?? '')),
            'tenant_id' => trim((string) ($payload['tenant_id'] ?? '')) ?: null,
            'payload' => $payload,
            'ignored' => false,
        ];
    }

    viabixValidateAsaasWebhook();

    $event = strtoupper(trim((string) ($payload['event'] ?? '')));
    $payment = is_array($payload['payment'] ?? null) ? $payload['payment'] : [];
    $gatewayInvoiceId = trim((string) ($payment['id'] ?? '')) ?: null;
    $externalReference = trim((string) ($payment['externalReference'] ?? ''));
    $localInvoiceId = null;
    $tenantId = null;
    $subscriptionId = null;

    if ($externalReference !== '') {
        if (preg_match('/invoice:(\d+)/', $externalReference, $matches)) {
            $localInvoiceId = (int) $matches[1];
        }
        if (preg_match('/tenant:([a-f0-9\-]{8,})/i', $externalReference, $matches)) {
            $tenantId = $matches[1];
        }
        if (preg_match('/subscription:([a-f0-9\-]{8,})/i', $externalReference, $matches)) {
            $subscriptionId = $matches[1];
        }
    }

    $eventMap = [
        'PAYMENT_CREATED' => 'invoice.pending',
        'PAYMENT_UPDATED' => 'invoice.pending',
        'PAYMENT_BANK_SLIP_VIEWED' => 'invoice.pending',
        'PAYMENT_CHECKOUT_VIEWED' => 'invoice.pending',
        'PAYMENT_AWAITING_RISK_ANALYSIS' => 'invoice.pending',
        'PAYMENT_APPROVED_BY_RISK_ANALYSIS' => 'invoice.pending',
        'PAYMENT_AUTHORIZED' => 'invoice.pending',
        'PAYMENT_CONFIRMED' => 'invoice.paid',
        'PAYMENT_RECEIVED' => 'invoice.paid',
        'PAYMENT_OVERDUE' => 'payment.failed',
        'PAYMENT_DELETED' => 'payment.failed',
        'PAYMENT_BANK_SLIP_CANCELLED' => 'payment.failed',
        'PAYMENT_CREDIT_CARD_CAPTURE_REFUSED' => 'payment.failed',
        'PAYMENT_REPROVED_BY_RISK_ANALYSIS' => 'payment.failed',
        'PAYMENT_REFUNDED' => 'payment.refunded',
        'PAYMENT_PARTIALLY_REFUNDED' => 'payment.refunded',
        'PAYMENT_REFUND_IN_PROGRESS' => 'payment.refunded',
        'PAYMENT_RECEIVED_IN_CASH_UNDONE' => 'payment.refunded',
        'PAYMENT_CHARGEBACK_REQUESTED' => 'payment.refunded',
        'PAYMENT_CHARGEBACK_DISPUTE' => 'payment.refunded',
        'PAYMENT_AWAITING_CHARGEBACK_REVERSAL' => 'payment.refunded',
    ];

    if (!isset($eventMap[$event])) {
        return [
            'provider' => 'asaas',
            'event_id' => 'asaas_' . ($event ?: 'unknown') . '_' . ($gatewayInvoiceId ?: str_replace('-', '', generateUUID())),
            'event_type' => $event ?: 'unknown',
            'tenant_id' => $tenantId,
            'payload' => [
                'raw_event' => $event,
                'raw_payload' => $payload,
                'gateway_invoice_id' => $gatewayInvoiceId,
            ],
            'ignored' => true,
            'ignore_reason' => 'Evento Asaas sem ação operacional no billing interno.',
        ];
    }

    return [
        'provider' => 'asaas',
        'event_id' => 'asaas_' . $event . '_' . ($gatewayInvoiceId ?: str_replace('-', '', generateUUID())),
        'event_type' => $eventMap[$event],
        'tenant_id' => $tenantId,
        'payload' => [
            'invoice_id' => $localInvoiceId,
            'tenant_id' => $tenantId,
            'subscription_id' => $subscriptionId,
            'gateway_invoice_id' => $gatewayInvoiceId,
            'gateway_payment_id' => $gatewayInvoiceId,
            'gateway_customer_id' => $payment['customer'] ?? null,
            'amount' => isset($payment['value']) ? (float) $payment['value'] : null,
            'method' => strtolower((string) ($payment['billingType'] ?? 'asaas')),
            'event_type' => $eventMap[$event],
            'raw_event' => $event,
            'raw_payload' => $payload,
        ],
        'ignored' => false,
    ];
}

/**
 * Garante que a estrutura mínima de billing exista antes de operar rotas comerciais.
 */
function viabixEnsureBillingSchema() {
    $requiredTables = ['tenants', 'plans', 'subscriptions', 'invoices', 'payments', 'subscription_events', 'webhook_events'];

    foreach ($requiredTables as $tableName) {
        if (!viabixHasTable($tableName)) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(503);
            echo json_encode([
                'success' => false,
                'message' => 'Billing indisponível. A migração SaaS ainda não foi aplicada por completo.',
                'missing_table' => $tableName,
            ]);
            exit;
        }
    }
}

require_once __DIR__ . '/billing.php';

/**
 * Atualiza assinatura, tenant e payment a partir de um evento de billing simplificado.
 */
function viabixApplyBillingEvent($provider, $eventType, array $payload) {
    global $pdo;

    $invoiceId = isset($payload['invoice_id']) ? (int) $payload['invoice_id'] : null;
    $gatewayInvoiceId = $payload['gateway_invoice_id'] ?? null;
    $paidAmount = isset($payload['amount']) ? (float) $payload['amount'] : null;

    if (!$invoiceId && !$gatewayInvoiceId) {
        throw new RuntimeException('Webhook sem invoice_id ou gateway_invoice_id.');
    }

    if ($invoiceId) {
        $stmt = $pdo->prepare(
            'SELECT i.*, s.plan_id, s.tenant_id, s.id AS subscription_uuid
             FROM invoices i
             INNER JOIN subscriptions s ON s.id = i.subscription_id
             WHERE i.id = ?
             LIMIT 1'
        );
        $stmt->execute([$invoiceId]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT i.*, s.plan_id, s.tenant_id, s.id AS subscription_uuid
             FROM invoices i
             INNER JOIN subscriptions s ON s.id = i.subscription_id
             WHERE i.gateway_invoice_id = ?
             LIMIT 1'
        );
        $stmt->execute([$gatewayInvoiceId]);
    }

    $invoice = $stmt->fetch();
    if (!$invoice) {
        throw new RuntimeException('Fatura não encontrada para o evento recebido.');
    }

    $tenantId = $invoice['tenant_id'];
    $subscriptionId = $invoice['subscription_uuid'];
    $paymentAmount = $paidAmount ?? (float) $invoice['valor_total'];

    switch ($eventType) {
        case 'invoice.paid':
        case 'payment.confirmed':
        case 'subscription.activated':
            $stmt = $pdo->prepare('UPDATE invoices SET status = ?, valor_pago = ?, pago_em = NOW(), updated_at = NOW() WHERE id = ?');
            $stmt->execute(['paga', $paymentAmount, $invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload, pago_em)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'confirmado',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare(
                "UPDATE subscriptions
                 SET status = 'ativa',
                     gateway = ?,
                     inicio_vigencia = COALESCE(inicio_vigencia, NOW()),
                     fim_vigencia = CASE ciclo WHEN 'anual' THEN DATE_ADD(NOW(), INTERVAL 1 YEAR) ELSE DATE_ADD(NOW(), INTERVAL 1 MONTH) END,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$provider, $subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'ativo', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'invoice.pending':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'pendente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);
            break;

        case 'invoice.failed':
        case 'payment.failed':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'vencida', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'falhou',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'payment.refunded':
            $stmt = $pdo->prepare("UPDATE invoices SET status = 'estornada', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$invoice['id']]);

            $stmt = $pdo->prepare(
                'INSERT INTO payments (tenant_id, invoice_id, gateway_payment_id, metodo, status, valor, payload)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $tenantId,
                $invoice['id'],
                $payload['gateway_payment_id'] ?? ($provider . '_' . str_replace('-', '', generateUUID())),
                $payload['method'] ?? 'manual',
                'estornado',
                $paymentAmount,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
            ]);

            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'inadimplente', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        case 'subscription.canceled':
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = 'cancelada', cancelada_em = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$subscriptionId]);

            $stmt = $pdo->prepare("UPDATE tenants SET status = 'cancelado', cancelado_em = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$tenantId]);
            break;

        default:
            throw new RuntimeException('Tipo de evento não suportado: ' . $eventType);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $subscriptionId,
        $tenantId,
        $eventType,
        'webhook',
        json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    return [
        'tenant_id' => $tenantId,
        'subscription_id' => $subscriptionId,
        'invoice_id' => (int) $invoice['id'],
        'event_type' => $eventType,
    ];
}

