<?php
/**
 * Verificacao de Sessao - Sistema Viabix
 */

require_once 'config.php';

// Limpar qualquer saida anterior
if (ob_get_level()) ob_clean();

header('Content-Type: application/json; charset=utf-8');

// Iniciar sessao apenas se nao estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_name('viabix_session');
    session_start();
}

$response = ['logado' => false];

if (isset($_SESSION['user_id']) && isset($_SESSION['user_login'])) {
    try {
        $select = 'id, login, nome, nivel, ultimo_acesso';
        if (viabixHasColumn('usuarios', 'tenant_id')) {
            $select .= ', tenant_id';
        }

        $stmt = $pdo->prepare("SELECT $select FROM usuarios WHERE id = ? AND ativo = 1");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $tenantContext = viabixGetTenantContext($user['tenant_id'] ?? ($_SESSION['tenant_id'] ?? null));
            [$canAccess, $accessMessage] = viabixCanAccessTenant($tenantContext);

            if (!$canAccess) {
                viabixClearAuthenticatedSession();
                echo json_encode([
                    'logado' => false,
                    'message' => $accessMessage,
                ]);
                exit;
            }

            viabixPopulateSession($user, $tenantContext);

            $response = [
                'logado' => true,
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
                $stmt = $pdo->prepare("UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
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
echo json_encode($response);
exit;
