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

// SECURITY: Obter tenant_id do usuário logado
$currentTenantId = $_SESSION['tenant_id'] ?? null;

try {
    $conn = getConnection();
    
    // Verificar se tabela tem coluna tenant_id
    $tenantAware = false;
    if ($result = $conn->query("DESCRIBE usuarios")) {
        while ($col = $result->fetch_assoc()) {
            if ($col['Field'] === 'tenant_id') {
                $tenantAware = true;
                break;
            }
        }
    }
    
    switch ($action) {
        case 'list':
            // SECURITY FIX: Filtrar por tenant_id se disponível
            if ($tenantAware && $currentTenantId) {
                $result = $conn->query("SELECT id, username, nome, nivel, ativo, created_at FROM usuarios WHERE tenant_id = '" . $conn->real_escape_string($currentTenantId) . "' ORDER BY created_at DESC");
            } else {
                // FALLBACK: Se não tem tenant_id, retornar erro
                if ($tenantAware && !$currentTenantId) {
                    echo json_encode(['success' => false, 'message' => 'Tenant ID não disponível']);
                    exit;
                }
                // Legacy: se tabela não tem tenant_id, listar tudo (AVISO: inseguro!)
                $result = $conn->query("SELECT id, username, nome, nivel, ativo, created_at FROM usuarios ORDER BY created_at DESC");
            }
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            echo json_encode(['success' => true, 'usuarios' => $usuarios]);
            break;
            
        case 'create':
            $username = $_POST['username'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $nivel = $_POST['nivel'] ?? 'visualizador';
            
            if (empty($username) || empty($senha) || empty($nome)) {
                echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios!']);
                exit;
            }
            
            // SECURITY FIX: Verificar se username já existe no MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND tenant_id = ?");
                $stmt->bind_param("ss", $username, $currentTenantId);
            } else {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
                $stmt->bind_param("s", $username);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Usuário já existe!']);
                exit;
            }
            
            // SECURITY FIX: Inserir com tenant_id se disponível
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("INSERT INTO usuarios (tenant_id, username, senha, nome, nivel) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $currentTenantId, $username, $senha, $nome, $nivel);
            } else {
                $stmt = $conn->prepare("INSERT INTO usuarios (username, senha, nome, nivel) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $senha, $nome, $nivel);
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Usuário criado com sucesso!',
                    'usuario' => [
                        'id' => $conn->insert_id,
                        'username' => $username,
                        'nome' => $nome,
                        'nivel' => $nivel
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário: ' . $conn->error]);
            }
            break;
            
        case 'update':
            $id = $_POST['id'] ?? 0;
            $username = $_POST['username'] ?? '';
            $senha = $_POST['senha'] ?? '';
            $nome = $_POST['nome'] ?? '';
            $nivel = $_POST['nivel'] ?? 'visualizador';
            $ativo = $_POST['ativo'] ?? 1;
            
            if (empty($id) || empty($username) || empty($nome)) {
                echo json_encode(['success' => false, 'message' => 'Dados incompletos!']);
                exit;
            }
            
            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ? AND tenant_id = ?");
                $stmt->bind_param("is", $id, $currentTenantId);
            } else {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
                exit;
            }
            
            // SECURITY FIX: Verificar se username já existe em outro usuário do MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ? AND tenant_id = ?");
                $stmt->bind_param("sis", $username, $id, $currentTenantId);
            } else {
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
                $stmt->bind_param("si", $username, $id);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Nome de usuário já está em uso!']);
                exit;
            }
            
            // Atualizar com ou sem senha
            if (!empty($senha)) {
                if ($tenantAware && $currentTenantId) {
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, senha = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->bind_param("ssssiis", $username, $senha, $nome, $nivel, $ativo, $id, $currentTenantId);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, senha = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->bind_param("ssssii", $username, $senha, $nome, $nivel, $ativo, $id);
                }
            } else {
                if ($tenantAware && $currentTenantId) {
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ? AND tenant_id = ?");
                    $stmt->bind_param("sssiis", $username, $nome, $nivel, $ativo, $id, $currentTenantId);
                } else {
                    $stmt = $conn->prepare("UPDATE usuarios SET username = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                    $stmt->bind_param("sssii", $username, $nome, $nivel, $ativo, $id);
                }
            }
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuário atualizado com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $conn->error]);
            }
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID inválido!']);
                exit;
            }
            
            // Não permitir excluir o próprio usuário
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode excluir seu próprio usuário!']);
                exit;
            }
            
            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ? AND tenant_id = ?");
                $stmt->bind_param("is", $id, $currentTenantId);
            } else {
                $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $id);
            }
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            } else {
                // Retornar 404 se não encontrou o usuário (não dizer que é outro tenant)
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            }
            break;
            
        case 'toggle_status':
            $id = $_POST['id'] ?? 0;
            $ativo = $_POST['ativo'] ?? 1;
            
            if (empty($id)) {
                echo json_encode(['success' => false, 'message' => 'ID inválido!']);
                exit;
            }
            
            // Não permitir desativar o próprio usuário
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Você não pode desativar seu próprio usuário!']);
                exit;
            }
            
            // SECURITY FIX: Verificar se usuário pertence ao MESMO tenant
            if ($tenantAware && $currentTenantId) {
                $stmt = $conn->prepare("UPDATE usuarios SET ativo = ? WHERE id = ? AND tenant_id = ?");
                $stmt->bind_param("iis", $ativo, $id, $currentTenantId);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
                $stmt->bind_param("ii", $ativo, $id);
            }
            
            if ($stmt->execute()) {
                $status = $ativo ? 'ativado' : 'desativado';
                echo json_encode(['success' => true, 'message' => "Usuário $status com sucesso!"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao alterar status: ' . $conn->error]);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Ação inválida!']);
    }
    
    $conn->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
