<?php
/**
 * Smoke checks estáticos para fluxos críticos.
 *
 * Estes testes não precisam de banco. Eles garantem que os endpoints principais
 * continuem contendo os pontos mínimos de segurança e regra de negócio.
 */

$root = dirname(__DIR__);
$failures = [];

function readProjectFile(string $relativePath): string {
    global $root, $failures;
    $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    if (!is_file($path)) {
        $failures[] = "Arquivo não encontrado: {$relativePath}";
        return '';
    }

    return (string) file_get_contents($path);
}

function assertContains(string $file, string $needle, string $message): void {
    global $failures;
    $content = readProjectFile($file);
    if ($content === '' || strpos($content, $needle) === false) {
        $failures[] = "{$file}: {$message}";
    }
}

function assertMatches(string $file, string $pattern, string $message): void {
    global $failures;
    $content = readProjectFile($file);
    if ($content === '' || !preg_match($pattern, $content)) {
        $failures[] = "{$file}: {$message}";
    }
}

function assertContainsAny(string $file, array $needles, string $message): void {
    global $failures;
    $content = readProjectFile($file);
    foreach ($needles as $needle) {
        if ($content !== '' && strpos($content, $needle) !== false) {
            return;
        }
    }
    $failures[] = "{$file}: {$message}";
}

function assertPhpSyntax(string $file): void {
    global $root, $failures;
    $path = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);
    $php = PHP_BINARY;
    $command = escapeshellarg($php) . ' -l ' . escapeshellarg($path);
    exec($command, $output, $exitCode);
    if ($exitCode !== 0) {
        $failures[] = "{$file}: erro de sintaxe PHP";
    }
}

$phpFiles = [
    'api/config.php',
    'api/list_users.php',
    'api/signup.php',
    'api/login.php',
    'api/anvi.php',
    'Controle_de_projetos/api_mysql.php',
    'api/checkout_create.php',
    'api/webhook_billing.php',
    'api/lib/runtime.php',
    'api/lib/support.php',
    'api/lib/schema.php',
    'api/lib/auth_tenant.php',
    'api/lib/billing.php',
    'api/lib/billing_gateway.php',
];

foreach ($phpFiles as $file) {
    assertPhpSyntax($file);
}

assertContains('api/signup.php', 'viabixValidateCsrfTokenWithInput', 'signup precisa validar CSRF.');
assertContains('api/signup.php', 'tenant_id', 'signup precisa criar dados vinculados ao tenant.');
assertContains('api/signup.php', 'subscriptions', 'signup precisa criar assinatura/trial.');
assertContains('api/signup.php', 'plans', 'signup precisa aplicar plano escolhido.');

assertContains('api/login.php', 'viabixValidateCsrfTokenWithInput', 'login web precisa validar CSRF.');
assertContains('api/login.php', 'viabixGetTenantContext', 'login precisa carregar contexto do tenant.');
assertContains('api/login.php', 'viabixCanAccessTenant', 'login precisa bloquear tenant/assinatura indisponível.');
assertContains('api/login.php', 'viabixGenerateJwt', 'login precisa manter emissão JWT para mobile/API.');

assertContainsAny('api/anvi.php', ['viabixRequireAuthenticatedSession', 'viabixGetAuthenticatedUser'], 'ANVI precisa exigir autenticação.');
assertContains('api/anvi.php', 'viabixValidateCsrfToken', 'ANVI precisa validar CSRF em escrita.');
assertContains('api/anvi.php', 'tenant_id', 'ANVI precisa filtrar por tenant.');
assertContains('api/anvi.php', 'viabixCheckPlanQuota', 'ANVI precisa respeitar limite do plano.');

assertContainsAny('Controle_de_projetos/api_mysql.php', ['viabixRequireAuthenticatedSession', 'viabixGetAuthenticatedUser'], 'projetos precisam exigir autenticação central.');
assertContains('Controle_de_projetos/api_mysql.php', 'viabixValidateCsrfTokenWithInput', 'projetos precisam validar CSRF em escrita.');
assertContains('Controle_de_projetos/api_mysql.php', 'tenant_id', 'projetos precisam filtrar por tenant.');

assertContains('api/checkout_create.php', 'viabixRequireAuthenticatedSession', 'checkout precisa exigir autenticação.');
assertContains('api/checkout_create.php', 'viabixValidateCsrfMiddleware', 'checkout precisa validar CSRF.');
assertContains('api/checkout_create.php', 'viabixResolveCheckoutProvider', 'checkout precisa resolver provedor configurado.');

assertContains('api/webhook_billing.php', 'viabixNormalizeBillingWebhook', 'webhook precisa normalizar evento.');
assertContains('api/webhook_billing.php', 'viabixApplyBillingEvent', 'webhook precisa aplicar evento de billing.');
assertContains('api/lib/billing_gateway.php', 'viabixValidateAsaasWebhook', 'Asaas precisa validar token de webhook quando configurado.');

assertContains('api/list_users.php', 'viabixRequireAdminSession', 'list_users precisa ser restrito a admin.');
assertContains('api/list_users.php', 'tenant_id', 'list_users precisa respeitar tenant.');
assertMatches('api/list_users.php', '/LIMIT\s+200/i', 'list_users precisa ter limite de retorno.');

if ($failures) {
    fwrite(STDERR, "Falhas encontradas:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'OK: smoke_static cobriu signup, login, ANVI, projetos, billing e usuários.' . PHP_EOL;
