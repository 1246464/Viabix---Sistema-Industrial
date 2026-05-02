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

ini_set('display_errors', 1);
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

    $conn = getConnection();
    createTables($conn);
    $tenantId = getCurrentTenantId();
    $tenantAwareProjects = tenantFilterEnabled($conn, 'projetos');
    $tenantAwareLeaders = tenantFilterEnabled($conn, 'lideres');
    $tenantAwareChanges = tenantFilterEnabled($conn, 'mudancas');

    // Função para registrar mudanças para sincronização em tempo real
    function registrarMudanca($conn, $tipo, $itemId, $tenantId = null) {
        if ($tenantId && schemaHasColumn($conn, 'mudancas', 'tenant_id')) {
            $stmt = $conn->prepare("INSERT INTO mudancas (tenant_id, tipo, item_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $tenantId, $tipo, $itemId);
        } else {
            $stmt = $conn->prepare("INSERT INTO mudancas (tipo, item_id) VALUES (?, ?)");
            $stmt->bind_param("si", $tipo, $itemId);
        }
        $stmt->execute();
        $stmt->close();
        
        // Limpar mudanças antigas (mantém últimas 1000)
        $conn->query("DELETE FROM mudancas WHERE id < (SELECT MAX(id) - 1000 FROM (SELECT id FROM mudancas) AS m)");
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
            if ($tenantAwareLeaders) {
                $stmt = $conn->prepare("SELECT * FROM lideres WHERE tenant_id = ? ORDER BY nome");
                $stmt->bind_param("s", $tenantId);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query("SELECT * FROM lideres ORDER BY nome");
            }
            $leaders = [];

            while ($row = $result->fetch_assoc()) {
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
            if ($tenantAwareProjects) {
                $stmt = $conn->prepare("SELECT * FROM projetos WHERE tenant_id = ? ORDER BY id");
                $stmt->bind_param("s", $tenantId);
                $stmt->execute();
                $result = $stmt->get_result();
            } else {
                $result = $conn->query("SELECT * FROM projetos ORDER BY id");
            }
            $projects = [];

            while ($row = $result->fetch_assoc()) {
                $dados = json_decode($row['dados'], true);
                $dados['id'] = (int)$row['id'];
                $dados['created_at'] = $row['created_at'];
                $dados['updated_at'] = $row['updated_at'];
                $projects[] = $dados;
            }

            echo json_encode([
                'success' => true,
                'data' => $projects
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

            $json = $conn->real_escape_string(json_encode($data, JSON_UNESCAPED_UNICODE));

            if (!empty($data['id'])) {
                $id = (int)$data['id'];
                $sql = "UPDATE projetos SET dados='$json' WHERE id=$id";
                if ($tenantAwareProjects) {
                    $safeTenantId = $conn->real_escape_string($tenantId);
                    $sql .= " AND tenant_id='$safeTenantId'";
                }
                
                if ($conn->query($sql)) {
                    registrarMudanca($conn, 'projeto_atualizado', $id, $tenantId);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Projeto atualizado'
                    ]);
                } else {
                    throw new Exception('Erro ao atualizar: ' . $conn->error);
                }
            } else {
                if ($tenantAwareProjects) {
                    $safeTenantId = $conn->real_escape_string($tenantId);
                    $sql = "INSERT INTO projetos (tenant_id, dados) VALUES ('$safeTenantId', '$json')";
                } else {
                    $sql = "INSERT INTO projetos (dados) VALUES ('$json')";
                }
                
                if ($conn->query($sql)) {
                    $insertId = $conn->insert_id;
                    registrarMudanca($conn, 'projeto_criado', $insertId, $tenantId);
                    echo json_encode([
                        'success' => true,
                        'insertId' => $insertId
                    ]);
                } else {
                    throw new Exception('Erro ao inserir: ' . $conn->error);
                }
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
            
            $sql = "DELETE FROM projetos WHERE id=$id";
            if ($tenantAwareProjects) {
                $safeTenantId = $conn->real_escape_string($tenantId);
                $sql .= " AND tenant_id='$safeTenantId'";
            }
            
            if ($conn->query($sql)) {
                if ($conn->affected_rows > 0) {
                    registrarMudanca($conn, 'projeto_excluido', $id, $tenantId);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Projeto não encontrado'
                    ]);
                }
            } else {
                throw new Exception('Erro ao excluir: ' . $conn->error);
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

            $nome = $conn->real_escape_string($data['name']);
            $email = $conn->real_escape_string($data['email']);
            $depto = $conn->real_escape_string($data['department']);

            if (!empty($data['id'])) {
                $id = (int)$data['id'];

                $sql = "UPDATE lideres 
                        SET nome='$nome', email='$email', departamento='$depto' 
                        WHERE id=$id";
                if ($tenantAwareLeaders) {
                    $safeTenantId = $conn->real_escape_string($tenantId);
                    $sql .= " AND tenant_id='$safeTenantId'";
                }
                
                if ($conn->query($sql)) {
                    if ($conn->affected_rows > 0) {
                        registrarMudanca($conn, 'lider_atualizado', $id, $tenantId);
                        echo json_encode(['success' => true]);
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Líder não encontrado ou dados inalterados'
                        ]);
                    }
                } else {
                    throw new Exception('Erro ao atualizar: ' . $conn->error);
                }
            } else {
                if ($tenantAwareLeaders) {
                    $safeTenantId = $conn->real_escape_string($tenantId);
                    $sql = "INSERT INTO lideres (tenant_id, nome, email, departamento) 
                            VALUES ('$safeTenantId', '$nome', '$email', '$depto')";
                } else {
                    $sql = "INSERT INTO lideres (nome, email, departamento) 
                            VALUES ('$nome', '$email', '$depto')";
                }
                
                if ($conn->query($sql)) {
                    $insertId = $conn->insert_id;
                    registrarMudanca($conn, 'lider_criado', $insertId, $tenantId);
                    echo json_encode([
                        'success' => true,
                        'insertId' => $insertId
                    ]);
                } else {
                    throw new Exception('Erro ao inserir: ' . $conn->error);
                }
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
            $checkSql = "SELECT COUNT(*) as total FROM projetos 
                        WHERE JSON_EXTRACT(dados, '$.leaderId') = $id";
            if ($tenantAwareProjects) {
                $safeTenantId = $conn->real_escape_string($tenantId);
                $checkSql .= " AND tenant_id='$safeTenantId'";
            }
            $checkResult = $conn->query($checkSql);
            
            if ($checkResult) {
                $row = $checkResult->fetch_assoc();
                if ($row['total'] > 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => "Existem {$row['total']} projeto(s) associado(s) a este líder"
                    ]);
                    break;
                }
            }
            
            $sql = "DELETE FROM lideres WHERE id=$id";
            if ($tenantAwareLeaders) {
                $safeTenantId = $conn->real_escape_string($tenantId);
                $sql .= " AND tenant_id='$safeTenantId'";
            }
            
            if ($conn->query($sql)) {
                if ($conn->affected_rows > 0) {
                    registrarMudanca($conn, 'lider_excluido', $id, $tenantId);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Líder não encontrado'
                    ]);
                }
            } else {
                throw new Exception('Erro ao excluir: ' . $conn->error);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Ação inválida'
            ]);
    }

    if (isset($conn)) {
        $conn->close();
    }

} catch (Throwable $e) {
    http_response_code(500);
    error_log('API MySQL Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao conectar ao servidor: ' . $e->getMessage(),
        'error_code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>