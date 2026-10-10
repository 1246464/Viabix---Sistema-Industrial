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

function projectRelativePath(string $path): string {
    global $root;
    $relative = substr($path, strlen($root) + 1);
    return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
}

function phpFilesUnder(string $relativeDirectory): array {
    global $root;
    $directory = $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDirectory);
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
            $files[] = projectRelativePath($file->getPathname());
        }
    }

    sort($files);
    return $files;
}

function assertLocalAssetsExist(string $entrypoint): void {
    global $root, $failures;
    $content = readProjectFile($entrypoint);
    if ($content === '') {
        return;
    }

    preg_match_all('/<(?:script|link)\b[^>]*\b(?:src|href)=["\']([^"\']+)["\']/i', $content, $matches);
    foreach (array_unique($matches[1] ?? []) as $reference) {
        if (
            preg_match('#^(?:https?:)?//#i', $reference)
            || str_starts_with($reference, 'data:')
            || str_starts_with($reference, '#')
        ) {
            continue;
        }

        $asset = strtok($reference, '?#');
        if ($asset === false || $asset === '') {
            continue;
        }

        $entrypointDirectory = dirname($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entrypoint));
        $assetPath = $entrypointDirectory . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $asset);
        if (!is_file($assetPath)) {
            $failures[] = "{$entrypoint}: recurso local ausente: {$reference}";
        }
    }
}

$phpFiles = array_merge(
    array_map('projectRelativePath', glob($root . DIRECTORY_SEPARATOR . '*.php') ?: []),
    phpFilesUnder('api'),
    phpFilesUnder('Controle_de_projetos'),
    phpFilesUnder('templates')
);
sort($phpFiles);

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
assertContains('api/login.php', 'session_regenerate_id(true)', 'login precisa renovar o ID da sessão.');
assertContains('api/login.php', 'verifyPassword', 'login precisa usar o helper seguro de verificação de senha.');
assertContains('api/lib/support.php', 'password_verify', 'helper de login precisa validar hash com a API nativa.');

assertContains('api/signup.php', 'hashPassword', 'signup precisa usar o helper seguro de hash de senha.');
assertContains('api/lib/support.php', 'password_hash', 'helper de cadastro precisa gerar hash com a API nativa.');
assertContains('api/signup.php', 'beginTransaction', 'signup precisa criar tenant e usuário em transação.');
assertContains('api/signup.php', 'rollBack', 'signup precisa desfazer a transação em caso de erro.');

assertContainsAny('api/anvi.php', ['viabixRequireAuthenticatedSession', 'viabixGetAuthenticatedUser'], 'ANVI precisa exigir autenticação.');
assertContains('api/anvi.php', 'viabixValidateCsrfToken', 'ANVI precisa validar CSRF em escrita.');
assertContains('api/anvi.php', 'tenant_id', 'ANVI precisa filtrar por tenant.');
assertContains('api/anvi.php', 'viabixCheckPlanQuota', 'ANVI precisa respeitar limite do plano.');

assertContainsAny('Controle_de_projetos/api_mysql.php', ['viabixRequireAuthenticatedSession', 'viabixGetAuthenticatedUser'], 'projetos precisam exigir autenticação central.');
assertContains('Controle_de_projetos/api_mysql.php', 'viabixValidateCsrfTokenWithInput', 'projetos precisam validar CSRF em escrita.');
assertContains('Controle_de_projetos/api_mysql.php', "['source'] ?? '') !== 'jwt'", 'projetos precisam aceitar escrita autenticada por JWT no app nativo.');
assertContains('Controle_de_projetos/api_mysql.php', 'tenant_id', 'projetos precisam filtrar por tenant.');

assertContains('api/checkout_create.php', 'viabixRequireAuthenticatedSession', 'checkout precisa exigir autenticação.');
assertContains('api/checkout_create.php', 'viabixValidateCsrfMiddleware', 'checkout precisa validar CSRF.');
assertContains('api/checkout_create.php', 'viabixResolveCheckoutProvider', 'checkout precisa resolver provedor configurado.');

assertContains('api/webhook_billing.php', 'viabixNormalizeBillingWebhook', 'webhook precisa normalizar evento.');
assertContains('api/webhook_billing.php', 'viabixApplyBillingEvent', 'webhook precisa aplicar evento de billing.');
assertContains('api/webhook_billing.php', 'hash_equals', 'webhook precisa comparar assinaturas em tempo constante.');
assertContains('api/webhook_billing.php', 'beginTransaction', 'webhook precisa processar evento em transação.');
assertContains('api/webhook_billing.php', 'rollBack', 'webhook precisa desfazer a transação em caso de erro.');
assertContains('api/lib/billing_gateway.php', 'viabixValidateAsaasWebhook', 'Asaas precisa validar token de webhook quando configurado.');

assertContains('api/usuarios.php', 'viabixRequireAuthentication', 'usuarios precisa exigir autenticação.');
assertContains('api/usuarios.php', "checkLevel('admin')", 'operações de usuários precisam ser restritas a admin.');
assertContains('api/usuarios.php', 'viabixValidateCsrfMiddleware', 'escritas de usuários precisam validar CSRF.');
assertContains('api/usuarios.php', 'tenant_id', 'usuarios precisa respeitar tenant.');

assertContains('api/healthcheck.php', "'liveness'", 'healthcheck precisa oferecer verificação de liveness sem banco.');
assertContains('api/healthcheck.php', 'http_response_code', 'healthcheck precisa sinalizar falha pela resposta HTTP.');
assertContains('api/healthcheck.php', "'missing_tables'", 'healthcheck precisa validar o schema mínimo.');

assertContains('js/anvi-api-core.js', "'X-CSRF-Token'", 'cliente da API precisa enviar token CSRF em mutações.');
assertContains('login.html', '_csrf_token', 'formulário de login precisa enviar token CSRF.');
assertContains('signup.html', '_csrf_token', 'formulário de cadastro precisa enviar token CSRF.');

$entrypoints = [
    'index.html',
    'login.html',
    'signup.html',
    'dashboard.html',
    'anvi.html',
    'billing.html',
    'admin_saas.html',
    'Controle_de_projetos/index.php',
];
foreach ($entrypoints as $entrypoint) {
    if (!is_file($root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $entrypoint))) {
        $failures[] = "Ponto de entrada ausente: {$entrypoint}";
        continue;
    }
    assertLocalAssetsExist($entrypoint);
}

if ($failures) {
    fwrite(STDERR, "Falhas encontradas:\n- " . implode("\n- ", $failures) . "\n");
    exit(1);
}

echo 'OK: smoke_static validou ' . count($phpFiles) . ' arquivos PHP e os contratos críticos de segurança, SaaS e frontend.' . PHP_EOL;
