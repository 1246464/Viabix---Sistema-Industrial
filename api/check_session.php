<?php
/**
 * Verificacao de Sessao - Sistema Viabix
 */

require_once 'config.php';

// Limpar qualquer saida anterior
if (ob_get_level()) ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Handle CORS preflight requests
viabixHandleCorsPreflight(
    ['GET', 'POST', 'OPTIONS'],
    3600,
    ['Content-Type', 'Authorization', 'X-CSRF-Token', 'X-Mobile-App']
);

// Iniciar sessao apenas se nao estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'viabix_session');
    session_start();
}

// Inicializar CSRF protection
viabixInitializeCsrfProtection();

$response = ['success' => false, 'message' => 'Sessão não autenticada', 'logado' => false, 'csrf_token' => viabixGetCsrfToken()];

// Tentar autenticar via SESSION ou JWT (mobile apps)
$user = viabixGetAuthenticatedUser();

// Sincronizar SESSION se for JWT - SEMPRE sincronizar para garantir dados atualizados
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_login'] = $user['login'] ?? '';
    $_SESSION['nome'] = $user['nome'] ?? '';
    $_SESSION['nivel'] = $user['nivel'] ?? '';
    $_SESSION['user_level'] = $user['nivel'] ?? '';
    $_SESSION['tenant_id'] = $user['tenant_id'] ?? '';
}

// Agora verifica APENAS user_id - se tem user_id, está autenticado
if ($user && isset($_SESSION['user_id'])) {
    try {
        // Rastrear tentativa de verificação de sessão
        viabix_sentry_tag('action', 'check_session');
        
        // SECURITY: Validar tenant_id da sessão
        $tenant_id = viabixCurrentTenantId();
        $tenantAware = viabixHasColumn('usuarios', 'tenant_id') && $tenant_id;
        
        $select = 'id, login, nome, nivel, ultimo_acesso';
        if ($tenantAware) {
            $select .= ', tenant_id';
        }

        if ($tenantAware) {
            $stmt = $pdo->prepare("SELECT $select FROM usuarios WHERE id = ? AND ativo = 1 AND tenant_id = ?");
            $stmt->execute([$_SESSION['user_id'], $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT $select FROM usuarios WHERE id = ? AND ativo = 1");
            $stmt->execute([$_SESSION['user_id']]);
        }
        $user = $stmt->fetch();
        
        if ($user) {
            // Configurar contexto de usuário e tenant no Sentry
            viabix_sentry_set_user($user['id'], $_SESSION['user_login'] ?? null, $user['nome'] ?? null);
            
            $tenantContext = viabixGetTenantContext($user['tenant_id'] ?? ($_SESSION['tenant_id'] ?? null));
            [$canAccess, $accessMessage] = viabixCanAccessTenant($tenantContext);

            if (!$canAccess) {
                viabix_sentry_message('Acesso negado ao tenant', 'warning', 'auth.access_denied', [
                    'tenant_id' => $user['tenant_id'] ?? null,
                    'reason' => $accessMessage,
                ]);

                viabixClearAuthenticatedSession();
                echo json_encode([
                    'logado' => false,
                    'message' => $accessMessage,
                ]);
                exit;
            }

            // Registrar tenant no Sentry
            if ($tenantContext['tenant_id'] ?? null) {
                viabix_sentry_set_tenant($tenantContext['tenant_id'], $tenantContext['tenant_nome'] ?? null);
            }

            viabixPopulateSession($user, $tenantContext);

            $response = [
                'success' => true,
                'message' => 'Sessão ativa',
                'logado' => true,
                'csrf_token' => viabixGetCsrfToken(),
                'user' => [
                    'id' => $user['id'],
                    'login' => $user['login'],
                    'nome' => $user['nome'],
                    'nivel' => $_SESSION['user_level'],
                    'nivel_original' => $user['nivel'],
                    'ultimo_acesso' => $user['ultimo_acesso']
                ],
                // Campos adicionais para compatibilidade
                'nome' => $user['nome'],
                'usuario' => $user['login'],
                'tenant' => [
                    'id' => $_SESSION['tenant_id'] ?? null,
                    'slug' => $_SESSION['tenant_slug'] ?? null,
                    'nome' => $_SESSION['tenant_nome'] ?? null,
                    'status' => $_SESSION['tenant_status'] ?? null,
                ],
                'subscription' => [
                    'id' => $_SESSION['subscription_id'] ?? null,
                    'status' => $_SESSION['subscription_status'] ?? null,
                    'ciclo' => $_SESSION['subscription_cycle'] ?? null,
                    'plano_codigo' => $_SESSION['plan_code'] ?? null,
                    'plano_nome' => $_SESSION['plan_name'] ?? null,
                ],
                'features' => $_SESSION['features'] ?? []
            ];
            
            // Atualizar ultimo acesso (apenas a cada 5 minutos)
            if (!isset($_SESSION['last_access_update']) || time() - $_SESSION['last_access_update'] > 300) {
                if ($tenantAware) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$user['id'], $tenant_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                }
                $_SESSION['last_access_update'] = time();
            }
        } else {
            // Usuario nao existe mais ou esta inativo
            viabixClearAuthenticatedSession();
        }
    } catch (PDOException $e) {
        logError("Erro ao verificar sessao", ['error' => $e->getMessage()]);
    }
}

// Garantir que nao ha saida antes do JSON
// Garantir status 200 OK
http_response_code(200);
echo json_encode($response);
exit;
