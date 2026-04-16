<?php
/**
 * API: Verificar vínculo entre ANVI e Projeto
 * Compatível com `projetos.dados` em JSON e com `anvis.projeto_id` quando disponível.
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$user = viabixRequireAuthenticatedSession();
$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();

function vinculoTenantClause($tableName, $tenantId, &$params) {
    if ($tenantId && viabixHasColumn($tableName, 'tenant_id')) {
        $params[] = $tenantId;
        return ' AND tenant_id = ?';
    }

    return '';
}

function vinculoLoadProject($projectId, $tenantId) {
    global $pdo;

    $params = [$projectId];
    $sql = 'SELECT id, dados FROM projetos WHERE id = ?';
    $sql .= vinculoTenantClause('projetos', $tenantId, $params);
    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $dados = json_decode($row['dados'] ?? '{}', true);
    if (!is_array($dados)) {
        $dados = [];
    }

    $progress = 0;
    if (!empty($dados['tasks']) && is_array($dados['tasks'])) {
        $total = count($dados['tasks']);
        $done = 0;
        foreach ($dados['tasks'] as $task) {
            if (!empty($task['executed'])) {
                $done++;
            }
        }
        if ($total > 0) {
            $progress = (int) round(($done / $total) * 100);
        }
    }

    return [
        'id' => (int) $row['id'],
        'dados' => $dados,
        'resumo' => [
            'id' => (int) $row['id'],
            'nome' => (string) ($dados['projectName'] ?? 'Projeto ' . $row['id']),
            'status' => (string) ($dados['status'] ?? 'Pendente'),
            'progresso' => $progress,
            'lider' => (string) ($dados['projectLeader'] ?? ''),
        ],
    ];
}

function vinculoLoadAnviById($anviId, $tenantId) {
    global $pdo;

    $params = [$anviId];
    $sql = 'SELECT id, numero, revisao, cliente, projeto, produto, dados';
    if (viabixHasColumn('anvis', 'projeto_id')) {
        $sql .= ', projeto_id';
    }
    $sql .= ' FROM anvis WHERE id = ?';
    $sql .= vinculoTenantClause('anvis', $tenantId, $params);

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $dados = json_decode($row['dados'] ?? '{}', true);
    if (!is_array($dados)) {
        $dados = [];
    }

    return [
        'row' => $row,
        'resumo' => [
            'id' => (string) $row['id'],
            'nome' => (string) ($row['produto'] ?: $row['projeto'] ?: $row['numero'] . ' Rev. ' . $row['revisao']),
            'valor' => $dados['calcPrecoBase'] ?? null,
        ],
    ];
}

function vinculoFindProjectByAnviId($anviId, $tenantId) {
    global $pdo;

    $params = [$anviId];
    $sql = "SELECT id FROM projetos WHERE JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')) = ?";
    $sql .= vinculoTenantClause('projetos', $tenantId, $params);
    $stmt = $pdo->prepare($sql . ' ORDER BY id DESC LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

function vinculoFindAnviByProjectId($projectId, $tenantId, $projectPayload = []) {
    global $pdo;

    if (viabixHasColumn('anvis', 'projeto_id')) {
        $params = [$projectId];
        $sql = 'SELECT id FROM anvis WHERE projeto_id = ?';
        $sql .= vinculoTenantClause('anvis', $tenantId, $params);
        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();

        if ($row) {
            return (string) $row['id'];
        }
    }

    return trim((string) ($projectPayload['anviId'] ?? '')) ?: null;
}

$anviId = trim((string) ($_GET['anvi_id'] ?? ''));
$projetoId = isset($_GET['projeto_id']) ? (int) $_GET['projeto_id'] : 0;

if ($anviId === '' && $projetoId <= 0) {
    http_response_code(400);
    echo json_encode(['erro' => 'ID da ANVI ou Projeto é obrigatório']);
    exit;
}

$vinculo = [
    'tem_vinculo' => false,
    'anvi_id' => null,
    'projeto_id' => null,
    'anvi' => null,
    'projeto' => null,
];

try {
    if ($anviId !== '') {
        $anvi = vinculoLoadAnviById($anviId, $tenantId);
        if ($anvi) {
            $vinculo['anvi_id'] = $anvi['resumo']['id'];
            $vinculo['anvi'] = $anvi['resumo'];

            $projectId = !empty($anvi['row']['projeto_id']) ? (int) $anvi['row']['projeto_id'] : vinculoFindProjectByAnviId($anviId, $tenantId);
            if ($projectId) {
                $project = vinculoLoadProject($projectId, $tenantId);
                if ($project) {
                    $vinculo['tem_vinculo'] = true;
                    $vinculo['projeto_id'] = $project['resumo']['id'];
                    $vinculo['projeto'] = $project['resumo'];
                }
            }
        }
    } else {
        $project = vinculoLoadProject($projetoId, $tenantId);
        if ($project) {
            $vinculo['projeto_id'] = $project['resumo']['id'];
            $vinculo['projeto'] = $project['resumo'];

            $resolvedAnviId = vinculoFindAnviByProjectId($projetoId, $tenantId, $project['dados']);
            if ($resolvedAnviId) {
                $anvi = vinculoLoadAnviById($resolvedAnviId, $tenantId);
                if ($anvi) {
                    $vinculo['tem_vinculo'] = true;
                    $vinculo['anvi_id'] = $anvi['resumo']['id'];
                    $vinculo['anvi'] = $anvi['resumo'];
                }
            }
        }
    }

    echo json_encode($vinculo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
?>