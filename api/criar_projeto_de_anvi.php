<?php
/**
 * API: Criar Projeto a partir de uma ANVI
 * Compatível com o schema atual de projetos (payload JSON em `projetos.dados`).
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'erro' => 'Método não permitido']);
    exit;
}

$user = viabixRequireAuthenticatedSession();
$tenantId = $user['tenant_id'] ?? viabixCurrentTenantId();

if (!viabixHasTable('anvis') || !viabixHasTable('projetos')) {
    http_response_code(503);
    echo json_encode(['sucesso' => false, 'erro' => 'Estrutura de integração ANVI-Projetos indisponível.']);
    exit;
}

function projetoDefaultTasks() {
    return [
        'kom' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 1, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'ferramental' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 5, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'cadBomFt' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 3, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'tryout' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 3, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'entrega' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 1, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'psw' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 1, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
        'handover' => ['planned' => null, 'start' => null, 'executed' => null, 'duration' => 1, 'number' => null, 'quantidadeEntrada' => null, 'quantidadeSaida' => null, 'resources' => null, 'history' => []],
    ];
}

function projetoFindExistingLink($anviId, $tenantId) {
    global $pdo;

    if (viabixHasColumn('anvis', 'projeto_id')) {
        $sql = 'SELECT projeto_id FROM anvis WHERE id = ?';
        $params = [$anviId];

        if (viabixHasColumn('anvis', 'tenant_id') && $tenantId) {
            $sql .= ' AND tenant_id = ?';
            $params[] = $tenantId;
        }

        $stmt = $pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch();

        if (!empty($row['projeto_id'])) {
            return (int) $row['projeto_id'];
        }
    }

    $sql = "SELECT id FROM projetos WHERE JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')) = ?";
    $params = [$anviId];

    if (viabixHasColumn('projetos', 'tenant_id') && $tenantId) {
        $sql .= ' AND tenant_id = ?';
        $params[] = $tenantId;
    }

    $stmt = $pdo->prepare($sql . ' ORDER BY id DESC LIMIT 1');
    $stmt->execute($params);
    $row = $stmt->fetch();

    return $row ? (int) $row['id'] : null;
}

$dados = json_decode(file_get_contents('php://input'), true);
if (!is_array($dados)) {
    $dados = $_POST;
}

$anviId = trim((string) ($dados['anvi_id'] ?? ''));
$nomeProjeto = trim((string) ($dados['nome_projeto'] ?? ''));
$descricao = trim((string) ($dados['descricao'] ?? ''));
$liderId = isset($dados['lider_id']) && $dados['lider_id'] !== '' ? (int) $dados['lider_id'] : null;
$dataInicio = trim((string) ($dados['data_inicio'] ?? date('Y-m-d')));
$dataFimPrevista = trim((string) ($dados['data_fim_prevista'] ?? '')) ?: null;

if ($anviId === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'ID da ANVI inválido']);
    exit;
}

if ($nomeProjeto === '') {
    http_response_code(400);
    echo json_encode(['sucesso' => false, 'erro' => 'Nome do projeto é obrigatório']);
    exit;
}

try {
    $sql = 'SELECT id, numero, revisao, cliente, projeto, produto, volume_mensal, dados';
    if (viabixHasColumn('anvis', 'projeto_id')) {
        $sql .= ', projeto_id';
    }
    $sql .= ' FROM anvis WHERE id = ?';
    $params = [$anviId];

    if (viabixHasColumn('anvis', 'tenant_id') && $tenantId) {
        $sql .= ' AND tenant_id = ?';
        $params[] = $tenantId;
    }

    $stmt = $pdo->prepare($sql . ' LIMIT 1');
    $stmt->execute($params);
    $anvi = $stmt->fetch();

    if (!$anvi) {
        http_response_code(404);
        echo json_encode(['sucesso' => false, 'erro' => 'ANVI não encontrada']);
        exit;
    }

    $projetoExistenteId = projetoFindExistingLink($anviId, $tenantId);
    if ($projetoExistenteId) {
        http_response_code(409);
        echo json_encode([
            'sucesso' => false,
            'erro' => 'Esta ANVI já está vinculada ao projeto #' . $projetoExistenteId,
            'projeto_id' => $projetoExistenteId,
        ]);
        exit;
    }

    $anviDados = json_decode($anvi['dados'] ?? '{}', true);
    if (!is_array($anviDados)) {
        $anviDados = [];
    }

    $liderNome = '';
    if ($liderId !== null && viabixHasTable('lideres')) {
        $leaderSql = 'SELECT id, nome FROM lideres WHERE id = ?';
        $leaderParams = [$liderId];

        if (viabixHasColumn('lideres', 'tenant_id') && $tenantId) {
            $leaderSql .= ' AND tenant_id = ?';
            $leaderParams[] = $tenantId;
        }

        $stmt = $pdo->prepare($leaderSql . ' LIMIT 1');
        $stmt->execute($leaderParams);
        $lider = $stmt->fetch();

        if ($lider) {
            $liderNome = (string) $lider['nome'];
        } else {
            $liderId = null;
        }
    }

    if ($descricao === '') {
        $descricao = 'Projeto criado a partir da ANVI ' . $anvi['numero'] . ' Rev. ' . $anvi['revisao'];
    }

    $projectPayload = [
        'cliente' => (string) ($anvi['cliente'] ?? ''),
        'projectName' => $nomeProjeto,
        'segmento' => (string) ($anviDados['segment'] ?? $anviDados['segmento'] ?? ''),
        'leaderId' => $liderId,
        'codigo' => (string) ($anviDados['codigo'] ?? ''),
        'anviNumber' => (string) ($anvi['numero'] ?? ''),
        'modelo' => (string) ($anviDados['modelo'] ?? $anviDados['geometry'] ?? ''),
        'processo' => (string) ($anviDados['processo'] ?? ''),
        'fase' => 'Planejamento',
        'observacoes' => $descricao,
        'projectLeader' => $liderNome,
        'tasks' => projetoDefaultTasks(),
        'manualStatus' => null,
        'status' => 'Pendente',
        'createdAt' => date('c'),
        'capability' => ['characteristics' => []],
        'apqp' => new stdClass(),
        'anviId' => $anvi['id'],
        'anviRevision' => (string) ($anvi['revisao'] ?? ''),
        'source' => 'anvi',
        'sourceContext' => [
            'produto' => (string) ($anvi['produto'] ?? ''),
            'projeto' => (string) ($anvi['projeto'] ?? ''),
            'volumeMensal' => (int) ($anvi['volume_mensal'] ?? 0),
            'dataInicio' => $dataInicio,
            'dataFimPrevista' => $dataFimPrevista,
        ],
    ];

    $jsonPayload = json_encode($projectPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $insertColumns = ['dados'];
    $placeholders = ['?'];
    $insertValues = [$jsonPayload];

    if (viabixHasColumn('projetos', 'tenant_id')) {
        $insertColumns[] = 'tenant_id';
        $placeholders[] = '?';
        $insertValues[] = $tenantId;
    }
    if (viabixHasColumn('projetos', 'criado_por')) {
        $insertColumns[] = 'criado_por';
        $placeholders[] = '?';
        $insertValues[] = $user['id'];
    }
    if (viabixHasColumn('projetos', 'atualizado_por')) {
        $insertColumns[] = 'atualizado_por';
        $placeholders[] = '?';
        $insertValues[] = $user['id'];
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'INSERT INTO projetos (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')'
    );
    $stmt->execute($insertValues);
    $projetoId = (int) $pdo->lastInsertId();

    if (viabixHasColumn('anvis', 'projeto_id')) {
        $updateSql = 'UPDATE anvis SET projeto_id = ?';
        $updateParams = [$projetoId];

        if (viabixHasColumn('anvis', 'atualizado_por')) {
            $updateSql .= ', atualizado_por = ?';
            $updateParams[] = $user['id'];
        }

        $updateSql .= ', data_atualizacao = NOW() WHERE id = ?';
        $updateParams[] = $anviId;

        if (viabixHasColumn('anvis', 'tenant_id') && $tenantId) {
            $updateSql .= ' AND tenant_id = ?';
            $updateParams[] = $tenantId;
        }

        $stmt = $pdo->prepare($updateSql);
        $stmt->execute($updateParams);
    }

    viabixLogActivity(
        $user['id'],
        'criar_projeto_de_anvi',
        'Projeto #' . $projetoId . ' criado a partir da ANVI ' . $anvi['numero'] . ' Rev. ' . $anvi['revisao'],
        'projeto',
        (string) $projetoId
    );

    $pdo->commit();

    http_response_code(201);
    echo json_encode([
        'sucesso' => true,
        'success' => true,
        'mensagem' => 'Projeto criado e vinculado à ANVI com sucesso!',
        'projeto_id' => $projetoId,
        'anvi_id' => $anviId,
        'projeto' => [
            'id' => $projetoId,
            'nome' => $projectPayload['projectName'],
            'status' => $projectPayload['status'],
            'progresso' => 0,
            'lider' => $liderNome,
        ],
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
}
?>