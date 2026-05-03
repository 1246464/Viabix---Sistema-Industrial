<?php
/**
 * =======================================================
 * SISTEMA CENTRALIZADO DE AUTENTICAÇÃO E AUTORIZAÇÃO
 * =======================================================
 * 
 * Este módulo unifica toda lógica de:
 * - Autenticação (validação de usuário/senha)
 * - Autorização (permissões baseadas em roles)
 * - Isolamento multi-tenant (1 tenant = 1 usuário)
 * - Validação de sessão
 * 
 * @version 2.0
 * @since 2026-05-03
 */

// ======================================================
// 1. CONSTANTES DE RECURSOS E AÇÕES
// ======================================================

/**
 * Define os recursos que podem ser protegidos.
 * Estrutura: 'recurso' => ['ação1', 'ação2', ...]
 */
const VIABIX_RESOURCES = [
    'usuarios' => [
        'view',      // Ver lista de usuários
        'create',    // Criar novo usuário
        'update',    // Editar usuário
        'delete',    // Deletar usuário
        'change_password', // Mudar senha de outro usuário
    ],
    'anvis' => [
        'view',      // Ver ANVIs
        'create',    // Criar ANVI
        'update',    // Editar ANVI
        'delete',    // Deletar ANVI
        'export',    // Exportar ANVI
    ],
    'projetos' => [
        'view',
        'create',
        'update',
        'delete',
    ],
    'relatorios' => [
        'view',
        'create',
        'export',
    ],
    'configuracoes' => [
        'view',
        'update',
        'backup',
    ],
    'admin_saas' => [
        'view_tenants',
        'change_plan',
        'suspend_tenant',
        'view_webhooks',
        'reprocess_webhook',
    ]
];

/**
 * Define os papéis padrão e suas permissões.
 * Esta é uma configuração padrão; pode ser sobrescrita no BD.
 */
const VIABIX_ROLES = [
    'admin' => [
        'usuarios:view',
        'usuarios:create',
        'usuarios:update',
        'usuarios:delete',
        'usuarios:change_password',
        'anvis:view',
        'anvis:create',
        'anvis:update',
        'anvis:delete',
        'anvis:export',
        'projetos:view',
        'projetos:create',
        'projetos:update',
        'projetos:delete',
        'relatorios:view',
        'relatorios:create',
        'relatorios:export',
        'configuracoes:view',
        'configuracoes:update',
        'configuracoes:backup',
        'admin_saas:view_tenants',
        'admin_saas:change_plan',
        'admin_saas:suspend_tenant',
        'admin_saas:view_webhooks',
        'admin_saas:reprocess_webhook',
    ],
    'editor' => [
        'usuarios:view',           // Pode ver lista de usuários
        'anvis:view',
        'anvis:create',
        'anvis:update',
        'anvis:export',
        'projetos:view',
        'projetos:create',
        'projetos:update',
        'relatorios:view',
        'relatorios:create',
        'relatorios:export',
    ],
    'visualizador' => [
        'usuarios:view',           // Pode ver lista de usuários
        'anvis:view',
        'anvis:export',
        'projetos:view',
        'relatorios:view',
        'relatorios:export',
    ],
    'visitante' => [
        'anvis:view',
        'projetos:view',
    ],
];

// ======================================================
// 2. FUNÇÕES DE AUTENTICAÇÃO
// ======================================================

/**
 * Valida autenticação e retorna usuário.
 * DEVE ser chamado antes de renderizar qualquer HTML/conteúdo.
 *
 * @param bool $requireAdmin Se true, apenas admin pode continuar
 * @return array Dados do usuário autenticado
 * @throws RuntimeException Se não autenticado
 */
