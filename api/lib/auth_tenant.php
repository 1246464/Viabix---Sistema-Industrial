<?php
/**
 * Autenticação legada, sessão, tenant e contexto SaaS.
 */

if (!defined('VIABIX_APP')) {
    http_response_code(403);
    exit('Acesso direto não permitido.');
}

function viabixNormalizeUserLevel($level) {
    $map = [
        'visitante' => 'visitante',
        'visualizador' => 'visitante',
        'usuario' => 'usuario',
        'lider' => 'usuario',
        'financeiro' => 'usuario',
        'admin' => 'admin',
        'owner' => 'admin',
        'suporte' => 'admin',
    ];

    return $map[$level] ?? 'visitante';
}

/**
 * Busca um usuário por login, mantendo compatibilidade com schema legado e SaaS.
 */
function viabixFindUserForAuth($login) {
    global $pdo;

    $select = [
        'id',
        'login',
        'nome',
        'senha',
        'nivel',
        'ativo',
    ];

    if (viabixHasColumn('usuarios', 'email')) {
        $select[] = 'email';
    }
    if (viabixHasColumn('usuarios', 'tenant_id')) {
        $select[] = 'tenant_id';
    }
    if (viabixHasColumn('usuarios', 'ultimo_acesso')) {
        $select[] = 'ultimo_acesso';
    }

    $where = 'login = ?';
    $params = [$login];

    if (viabixHasColumn('usuarios', 'email')) {
        $where = '(login = ? OR email = ?)';
        $params[] = $login;
    }

    $sql = 'SELECT ' . implode(', ', $select) . ' FROM usuarios WHERE ' . $where . ' ORDER BY ativo DESC, id ASC LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetch() ?: null;
}

/**
 * Carrega tenant, assinatura e plano quando a base ja estiver preparada para SaaS.
 */
