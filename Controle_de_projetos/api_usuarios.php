<?php
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json');

// Apenas admin pode gerenciar usuários
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem gerenciar usuários.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $conn = getConnection();
    
    switch ($action) {
        case 'list':
            $result = $conn->query("SELECT id, username, nome, nivel, ativo, created_at FROM usuarios ORDER BY created_at DESC");
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
            
            // Verificar se username já existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Usuário já existe!']);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO usuarios (username, senha, nome, nivel) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $senha, $nome, $nivel);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Usuário criado com sucesso!',
                    'usuario' => [
                        'id' => $conn->insert_id,
                        'username' => $username,
                        'senha' => $senha,
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
            
            // Verificar se username já existe em outro usuário
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $username, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Nome de usuário já está em uso!']);
                exit;
            }
            
            // Atualizar com ou sem senha
            if (!empty($senha)) {
                $stmt = $conn->prepare("UPDATE usuarios SET username = ?, senha = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                $stmt->bind_param("ssssii", $username, $senha, $nome, $nivel, $ativo, $id);
            } else {
                $stmt = $conn->prepare("UPDATE usuarios SET username = ?, nome = ?, nivel = ?, ativo = ? WHERE id = ?");
                $stmt->bind_param("sssii", $username, $nome, $nivel, $ativo, $id);
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
            
            $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Usuário excluído com sucesso!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir: ' . $conn->error]);
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
            
            $stmt = $conn->prepare("UPDATE usuarios SET ativo = ? WHERE id = ?");
            $stmt->bind_param("ii", $ativo, $id);
            
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
