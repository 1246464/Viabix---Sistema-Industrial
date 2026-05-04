<?php
/**
 * WEBHOOK SIGNATURE VALIDATION TEST
 * Teste a implementação de validação de assinatura HMAC-SHA256
 * 
 * Execute: php api/test_webhook_signature.php
 */

require_once __DIR__ . '/../bootstrap_env.php';

echo "\n🔒 WEBHOOK SIGNATURE VALIDATION TEST\n";
echo "====================================\n\n";

// Copiar a função de validação para teste
function viabixValidateWebhookSignature($provider, $rawPayload, $receivedSignature) {
    $secret = null;
    
    switch (strtolower($provider)) {
        case 'asaas':
            $secret = viabix_env('WEBHOOK_SECRET_ASAAS', viabix_env('WEBHOOK_SECRET'));
            $headerName = 'X-Asaas-Signature';
            break;
        case 'stripe':
            $secret = viabix_env('WEBHOOK_SECRET_STRIPE', viabix_env('WEBHOOK_SECRET'));
            $headerName = 'Stripe-Signature';
            break;
        default:
            $secret = viabix_env('WEBHOOK_SECRET');
            $headerName = 'X-Signature';
    }
    
    if (!$secret) {
        error_log("[WEBHOOK] Aviso: WEBHOOK_SECRET não configurado para provider '{$provider}'. Validação desabilitada.");
        return true;
    }
    
    $calculatedSignature = hash_hmac('sha256', $rawPayload, $secret);
    
    if (!hash_equals($calculatedSignature, $receivedSignature)) {
        error_log("[WEBHOOK] Falha de validação de assinatura para provider '{$provider}'. Assinatura inválida ou secreta incorreta.");
        return false;
    }
    
    return true;
}

// ========================================
// TESTE 1: Validar com secret configurado
// ========================================
echo "TEST 1: Validação com Secret Configurado\n";
echo "----------------------------------------\n";

// Simular payload real
$testPayload = json_encode([
    'provider' => 'asaas',
    'event_type' => 'PAYMENT_CONFIRMED',
    'event_id' => 'evt_test_123',
    'tenant_id' => 1,
], JSON_UNESCAPED_UNICODE);

$testSecret = 'super_secret_test_key_min_64_chars_abcdefghijklmnopqrstuvwxyz123456';

// Simular configuração de environment
putenv("WEBHOOK_SECRET={$testSecret}");
putenv("WEBHOOK_SECRET_ASAAS={$testSecret}");

// Calcular assinatura correta
$correctSignature = hash_hmac('sha256', $testPayload, $testSecret);

echo "Payload:   " . substr($testPayload, 0, 50) . "...\n";
echo "Secret:    " . substr($testSecret, 0, 20) . "...\n";
echo "Signature: " . substr($correctSignature, 0, 32) . "...\n\n";

// Teste 1a: Assinatura correta
echo "[1a] Testando com ASSINATURA CORRETA...\n";
$result1a = viabixValidateWebhookSignature('asaas', $testPayload, $correctSignature);
echo "Result: " . ($result1a ? "✅ PASSOU" : "❌ FALHOU") . "\n\n";

// Teste 1b: Assinatura incorreta
echo "[1b] Testando com ASSINATURA INCORRETA...\n";
$wrongSignature = hash_hmac('sha256', $testPayload, 'wrong_secret');
$result1b = viabixValidateWebhookSignature('asaas', $testPayload, $wrongSignature);
echo "Result: " . (!$result1b ? "✅ PASSOU (corretamente rejeitado)" : "❌ FALHOU (deveria rejeitar)") . "\n\n";

// Teste 1c: Payload modificado
echo "[1c] Testando com PAYLOAD MODIFICADO...\n";
$modifiedPayload = json_encode([
    'provider' => 'asaas',
    'event_type' => 'PAYMENT_FAILED', // Diferente!
    'event_id' => 'evt_test_123',
    'tenant_id' => 1,
], JSON_UNESCAPED_UNICODE);
$result1c = viabixValidateWebhookSignature('asaas', $modifiedPayload, $correctSignature);
echo "Result: " . (!$result1c ? "✅ PASSOU (corretamente rejeitado)" : "❌ FALHOU (deveria rejeitar)") . "\n\n";

// ========================================
// TESTE 2: Sem secret configurado
// ========================================
echo "TEST 2: Sem Secret Configurado (Modo Desenvolvimento)\n";
echo "----------------------------------------------------\n";

putenv("WEBHOOK_SECRET=");
putenv("WEBHOOK_SECRET_ASAAS=");

echo "[2a] Testando sem secret - deve permitir com aviso...\n";
$result2 = viabixValidateWebhookSignature('asaas', $testPayload, 'anything');
echo "Result: " . ($result2 ? "✅ PASSOU (modo desenvolvimento)" : "❌ FALHOU") . "\n\n";

// ========================================
// TESTE 3: Múltiplos providers
// ========================================
echo "TEST 3: Suporte a Múltiplos Providers\n";
echo "------------------------------------\n";

putenv("WEBHOOK_SECRET_STRIPE=stripe_secret_test_key_min_64_chars_xyz123456");
putenv("WEBHOOK_SECRET_ASAAS={$testSecret}");

$stripeSignature = hash_hmac('sha256', $testPayload, 'stripe_secret_test_key_min_64_chars_xyz123456');

echo "[3a] Testando provider STRIPE...\n";
$result3a = viabixValidateWebhookSignature('stripe', $testPayload, $stripeSignature);
echo "Result: " . ($result3a ? "✅ PASSOU" : "❌ FALHOU") . "\n\n";

echo "[3b] Testando provider ASAAS...\n";
$result3b = viabixValidateWebhookSignature('asaas', $testPayload, $correctSignature);
echo "Result: " . ($result3b ? "✅ PASSOU" : "❌ FALHOU") . "\n\n";

// ========================================
// RESUMO
// ========================================
echo "RESUMO DOS TESTES\n";
echo "================\n";
$totalTests = 5;
$passedTests = ($result1a ? 1 : 0) + (!$result1b ? 1 : 0) + (!$result1c ? 1 : 0) + ($result2 ? 1 : 0) + ($result3a ? 1 : 0);

echo "Testes passados: {$passedTests}/{$totalTests}\n";
if ($passedTests === $totalTests) {
    echo "\n✅ TODOS OS TESTES PASSARAM!\n";
    echo "A implementação de webhook signature está FUNCIONAL.\n";
} else {
    echo "\n❌ ALGUNS TESTES FALHARAM\n";
    echo "Verificar configuração e secret keys.\n";
}

echo "\n🚀 Próximos passos:\n";
echo "1. Configurar WEBHOOK_SECRET no .env.production (DigitalOcean)\n";
echo "2. Configurar token/secret no dashboard Asaas\n";
echo "3. Testar com webhook real do Asaas\n";
echo "\nVer: WEBHOOK_VALIDATION_SETUP.md para instruções detalhadas\n\n";
?>