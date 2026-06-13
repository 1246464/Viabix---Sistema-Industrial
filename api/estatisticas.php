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

// Verificar autenticação via sessão web ou JWT mobile
$currentUser = viabixGetAuthenticatedUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $currentUser['id'];
    $_SESSION['user_login'] = $currentUser['login'] ?? '';
    $_SESSION['user_nome'] = $currentUser['nome'] ?? '';
    $_SESSION['user_level'] = viabixNormalizeUserLevel($currentUser['nivel'] ?? 'visitante');
    $_SESSION['tenant_id'] = $currentUser['tenant_id'] ?? null;
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
    $anvisHasStatus = viabixHasColumn('anvis', 'status');
    $anvisHasProjetoId = viabixHasColumn('anvis', 'projeto_id');
    $anvisHasDataAtualizacao = viabixHasColumn('anvis', 'data_atualizacao');
    $anvisHasDataCriacao = viabixHasColumn('anvis', 'data_criacao');
    $anvisHasDados = viabixHasColumn('anvis', 'dados');
    $anvisHasDadosFinanceiros = viabixHasColumn('anvis', 'dados_financeiros');
    $anvisHasCriadoPor = viabixHasColumn('anvis', 'criado_por');
    $anvisHasAtualizadoPor = viabixHasColumn('anvis', 'atualizado_por');
    $anvisHasCliente = viabixHasColumn('anvis', 'cliente');
    $anvisHasProjeto = viabixHasColumn('anvis', 'projeto');
    $projectUpdatedColumn = viabixHasColumn('projetos', 'updated_at') ? 'updated_at' : null;
    $projectCreatedColumn = viabixHasColumn('projetos', 'created_at') ? 'created_at' : null;
    $anviStatusExpr = $anvisHasStatus
        ? "COALESCE(status, '')"
        : ($anvisHasDados ? "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), '')" : "''");
    $anviProjetoIdExpr = $anvisHasProjetoId ? 'projeto_id' : 'NULL';
    $anviUpdatedExpr = $anvisHasDataAtualizacao
        ? 'data_atualizacao'
        : ($anvisHasDataCriacao ? 'data_criacao' : 'NOW()');
    $anviCreatedExpr = $anvisHasDataCriacao ? 'data_criacao' : $anviUpdatedExpr;
    $anviClienteExpr = $anvisHasCliente
        ? "COALESCE(cliente, '')"
        : ($anvisHasDados ? "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.cliente')), '')" : "''");
    $anviProjetoExpr = $anvisHasProjeto
        ? "COALESCE(projeto, '')"
        : ($anvisHasDados ? "COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.projeto')), '')" : "''");

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
        $statusClause = ' AND ' . $anviStatusExpr . ' = ?';
        $filterParams[] = $statusFilter;
    }

    if ($clienteFilter !== '') {
        $clienteClause = ' AND (' . $anviClienteExpr . ' LIKE ? OR ' . $anviProjetoExpr . ' LIKE ?)';
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

        if ($anvisHasCriadoPor) {
            $clauses[] = 'COALESCE(criado_por, \'\') LIKE ?';
            $filterParams[] = $likeValue;
        }
        if ($anvisHasAtualizadoPor) {
            $clauses[] = 'COALESCE(atualizado_por, \'\') LIKE ?';
            $filterParams[] = $likeValue;
        }

        if ($anvisHasDados) {
            $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelTecnica\')), \'\') LIKE ?';
            $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelComercial\')), \'\') LIKE ?';
            $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelEconomica\')), \'\') LIKE ?';
            $clauses[] = 'COALESCE(JSON_UNQUOTE(JSON_EXTRACT(dados, \'$.responsavelFiscal\')), \'\') LIKE ?';
            $filterParams = array_merge($filterParams, array_fill(0, 4, $likeValue));
        }

        if ($clauses) {
            $responsavelClause = ' AND (' . implode(' OR ', $clauses) . ')';
        }
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
    if ($anvisHasProjetoId) {
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
    } else {
        $sql = "SELECT COUNT(*) as total
                FROM projetos
                WHERE JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')) IS NOT NULL";
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare($sql . ' AND tenant_id = ?');
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query($sql);
        }
        $stats['vinculos'] = $stmt->fetch()['total'] ?? 0;
    }
    
    // ANVIs por status
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT {$anviStatusExpr} as status, COUNT(*) as total
             FROM anvis
             WHERE tenant_id = ?
             GROUP BY status"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT {$anviStatusExpr} as status, COUNT(*) as total
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
               AND {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL {$period} DAY)"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
        ");
    }
    $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    
    // Projetos atualizados nos últimos N dias
    $projectActivityColumn = $projectUpdatedColumn ?: $projectCreatedColumn;
    if ($projectActivityColumn) {
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) as total
                 FROM projetos
                 WHERE tenant_id = ?
                   AND {$projectActivityColumn} >= DATE_SUB(NOW(), INTERVAL {$period} DAY)"
            );
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query("
                SELECT COUNT(*) as total
                FROM projetos
                WHERE {$projectActivityColumn} >= DATE_SUB(NOW(), INTERVAL {$period} DAY)
            ");
        }
        $stats['projetos_recentes'] = $stmt->fetch()['total'] ?? 0;
    } else {
        $stats['projetos_recentes'] = 0;
    }

    $tenantParam = $tenantId ? [$tenantId] : [];
    $anviScope = $tenantAwareAnvis ? 'tenant_id = ?' : '1 = 1';
    $projetoScope = $tenantAwareProjetos ? 'tenant_id = ?' : '1 = 1';

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) as total
         FROM anvis
         WHERE {$anviScope}
           AND {$anviStatusExpr} NOT IN ('aprovada', 'aprovado', 'concluida', 'concluido')
           AND {$anviUpdatedExpr} < DATE_SUB(NOW(), INTERVAL 14 DAY)"
    );
    $stmt->execute($tenantAwareAnvis ? $tenantParam : []);
    $anvisParadas = (int) ($stmt->fetch()['total'] ?? 0);

    $anvisSemProjeto = 0;
    if ($anvisHasProjetoId) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM anvis
             WHERE {$anviScope}
               AND projeto_id IS NULL"
        );
        $stmt->execute($tenantAwareAnvis ? $tenantParam : []);
        $anvisSemProjeto = (int) ($stmt->fetch()['total'] ?? 0);
    }

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

    $alertasFinanceiros = [];
    $financeiroResumo = [
        'anvis_com_indicadores' => 0,
        'margem_media_pct' => 0,
        'roi_medio_pct' => 0,
        'payback_medio_meses' => 0,
        'alertas_criticos' => 0,
        'alertas_atencao' => 0,
    ];

    if ($anvisHasDadosFinanceiros) {
        $financeWhere = "{$anviScope} AND dados_financeiros IS NOT NULL";
        $financeParams = $tenantAwareAnvis ? $tenantParam : [];

        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) as total,
                AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.margem_esperada_pct')) AS DECIMAL(12,2))) as margem_media,
                AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.roi_esperado_pct')) AS DECIMAL(12,2))) as roi_medio,
                AVG(NULLIF(CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.payback_meses')) AS DECIMAL(12,2)), 0)) as payback_medio
             FROM anvis
             WHERE {$financeWhere}"
        );
        $stmt->execute($financeParams);
        $financeiroRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $financeiroResumo['anvis_com_indicadores'] = (int) ($financeiroRow['total'] ?? 0);
        $financeiroResumo['margem_media_pct'] = round((float) ($financeiroRow['margem_media'] ?? 0), 2);
        $financeiroResumo['roi_medio_pct'] = round((float) ($financeiroRow['roi_medio'] ?? 0), 2);
        $financeiroResumo['payback_medio_meses'] = round((float) ($financeiroRow['payback_medio'] ?? 0), 2);

        $stmt = $pdo->prepare(
            "SELECT id, numero, revisao,
                {$anviClienteExpr} as cliente,
                {$anviProjetoExpr} as projeto,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.margem_esperada_pct')) AS DECIMAL(12,2)) as margem,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.roi_esperado_pct')) AS DECIMAL(12,2)) as roi,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.payback_meses')) AS DECIMAL(12,2)) as payback,
                CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.desvio_estimado_realizado_pct')) AS DECIMAL(12,2)) as desvio
             FROM anvis
             WHERE {$financeWhere}
               {$statusClause}
               {$clienteClause}
               {$responsavelClause}
               AND (
                    CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.margem_esperada_pct')) AS DECIMAL(12,2)) BETWEEN 0.01 AND 14.99
                 OR CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.roi_esperado_pct')) AS DECIMAL(12,2)) BETWEEN 0.01 AND 19.99
                 OR CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.payback_meses')) AS DECIMAL(12,2)) > 24
                 OR CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.desvio_estimado_realizado_pct')) AS DECIMAL(12,2)) > 10
               )
             ORDER BY
               CASE
                 WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.margem_esperada_pct')) AS DECIMAL(12,2)) BETWEEN 0.01 AND 9.99 THEN 0
                 WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.desvio_estimado_realizado_pct')) AS DECIMAL(12,2)) > 25 THEN 1
                 WHEN CAST(JSON_UNQUOTE(JSON_EXTRACT(dados_financeiros, '$.payback_meses')) AS DECIMAL(12,2)) > 24 THEN 2
                 ELSE 3
               END,
               {$anviUpdatedExpr} DESC
             LIMIT 5"
        );
        $params = array_merge($financeParams, $filterParams);
        $stmt->execute($params);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $margem = (float) ($item['margem'] ?? 0);
            $roi = (float) ($item['roi'] ?? 0);
            $payback = (float) ($item['payback'] ?? 0);
            $desvio = (float) ($item['desvio'] ?? 0);
            $problemas = [];
            $status = 'atencao';

            if ($margem > 0 && $margem < 10) {
                $problemas[] = 'margem ' . round($margem, 1) . '%';
                $status = 'critico';
            } elseif ($margem > 0 && $margem < 15) {
                $problemas[] = 'margem ' . round($margem, 1) . '%';
            }
            if ($roi > 0 && $roi < 20) {
                $problemas[] = 'ROI ' . round($roi, 1) . '%';
            }
            if ($payback > 24) {
                $problemas[] = 'payback ' . round($payback, 1) . ' meses';
            }
            if ($desvio > 10) {
                $problemas[] = 'desvio +' . round($desvio, 1) . '%';
                if ($desvio > 25) {
                    $status = 'critico';
                }
            }

            $codigo = trim(($item['numero'] ?? '') . ' Rev. ' . ($item['revisao'] ?? ''));
            $alertasFinanceiros[] = [
                'titulo' => 'Risco financeiro em ' . ($codigo !== 'Rev.' ? $codigo : $item['id']),
                'detalhe' => ($item['cliente'] ?: 'Cliente não informado') . ' • ' . implode(', ', $problemas),
                'status' => $status,
                'statusLabel' => $status === 'critico' ? 'Crítico' : 'Atenção',
                'actionLabel' => 'Abrir análise',
                'actionUrl' => 'dashboard_viabilidade.html?anvi_id=' . urlencode($item['id']),
            ];
        }

        $financeiroResumo['alertas_criticos'] = count(array_filter($alertasFinanceiros, fn($item) => $item['status'] === 'critico'));
        $financeiroResumo['alertas_atencao'] = count(array_filter($alertasFinanceiros, fn($item) => $item['status'] === 'atencao'));
    }

    if ($alertasFinanceiros) {
        $criticosFinanceiros = count(array_filter($alertasFinanceiros, fn($item) => $item['status'] === 'critico'));
        $atencaoHoje[] = [
            'titulo' => count($alertasFinanceiros) . ' ANVI(s) com risco financeiro',
            'detalhe' => $criticosFinanceiros > 0
                ? $criticosFinanceiros . ' caso(s) crítico(s) de margem ou desvio'
                : 'Há indicadores abaixo dos parâmetros financeiros',
            'status' => $criticosFinanceiros > 0 ? 'critico' : 'atencao',
            'statusLabel' => 'Financeiro',
            'actionLabel' => 'Ver riscos',
            'actionUrl' => 'dashboard_viabilidade.html',
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

    $sqlPrioridades = "SELECT id, numero, revisao,
             {$anviClienteExpr} as cliente,
             {$anviProjetoExpr} as projeto,
             {$anviStatusExpr} as status,
             {$anviProjetoIdExpr} as projeto_id,
             {$anviUpdatedExpr} as data_atualizacao
         FROM anvis
         WHERE {$anviScope}
           AND {$anviStatusExpr} NOT IN ('aprovada', 'aprovado', 'concluida', 'concluido')
           {$statusClause}
           {$clienteClause}
           {$responsavelClause}
         ORDER BY
           CASE
             WHEN {$anviStatusExpr} IN ('reprovada', 'reprovado', 'aprovada-condicional') THEN 0
             WHEN {$anviProjetoIdExpr} IS NULL THEN 1
             WHEN {$anviUpdatedExpr} < DATE_SUB(NOW(), INTERVAL 14 DAY) THEN 2
             ELSE 3
           END,
           {$anviUpdatedExpr} ASC
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
            'actionUrl' => 'anvi.html?anvi_id=' . urlencode($anvi['id']),
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
            SUM(CASE WHEN {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as ultimos_7,
            SUM(CASE WHEN {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30,
            SUM(CASE WHEN {$anviProjetoIdExpr} IS NOT NULL AND {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as vinculos_7,
            SUM(CASE WHEN {$anviProjetoIdExpr} IS NOT NULL AND {$anviCreatedExpr} >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as vinculos_30
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
        'financeiro' => [
            'resumo' => $financeiroResumo,
            'alertas' => $alertasFinanceiros,
        ],
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