function viabixRequireAuthentication($requireAdmin = false) {
    // 1. Inicializar sessão
    if (session_status() === PHP_SESSION_NONE) {
        session_name(viabix_env('SESSION_NAME', 'viabix_session'));
        session_start();
    }

    // 2. Verificar se está logado
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['tenant_id'])) {
        viabix_sentry_breadcrumb('Tentativa de acesso sem autenticação', 'auth.require', 'warning');
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Não autenticado. Faça login novamente.']);
        exit;
    }

    // 3. Validar dados críticos
    $user = [
        'id'           => $_SESSION['user_id'],
        'tenant_id'    => $_SESSION['tenant_id'],
        'email'        => $_SESSION['email'] ?? null,
        'nome'         => $_SESSION['user_nome'] ?? $_SESSION['nome'] ?? 'Desconhecido',
        'role'         => $_SESSION['user_role'] ?? 'visualizador',
        'permissions'  => $_SESSION['user_permissions'] ?? [],
    ];

    if (empty($user['id']) || empty($user['tenant_id'])) {
        viabix_sentry_breadcrumb('Sessão inválida', 'auth.require', 'error', ['user_id' => $user['id']]);
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Sessão inválida. Faça login novamente.']);
        session_destroy();
        exit;
    }

    // 4. Validar tenant ativo
    if (!viabixValidateTenantAccess($user['tenant_id'])) {
        viabix_sentry_breadcrumb('Tenant inativo ou assinatura inválida', 'auth.require', 'warning', ['tenant_id' => $user['tenant_id']]);
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Sua conta foi suspensa. Contate o suporte.']);
        session_destroy();
        exit;
    }

    // 5. Se exigir admin, validar
    if ($requireAdmin && $user['role'] !== 'admin') {
        viabix_sentry_breadcrumb('Acesso negado: requer admin', 'auth.require', 'warning', ['user_id' => $user['id'], 'role' => $user['role']]);
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Acesso negado. Apenas administradores podem acessar.']);
        exit;
    }

    return $user;
}

/**
 * Versão simplificada: apenas verifica se está logado (sem exigir admin)
 * @return array Dados do usuário
 */
function viabixGetCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(viabix_env('SESSION_NAME', 'viabix_session'));
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    return [
        'id'           => $_SESSION['user_id'],
        'tenant_id'    => $_SESSION['tenant_id'],
        'email'        => $_SESSION['email'] ?? null,
        'nome'         => $_SESSION['user_nome'] ?? $_SESSION['nome'] ?? 'Desconhecido',
        'role'         => $_SESSION['user_role'] ?? 'visualizador',
        'permissions'  => $_SESSION['user_permissions'] ?? [],
    ];
}

// ======================================================
// 3. FUNÇÕES DE AUTORIZAÇÃO (PERMISSION-BASED)
// ======================================================

/**
 * Valida se usuário tem permissão para um recurso:ação
 *
 * @param string $resource Recurso (ex: 'usuarios')
 * @param string $action Ação (ex: 'create')
 * @param array|null $user Dados do usuário (se null, usa sessão)
 * @return bool True se tem permissão
 */
function viabixHasPermission($resource, $action, $user = null) {
    if ($user === null) {
        $user = viabixGetCurrentUser();
    }

    if (!$user) {
        return false;
    }

    // Admin tem todas as permissões
    if ($user['role'] === 'admin') {
        return true;
    }

    // Validar que recurso:ação existe
    if (!isset(VIABIX_RESOURCES[$resource]) || !in_array($action, VIABIX_RESOURCES[$resource])) {
        viabix_sentry_breadcrumb('Validação de permissão: recurso/ação inválido', 'auth.permission', 'warning', [
            'resource' => $resource,
            'action' => $action,
        ]);
        return false;
    }

    $permission_key = "$resource:$action";

    // Verificar se está na lista de permissões
    $permissions = $user['permissions'] ?? [];
    if (!is_array($permissions)) {
        return false;
    }

    return in_array($permission_key, $permissions, true);
}

/**
 * Exigir permissão e sair se não tiver.
 * Use no começo de endpoints sensíveis.
 *
 * @param string $resource Recurso
 * @param string $action Ação
 * @param array|null $user Dados do usuário
 * @throws Exception Se não tem permissão
 */
function viabixRequirePermission($resource, $action, $user = null) {
    if (!viabixHasPermission($resource, $action, $user)) {
        $current_user = $user ?? viabixGetCurrentUser();
        viabix_sentry_breadcrumb('Acesso negado: permissão insuficiente', 'auth.permission', 'warning', [
            'user_id' => $current_user['id'] ?? null,
            'resource' => $resource,
            'action' => $action,
            'role' => $current_user['role'] ?? null,
        ]);

        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => "Acesso negado. Você não tem permissão para $action em $resource.",
        ]);
        exit;
    }
}

/**
 * Lista todas as permissões de um usuário.
 * Útil para frontend renderizar UI condicionalmente.
 *
 * @param array|null $user Dados do usuário
 * @return array Lista de permissões
 */
function viabixGetUserPermissions($user = null) {
    if ($user === null) {
        $user = viabixGetCurrentUser();
    }

    if (!$user) {
        return [];
    }

    // Admin tem todas
    if ($user['role'] === 'admin') {
        return array_keys(array_reduce(VIABIX_RESOURCES, function($carry, $actions) {
            foreach ($actions as $action) {
                $carry[] = "$resource:$action";
            }
            return $carry;
        }, []));
    }

    return $user['permissions'] ?? [];
}

