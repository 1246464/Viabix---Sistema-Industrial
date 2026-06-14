<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    $user = function_exists('viabixGetAuthenticatedUser') ? viabixGetAuthenticatedUser() : null;
    if (!$user && empty($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'erro' => 'Não autenticado']);
        exit;
    }

    if ($user && empty($_SESSION['tenant_id'])) {
        $_SESSION['tenant_id'] = $user['tenant_id'] ?? null;
    }

    if (!viabixHasTable('anvis')) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $tenantId = function_exists('viabixCurrentTenantId') ? viabixCurrentTenantId() : ($_SESSION['tenant_id'] ?? null);
    $q = trim((string) ($_GET['q'] ?? ''));
    $limit = min(25, max(5, (int) ($_GET['limit'] ?? 12)));
    $availableForProject = ($_GET['available_for_project'] ?? '') === '1';
    $currentProjectId = (int) ($_GET['project_id'] ?? 0);

    $select = [
        'id',
        viabixHasColumn('anvis', 'numero') ? 'numero' : "'' AS numero",
        viabixHasColumn('anvis', 'revisao') ? 'revisao' : "'' AS revisao",
        viabixHasColumn('anvis', 'cliente') ? 'cliente' : "'' AS cliente",
        viabixHasColumn('anvis', 'projeto') ? 'projeto' : "'' AS projeto",
        viabixHasColumn('anvis', 'produto') ? 'produto' : "'' AS produto",
        viabixHasColumn('anvis', 'status') ? 'status' : "'' AS status",
    ];

    $where = [];
    $params = [];

    if (viabixHasColumn('anvis', 'tenant_id') && $tenantId) {
        $where[] = 'tenant_id = ?';
        $params[] = $tenantId;
    }

    if ($availableForProject && viabixHasColumn('anvis', 'projeto_id')) {
        if ($currentProjectId > 0) {
            $where[] = '(projeto_id IS NULL OR projeto_id = ?)';
            $params[] = $currentProjectId;
        } else {
            $where[] = 'projeto_id IS NULL';
        }
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $searchParts = ['id LIKE ?'];
        $params[] = $like;

        foreach (['numero', 'revisao', 'cliente', 'projeto', 'produto'] as $column) {
            if (viabixHasColumn('anvis', $column)) {
                $searchParts[] = $column . ' LIKE ?';
                $params[] = $like;
            }
        }

        $where[] = '(' . implode(' OR ', $searchParts) . ')';
    }

    $orderColumn = viabixHasColumn('anvis', 'data_atualizacao')
        ? 'data_atualizacao'
        : (viabixHasColumn('anvis', 'data_criacao') ? 'data_criacao' : 'id');
    $sql = 'SELECT ' . implode(', ', $select) . ' FROM anvis';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . $orderColumn . ' DESC LIMIT ' . $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $items = array_map(function ($row) {
        $numero = (string) ($row['numero'] ?? '');
        $revisao = (string) ($row['revisao'] ?? '');
        $nome = trim($numero . ($revisao !== '' ? ' Rev. ' . $revisao : ''));

        return [
            'id' => (string) ($row['id'] ?? ''),
            'numero' => $numero,
            'revisao' => $revisao,
            'nome' => $nome !== '' ? $nome : (string) ($row['id'] ?? ''),
            'cliente' => (string) ($row['cliente'] ?? ''),
            'projeto' => (string) ($row['projeto'] ?? ''),
            'produto' => (string) ($row['produto'] ?? ''),
            'status' => (string) ($row['status'] ?? ''),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    echo json_encode(['success' => true, 'data' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'erro' => 'Erro ao buscar ANVIs']);
}