function viabixGetTenantContext($tenantId) {
    global $pdo;

    $context = [
        'tenant_id' => $tenantId,
        'tenant_slug' => null,
        'tenant_nome' => null,
        'tenant_status' => null,
        'subscription_id' => null,
        'subscription_status' => null,
        'subscription_cycle' => null,
        'plan_id' => null,
        'plan_code' => null,
        'plan_name' => null,
        'limits' => [
            'users' => null,
            'anvis_monthly' => null,
            'active_projects' => null,
        ],
        'features' => [
            'modulo_anvi' => true,
            'modulo_projetos' => true,
            'exportacao' => true,
            'api' => false,
            'sso' => false,
        ],
    ];

    if (!$tenantId || !viabixHasTable('tenants')) {
        return $context;
    }

    $stmt = $pdo->prepare(
        "SELECT id, slug, nome_fantasia, status
         FROM tenants
         WHERE id = ?
         LIMIT 1"
    );
    $stmt->execute([$tenantId]);
    $tenant = $stmt->fetch();

    if (!$tenant) {
        return $context;
    }

    $context['tenant_slug'] = $tenant['slug'] ?? null;
    $context['tenant_nome'] = $tenant['nome_fantasia'] ?? null;
    $context['tenant_status'] = $tenant['status'] ?? null;

    if (!viabixHasTable('subscriptions') || !viabixHasTable('plans')) {
        return $context;
    }

    $stmt = $pdo->prepare(
        "SELECT
            s.id AS subscription_id,
            s.status AS subscription_status,
            s.ciclo AS subscription_cycle,
            p.id AS plan_id,
            p.codigo AS plan_code,
            p.nome AS plan_name,
            p.limite_usuarios,
            p.limite_anvis_mensal,
            p.limite_projetos_ativos,
            p.permite_modulo_anvi,
            p.permite_modulo_projetos,
            p.permite_exportacao,
            p.permite_api,
            p.permite_sso
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
    $subscription = $stmt->fetch();

    if (!$subscription) {
        return $context;
    }

    $context['subscription_id'] = $subscription['subscription_id'];
    $context['subscription_status'] = $subscription['subscription_status'];
    $context['subscription_cycle'] = $subscription['subscription_cycle'];
    $context['plan_id'] = $subscription['plan_id'];
    $context['plan_code'] = $subscription['plan_code'];
    $context['plan_name'] = $subscription['plan_name'];
    $context['limits'] = [
        'users' => $subscription['limite_usuarios'] !== null ? (int) $subscription['limite_usuarios'] : null,
        'anvis_monthly' => $subscription['limite_anvis_mensal'] !== null ? (int) $subscription['limite_anvis_mensal'] : null,
        'active_projects' => $subscription['limite_projetos_ativos'] !== null ? (int) $subscription['limite_projetos_ativos'] : null,
    ];
    $context['features'] = [
        'modulo_anvi' => (bool) $subscription['permite_modulo_anvi'],
        'modulo_projetos' => (bool) $subscription['permite_modulo_projetos'],
        'exportacao' => (bool) $subscription['permite_exportacao'],
        'api' => (bool) $subscription['permite_api'],
        'sso' => (bool) $subscription['permite_sso'],
    ];

    return $context;
}

/**
 * Determina se o acesso SaaS esta liberado para o tenant atual.
 */
function viabixCanAccessTenant($tenantContext) {
    if (empty($tenantContext['tenant_id'])) {
        return [true, null];
    }

    $tenantStatus = $tenantContext['tenant_status'] ?? null;
    if ($tenantStatus && !in_array($tenantStatus, ['trial', 'ativo'], true)) {
        return [false, 'Conta suspensa ou indisponível. Contate o administrador.'];
    }

    $subscriptionStatus = $tenantContext['subscription_status'] ?? null;
    if ($subscriptionStatus && !in_array($subscriptionStatus, ['trial', 'ativa'], true)) {
        return [false, 'Assinatura inativa ou bloqueada. Regularize o plano para continuar.'];
    }

    return [true, null];
}

/**
 * Persiste o contexto principal do usuário e do tenant na sessão.
 */
function viabixPopulateSession($user, $tenantContext = []) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'];
    $_SESSION['user_nome'] = $user['nome'];
    $_SESSION['user_level'] = viabixNormalizeUserLevel($user['nivel']);
    $_SESSION['user_role_raw'] = $user['nivel'];
    $_SESSION['login'] = $user['login'];
    $_SESSION['nome'] = $user['nome'];
    $_SESSION['nivel'] = $_SESSION['user_level'];
    $_SESSION['user_role'] = $_SESSION['user_level'];
    $_SESSION['login_time'] = time();

    $_SESSION['tenant_id'] = $tenantContext['tenant_id'] ?? ($user['tenant_id'] ?? null);
    $_SESSION['tenant_slug'] = $tenantContext['tenant_slug'] ?? null;
    $_SESSION['tenant_nome'] = $tenantContext['tenant_nome'] ?? null;
    $_SESSION['tenant_status'] = $tenantContext['tenant_status'] ?? null;

    $_SESSION['subscription_id'] = $tenantContext['subscription_id'] ?? null;
    $_SESSION['subscription_status'] = $tenantContext['subscription_status'] ?? null;
    $_SESSION['subscription_cycle'] = $tenantContext['subscription_cycle'] ?? null;

    $_SESSION['plan_id'] = $tenantContext['plan_id'] ?? null;
    $_SESSION['plan_code'] = $tenantContext['plan_code'] ?? null;
    $_SESSION['plan_name'] = $tenantContext['plan_name'] ?? null;
    $_SESSION['limits'] = $tenantContext['limits'] ?? [
        'users' => null,
        'anvis_monthly' => null,
        'active_projects' => null,
    ];
    $_SESSION['features'] = $tenantContext['features'] ?? [
        'modulo_anvi' => true,
        'modulo_projetos' => true,
        'exportacao' => true,
        'api' => false,
        'sso' => false,
    ];
}

/**
 * Limpa a sessão autenticada quando o usuário nao pode mais acessar.
 */
function viabixClearAuthenticatedSession() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

/**
 * Retorna o tenant atual da sessão, quando existir.
 */