// ======================================================
// 4. FUNÇÕES DE ISOLAMENTO MULTI-TENANT
// ======================================================

/**
 * Valida que o tenant está ativo e a assinatura é válida.
 * Deve ser chamado em viabixRequireAuthentication().
 *
 * @param string $tenant_id ID do tenant
 * @return bool True se tenant está ativo
 */
function viabixValidateTenantAccess($tenant_id) {
    global $pdo;

    if (!$pdo || !$tenant_id) {
        return false;
    }

    try {
        // Verificar status do tenant e assinatura
        $stmt = $pdo->prepare("
            SELECT t.status, s.status AS subscription_status, s.fim_vigencia
            FROM tenants t
            LEFT JOIN subscriptions s ON s.tenant_id = t.id AND s.status IN ('ativa', 'trial')
            WHERE t.id = ?
            LIMIT 1
        ");
        $stmt->execute([$tenant_id]);
        $result = $stmt->fetch();

        if (!$result) {
            return false;
        }

        // Tenant pode estar: trial, ativo
        $tenant_active = in_array($result['status'], ['trial', 'ativo', 'trial_expirado']);
        
        // Se não tem subscription ativa, pode estar em trial
        $subscription_ok = ($result['subscription_status'] === null) || 
                          ($result['subscription_status'] === 'trial' && $result['fim_vigencia'] > date('Y-m-d'));

        return $tenant_active && $subscription_ok;
    } catch (Exception $e) {
        viabix_sentry_exception($e, 'error');
        return false;
    }
}

/**
 * Obtém o tenant_id do usuário autenticado.
 * IMPORTANTE: Sempre valide que um recurso pertence a este tenant!
 *
 * @param array|null $user Dados do usuário
 * @return string|null ID do tenant
 */
function viabixGetCurrentTenantId($user = null) {
    if ($user === null) {
        $user = viabixGetCurrentUser();
    }

    return $user['tenant_id'] ?? null;
}

/**
 * Valida que um recurso pertence ao tenant do usuário.
 * Use antes de retornar qualquer dado sensível.
 *
 * @param string $tenant_id_from_resource ID do tenant do recurso
 * @param string $user_tenant_id ID do tenant do usuário
 * @return bool True se pertencem ao mesmo tenant
 */
function viabixValidateResourceTenant($tenant_id_from_resource, $user_tenant_id) {
    // Comparação strict!
    return ($tenant_id_from_resource === $user_tenant_id);
}

// ======================================================
// 5. FUNÇÕES DE VALIDAÇÃO DE SESSÃO
// ======================================================

/**
 * Regenera ID da sessão por segurança.
 * Use após login bem-sucedido.
 */
function viabixRegenerateSessionId() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Invalida a sessão completamente.
 * Use em logout ou ao detectar comportamento suspeito.
 */
function viabixDestroySession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];
        session_destroy();
    }
}

/**
 * Popula a sessão com dados do usuário e suas permissões.
 * Use após autenticação bem-sucedida.
 *
 * @param array $user Dados do usuário do BD
 * @param array $tenant Dados do tenant
 */
