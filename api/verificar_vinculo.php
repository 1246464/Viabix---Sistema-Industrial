<?php
/**
 * API: Verificar Vínculo entre ANVI e Projeto
 * Retorna informações sobre o vínculo entre uma ANVI e um Projeto
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

// Obter parâmetros
$anvi_id = isset($_GET['anvi_id']) ? intval($_GET['anvi_id']) : 0;
$projeto_id = isset($_GET['projeto_id']) ? intval($_GET['projeto_id']) : 0;

if ($anvi_id <= 0 && $projeto_id <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID da ANVI ou Projeto é obrigatório']);
    exit;
}

try {
    $vinculo = [
        'tem_vinculo' => false,
        'anvi_id' => null,
        'projeto_id' => null,
        'anvi' => null,
        'projeto' => null
    ];
    
    if ($anvi_id > 0) {
        // Buscar por ANVI
        $stmt = $conn->prepare("
            SELECT 
                a.id as anvi_id,
                a.nome_anvi,
                a.valor_final,
                a.projeto_id,
                p.id as pid,
                p.nome as projeto_nome,
                p.status as projeto_status,
                p.progresso,
                l.nome as lider_nome
            FROM anvis a
            LEFT JOIN projetos p ON a.projeto_id = p.id
            LEFT JOIN lideres l ON p.lider_id = l.id
            WHERE a.id = ?
        ");
        $stmt->bind_param("i", $anvi_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $dados = $result->fetch_assoc();
            $vinculo['anvi_id'] = $dados['anvi_id'];
            $vinculo['anvi'] = [
                'id' => $dados['anvi_id'],
                'nome' => $dados['nome_anvi'],
                'valor' => $dados['valor_final']
            ];
            
            if ($dados['projeto_id']) {
                $vinculo['tem_vinculo'] = true;
                $vinculo['projeto_id'] = $dados['pid'];
                $vinculo['projeto'] = [
                    'id' => $dados['pid'],
                    'nome' => $dados['projeto_nome'],
                    'status' => $dados['projeto_status'],
                    'progresso' => $dados['progresso'],
                    'lider' => $dados['lider_nome']
                ];
            }
        }
        
    } else if ($projeto_id > 0) {
        // Buscar por Projeto
        $stmt = $conn->prepare("
            SELECT 
                p.id as projeto_id,
                p.nome as projeto_nome,
                p.status,
                p.progresso,
                p.anvi_id,
                a.id as aid,
                a.nome_anvi,
                a.valor_final,
                l.nome as lider_nome
            FROM projetos p
            LEFT JOIN anvis a ON p.anvi_id = a.id
            LEFT JOIN lideres l ON p.lider_id = l.id
            WHERE p.id = ?
        ");
        $stmt->bind_param("i", $projeto_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $dados = $result->fetch_assoc();
            $vinculo['projeto_id'] = $dados['projeto_id'];
            $vinculo['projeto'] = [
                'id' => $dados['projeto_id'],
                'nome' => $dados['projeto_nome'],
                'status' => $dados['status'],
                'progresso' => $dados['progresso'],
                'lider' => $dados['lider_nome']
            ];
            
            if ($dados['anvi_id']) {
                $vinculo['tem_vinculo'] = true;
                $vinculo['anvi_id'] = $dados['aid'];
                $vinculo['anvi'] = [
                    'id' => $dados['aid'],
                    'nome' => $dados['nome_anvi'],
                    'valor' => $dados['valor_final']
                ];
            }
        }
    }
    
    echo json_encode($vinculo);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}

$conn->close();
?>