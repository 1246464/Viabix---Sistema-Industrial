<?php
/**
 * Onboarding SaaS - cria tenant, usuario administrador inicial e assinatura trial.
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Iniciar sessão e validar CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

viabixInitializeCsrfProtection();

// Decodificar input JSON ANTES de validar CSRF para evitar ler php://input duas vezes
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validate CSRF token (skip for testing mode and mobile apps - header X-Mobile-App)
try {
    viabixValidateCsrfTokenWithInput($input);
} catch (RuntimeException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Validação de segurança falhou. Recarregue a página.']);
    exit;
}

// ======================================================
// CHECK RATE LIMITING (Brute Force Protection)
// ======================================================
$rate_limit_check = viabixCheckIpRateLimit('signup', 3, 300); // 3 attempts per 5 minutes per IP
if (!$rate_limit_check['allowed']) {
    http_response_code(429);
    header('Retry-After: ' . intval($rate_limit_check['reset_in']), true);
    echo json_encode([
        'success' => false,
        'message' => 'Muitas tentativas de cadastro. Tente novamente em ' . intval($rate_limit_check['reset_in']) . ' segundos.',
        'error_code' => 'rate_limit_exceeded',
        'retry_after' => intval($rate_limit_check['reset_in'])
    ]);
    exit;
}

if (!viabixHasTable('tenants') || !viabixHasTable('plans') || !viabixHasTable('subscriptions')) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Onboarding SaaS indisponível. A migração do banco para tenancy e assinatura ainda não foi aplicada.'
    ]);
    exit;
}

if (!viabixHasColumn('usuarios', 'tenant_id') || !viabixHasColumn('usuarios', 'email')) {
    http_response_code(503);
    echo json_encode([
        'success' => false,
        'message' => 'Estrutura de usuários incompatível com onboarding SaaS. Execute a migração da fase 1 antes de liberar cadastro.'
    ]);
    exit;
}

function signupSlugify($value) {
    $value = trim(mb_strtolower($value, 'UTF-8'));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value ?? '');
    $value = trim($value, '-');

    return $value ?: 'empresa';
}

function signupUniqueSlug($baseSlug) {
    global $pdo;

    $slug = $baseSlug;
    $suffix = 1;

    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM tenants WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);

        if (!$stmt->fetch()) {
            return $slug;
        }

        $suffix++;
        $slug = $baseSlug . '-' . $suffix;
    }
}

function signupDefaultPlan() {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id, codigo, nome
         FROM plans
         WHERE status = 'ativo'
         ORDER BY CASE codigo WHEN 'starter' THEN 1 WHEN 'pro' THEN 2 ELSE 3 END, nome ASC
         LIMIT 1"
    );
    $stmt->execute();

    return $stmt->fetch() ?: null;
}

function signupPlanByCode($planCode) {
    global $pdo;

    $stmt = $pdo->prepare(
        "SELECT id, codigo, nome
         FROM plans
         WHERE status = 'ativo' AND codigo = ?
         LIMIT 1"
    );
    $stmt->execute([$planCode]);

    return $stmt->fetch() ?: null;
}

$companyName = trim($input['company_name'] ?? '');
$contactName = trim($input['contact_name'] ?? '');
$login = trim(mb_strtolower($input['login'] ?? '', 'UTF-8'));
$email = trim(mb_strtolower($input['email'] ?? '', 'UTF-8'));
$password = (string) ($input['password'] ?? '');
$phone = trim($input['phone'] ?? '');
$cnpj = trim($input['cnpj'] ?? '');
$requestedPlanCode = trim(mb_strtolower((string) ($input['plan_code'] ?? ''), 'UTF-8'));

if ($companyName === '' || $contactName === '' || $login === '' || $email === '' || $password === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Empresa, nome, login, e-mail e senha são obrigatórios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Informe um e-mail válido.']);
    exit;
}

if (strlen($login) < 3 || strlen($login) > 50) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'O login deve ter entre 3 e 50 caracteres.']);
    exit;
}

if (!preg_match('/^[a-z0-9._-]+$/', $login)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'O login deve usar apenas letras minúsculas, números, ponto, hífen ou underscore.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A senha deve ter no mínimo 8 caracteres.']);
    exit;
}

try {
    $plan = $requestedPlanCode !== '' ? signupPlanByCode($requestedPlanCode) : signupDefaultPlan();

    if (!$plan) {
        http_response_code($requestedPlanCode !== '' ? 422 : 503);
        echo json_encode([
            'success' => false,
            'message' => $requestedPlanCode !== ''
                ? 'O plano selecionado não está disponível para onboarding no momento.'
                : 'Nenhum plano ativo disponível para onboarding.'
        ]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE login = ? LIMIT 1');
    $stmt->execute([$login]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Esse login já está em uso.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Esse e-mail já está cadastrado.']);
        exit;
    }

    $tenantId = generateUUID();
    $userId = generateUUID();
    $subscriptionId = generateUUID();
    $slug = signupUniqueSlug(signupSlugify($companyName));
    $trialDays = 14;
    $now = new DateTimeImmutable('now');
    $trialUntil = $now->modify('+' . $trialDays . ' days')->format('Y-m-d H:i:s');
    $nowFormatted = $now->format('Y-m-d H:i:s');

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO tenants (id, slug, nome_fantasia, razao_social, cnpj, email_financeiro, telefone, status, timezone, moeda, trial_ate, ativado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $tenantId,
        $slug,
        $companyName,
        $companyName,
        $cnpj ?: null,
        $email,
        $phone ?: null,
        'trial',
        'America/Sao_Paulo',
        'BRL',
        $trialUntil,
        $nowFormatted,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO usuarios (id, tenant_id, login, email, nome, senha, nivel, ativo, email_verificado_em)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1, NULL)'
    );
    $stmt->execute([
        $userId,
        $tenantId,
        $login,
        $email,
        $contactName,
        hashPassword($password),
        'admin',
    ]);

    $stmt = $pdo->prepare(
        "INSERT INTO subscriptions (
            id, tenant_id, plan_id, status, gateway, ciclo, quantidade_usuarios_contratados,
            valor_contratado, trial_iniciado_em, trial_ate, inicio_vigencia, fim_vigencia
        ) VALUES (?, ?, ?, 'trial', 'manual', 'mensal', ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([
        $subscriptionId,
        $tenantId,
        $plan['id'],
        1,
        0,
        $nowFormatted,
        $trialUntil,
        $nowFormatted,
        $trialUntil,
    ]);

    $stmt = $pdo->prepare(
        'INSERT INTO subscription_events (subscription_id, tenant_id, tipo_evento, origem, payload)
         VALUES (?, ?, ?, ?, ?)' 
    );
    $stmt->execute([
        $subscriptionId,
        $tenantId,
        'trial_started',
        'sistema',
        json_encode([
            'plan_code' => $plan['codigo'],
            'trial_days' => $trialDays,
            'signup_email' => $email,
        ], JSON_UNESCAPED_UNICODE),
    ]);

    if (viabixHasTable('tenant_settings')) {
        $stmt = $pdo->prepare(
            'INSERT INTO tenant_settings (tenant_id, chave, valor, tipo, updated_by)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$tenantId, 'onboarding_status', 'trial_started', 'texto', $userId]);
        $stmt->execute([$tenantId, 'trial_days', (string) $trialDays, 'numero', $userId]);
    }

    $pdo->commit();

    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    session_regenerate_id(true);
    $tenantContext = viabixGetTenantContext($tenantId);
    viabixPopulateSession([
        'id' => $userId,
        'login' => $login,
        'nome' => $contactName,
        'nivel' => 'admin',
        'tenant_id' => $tenantId,
    ], $tenantContext);

    viabixLogActivity($userId, 'signup_trial', 'Tenant criado via onboarding público', 'tenant', $tenantId);

    // Send welcome email to new user
    $welcomeEmailResult = viabixSendWelcomeEmail(
        $email,
        $contactName,
        (getenv('APP_URL') ?: 'https://app.viabix.com') . '/login.html'
    );
    
    if (!$welcomeEmailResult['success']) {
        // Log email failure but don't block signup
        logError('Email de welcome não foi enviado', [
            'email' => $email,
            'user_id' => $userId,
            'error' => $welcomeEmailResult['message']
        ]);
        
        // Registrar no Sentry
        if (function_exists('viabixSentryMessage')) {
            viabixSentryMessage(
                "Welcome email failed for new signup: {$email}",
                'warning',
                ['user_id' => $userId, 'tenant_id' => $tenantId]
            );
        }
    }

    // Clear rate limit on successful signup
    viabixClearRateLimit('signup');

    echo json_encode([
        'success' => true,
        'message' => 'Conta trial criada com sucesso.',
        'tenant' => [
            'id' => $tenantId,
            'slug' => $slug,
            'nome' => $companyName,
            'status' => 'trial',
            'trial_ate' => $trialUntil,
        ],
        'user' => [
            'id' => $userId,
            'login' => $login,
            'nome' => $contactName,
            'email' => $email,
            'nivel' => 'admin',
        ],
        'subscription' => [
            'id' => $subscriptionId,
            'status' => 'trial',
            'plan_code' => $plan['codigo'],
            'plan_name' => $plan['nome'],
            'trial_days' => $trialDays,
            'trial_ate' => $trialUntil,
        ],
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    logError('Erro no onboarding SaaS', ['error' => $e->getMessage()]);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Não foi possível criar a conta trial.']);
}
?>