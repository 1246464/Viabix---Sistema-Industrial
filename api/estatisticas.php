<?php
/**
 * API para retornar estatísticas do sistema integrado
 */

require_once 'config.php';

header('Content-Type: application/json');

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

try {
    $stats = [
        'success' => true,
        'message' => 'Estatísticas carregadas com sucesso'
    ];
    $tenantId = viabixCurrentTenantId();
    $tenantAwareAnvis = viabixHasColumn('anvis', 'tenant_id') && $tenantId;
    $tenantAwareProjetos = viabixHasColumn('projetos', 'tenant_id') && $tenantId;
    $tenantAwareUsuarios = viabixHasColumn('usuarios', 'tenant_id') && $tenantId;
    $tenantAwareLideres = viabixHasColumn('lideres', 'tenant_id') && $tenantId;
    $leadersHasActive = viabixHasColumn('lideres', 'ativo');
    $projectsHasStatusColumn = viabixHasColumn('projetos', 'status');
    $anvisHasResponsavel = viabixHasColumn('anvis', 'responsavel');

    $period = isset($_GET['period']) && in_array($_GET['period'], ['7', '30', '90'], true)
        ? (int) $_GET['period']
        : 30;
    $statusFilter = trim((string) ($_GET['status'] ?? ''));
    $clienteFilter = trim((string) ($_GET['cliente'] ?? ''));
    $responsavelFilter = trim((string) ($_GET['responsavel'] ?? ''));

    $statusClause = '';
    $clienteClause = '';
    $responsavelClause = '';
    $filterParams = [];

    if ($statusFilter !== '') {
        $statusClause = ' AND COALESCE(status, \'\') = ?';
        $filterParams[] = $statusFilter;
    }

    if ($clienteFilter !== '') {
        $clienteClause = ' AND (cliente LIKE ? OR projeto LIKE ?)';
        $filterParams[] = '%' . $clienteFilter . '%';
        $filterParams[] = '%' . $clienteFilter . '%';
    }

    if ($responsavelFilter !== '') {
        $clauses = [];
        $likeValue = '%' . $responsavelFilter . '%';

        if ($anvisHasResponsavel) {
            $clauses[] = 'COALESCE(responsavel, \'\') LIKE ?';
            $filterParams[] = $likeValue;
        }

        $clauses[] = 'COALESCE(criado_por, \'\') LIKE ?';
        $clauses[] = 'COALESCE(atualizado_por, \'\') LIKE ?';
        $filterParams[] = $likeValue;
        $filterParams[] = $likeValue;

        $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelTecnica\')), \'\') LIKE ?';
        $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelComercial\')), \'\') LIKE ?';
        $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelEconomica\')), \'\') LIKE ?';
        $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelFiscal\')), \'\') LIKE ?';
        $filterParams = array_merge($filterParams, array_fill(0, 4, $likeValue));

        $responsavelClause = ' AND (' . implode(' OR ', $clauses) . ')';
    }

    // Contar ANVIs
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM anvis WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
    }
    $stats['anvis'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Projetos
    if ($tenantAwareProjetos) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM projetos WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
    }
    $stats['projetos'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Usuários ativos
    if ($tenantAwareUsuarios) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
    }
    $stats['usuarios'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Líderes ativos
    if ($tenantAwareLideres) {
        $sql = 'SELECT COUNT(*) as total FROM lideres WHERE tenant_id = ?';
        if ($leadersHasActive) {
            $sql .= ' AND ativo = 1';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tenantId]);
    } else {
        $sql = 'SELECT COUNT(*) as total FROM lideres';
        if ($leadersHasActive) {
            $sql .= ' WHERE ativo = 1';
        }
        $stmt = $pdo->query($sql);
    }
    $stats['lideres'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar vínculos ANVI-Projeto
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM anvis
             WHERE projeto_id IS NOT NULL AND tenant_id = ?"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE projeto_id IS NOT NULL
        ");
    }
    $stats['vinculos'] = $stmt->fetch()['total'] ?? 0;
    
    // ANVIs por status
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) as total
             FROM anvis
             WHERE tenant_id = ?
             GROUP BY status"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT status, COUNT(*) as total 
            FROM anvis 
            GROUP BY status
        ");
    }
    $stats['anvis_por_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Projetos por status
    if ($projectsHasStatusColumn) {
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare(
                "SELECT status, COUNT(*) as total
                 FROM projetos
                 WHERE tenant_id = ?
                 GROUP BY status"
            );
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query(
                "SELECT status, COUNT(*) as total
                 FROM projetos
                 GROUP BY status"
            );
        }
    } else {
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), ''), 'Pendente') as status, COUNT(*) as total
                 FROM projetos
                 WHERE tenant_id = ?
                 GROUP BY status"
            );
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query(
                "SELECT COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), ''), 'Pendente') as status, COUNT(*) as total
                 FROM projetos
                 GROUP BY status"
            );
        }
    }
    $stats['projetos_por_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // ANVIs criadas nos últimos N dias
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM anvis
             WHERE tenant_id = ?
               AND data_criacao >= DATE_SUB(NOW(), INTERVAL {$period} DAY)"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
        ");
    }
    $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    
    // Projetos atualizados nos últimos N dias
    if ($tenantAwareProjetos) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM projetos
             WHERE tenant_id = ?
               AND updated_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM projetos 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
        ");
    }
    $stats['projetos_recentes'] = $stmt->fetch()['total'] ?? 0;

    $tenantParam = $tenantId ? [$tenantId] : [];
    $anviScope = $tenantAwareAnvis ? 'tenant_id = ?' : '1 = 1';
    $projetoScope = $tenantAwareProjetos ? 'tenant_id = ?' : '1 = 1';
    $projectUpdatedColumn = viabixHasColumn('projetos', 'updated_at') ? 'updated_at' : null;
    $projectCreatedColumn = viabixHasColumn('projetos', 'created_at') ? 'created_at' : null;

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total
         FROM anvis
         WHERE {$anviScope}
           AND COALESCE(status, '') NOT IN ('aprovada', 'aprovado', 'concluida', 'concluido')
           AND data_atualizacao < DATE_SUB(NOW(), INTERVAL 14 DAY)"
    );
    $stmt->execute($tenantAwareAnvis ? $tenantParam : []);
    $anvisParadas = (int) ($stmt->fetch()['total'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total
         FROM anvis
         WHERE {$anviScope}
           AND projeto_id IS NULL"
    );
    $stmt->execute($tenantAwareAnvis ? $tenantParam : []);
    $anvisSemProjeto = (int) ($stmt->fetch()['total'] ?? 0);

    $projetosParados = 0;
    if ($projectUpdatedColumn) {
        $statusFilter = $projectsHasStatusColumn
            ? "AND COALESCE(status, '') NOT IN ('concluido', 'concluida', 'finalizado', 'finalizada', 'cancelado', 'cancelada')"
            : '';
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM projetos
             WHERE {$projetoScope}
               {$statusFilter}
               AND {$projectUpdatedColumn} < DATE_SUB(NOW(), INTERVAL 10 DAY)"
        );
        $stmt->execute($tenantAwareProjetos ? $tenantParam : []);
        $projetosParados = (int) ($stmt->fetch()['total'] ?? 0);
    }

    $atencaoHoje = [];
    if ($anvisParadas > 0) {
        $atencaoHoje[] = [
            'titulo' => $anvisParadas . ' ANVI(s) sem atualização recente',
            'detalhe' => 'Registros em andamento parados há mais de 14 dias',
            'status' => $anvisParadas >= 5 ? 'critico' : 'atencao',
            'statusLabel' => $anvisParadas >= 5 ? 'Crítico' : 'Atenção',
        ];
    }
    if ($anvisSemProjeto > 0) {
        $atencaoHoje[] = [
            'titulo' => $anvisSemProjeto . ' ANVI(s) sem projeto vinculado',
            'detalhe' => 'O vínculo ajuda a acompanhar execução, responsáveis e prazos',
            'status' => $anvisSemProjeto >= 5 ? 'critico' : 'atencao',
            'statusLabel' => 'Vínculo',
        ];
    }
    if ($projetosParados > 0) {
        $atencaoHoje[] = [
            'titulo' => $projetosParados . ' projeto(s) sem movimentação',
            'detalhe' => 'Projetos não concluídos sem atualização há mais de 10 dias',
            'status' => $projetosParados >= 3 ? 'critico' : 'atencao',
            'statusLabel' => 'Prazo',
        ];
    }
    if (!$atencaoHoje) {
        $atencaoHoje[] = [
            'titulo' => 'Nenhum alerta operacional crítico',
            'detalhe' => 'ANVIs e projetos não apresentam atrasos relevantes pelos critérios atuais',
            'status' => 'ok',
            'statusLabel' => 'OK',
        ];
    }

    $sqlPrioridades = "SELECT id, numero, revisao, cliente, projeto, status, projeto_id, data_atualizacao
         FROM anvis
         WHERE {$anviScope}
           AND COALESCE(status, '') NOT IN ('aprovada', 'aprovado', 'concluida', 'concluido')
           {$statusClause}
           {$clienteClause}
           {$responsavelClause}
         ORDER BY
           CASE
             WHEN COALESCE(status, '') IN ('reprovada', 'reprovado', 'aprovada-condicional') THEN 0
             WHEN projeto_id IS NULL THEN 1
             WHEN data_atualizacao < DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 2
             ELSE 3
           END,
           data_atualizacao ASC
         LIMIT 5";

    $params = $tenantAwareAnvis ? $tenantParam : [];
    $params = array_merge($params, $filterParams);

    $stmt = $pdo->prepare($sqlPrioridades);
    $stmt->execute($params);
    $prioridades = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $anvi) {
        $semProjeto = empty($anvi['projeto_id']);
        $status = strtolower((string) ($anvi['status'] ?? ''));
        $critico = in_array($status, ['reprovada', 'reprovado', 'aprovada-condicional'], true);
        $codigo = trim(($anvi['numero'] ?? '') . ' Rev. ' . ($anvi['revisao'] ?? ''));

        $prioridades[] = [
            'titulo' => 'Revisar ANVI ' . ($codigo !== 'Rev.' ? $codigo : $anvi['id']),
            'detalhe' => ($anvi['cliente'] ?: 'Cliente não informado') .
                ($semProjeto ? ' • sem projeto vinculado' : ' • ' . ($anvi['projeto'] ?: 'projeto vinculado')),
            'status' => $critico ? 'critico' : ($semProjeto ? 'atencao' : 'ok'),
            'statusLabel' => $critico ? 'Urgente' : ($semProjeto ? 'Vincular' : 'Revisar'),
            'actionLabel' => 'Abrir ANVI',
            'actionUrl' => 'anvi.html?id=' . urlencode($anvi['id']),
        ];
    }
    if (!$prioridades) {
        $prioridades[] = [
            'titulo' => 'Nenhuma ANVI pendente na fila',
            'detalhe' => 'As ANVIs em aberto aparecerão aqui automaticamente',
            'status' => 'ok',
            'statusLabel' => 'Livre',
        ];
    }

    $healthScore = max(0, 100 - ($anvisParadas * 10) - ($anvisSemProjeto * 8) - ($projetosParados * 12));
    $healthLabel = $healthScore >= 80 ? 'Saudável' : ($healthScore >= 60 ? 'Estável' : ($healthScore >= 40 ? 'Atenção' : 'Crítico'));
    $healthMessage = sprintf(
        'A saúde operacional é baseada em %d alertas de atualização, %d vínculos e %d projetos parados.',
        $anvisParadas,
        $anvisSemProjeto,
        $projetosParados
    );

    $healthSummary = [
        'score' => $healthScore,
        'label' => $healthLabel,
        'message' => $healthMessage,
    ];

    $stmt = $pdo->prepare(
        "SELECT
            SUM(CASE WHEN data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ultimos_7,
            SUM(CASE WHEN data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30,
            SUM(CASE WHEN projeto_id IS NOT NULL AND data_criacao >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as vinculos_7,
            SUM(CASE WHEN projeto_id IS NOT NULL AND data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as vinculos_30
         FROM anvis
         WHERE {$anviScope}"
    );
    $stmt->execute($tenantAwareAnvis ? $tenantParam : []);
    $trendAnvis = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $projetos7 = 0;
    $projetos30 = 0;
    $projectTrendColumn = $projectUpdatedColumn ?: $projectCreatedColumn;
    if ($projectTrendColumn) {
        $stmt = $pdo->prepare(
            "SELECT
                SUM(CASE WHEN {$projectTrendColumn} >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ultimos_7,
                SUM(CASE WHEN {$projectTrendColumn} >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30
             FROM projetos
             WHERE {$projetoScope}"
        );
        $stmt->execute($tenantAwareProjetos ? $tenantParam : []);
        $trendProjetos = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $projetos7 = (int) ($trendProjetos['ultimos_7'] ?? 0);
        $projetos30 = (int) ($trendProjetos['ultimos_30'] ?? 0);
    }

    $stats['dashboard_operacional'] = [
        'atencao_hoje' => $atencaoHoje,
        'prioridades' => $prioridades,
        'health' => $healthSummary,
        'tendencias' => [
            [
                'label' => 'ANVIs criadas',
                'valorAtual' => (int) ($trendAnvis['ultimos_7'] ?? 0),
                'referencia' => (int) ($trendAnvis['ultimos_30'] ?? 0),
                'unidade' => '',
                'cor' => 'linear-gradient(90deg, #4caf50, #2e7d32)',
                'descricao' => 'últimos 7 dias vs 30 dias',
            ],
            [
                'label' => 'Projetos movimentados',
                'valorAtual' => $projetos7,
                'referencia' => $projetos30,
                'unidade' => '',
                'cor' => 'linear-gradient(90deg, #42a5f5, #1565c0)',
                'descricao' => 'últimos 7 dias vs 30 dias',
            ],
            [
                'label' => 'ANVIs vinculadas',
                'valorAtual' => (int) ($trendAnvis['vinculos_7'] ?? 0),
                'referencia' => (int) ($trendAnvis['vinculos_30'] ?? 0),
                'unidade' => '',
                'cor' => 'linear-gradient(90deg, #ffb74d, #ef6c00)',
                'descricao' => 'vínculos criados nos últimos períodos',
            ],
        ],
    ];
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar estatísticas: ' . $e->getMessage()]);
}
