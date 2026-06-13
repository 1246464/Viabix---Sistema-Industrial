<?php
/**
 * API: consulta o vinculo entre ANVI e Projetos.
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'erro' => 'Metodo nao permitido']);
    exit;
}

$user = viabixRequireAuthenticatedSession();
$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();

if (!viabixHasTable('anvis') || !viabixHasTable('projetos')) {
    http_response_code(503);
    echo json_encode(['success' => false, 'erro' => 'Estrutura de integracao indisponivel.']);
    exit;
}

function viabixTenantWhere($tableName, &$params, $tenantId) {
    if (viabixHasColumn($tableName, 'tenant_id') && $tenantId) {
        $params[] = $tenantId;
        return ' AND tenant_id = ?';
    }

    return '';
}

function viabixDecodeProjectData($row) {
    $data = json_decode($row['dados'] ?? '{}', true);
    return is_array($data) ? $data : [];
}

function viabixEstimateProjectProgress($data) {
    if (isset($data['progresso']) && is_numeric($data['progresso'])) {
        return max(0, min(100, (int) $data['progresso']));
    }

    if (isset($data['progress']) && is_numeric($data['progress'])) {
        return max(0, min(100, (int) $data['progress']));
    }

    $tasks = $data['tasks'] ?? [];
    if (!is_array($tasks) || count($tasks) === 0) {
        return 0;
    }

    $total = 0;
    $done = 0;
    foreach ($tasks as $task) {
        if (!is_array($task)) {
            continue;
        }

        $total++;
        if (!empty($task['executed']) || !empty($task['done']) || (($task['status'] ?? '') === 'concluido')) {
            $done++;
        }
    }

    return $total > 0 ? (int) round(($done / $total) * 100) : 0;
}

function viabixProjectPayload($row) {
    $data = viabixDecodeProjectData($row);
    $projectName = $data['projectName'] ?? $data['nome'] ?? $data['name'] ?? ('Projeto #' . $row['id']);
    $status = $data['manualStatus'] ?? $data['status'] ?? $data['fase'] ?? 'Pendente';

    return [
        'id' => (int) $row['id'],
        'nome' => (string) $projectName,
        'cliente' => (string) ($data['cliente'] ?? ''),
        'status' => (string) $status,
        'fase' => (string) ($data['fase'] ?? $status),
        'progresso' => viabixEstimateProjectProgress($data),
        'lider' => (string) ($data['projectLeader'] ?? $data['lider'] ?? ''),
        'codigo' => (string) ($data['codigo'] ?? ''),
        'updated_at' => $row['updated_at'] ?? null,
        'created_at' => $row['created_at'] ?? null,
        'url' => 'Controle_de_projetos/index.php?projeto_id=' . (int) $row['id'],
    ];
}

function viabixAnviPayload($row) {
    $name = trim(($row['numero'] ?? '') . ' Rev. ' . ($row['revisao'] ?? ''));

    return [
        'id' => (string) ($row['id'] ?? ''),
        'numero' => (string) ($row['numero'] ?? ''),
        'revisao' => (string) ($row['revisao'] ?? ''),
        'nome' => $name,
        'cliente' => (string) ($row['cliente'] ?? ''),
        'projeto' => (string) ($row['projeto'] ?? ''),
        'produto' => (string) ($row['produto'] ?? ''),
        'status' => (string) ($row['status'] ?? ''),
        'url' => 'anvi.html?anvi_id=' . rawurlencode((string) ($row['id'] ?? '')),
    ];
}

function viabixFindAnviById($anviId, $tenantId) {
    global $pdo;

    $select = 'id, numero, revisao, cliente, projeto, produto';
    if (viabixHasColumn('anvis', 'status')) {
        $select .= ', status';
    }
    if (viabixHasColumn('anvis', 'projeto_id')) {
        $select .= ', projeto_id';
    }

    $params = [$anviId];
    $where = 'id = ?';

    if (viabixHasColumn('anvis', 'numero') && viabixHasColumn('anvis', 'revisao')) {
        $where = '(id = ? OR CONCAT(numero, "_", revisao) = ?)';
        $params[] = $anviId;
    }

    $sql = 'SELECT ' . $select . ' FROM anvis WHERE ' . $where;
    $sql .= viabixTenantWhere('anvis', $params, $tenantId);

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function viabixFindProjectById($projectId, $tenantId) {
    global $pdo;

    $params = [(int) $projectId];
    $sql = 'SELECT id, dados, created_at, updated_at FROM projetos WHERE id = ?';
    $sql .= viabixTenantWhere('projetos', $params, $tenantId);

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function viabixFindProjectForAnvi($anvi, $tenantId) {
    global $pdo;

    if (!empty($anvi['projeto_id'])) {
        $project = viabixFindProjectById((int) $anvi['projeto_id'], $tenantId);
        if ($project) {
            return $project;
        }
    }

    $params = [(string) $anvi['id'], (string) $anvi['id']];
    $sql = "SELECT id, dados, created_at, updated_at
            FROM projetos
            WHERE (
                JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')) = ?
                OR JSON_UNQUOTE(JSON_EXTRACT(dados, '$.sourceContext.anviId')) = ?
            ";

    if (!empty($anvi['numero'])) {
        $params[] = (string) $anvi['numero'];
        $sql .= " OR JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviNumber')) = ?";
    }

    $sql .= ')';
    $sql .= viabixTenantWhere('projetos', $params, $tenantId);

    $stmt = $pdo->prepare($sql . ' ORDER BY id DESC LIMIT 1');
    $stmt->execute($params);

    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function viabixFindAnviForProject($project, $tenantId) {
    global $pdo;

    if (viabixHasColumn('anvis', 'projeto_id')) {
        $params = [(int) $project['id']];
        $select = 'id, numero, revisao, cliente, projeto, produto';
        if (viabixHasColumn('anvis', 'status')) {
            $select .= ', status';
        }
        $sql = 'SELECT ' . $select . ' FROM anvis WHERE projeto_id = ?';
        $sql .= viabixTenantWhere('anvis', $params, $tenantId);

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $anvi = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($anvi) {
            return $anvi;
        }
    }

    $data = viabixDecodeProjectData($project);
    $anviId = $data['anviId'] ?? null;
    if (!$anviId) {
        return null;
    }

    return viabixFindAnviById((string) $anviId, $tenantId);
}

try {
    $anviId = trim((string) ($_GET['anvi_id'] ?? ''));
    $projectId = trim((string) ($_GET['projeto_id'] ?? ''));

    if ($anviId !== '') {
        $anvi = viabixFindAnviById($anviId, $tenantId);
        if (!$anvi) {
            http_response_code(404);
            echo json_encode(['success' => false, 'tem_vinculo' => false, 'erro' => 'ANVI nao encontrada']);
            exit;
        }

        $project = viabixFindProjectForAnvi($anvi, $tenantId);
        echo json_encode([
            'success' => true,
            'tem_vinculo' => (bool) $project,
            'anvi' => viabixAnviPayload($anvi),
            'projeto' => $project ? viabixProjectPayload($project) : null,
        ]);
        exit;
    }

    if ($projectId !== '' && ctype_digit($projectId)) {
        $project = viabixFindProjectById((int) $projectId, $tenantId);
        if (!$project) {
            http_response_code(404);
            echo json_encode(['success' => false, 'tem_vinculo' => false, 'erro' => 'Projeto nao encontrado']);
            exit;
        }

        $anvi = viabixFindAnviForProject($project, $tenantId);
        echo json_encode([
            'success' => true,
            'tem_vinculo' => (bool) $anvi,
            'projeto' => viabixProjectPayload($project),
            'anvi' => $anvi ? viabixAnviPayload($anvi) : null,
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'tem_vinculo' => false, 'erro' => 'Informe anvi_id ou projeto_id.']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erro' => $e->getMessage()]);
}
?>