function viabixPopulateSessionWithPermissions($user, $tenant) {
    // Dados básicos
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['email']        = $user['email'] ?? null;
    $_SESSION['user_nome']    = $user['nome'] ?? '';
    $_SESSION['user_login']   = $user['login'] ?? $user['email'] ?? '';
    $_SESSION['user_role']    = $user['role'] ?? 'visualizador';

    // Tenant
    $_SESSION['tenant_id']    = $tenant['id'];
    $_SESSION['tenant_nome']  = $tenant['nome_fantasia'] ?? $tenant['nome'] ?? '';
    $_SESSION['tenant_status'] = $tenant['status'] ?? 'ativo';
    $_SESSION['tenant_slug']  = $tenant['slug'] ?? null;

    // Permissões
    $permissions = viabixLoadUserPermissionsFromDb($user['id'], $user['role']);
    $_SESSION['user_permissions'] = $permissions;

    // Subscription
    $_SESSION['subscription_status'] = $tenant['subscription_status'] ?? 'trial';
    $_SESSION['plan_code']     = $tenant['plan_code'] ?? 'starter';
    $_SESSION['plan_name']     = $tenant['plan_name'] ?? 'Starter';
    $_SESSION['features']      = $tenant['features'] ?? [];

    // Timestamp de login
    $_SESSION['login_at'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Carrega permissões do usuário do banco de dados.
 * Se tabelas não existem, usa fallback de VIABIX_ROLES.
 *
 * @param string $user_id ID do usuário
 * @param string $role Papel padrão
 * @return array Lista de permissões
 */
function viabixLoadUserPermissionsFromDb($user_id, $role = 'visualizador') {
    global $pdo;

    // Validação
    if (!$pdo || !$user_id) {
        return VIABIX_ROLES[$role] ?? [];
    }

    try {
        // Tentar carregar do BD
        // Se a tabela não existir, isso vai falhar e usar fallback
        $stmt = $pdo->prepare("
            SELECT p.permission_name
            FROM user_roles ur
            JOIN role_permissions rp ON rp.role_id = ur.role_id
            JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.user_id = ?
            UNION
            SELECT p.permission_name
            FROM user_custom_permissions ucp
            JOIN permissions p ON p.id = ucp.permission_id
            WHERE ucp.user_id = ?
        ");
        $stmt->execute([$user_id, $user_id]);
        $results = $stmt->fetchAll();

        if (!empty($results)) {
            return array_column($results, 'permission_name');
        }
    } catch (Exception $e) {
        // Tabelas não existem? Usar fallback
        viabix_sentry_breadcrumb('Falha ao carregar permissões do BD, usando fallback', 'auth.permissions', 'warning');
    }

    // Fallback: usar configuração padrão
    return VIABIX_ROLES[$role] ?? [];
}

/**
 * Valida que email não está duplicado para este tenant.
 * Importante para evitar conflitos em novo usuário.
 *
 * @param string $email Email a validar
 * @param string $tenant_id ID do tenant
 * @param string|null $exclude_user_id Excluir um usuário na comparação
 * @return bool True se email é único no tenant
 */
function viabixEmailUniqueInTenant($email, $tenant_id, $exclude_user_id = null) {
    global $pdo;

    if (!$pdo || !$email || !$tenant_id) {
        return false;
    }

    $sql = "SELECT COUNT(*) as count FROM usuarios WHERE email = ? AND tenant_id = ?";
    $params = [$email, $tenant_id];

    if ($exclude_user_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_user_id;
    }

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return ($result['count'] ?? 0) === 0;
    } catch (Exception $e) {
        return false;
    }
}

// ======================================================
// 6. HELPERS PARA ENDPOINTS
// ======================================================

/**
 * Helper para validar entrada com whitelist.
 * Retorna valor se válido, null se não.
 *
 * @param mixed $value Valor a validar
 * @param array $allowed Lista de valores permitidos
 * @return string|null Valor se válido, null se não
 */
function viabixValidateEnum($value, $allowed = []) {
    if (in_array($value, $allowed, true)) {
        return $value;
    }
    return null;
}

/**
 * Valida que ID está em formato válido (UUID ou INT)
 *
 * @param mixed $id ID a validar
 * @return bool True se válido
 */
function viabixValidateId($id) {
    // UUID v4 ou número
    return (is_string($id) && preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $id)) ||
           (is_numeric($id) && $id > 0);
}

// ======================================================
// 7. FUNÇÕES DE LOGGING E AUDITORIA
// ======================================================

/**
 * Registra ação sensível no log de auditoria.
 * Sempre inclua user_id, tenant_id e o que foi feito.
 *
 * @param string $user_id ID do usuário que fez a ação
 * @param string $tenant_id ID do tenant
 * @param string $action Ação (ex: 'user.created', 'anvi.updated')
 * @param array $details Detalhes em JSON (o que mudou, etc)
 * @param int|null $affected_resource_id ID do recurso afetado
 */
function viabixLogAudit($user_id, $tenant_id, $action, $details = [], $affected_resource_id = null) {
    global $pdo;

    if (!$pdo || !$user_id || !$tenant_id) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (tenant_id, user_id, action, details, affected_resource_id, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $tenant_id,
            $user_id,
            $action,
            json_encode($details),
            $affected_resource_id,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        viabix_sentry_breadcrumb('Auditoria: ' . $action, 'audit', 'info', ['user_id' => $user_id, 'tenant_id' => $tenant_id]);
    } catch (Exception $e) {
        viabix_sentry_exception($e, 'error');
    }
}

/**
 * Log de erro de segurança (tentativa de acesso não autorizado).
 */
function viabixLogSecurityEvent($event_type, $user_id = null, $details = []) {
    global $pdo;

    if (!$pdo) {
        return;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO security_events (event_type, user_id, ip_address, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $event_type,
            $user_id,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            json_encode($details),
        ]);

        viabix_sentry_breadcrumb('Evento de segurança: ' . $event_type, 'security', 'warning');
    } catch (Exception $e) {
        viabix_sentry_exception($e, 'error');
    }
}

?>
