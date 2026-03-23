<?php
/**
 * Gerenciamento de ANVIs - Sistema Viabix
 * VERSÃO SIMPLIFICADA - SEM PROCEDURE
 */

require_once 'config.php';

// Limpar qualquer saída anterior
if (ob_get_level()) ob_clean();

header('Content-Type: application/json');

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_level = $_SESSION['user_level'] ?? 'usuario';
$tenant_id = viabixCurrentTenantId();
$tenantAwareAnvis = viabixHasColumn('anvis', 'tenant_id') && $tenant_id;

// Verificar método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                // Buscar uma ANVI específica
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ? AND tenant_id = ?");
                    $stmt->execute([$_GET['id'], $tenant_id]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ?");
                    $stmt->execute([$_GET['id']]);
                }
                $anvi = $stmt->fetch();
                
                if (!$anvi) {
                    http_response_code(404);
                    echo json_encode(['error' => 'ANVI não encontrada']);
                    exit;
                }
                
                // Decodificar JSON - os dados completos estão no campo 'dados'
                $dadosCompletos = json_decode($anvi['dados'], true);
                
                // Mesclar com os metadados
                if (is_array($dadosCompletos)) {
                    $anvi = array_merge($anvi, $dadosCompletos);
                }
                
                echo json_encode($anvi);
                
            } else {
                // Listar todas as ANVIs (apenas metadados)
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare(
                        "SELECT id, numero, revisao, cliente, projeto, produto, volume_mensal, data_anvi, status, versao, data_criacao, data_atualizacao
                         FROM anvis
                         WHERE tenant_id = ?
                         ORDER BY data_atualizacao DESC"
                    );
                    $stmt->execute([$tenant_id]);
                } else {
                    $stmt = $pdo->query("SELECT id, numero, revisao, cliente, projeto, produto, volume_mensal, data_anvi, status, versao, data_criacao, data_atualizacao FROM anvis ORDER BY data_atualizacao DESC");
                }
                $anvis = $stmt->fetchAll();
                
                echo json_encode($anvis);
            }
            break;
            
        case 'POST':
            // Salvar ANVI
            if ($user_level === 'visitante') {
                http_response_code(403);
                echo json_encode(['error' => 'Visitantes não podem salvar ANVIs']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                http_response_code(400);
                echo json_encode(['error' => 'Dados inválidos']);
                exit;
            }
            
            // Validar campos obrigatórios
            if (empty($input['id']) || empty($input['numero']) || empty($input['revisao'])) {
                echo json_encode(['success' => false, 'message' => 'ID, Nº ANVI e Revisão são obrigatórios']);
                exit;
            }
            
            $id = $input['id'];
            $numero = $input['numero'];
            $revisao = $input['revisao'];
            $cliente = $input['cliente'] ?? '';
            $projeto = $input['projeto'] ?? '';
            $produto = $input['produto'] ?? '';
            $volume_mensal = intval($input['volumeMensal'] ?? 1000);
            $data_anvi = $input['dataANVI'] ?? date('Y-m-d');
            $status = $input['status'] ?? 'em-andamento';
            $force = $input['force'] ?? false; // Flag para forçar substituição
            
            // Converter todos os dados para JSON
            $dados_json = json_encode($input, JSON_UNESCAPED_UNICODE);
            
            // Calcular hash
            $hash = hash('sha256', $dados_json);
            
            // Verificar se já existe uma ANVI com o mesmo número e revisão
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT id, numero, revisao, cliente FROM anvis WHERE numero = ? AND revisao = ? AND tenant_id = ?");
                $stmt->execute([$numero, $revisao, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id, numero, revisao, cliente FROM anvis WHERE numero = ? AND revisao = ?");
                $stmt->execute([$numero, $revisao]);
            }
            $duplicata = $stmt->fetch();
            
            // Se existe duplicata e não foi forçado, perguntar ao usuário
            if ($duplicata && !$force) {
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false,
                    'duplicate' => true,
                    'message' => "Já existe um registro salvo com Nº {$numero} Rev. {$revisao}",
                    'existing' => [
                        'id' => $duplicata['id'],
                        'numero' => $duplicata['numero'],
                        'revisao' => $duplicata['revisao'],
                        'cliente' => $duplicata['cliente']
                    ]
                ]);
                exit;
            }
            
            // Verificar se a ANVI já existe pelo ID
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT id, versao FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT id, versao FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            $existente = $stmt->fetch();
            
            if ($existente) {
                // Atualizar existente
                $nova_versao = $existente['versao'] + 1;
                
                $stmt = $pdo->prepare("
                    UPDATE anvis SET 
                        numero = ?, 
                        revisao = ?, 
                        cliente = ?, 
                        projeto = ?, 
                        produto = ?, 
                        volume_mensal = ?, 
                        data_anvi = ?, 
                        status = ?, 
                        dados = ?, 
                        versao = ?, 
                        hash_conteudo = ?,
                        atualizado_por = ?,
                        data_atualizacao = NOW()
                    WHERE id = ?" . ($tenantAwareAnvis ? " AND tenant_id = ?" : "") . "
                ");

                $params = [
                    $numero, $revisao, $cliente, $projeto, $produto, 
                    $volume_mensal, $data_anvi, $status, $dados_json, 
                    $nova_versao, $hash, $user_id, $id
                ];
                if ($tenantAwareAnvis) {
                    $params[] = $tenant_id;
                }
                $stmt->execute($params);
                
                $mensagem = 'ANVI atualizada com sucesso';
                $versao_retorno = $nova_versao;
                
            } else {
                // Inserir nova
                $nova_versao = 1;
                
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare("
                        INSERT INTO anvis (
                            id, tenant_id, numero, revisao, cliente, projeto, produto,
                            volume_mensal, data_anvi, status, dados, versao,
                            hash_conteudo, criado_por, atualizado_por
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id, $tenant_id, $numero, $revisao, $cliente, $projeto, $produto,
                        $volume_mensal, $data_anvi, $status, $dados_json,
                        $nova_versao, $hash, $user_id, $user_id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO anvis (
                            id, numero, revisao, cliente, projeto, produto, 
                            volume_mensal, data_anvi, status, dados, versao, 
                            hash_conteudo, criado_por, atualizado_por
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $id, $numero, $revisao, $cliente, $projeto, $produto,
                        $volume_mensal, $data_anvi, $status, $dados_json, 
                        $nova_versao, $hash, $user_id, $user_id
                    ]);
                }
                
                $mensagem = 'ANVI criada com sucesso';
                $versao_retorno = $nova_versao;
            }
            
            // Registrar log
            viabixLogActivity(
                $user_id,
                'salvar_anvi',
                "Salvou ANVI: {$numero} Rev. {$revisao} (versão {$versao_retorno})",
                'anvi',
                $id
            );
            
            echo json_encode([
                'success' => true,
                'message' => $mensagem,
                'versao' => $versao_retorno
            ]);
            
            break;
            
        case 'PUT':
            // Bloquear/Desbloquear ANVI para edição
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['acao'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Ação não especificada']);
                exit;
            }
            
            $acao = $input['acao'];
            
            // Limpar bloqueios antigos do próprio usuário
            if ($acao === 'limpar_bloqueios') {
                if ($tenantAwareAnvis) {
                    $stmt = $pdo->prepare("UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE bloqueado_por = ? AND tenant_id = ?");
                    $stmt->execute([$user_id, $tenant_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE bloqueado_por = ?");
                    $stmt->execute([$user_id]);
                }
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Bloqueios limpos',
                    'count' => $stmt->rowCount()
                ]);
                break;
            }
            
            // Para outras ações, ID é obrigatório
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não especificado']);
                exit;
            }
            
            $id = $input['id'];
            
            if ($acao === 'bloquear') {
                // Bloquear ANVI para edição exclusiva
                $sql = "UPDATE anvis SET bloqueado_por = ?, bloqueado_em = NOW() WHERE id = ?";
                if ($tenantAwareAnvis) {
                    $sql .= " AND tenant_id = ?";
                }
                $sql .= " AND (bloqueado_por IS NULL OR bloqueado_por = ? OR bloqueado_em < DATE_SUB(NOW(), INTERVAL 30 MINUTE))";
                $stmt = $pdo->prepare($sql);
                $params = [$user_id, $id];
                if ($tenantAwareAnvis) {
                    $params[] = $tenant_id;
                }
                $params[] = $user_id;
                $stmt->execute($params);
                
                if ($stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'ANVI bloqueada para edição']);
                } else {
                    // Verificar quem bloqueou
                    $sql = "SELECT u.nome as usuario, bloqueado_em FROM anvis a JOIN usuarios u ON a.bloqueado_por = u.id WHERE a.id = ?";
                    $params = [$id];
                    if ($tenantAwareAnvis) {
                        $sql .= " AND a.tenant_id = ?";
                        $params[] = $tenant_id;
                    }
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $bloqueio = $stmt->fetch();
                    
                    echo json_encode([
                        'success' => false, 
                        'message' => 'ANVI já está sendo editada por outro usuário',
                        'bloqueado_por' => $bloqueio['usuario'] ?? 'outro usuário',
                        'bloqueado_em' => $bloqueio['bloqueado_em'] ?? null
                    ]);
                }
            } else if ($acao === 'desbloquear') {
                // Desbloquear ANVI
                $sql = "UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE id = ? AND bloqueado_por = ?";
                $params = [$id, $user_id];
                if ($tenantAwareAnvis) {
                    $sql .= " AND tenant_id = ?";
                    $params[] = $tenant_id;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                echo json_encode(['success' => true, 'message' => 'ANVI desbloqueada']);
            }
            break;
            
        case 'DELETE':
            // Excluir ANVI
            if ($user_level === 'visitante') {
                http_response_code(403);
                echo json_encode(['error' => 'Visitantes não podem excluir ANVIs']);
                exit;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'ID não fornecido']);
                exit;
            }
            
            $id = $input['id'];
            
            // Verificar se está bloqueada
            $sql = "SELECT bloqueado_por, u.nome as usuario FROM anvis a LEFT JOIN usuarios u ON a.bloqueado_por = u.id WHERE a.id = ?";
            $params = [$id];
            if ($tenantAwareAnvis) {
                $sql .= " AND a.tenant_id = ?";
                $params[] = $tenant_id;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $anvi = $stmt->fetch();
            
            if ($anvi && $anvi['bloqueado_por'] && $anvi['bloqueado_por'] != $user_id) {
                echo json_encode([
                    'success' => false, 
                    'message' => "ANVI está sendo editada por {$anvi['usuario']}. Não é possível excluir no momento."
                ]);
                exit;
            }
            
            // Obter dados para o log
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("SELECT numero, revisao FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("SELECT numero, revisao FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            $dados = $stmt->fetch();
            
            // Excluir
            if ($tenantAwareAnvis) {
                $stmt = $pdo->prepare("DELETE FROM anvis WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$id, $tenant_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM anvis WHERE id = ?");
                $stmt->execute([$id]);
            }
            
            if ($stmt->rowCount() > 0) {
                // Registrar log
                viabixLogActivity(
                    $user_id,
                    'excluir_anvi',
                    "Excluiu ANVI: {$dados['numero']} Rev. {$dados['revisao']}",
                    'anvi',
                    $id
                );
                
                echo json_encode(['success' => true, 'message' => 'ANVI excluída com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ANVI não encontrada']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (PDOException $e) {
    logError("Erro em anvi.php", ['error' => $e->getMessage(), 'method' => $method]);
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor: ' . $e->getMessage()]);
}
?>