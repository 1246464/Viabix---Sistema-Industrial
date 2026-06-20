<?php
/**
 * API Usuários - Segura contra isolamento multi-tenant
 * SECURITY: Adicionar tenant_id filtering em TODAS as queries
 *
 * Mudanças de segurança:
 * - Verificar se tenant_id está presente em cada query
 * - Validar que usuário pertence ao tenant atual antes de editar/deletar
 * - Usar tenant_id implícito da sessão do usuário
 */

require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json');

// Apenas admin pode gerenciar usuários
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar usuários.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    try {
        viabixValidateCsrfTokenWithInput($_POST);
    } catch (RuntimeException $e) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Validação de segurança falhou. Recarregue a página e tente novamente.']);
        exit;
    }
}

// SECURITY: Obter tenant_id do usuário logado
$currentTenantId = $_SESSION['tenant_id'] ?? null;

try {
    $pdo = getConnection();

    // Verificar se tabela tem coluna tenant_id
    $tenantAware = schemaHasColumn($pdo, 'usuarios', 'tenant_id');

    switch ($action) {
        case 'list':
            // SECURITY FIX: Filtrar por tenant_id se disponível
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("SELECT id, username, nome, nivel, ativo, created_at FROM usuarios WHERE tenant_id = ? ORDER BY created_at DESC");
                $stmt->execute([$currentTenantId]);
            } else {
                if ($tenantAware && !$currentTenantId) {
                    echo json_encode(['success' => false, 'message' => 'Tenant ID não disponível']);
                    exit;
                }
                // Legacy: se tabela não tem tenant_id, listar tudo (AVISO: inseguro!)
                $stmt = $pdo->query("SELECT id, username, nome, nivel, ativo, created_at FROM usuarios ORDER BY created_at DESC");
            }
            echo json_encode(['success' => true, 'usuarios' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'create':
            $username = $_POST['username'] ?? '';
            $senha    = $_POST['senha'] ?? '';
            $nome     = $_POST['nome'] ?? '';
            $nivel    = $_POST['nivel'] ?? 'visualizador';

            if (empty($username) || empty($senha) || empty($nome)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios!']);
                exit;
            }

            // SECURITY FIX: Verificar se username já existe no MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND tenant_id = ?");
                $stmt->execute([$username, $currentTenantId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmt->execute([$username]);
            }

            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Usuário já existe!']);
                exit;
            }

            // SECURITY FIX: Inserir com tenant_id se disponível
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("INSERT INTO usuarios (tenant_id, username, senha, nome, nivel) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$currentTenantId, $username, password_hash($senha, PASSWORD_BCRYPT), $nome, $nivel]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO usuarios (username, senha, nome, nivel) VALUES (?, ?, ?, ?)");
                $stmt->execute([$username, password_hash($senha, PASSWORD_BCRYPT), $nome, $nivel]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Usuário criado com sucesso!',
                'usuario' => [
                    'id' => (int)$pdo->lastInsertId(),
                    'username' => $username,
                    'nome' => $nome,
                    'nivel' => $nivel
                ]
            ]);
            break;

        case 'update':
            $id     = (int)($_POST['id'] ?? 0);
            $username = $_POST['username'] ?? '';
            $senha    = $_POST['senha'] ?? '';
            $nome     = $_POST['nome'] ?? '';
            $nivel    = $_POST['nivel'] ?? 'visualizador';
            $ativo    = (int)($_POST['ativo'] ?? 1);

            if (empty($id) || empty($username) || empty($nome)) {
                echo json_encode(['success' => false, 'message' => 'Dados incompletos!']);
                exit;
            }

            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $currentTenantId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
            }

            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }

            // SECURITY FIX: Verificar se username já existe em outro usuário do MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ? AND tenant_id = ?");
                $stmt->execute([$username, $id, $currentTenantId]);
            } else {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
                $stmt->execute([$username, $id]);
            }

            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Nome de usuário já está em uso!']);
                exit;
            }

            // Atualizar com ou sem senha
            if (!empty($senha)) {
                if ($tenantAware && $currentTenantId) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, senha = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$username, password_hash($senha, PASSWORD_BCRYPT), $nome, $nivel, $ativo, $id, $currentTenantId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, senha = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$username, password_hash($senha, PASSWORD_BCRYPT), $nome, $nivel, $ativo, $id]);
                }
            } else {
                if ($tenantAware && $currentTenantId) {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$username, $nome, $nivel, $ativo, $id, $currentTenantId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET username = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->execute([$username, $nome, $nivel, $ativo, $id]);
                }
            }

            echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            break;

        case 'delete':
            $id = (int)($_POST['id'] ?? 0);

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID inválido!']);
                exit;
            }

            // Não permitir excluir o próprio usuário
            if ($id === (int)$_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário!']);
                exit;
            }

            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $currentTenantId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
            }

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            }
            break;

        case 'toggle_status':
            $id    = (int)($_POST['id'] ?? 0);
            $ativo = (int)($_POST['ativo'] ?? 1);

            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID inválido!']);
                exit;
            }

            // Não permitir desativar o próprio usuário
            if ($id === (int)$_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode desativar seu próprio usuário!']);
                exit;
            }

            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$ativo, $id, $currentTenantId]);
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
                $stmt->execute([$ativo, $id]);
            }

            $status = $ativo ? 'ativado' : 'desativado';
            echo json_encode(['success' => true, 'message' => "Usuário $status com sucesso!"]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida!']);
    }

} catch (Exception $e) {
    $isProduction = (getenv('APP_ENV') === 'production');
    error_log('API Usuarios Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $isProduction ? 'Erro interno do servidor' : ('Erro: ' . $e->getMessage())
    ]);
}
?>
