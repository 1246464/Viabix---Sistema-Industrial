<?php
/**
 * API: Criar Projeto a partir de uma ANVI
 * Cria um novo projeto vinculado a uma ANVI existente
 */

session_name('viabix_session');
session_start();

header('Content-Type: application/json; charset=utf-8');

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['erro' => 'Usuário não autenticado']);
    exit;
}

require_once 'config.php';

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['erro' => 'Método não permitido']);
    exit;
}

// Obter dados do POST
$dados = json_decode(file_get_contents('php://input'), true);

$anvi_id = isset($dados['anvi_id']) ? intval($dados['anvi_id']) : 0;
$nome_projeto = isset($dados['nome_projeto']) ? trim($dados['nome_projeto']) : '';
$descricao = isset($dados['descricao']) ? trim($dados['descricao']) : '';
$lider_id = isset($dados['lider_id']) ? intval($dados['lider_id']) : null;
$data_inicio = isset($dados['data_inicio']) ? $dados['data_inicio'] : date('Y-m-d');
$data_fim_prevista = isset($dados['data_fim_prevista']) ? $dados['data_fim_prevista'] : null;

// Validações
if ($anvi_id <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID da ANVI inválido']);
    exit;
}

if (empty($nome_projeto)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nome do projeto é obrigatório']);
    exit;
}

try {
    // Verificar se a ANVI existe
    $stmt = $conn->prepare("SELECT id, nome_anvi, valor_final FROM anvis WHERE id = ?");
    $stmt->bind_param("i", $anvi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $anvi = $result->fetch_assoc();
    
    if (!$anvi) {
        http_response_code(404);
        echo json_encode(['erro' => 'ANVI não encontrada']);
        exit;
    }
    
    // Verificar se ANVI já tem projeto vinculado
    if ($anvi['id'] && $result->num_rows > 0) {
        $stmt2 = $conn->prepare("SELECT projeto_id FROM anvis WHERE id = ? AND projeto_id IS NOT NULL");
        $stmt2->bind_param("i", $anvi_id);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        
        if ($result2->num_rows > 0) {
            $projeto_existente = $result2->fetch_assoc();
            http_response_code(409);
            echo json_encode([
                'erro' => 'Esta ANVI já está vinculada ao projeto #' . $projeto_existente['projeto_id'],
                'projeto_id' => $projeto_existente['projeto_id']
            ]);
            exit;
        }
    }
    
    // Se descrição vazia, usar dados da ANVI
    if (empty($descricao)) {
        $descricao = "Projeto criado a partir da ANVI: {$anvi['nome_anvi']}";
    }
    
    // Calcular orçamento do projeto (valor da ANVI)
    $orcamento = $anvi['valor_final'] ?? 0;
    
    // Inserir novo projeto
    $stmt = $conn->prepare("
        INSERT INTO projetos 
        (nome, descricao, lider_id, data_inicio, data_fim_prevista, status, orcamento, progresso, anvi_id, criado_por, criado_em)
        VALUES (?, ?, ?, ?, ?, 'planejamento', ?, 0, ?, ?, NOW())
    ");
    
    $criado_por = $_SESSION['user_id'];
    
    $stmt->bind_param(
        "ssissdii",
        $nome_projeto,
        $descricao,
        $lider_id,
        $data_inicio,
        $data_fim_prevista,
        $orcamento,
        $anvi_id,
        $criado_por
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Erro ao criar projeto: " . $stmt->error);
    }
    
    $projeto_id = $conn->insert_id;
    
    // Atualizar ANVI com o projeto_id
    $stmt = $conn->prepare("UPDATE anvis SET projeto_id = ?, atualizado_em = NOW() WHERE id = ?");
    $stmt->bind_param("ii", $projeto_id, $anvi_id);
    
    if (!$stmt->execute()) {
        // Se falhar ao vincular, deletar o projeto criado
        $conn->query("DELETE FROM projetos WHERE id = $projeto_id");
        throw new Exception("Erro ao vincular projeto à ANVI");
    }
    
    // Registrar no log de atividades
    $acao = "Projeto #{$projeto_id} criado a partir da ANVI #{$anvi_id}";
    $stmt = $conn->prepare("
        INSERT INTO logs_atividade (usuario_id, acao, detalhes, criado_em)
        VALUES (?, 'criar_projeto_de_anvi', ?, NOW())
    ");
    $stmt->bind_param("is", $_SESSION['user_id'], $acao);
    $stmt->execute();
    
    // Buscar dados completos do projeto criado
    $stmt = $conn->prepare("
        SELECT p.*, l.nome as lider_nome, u.user_nome as criador_nome
        FROM projetos p
        LEFT JOIN lideres l ON p.lider_id = l.id
        LEFT JOIN usuarios u ON p.criado_por = u.user_id
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $projeto_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $projeto = $result->fetch_assoc();
    
    // Sucesso
    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Projeto criado e vinculado à ANVI com sucesso!',
        'projeto_id' => $projeto_id,
        'anvi_id' => $anvi_id,
        'projeto' => $projeto
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}

$conn->close();
?>