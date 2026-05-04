<?php
/**
 * Verificação de Sessão - Sistema Viabix
 * CORRIGIDO - SEM BOM
 */

require_once 'config.php';

// Limpar qualquer saída anterior
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');

// Iniciar sessão apenas se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_name('viabix_session');
    session_start();
}

$response = ['logado' => false];

if (isset($_SESSION['user_id']) && isset($_SESSION['user_login'])) {
    try {
        // SECURITY: Validar tenant_id da sessão
        $tenant_id = viabixCurrentTenantId();
        $tenantAware = viabixHasColumn('usuarios', 'tenant_id') && $tenant_id;
        
        // Verificar se usuário ainda existe no banco
        if ($tenantAware) {
            $stmt = $pdo->prepare("SELECT id, login, nome, nivel, ultimo_acesso, tenant_id FROM usuarios WHERE id = ? AND ativo = 1 AND tenant_id = ?");
            $stmt->execute([$_SESSION['user_id'], $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id, login, nome, nivel, ultimo_acesso FROM usuarios WHERE id = ? AND ativo = 1");
            $stmt->execute([$_SESSION['user_id']]);
        }
        $user = $stmt->fetch();
        
        if ($user) {
            $response = [
                'logado' => true,
                'user' => [
                    'id' => $user['id'],
                    'login' => $user['login'],
                    'nome' => $user['nome'],
                    'nivel' => $user['nivel'],
                    'ultimo_acesso' => $user['ultimo_acesso']
                ],
                // Campos adicionais para compatibilidade
                'nome' => $user['nome'],
                'usuario' => $user['login']
            ];
            
            // Atualizar último acesso (apenas a cada 5 minutos)
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
            // Usuário não existe mais ou está inativo
            $_SESSION = array();
            session_destroy();
        }
    } catch (PDOException $e) {
        logError("Erro ao verificar sessão", ['error' => $e->getMessage()]);
    }
}

// Garantir que não há saída antes do JSON
echo json_encode($response);
?>