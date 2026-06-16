<?php
header('Content-Type: application/json; charset=utf-8');

// CORS: permitir apenas o mesmo domínio para proteger cookies de sessão
$allowed_origins = [
    'https://viabix.com.br',
    'https://www.viabix.com.br',
    'http://localhost',
    'http://localhost:80',
    'http://localhost:8080'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = $_SERVER['HTTP_HOST'] ?? '';
$isLocalHost = preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $host) === 1;
$isProduction = (getenv('APP_ENV') === 'production') || !$isLocalHost;
ini_set('display_errors', $isProduction ? '0' : '1');
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/error_api_mysql.log');

try {
    require_once __DIR__ . '/../api/config.php';

    $authenticatedUser = function_exists('viabixGetAuthenticatedUser') ? viabixGetAuthenticatedUser() : null;
    if (!$authenticatedUser) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Não autenticado'
        ]);
        exit;
    }

    if (empty($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $authenticatedUser['id'];
        $_SESSION['user_login'] = $authenticatedUser['login'] ?? '';
        $_SESSION['user_nome'] = $authenticatedUser['nome'] ?? '';
        $_SESSION['user_level'] = $authenticatedUser['nivel'] ?? '';
        $_SESSION['tenant_id'] = $authenticatedUser['tenant_id'] ?? null;
    }

    require_once __DIR__ . '/config.php';

    $pdo = getConnection();
    $tenantId = getCurrentTenantId();
    $tenantAwareProjects = tenantFilterEnabled($pdo, 'projetos');
    $tenantAwareLeaders = tenantFilterEnabled($pdo, 'lideres');
    $tenantAwareChanges = tenantFilterEnabled($pdo, 'mudancas');

    // Registra mudanças para sincronização em tempo real via SSE
    function registrarMudanca($pdo, $tipo, $itemId, $tenantId = null) {
        if ($tenantId && schemaHasColumn($pdo, 'mudancas', 'tenant_id')) {
            $stmt = $pdo->prepare("INSERT INTO mudancas (tenant_id, tipo, item_id) VALUES (?, ?, ?)");
            $stmt->execute([$tenantId, $tipo, $itemId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mudancas (tipo, item_id) VALUES (?, ?)");
            $stmt->execute([$tipo, $itemId]);
        }

        // Limpar mudanças antigas periodicamente para não pesar em todo salvamento.
        $lastChangeId = (int)$pdo->lastInsertId();
        if ($lastChangeId > 0 && $lastChangeId % 50 === 0) {
            $pdo->exec("DELETE FROM mudancas WHERE id < (SELECT MAX(id) - 1000 FROM (SELECT id FROM mudancas) AS m)");
        }
    }

    function vincularAnviSelecionadaAoProjeto($pdo, $projectData, $projectId, $tenantId = null) {
        $anviId = trim((string)($projectData['anviId'] ?? ($projectData['sourceContext']['anviId'] ?? '')));
        if ($anviId === '' || $projectId <= 0 || !schemaHasColumn($pdo, 'anvis', 'projeto_id')) {
            return;
        }

        $sql = "UPDATE anvis SET projeto_id = ?";
        $params = [$projectId];

        if (schemaHasColumn($pdo, 'anvis', 'atualizado_por') && !empty($_SESSION['user_id'])) {
            $sql .= ", atualizado_por = ?";
            $params[] = $_SESSION['user_id'];
        }

        $sql .= " WHERE id = ?";
        $params[] = $anviId;

        if ($tenantId && schemaHasColumn($pdo, 'anvis', 'tenant_id')) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenantId;
        }

        $pdo->prepare($sql)->execute($params);
    }

    function registrarAcaoProjeto($userId, $action, $details, $projectId = null) {
        if (!function_exists('viabixLogActivity')) {
            return;
        }

        try {
            viabixLogActivity($userId, $action, $details, 'projeto', $projectId);
        } catch (Throwable $e) {
            if (function_exists('logError')) {
                logError('Falha ao registrar ação de projeto', [
                    'error' => $e->getMessage(),
                    'action' => $action,
                    'project_id' => $projectId,
                ]);
            }
        }
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'testConnection':
            createTables($pdo);
            echo json_encode([
                'success' => true,
                'message' => 'Conectado ao MySQL'
            ]);
            break;

        case 'getLeaders':
            // Líderes são poucos — sem paginação (limite máximo: 200)
            if ($tenantAwareLeaders) {
                $stmt = $pdo->prepare("SELECT * FROM lideres WHERE tenant_id = ? ORDER BY nome LIMIT 200");
                $stmt->execute([$tenantId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM lideres ORDER BY nome LIMIT 200");
            }
            $leaders = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $leaders[] = [
                    'id' => (int)$row['id'],
                    'name' => $row['nome'],
                    'email' => $row['email'],
                    'department' => $row['departamento']
                ];
            }

            echo json_encode([
                'success' => true,
                'data' => $leaders
            ]);
            break;

        case 'getProjects':
            $page  = max(1, (int)($_POST['page'] ?? 1));
            $limit = min(100, max(1, (int)($_POST['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;

            if ($tenantAwareProjects) {
                $cStmt = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE tenant_id = ?");
                $cStmt->execute([$tenantId]);
                $total = (int)$cStmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT * FROM projetos WHERE tenant_id = ? ORDER BY id LIMIT ? OFFSET ?");
                $stmt->execute([$tenantId, $limit, $offset]);
            } else {
                $total = (int)$pdo->query("SELECT COUNT(*) FROM projetos")->fetchColumn();
                $stmt  = $pdo->prepare("SELECT * FROM projetos ORDER BY id LIMIT ? OFFSET ?");
                $stmt->execute([$limit, $offset]);
            }

            $projects = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $dados = json_decode($row['dados'], true);
                if (!is_array($dados)) {
                    $dados = [];
                }
                $dados['id'] = (int)$row['id'];
                $dados['created_at'] = $row['created_at'] ?? null;
                $dados['updated_at'] = $row['updated_at'] ?? null;
                $projects[] = $dados;
            }

            echo json_encode([
                'success'     => true,
                'data'        => $projects,
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int)ceil($total / $limit),
            ]);
            break;

        case 'saveProject':
            $data = json_decode($_POST['project'] ?? '', true);

            if (!$data) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados inválidos'
                ]);
                break;
            }

            // Remover campos de data que são gerenciados pelo MySQL
            unset($data['created_at']);
            unset($data['updated_at']);

            $json = json_encode($data, JSON_UNESCAPED_UNICODE);

            if (!empty($data['id'])) {
                $id = (int)$data['id'];
                if ($tenantAwareProjects) {
                    $stmt = $pdo->prepare("UPDATE projetos SET dados=? WHERE id=? AND tenant_id=?");
                    $stmt->execute([$json, $id, $tenantId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE projetos SET dados=? WHERE id=?");
                    $stmt->execute([$json, $id]);
                }
                registrarMudanca($pdo, 'projeto_atualizado', $id, $tenantId);
                vincularAnviSelecionadaAoProjeto($pdo, $data, $id, $tenantId);
                registrarAcaoProjeto(
                    $_SESSION['user_id'] ?? null,
                    'salvar_projeto',
                    'Atualizou projeto #' . $id . ': ' . ($data['projectName'] ?? 'Sem nome'),
                    (string)$id
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Projeto atualizado',
                    'updatedId' => $id
                ]);
            } else {
                if ($tenantAwareProjects) {
                    $stmt = $pdo->prepare("INSERT INTO projetos (tenant_id, dados) VALUES (?, ?)");
                    $stmt->execute([$tenantId, $json]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO projetos (dados) VALUES (?)");
                    $stmt->execute([$json]);
                }
                $insertId = (int)$pdo->lastInsertId();
                registrarMudanca($pdo, 'projeto_criado', $insertId, $tenantId);
                vincularAnviSelecionadaAoProjeto($pdo, $data, $insertId, $tenantId);
                registrarAcaoProjeto(
                    $_SESSION['user_id'] ?? null,
                    'criar_projeto',
                    'Criou projeto #' . $insertId . ': ' . ($data['projectName'] ?? 'Sem nome'),
                    (string)$insertId
                );
                echo json_encode([
                    'success' => true,
                    'message' => 'Projeto salvo',
                    'insertId' => $insertId
                ]);
            }
            break;

        case 'deleteProject':
            $id = (int)($_POST['projectId'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID inválido'
                ]);
                break;
            }
            
            if ($tenantAwareProjects) {
                $stmt = $pdo->prepare("DELETE FROM projetos WHERE id=? AND tenant_id=?");
                $stmt->execute([$id, $tenantId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM projetos WHERE id=?");
                $stmt->execute([$id]);
            }
            $affected = $stmt->rowCount();
            
            if ($affected > 0) {
                registrarMudanca($pdo, 'projeto_excluido', $id, $tenantId);
                registrarAcaoProjeto(
                    $_SESSION['user_id'] ?? null,
                    'excluir_projeto',
                    'Excluiu projeto #' . $id,
                    (string)$id
                );
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Projeto não encontrado'
                ]);
            }
            break;

        case 'saveLeader':
            $data = json_decode($_POST['leader'] ?? '', true);

            if (!$data) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados inválidos'
                ]);
                break;
            }

            $nome  = trim($data['name'] ?? '');
            $email = trim($data['email'] ?? '');
            $depto = trim($data['department'] ?? '');

            if (!empty($data['id'])) {
                $id = (int)$data['id'];

                if ($tenantAwareLeaders) {
                    $stmt = $pdo->prepare("UPDATE lideres SET nome=?, email=?, departamento=? WHERE id=? AND tenant_id=?");
                    $stmt->execute([$nome, $email, $depto, $id, $tenantId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE lideres SET nome=?, email=?, departamento=? WHERE id=?");
                    $stmt->execute([$nome, $email, $depto, $id]);
                }
                $affected = $stmt->rowCount();

                if ($affected > 0) {
                    registrarMudanca($pdo, 'lider_atualizado', $id, $tenantId);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Líder não encontrado ou dados inalterados'
                    ]);
                }
            } else {
                if ($tenantAwareLeaders) {
                    $stmt = $pdo->prepare("INSERT INTO lideres (tenant_id, nome, email, departamento) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$tenantId, $nome, $email, $depto]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO lideres (nome, email, departamento) VALUES (?, ?, ?)");
                    $stmt->execute([$nome, $email, $depto]);
                }
                $insertId = (int)$pdo->lastInsertId();
                registrarMudanca($pdo, 'lider_criado', $insertId, $tenantId);
                echo json_encode([
                    'success' => true,
                    'insertId' => $insertId
                ]);
            }
            break;

        case 'deleteLeader':
            $id = (int)($_POST['leaderId'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID inválido'
                ]);
                break;
            }
            
            // Verificar se existem projetos associados a este líder
            if ($tenantAwareProjects) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE JSON_EXTRACT(dados, '$.leaderId') = ? AND tenant_id=?");
                $stmt->execute([$id, $tenantId]);
            } else {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE JSON_EXTRACT(dados, '$.leaderId') = ?");
                $stmt->execute([$id]);
            }
            $count = (int)$stmt->fetchColumn();
            if ($count > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => "Existem {$count} projeto(s) associado(s) a este líder"
                ]);
                break;
            }

            if ($tenantAwareLeaders) {
                $stmt = $pdo->prepare("DELETE FROM lideres WHERE id=? AND tenant_id=?");
                $stmt->execute([$id, $tenantId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM lideres WHERE id=?");
                $stmt->execute([$id]);
            }
            $affected = $stmt->rowCount();

            if ($affected > 0) {
                registrarMudanca($pdo, 'lider_excluido', $id, $tenantId);
                echo json_encode(['success' => true]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Líder não encontrado'
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação inválida'
            ]);
    }

    // PDO closes automatically when $pdo goes out of scope

} catch (Throwable $e) {
    $errorId = function_exists('viabixGenerateErrorId')
        ? viabixGenerateErrorId('proj')
        : ('proj_' . date('Ymd_His') . '_' . substr(sha1($e->getMessage()), 0, 8));
    http_response_code(500);
    if (function_exists('logError')) {
        logError('Erro na API do Controle de Projetos', [
            'error_id' => $errorId,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'action' => $_POST['action'] ?? null,
            'tenant_id' => $_SESSION['tenant_id'] ?? null,
            'user_id' => $_SESSION['user_id'] ?? null,
        ]);
    } else {
        error_log('API MySQL Error [' . $errorId . ']: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    }
    
    $friendlyMessage = function_exists('viabixPublicErrorMessage')
        ? viabixPublicErrorMessage($e, 'Não foi possível salvar agora. Tente novamente em instantes.')
        : 'Não foi possível salvar agora. Tente novamente em instantes.';
    echo json_encode([
        'success' => false,
        'message' => $friendlyMessage,
        'error_id' => $errorId,
    ]);
}
?>
