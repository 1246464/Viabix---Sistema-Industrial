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

$isProduction = (getenv('APP_ENV') === 'production');
ini_set('display_errors', $isProduction ? 0 : 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);
ini_set('error_log', __DIR__ . '/../logs/error_api_mysql.log');

try {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('viabix_session');
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Não autenticado'
        ]);
        exit;
    }

    require_once 'config.php';

    $pdo = getConnection();
    createTables($pdo);
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

        // Limpar mudanças antigas (mantém últimas 1000)
        $pdo->exec("DELETE FROM mudancas WHERE id < (SELECT MAX(id) - 1000 FROM (SELECT id FROM mudancas) AS m)");
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'testConnection':
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
                $total = (int)$pdo->prepare("SELECT COUNT(*) FROM projetos WHERE tenant_id = ?")->execute([$tenantId]);
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
                $dados['id'] = (int)$row['id'];
                $dados['created_at'] = $row['created_at'];
                $dados['updated_at'] = $row['updated_at'];
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
                echo json_encode([
                    'success' => true,
                    'message' => 'Projeto atualizado'
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
                echo json_encode([
                    'success' => true,
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
    http_response_code(500);
    error_log('API MySQL Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    $isProduction = (getenv('APP_ENV') === 'production');
    echo json_encode([
        'success' => false,
        'message' => $isProduction ? 'Erro interno do servidor' : ('Erro: ' . $e->getMessage()),
    ]);
}
?>