function viabixCurrentTenantId() {
    return $_SESSION['tenant_id'] ?? null;
}

/**
 * Registra log de atividade com tenant quando o schema já suportar esse contexto.
 */
function viabixLogActivity($userId, $action, $details, $entity = null, $entityId = null) {
    global $pdo;

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if (viabixHasColumn('logs_atividade', 'tenant_id') && viabixHasColumn('logs_atividade', 'entidade')) {
        $stmt = $pdo->prepare(
            'INSERT INTO logs_atividade (tenant_id, usuario_id, acao, entidade, entidade_id, detalhes, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            viabixCurrentTenantId(),
            $userId,
            $action,
            $entity,
            $entityId,
            $details,
            $ipAddress,
            $userAgent,
        ]);
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO logs_atividade (usuario_id, acao, detalhes, ip_address, user_agent)
         VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $action, $details, $ipAddress, $userAgent]);
}

/**
 * Valida autenticação de múltiplas fontes:
 * 1. Sessão PHP ($_SESSION['user_id'])
 * 2. JWT Token (Authorization header, Cookie, GET param)
 * 
 * Retorna array com dados do usuário ou null se não autenticado
 */
if (!function_exists('viabixGetAuthenticatedUser')) {
function viabixGetAuthenticatedUser() {
    global $pdo;
    
    // 1. Tenta sessão PHP primeiro (melhor performance)
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }
    
    if (!empty($_SESSION['user_id'])) {
        // Retorna dados da sessão
        return [
            'id' => $_SESSION['user_id'],
            'login' => $_SESSION['user_login'] ?? $_SESSION['login'] ?? null,
            'nome' => $_SESSION['user_nome'] ?? $_SESSION['nome'] ?? null,
            'nivel' => $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? $_SESSION['user_role'] ?? null,
            'nivel_original' => $_SESSION['user_role_raw'] ?? $_SESSION['nivel'] ?? $_SESSION['user_level'] ?? null,
            'tenant_id' => $_SESSION['tenant_id'] ?? null,
            'source' => 'session'
        ];
    }
    
    // 2. Tenta JWT Token (para mobile apps)
    if (function_exists('viabixValidateJwtFromRequest')) {
        $payload = viabixValidateJwtFromRequest();
        if ($payload && !empty($payload['user_id'])) {
            // Busca dados do usuário do banco
            try {
                $stmt = $pdo->prepare('SELECT id, login, nome, nivel, tenant_id FROM usuarios WHERE id = ? LIMIT 1');
                $stmt->execute([$payload['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Use JWT tenant_id if provided, else use database tenant_id
                    if (!empty($payload['tenant_id'])) {
                        $user['tenant_id'] = $payload['tenant_id'];
                    }
                    return array_merge($user, ['source' => 'jwt']);
                }
            } catch (Exception $e) {
                // Silently fail JWT lookup
            }
        }
    }
    
    // Não autenticado
    return null;
}
}

/**
 * Garante que a chamada atual seja autenticada e retorna o usuário da sessão.
 */
function viabixRequireAuthenticatedSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    // Tenta validar sessão PHP ou JWT token
    $user = viabixGetAuthenticatedUser();

    if (!$user) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Não autenticado']);
        exit;
    }

    return [
        'id' => $user['id'],
        'login' => $user['login'] ?? null,
        'nome' => $user['nome'] ?? null,
        'nivel' => $user['nivel'] ?? null,
        'tenant_id' => $user['tenant_id'] ?? null,
        'source' => $user['source'] ?? null,
    ];
}

/**
 * Exige sessão autenticada com permissão administrativa.
 */
function viabixRequireAdminSession() {
    $user = viabixRequireAuthenticatedSession();
    $nivel = viabixNormalizeUserLevel($user['nivel'] ?? null);

    if ($nivel !== 'admin') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso restrito à administração.']);
        exit;
    }

    $user['nivel'] = $nivel;
    return $user;
}

/**
 * Lista planos ativos para uso em painéis administrativos e checkout.
 */

