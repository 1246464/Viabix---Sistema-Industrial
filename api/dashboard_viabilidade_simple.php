<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

function viabixDvHasColumn(string $table, string $column): bool {
    return function_exists('viabixHasColumn') ? viabixHasColumn($table, $column) : false;
}

function viabixDvDecodeJson($value): array {
    $data = json_decode($value ?? '{}', true);
    return is_array($data) ? $data : [];
}

function viabixDvSelectColumn(string $table, string $column, string $fallbackSql, string $alias = ''): string {
    $prefix = $alias !== '' ? $alias . '.' : '';
    return viabixDvHasColumn($table, $column)
        ? $prefix . $column
        : $fallbackSql . ' AS ' . $column;
}

function viabixDvProjectSelectList(): string {
    return implode(', ', [
        'id',
        viabixDvSelectColumn('projetos', 'dados', "'{}'"),
        viabixDvSelectColumn('projetos', 'created_at', 'NULL'),
        viabixDvSelectColumn('projetos', 'updated_at', 'NULL'),
    ]);
}

function viabixDvDateOnly($value): ?DateTime {
    if (!$value) return null;
    try {
        return new DateTime((string) $value);
    } catch (Throwable $e) {
        return null;
    }
}

function viabixDvProjectProgress(array $projectData): array {
    $tasks = is_array($projectData['tasks'] ?? null) ? $projectData['tasks'] : [];
    $total = 0;
    $done = 0;
    $late = 0;
    $planned = 0;
    $today = new DateTime('today');

    foreach ($tasks as $task) {
        if (!is_array($task)) continue;
        $total++;
        $executed = !empty($task['executed']);
        $plannedDate = viabixDvDateOnly($task['planned'] ?? null);

        if ($plannedDate) {
            $planned++;
        }
        if ($executed) {
            $done++;
        } elseif ($plannedDate && $plannedDate < $today) {
            $late++;
        }
    }

    $progress = isset($projectData['progresso']) && is_numeric($projectData['progresso'])
        ? (float) $projectData['progresso']
        : ($total > 0 ? round(($done / $total) * 100, 1) : 0);

    $onTimePct = $planned > 0 ? round((($planned - $late) / $planned) * 100, 1) : ($total > 0 ? 100 : 0);

    return [
        'total_tarefas' => $total,
        'tarefas_concluidas' => $done,
        'tarefas_atrasadas' => $late,
        'progresso' => max(0, min(100, $progress)),
        'pontualidade_pct' => max(0, min(100, $onTimePct)),
    ];
}

function viabixDvFindLinkedProject(PDO $pdo, array $anvi, ?string $tenantId): ?array {
    if (!function_exists('viabixHasTable') || !viabixHasTable('projetos')) {
        return null;
    }

    $tenantWhere = '';
    $tenantParams = [];
    if ($tenantId && viabixDvHasColumn('projetos', 'tenant_id')) {
        $tenantWhere = ' AND tenant_id = ?';
        $tenantParams[] = $tenantId;
    }

    if (!empty($anvi['projeto_id'])) {
        $stmt = $pdo->prepare("SELECT " . viabixDvProjectSelectList() . " FROM projetos WHERE id = ?{$tenantWhere} LIMIT 1");
        $stmt->execute(array_merge([(int) $anvi['projeto_id']], $tenantParams));
        $project = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($project) return $project;
    }

    $params = [(string) $anvi['id'], (string) $anvi['id']];
    $sql = "SELECT " . viabixDvProjectSelectList() . "
            FROM projetos
            WHERE (
                JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')) = ?
                OR JSON_UNQUOTE(JSON_EXTRACT(dados, '$.sourceContext.anviId')) = ?";

    if (!empty($anvi['numero'])) {
        $sql .= " OR JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviNumber')) = ?";
        $params[] = (string) $anvi['numero'];
    }

    $sql .= ')';
    if ($tenantWhere) {
        $sql .= $tenantWhere;
        $params = array_merge($params, $tenantParams);
    }

    $stmt = $pdo->prepare($sql . ' ORDER BY id DESC LIMIT 1');
    $stmt->execute($params);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    return $project ?: null;
}

try {
    $anvi_id = $_GET['anvi_id'] ?? null;
    
    if (!$anvi_id) {
        http_response_code(400);
        exit(json_encode(['erro' => 'ANVI ID obrigatório']));
    }
    
    // Get ANVI data (tenant filtered from session)
    $tenant_id = function_exists('viabixCurrentTenantId') ? viabixCurrentTenantId() : ($_SESSION['tenant_id'] ?? null);
    if (!$tenant_id) {
        http_response_code(401);
        exit(json_encode(['erro' => 'Sessão expirada ou tenant não identificado']));
    }
    
    $select = [
        'a.id',
        viabixDvSelectColumn('anvis', 'numero', "''", 'a'),
        viabixDvSelectColumn('anvis', 'revisao', "''", 'a'),
        viabixDvSelectColumn('anvis', 'cliente', "''", 'a'),
        viabixDvSelectColumn('anvis', 'projeto', "''", 'a'),
        viabixDvSelectColumn('anvis', 'produto', "''", 'a'),
        viabixDvSelectColumn('anvis', 'status', "''", 'a'),
        viabixDvSelectColumn('anvis', 'data_anvi', 'NULL', 'a'),
        viabixDvSelectColumn('anvis', 'data_criacao', 'NULL', 'a'),
        viabixDvSelectColumn('anvis', 'dados', "'{}'", 'a'),
        viabixDvSelectColumn('anvis', 'dados_financeiros', "'{}'", 'a'),
        viabixDvSelectColumn('anvis', 'projeto_id', 'NULL', 'a'),
    ];

    $orderColumn = viabixDvHasColumn('anvis', 'data_criacao') ? 'a.data_criacao' : 'a.id';
    $whereParts = ['a.id = ?'];
    $whereParams = [$anvi_id];

    if (viabixDvHasColumn('anvis', 'numero')) {
        $whereParts[] = 'a.numero = ?';
        $whereParams[] = $anvi_id;
    }

    if (viabixDvHasColumn('anvis', 'numero') && viabixDvHasColumn('anvis', 'revisao')) {
        $whereParts[] = "CONCAT(a.numero, '_', a.revisao) = ?";
        $whereParams[] = $anvi_id;
    }

    $whereSql = '(' . implode(' OR ', $whereParts) . ')';
    if (viabixDvHasColumn('anvis', 'tenant_id')) {
        $whereSql .= ' AND a.tenant_id = ?';
        $whereParams[] = $tenant_id;
    }

    $stmt = $pdo->prepare("
        SELECT " . implode(', ', $select) . "
        FROM anvis a
        WHERE {$whereSql}
        ORDER BY {$orderColumn} DESC
        LIMIT 1
    ");
    
    $stmt->execute($whereParams);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anvi) {
        http_response_code(404);
        exit(json_encode(['erro' => 'ANVI não encontrado']));
    }
    
    // Decode JSON fields
    $dados = viabixDvDecodeJson($anvi['dados'] ?? '{}');
    $dados_financeiros = viabixDvDecodeJson($anvi['dados_financeiros'] ?? '{}');
    $financeiro_salvo = is_array($dados_financeiros) ? $dados_financeiros : [];
    $projectRow = viabixDvFindLinkedProject($pdo, $anvi, $tenant_id);
    $projectData = $projectRow ? viabixDvDecodeJson($projectRow['dados'] ?? '{}') : [];
    $projectProgress = $projectRow ? viabixDvProjectProgress($projectData) : null;
    
    // Calculate compatibility scores
    $financeiro_score = 0;
    $planejamento_score = 0;
    $qualidade_score = 0;
    $recursos_score = 0;
    
    // Financeiro
    $roi = (float) ($financeiro_salvo['roi_esperado_pct'] ?? 0);
    $payback = (float) ($financeiro_salvo['payback_meses'] ?? 0);
    $financeiro_score = min(100, ($roi / 150) * 70 + (12 / max($payback, 1)) * 30);
    if ($roi <= 0 && $payback <= 0) {
        $financeiro_score = 0;
    }
    
    // Planejamento
    $fases = $dados['planejamento']['fases'] ?? 0;
    $duracao = $dados['planejamento']['duracao_meses'] ?? 0;
    $planejamento_score = min(100, $fases * 20 + min($duracao, 5) * 10);
    if ($projectProgress) {
        $planejamento_score = round(($projectProgress['progresso'] * 0.55) + ($projectProgress['pontualidade_pct'] * 0.45), 1);
        $fases = $projectProgress['total_tarefas'];
    }
    $tarefasPendentes = $projectProgress
        ? max(0, (int) $projectProgress['total_tarefas'] - (int) $projectProgress['tarefas_concluidas'])
        : 0;
    $execucao_score = $projectProgress
        ? round(((float) $projectProgress['progresso'] * 0.7) + ((float) $projectProgress['pontualidade_pct'] * 0.3), 1)
        : $planejamento_score;
    $prazo_score = $projectProgress
        ? max(0, round((float) $projectProgress['pontualidade_pct'] - ((int) $projectProgress['tarefas_atrasadas'] * 8), 1))
        : min(100, $planejamento_score + 10);
    
    // Qualidade
    $cobertura = $dados['qualidade']['cobertura_testes'] ?? 0;
    $score_code = $dados['qualidade']['score_codigo'] ?? 0;
    $qualidade_score = ($cobertura + $score_code) / 2;
    
    // Recursos
    $equipe = $dados['recursos']['equipe'] ?? 0;
    $especialistas = $dados['recursos']['especialistas'] ?? 0;
    $recursos_score = min(100, $equipe * 10 + $especialistas * 15);
    
    $alertas_financeiros = [];
    $margem = (float) ($financeiro_salvo['margem_esperada_pct'] ?? 0);
    $desvio = (float) ($financeiro_salvo['desvio_estimado_realizado_pct'] ?? 0);

    if ($margem > 0 && $margem < 10) {
        $alertas_financeiros[] = [
            'tipo' => 'margem_baixa',
            'severidade' => 'critico',
            'mensagem' => 'Margem líquida abaixo de 10%'
        ];
    } elseif ($margem > 0 && $margem < 15) {
        $alertas_financeiros[] = [
            'tipo' => 'margem_atencao',
            'severidade' => 'atencao',
            'mensagem' => 'Margem líquida abaixo do recomendado'
        ];
    }
    if ($roi > 0 && $roi < 20) {
        $alertas_financeiros[] = [
            'tipo' => 'roi_baixo',
            'severidade' => 'atencao',
            'mensagem' => 'ROI anual abaixo de 20%'
        ];
    }
    if ($payback > 24) {
        $alertas_financeiros[] = [
            'tipo' => 'payback_longo',
            'severidade' => 'atencao',
            'mensagem' => 'Payback acima de 24 meses'
        ];
    }
    if ($desvio > 10) {
        $alertas_financeiros[] = [
            'tipo' => 'desvio_custo',
            'severidade' => $desvio > 25 ? 'critico' : 'atencao',
            'mensagem' => 'Custo realizado acima do estimado'
        ];
    }

    $prioridades = [];
    foreach ($alertas_financeiros as $alerta) {
        $acaoAlerta = 'Revisar o indicador antes da decisão final.';
        switch ($alerta['tipo']) {
            case 'margem_baixa':
            case 'margem_atencao':
                $acaoAlerta = 'Revisar custos, preço sugerido ou margem alvo antes da aprovação.';
                break;
            case 'roi_baixo':
                $acaoAlerta = 'Validar retorno esperado e comparar com o mínimo industrial.';
                break;
            case 'payback_longo':
                $acaoAlerta = 'Reavaliar investimento inicial ou fasear o projeto para reduzir prazo de retorno.';
                break;
            case 'desvio_custo':
                $acaoAlerta = 'Conferir custos realizados e atualizar a base financeira do ANVI.';
                break;
        }
        $prioridades[] = [
            'area' => 'Financeiro',
            'prioridade' => $alerta['severidade'] === 'critico' ? 'Alta' : 'Média',
            'titulo' => $alerta['mensagem'],
            'acao' => $acaoAlerta
        ];
    }

    if ($financeiro_score < 60) {
        $prioridades[] = [
            'area' => 'Financeiro',
            'prioridade' => 'Alta',
            'titulo' => 'Score financeiro abaixo do aceitável',
            'acao' => 'Ajustar margem, ROI ou payback antes de liberar o projeto.'
        ];
    }
    if ($planejamento_score > 0 && $planejamento_score < 60) {
        $prioridades[] = [
            'area' => 'Planejamento',
            'prioridade' => 'Média',
            'titulo' => $projectRow ? 'Projeto vinculado com baixo avanço ou atraso' : 'Planejamento com baixa maturidade',
            'acao' => $projectRow ? 'Revisar tarefas atrasadas e atualizar o cronograma do projeto vinculado.' : 'Detalhar fases e duração para reduzir incerteza de execução.'
        ];
    }
    if ($projectProgress && $projectProgress['tarefas_atrasadas'] > 0) {
        $prioridades[] = [
            'area' => 'Projeto',
            'prioridade' => $projectProgress['tarefas_atrasadas'] >= 3 ? 'Alta' : 'Média',
            'titulo' => $projectProgress['tarefas_atrasadas'] . ' tarefa(s) atrasada(s) no projeto vinculado',
            'acao' => 'Atualizar responsáveis, datas planejadas e ações de recuperação no Controle de Projetos.'
        ];
    }
    if (!$projectRow) {
        $prioridades[] = [
            'area' => 'Projeto',
            'prioridade' => 'Média',
            'titulo' => 'ANVI sem projeto vinculado',
            'acao' => 'Vincular a ANVI a um projeto para completar a análise de execução.'
        ];
    }
    if ($qualidade_score > 0 && $qualidade_score < 60) {
        $prioridades[] = [
            'area' => 'Qualidade',
            'prioridade' => 'Média',
            'titulo' => 'Indicadores de qualidade baixos',
            'acao' => 'Revisar cobertura de testes e critérios de qualidade antes da aprovação.'
        ];
    }
    if ($recursos_score > 0 && $recursos_score < 60) {
        $prioridades[] = [
            'area' => 'Recursos',
            'prioridade' => 'Média',
            'titulo' => 'Recursos insuficientes',
            'acao' => 'Confirmar equipe e especialistas necessários para execução.'
        ];
    }

    $dados_incompletos = [];
    if (empty($financeiro_salvo)) {
        $dados_incompletos[] = 'financeiro';
    }
    if (!$projectRow && !$fases && !$duracao) {
        $dados_incompletos[] = 'planejamento';
    }
    if (!$cobertura && !$score_code) {
        $dados_incompletos[] = 'qualidade';
    }
    if (!$equipe && !$especialistas) {
        $dados_incompletos[] = 'recursos';
    }

    $risco_score = 100;
    $risco_score -= count(array_filter($alertas_financeiros, fn($alerta) => ($alerta['severidade'] ?? '') === 'critico')) * 28;
    $risco_score -= count(array_filter($alertas_financeiros, fn($alerta) => ($alerta['severidade'] ?? '') !== 'critico')) * 14;
    if ($projectProgress) {
        $risco_score -= ((int) $projectProgress['tarefas_atrasadas']) * 10;
        if ($tarefasPendentes > 5) {
            $risco_score -= 8;
        }
    }
    $risco_score -= count($dados_incompletos) * 8;
    $risco_score = max(0, min(100, round($risco_score, 1)));

    $viabilidade_geral = ($financeiro_score + $execucao_score + $prazo_score + $risco_score + $recursos_score) / 5;
    $status_viabilidade = $viabilidade_geral >= 75 ? 'VIÁVEL' : ($viabilidade_geral >= 50 ? 'PARCIAL' : 'NÃO VIÁVEL');

    $temCritico = count(array_filter($alertas_financeiros, fn($alerta) => ($alerta['severidade'] ?? '') === 'critico')) > 0;
    if ($temCritico || $financeiro_score < 50 || $viabilidade_geral < 50 || ($projectProgress && $projectProgress['tarefas_atrasadas'] >= 3) || $risco_score < 50) {
        $decisao_status = 'REVISAR ANTES DE APROVAR';
        $decisao_tom = 'danger';
        $decisao_resumo = 'Há pontos críticos ou score baixo que podem comprometer a viabilidade.';
    } elseif ($viabilidade_geral < 75 || !empty($alertas_financeiros) || !empty($dados_incompletos)) {
        $decisao_status = 'APROVAR COM RESSALVAS';
        $decisao_tom = 'warning';
        $decisao_resumo = 'O projeto pode seguir, mas exige validações antes da decisão final.';
    } else {
        $decisao_status = 'APROVAR';
        $decisao_tom = 'success';
        $decisao_resumo = 'Os indicadores principais estão dentro dos critérios atuais.';
    }

    $decisao = [
        'status' => $decisao_status,
        'tom' => $decisao_tom,
        'resumo' => $decisao_resumo,
        'proxima_acao' => $prioridades[0]['acao'] ?? 'Registrar a decisão e seguir para a próxima etapa do fluxo.',
        'dados_incompletos' => $dados_incompletos,
        'prioridades' => array_slice($prioridades, 0, 5),
    ];

    $comparativo_revisoes = [];
    if (viabixDvHasColumn('anvis', 'numero') && viabixDvHasColumn('anvis', 'revisao')) {
        $comparativoSelect = [
            'id',
            'revisao',
            viabixDvHasColumn('anvis', 'dados_financeiros') ? 'dados_financeiros' : "'{}' AS dados_financeiros",
        ];
        $comparativoOrder = viabixDvHasColumn('anvis', 'data_atualizacao')
            ? 'data_atualizacao'
            : (viabixDvHasColumn('anvis', 'data_criacao') ? 'data_criacao' : 'id');

        $comparativoWhere = 'numero = ? AND id <> ?';
        $comparativoParams = [$anvi['numero'], $anvi['id']];
        if (viabixDvHasColumn('anvis', 'tenant_id')) {
            $comparativoWhere = 'numero = ? AND tenant_id = ? AND id <> ?';
            $comparativoParams = [$anvi['numero'], $tenant_id, $anvi['id']];
        }

        $stmt = $pdo->prepare("
            SELECT " . implode(', ', $comparativoSelect) . "
            FROM anvis
            WHERE {$comparativoWhere}
            ORDER BY {$comparativoOrder} DESC
            LIMIT 1
        ");
        $stmt->execute($comparativoParams);
        $revisao_anterior = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $revisao_anterior = null;
    }

    if ($revisao_anterior) {
        $financeiro_anterior = viabixDvDecodeJson($revisao_anterior['dados_financeiros'] ?? '{}');
        if (is_array($financeiro_anterior)) {
            $comparativo_revisoes = [
                'revisao_atual' => $anvi['revisao'],
                'revisao_anterior' => $revisao_anterior['revisao'],
                'anvi_anterior_id' => $revisao_anterior['id'],
                'variacoes' => [
                    'margem_esperada_pct' => round((float) ($financeiro_salvo['margem_esperada_pct'] ?? 0) - (float) ($financeiro_anterior['margem_esperada_pct'] ?? 0), 2),
                    'custo_total' => round((float) ($financeiro_salvo['custo_total'] ?? 0) - (float) ($financeiro_anterior['custo_total'] ?? 0), 2),
                    'preco_sugerido' => round((float) ($financeiro_salvo['preco_sugerido'] ?? 0) - (float) ($financeiro_anterior['preco_sugerido'] ?? 0), 2),
                    'payback_meses' => round((float) ($financeiro_salvo['payback_meses'] ?? 0) - (float) ($financeiro_anterior['payback_meses'] ?? 0), 2),
                    'roi_esperado_pct' => round((float) ($financeiro_salvo['roi_esperado_pct'] ?? 0) - (float) ($financeiro_anterior['roi_esperado_pct'] ?? 0), 2),
                ]
            ];
            $comparativo_revisoes['leituras'] = [
                'margem_esperada_pct' => ($comparativo_revisoes['variacoes']['margem_esperada_pct'] ?? 0) >= 0 ? 'melhorou' : 'piorou',
                'custo_total' => ($comparativo_revisoes['variacoes']['custo_total'] ?? 0) <= 0 ? 'melhorou' : 'piorou',
                'preco_sugerido' => ($comparativo_revisoes['variacoes']['preco_sugerido'] ?? 0) >= 0 ? 'subiu' : 'caiu',
                'payback_meses' => ($comparativo_revisoes['variacoes']['payback_meses'] ?? 0) <= 0 ? 'melhorou' : 'piorou',
                'roi_esperado_pct' => ($comparativo_revisoes['variacoes']['roi_esperado_pct'] ?? 0) >= 0 ? 'melhorou' : 'piorou',
            ];
        }
    }

    // Build response
    $report = [
        'anvi' => [
            'id' => $anvi['id'],
            'numero' => $anvi['numero'],
            'revisao' => $anvi['revisao'],
            'cliente' => $anvi['cliente'],
            'projeto' => $anvi['projeto'],
            'produto' => $anvi['produto'],
            'status' => $anvi['status'],
            'data_anvi' => $anvi['data_anvi'],
            'data_criacao' => $anvi['data_criacao'],
        ],
        'analise' => [
            'financeiro' => [
                'investimento' => $financeiro_salvo['investimento_total'] ?? ($dados['financeiro']['investimento'] ?? 0),
                'margem' => $financeiro_salvo['margem_esperada_pct'] ?? ($dados['financeiro']['margem'] ?? 0),
                'custo_total' => $financeiro_salvo['custo_total'] ?? 0,
                'preco_sugerido' => $financeiro_salvo['preco_sugerido'] ?? 0,
                'investimento_total' => $financeiro_salvo['investimento_total'] ?? 0,
                'roi_esperado' => $financeiro_salvo['roi_esperado_pct'] ?? 0,
                'payback_meses' => $financeiro_salvo['payback_meses'] ?? 0,
                'desvio_estimado_realizado_pct' => $financeiro_salvo['desvio_estimado_realizado_pct'] ?? 0,
                'riscos' => $financeiro_salvo['riscos_identificados'] ?? [],
                'alertas' => $alertas_financeiros,
                'score' => round($financeiro_score, 1)
            ],
            'planejamento' => [
                'duracao_meses' => $dados['planejamento']['duracao_meses'] ?? 0,
                'fases' => $projectProgress['total_tarefas'] ?? ($dados['planejamento']['fases'] ?? 0),
                'score' => round($planejamento_score, 1),
                'origem' => $projectRow ? 'projeto_vinculado' : 'anvi',
                'progresso_projeto' => $projectProgress['progresso'] ?? null,
                'tarefas_concluidas' => $projectProgress['tarefas_concluidas'] ?? null,
                'tarefas_atrasadas' => $projectProgress['tarefas_atrasadas'] ?? null,
                'pontualidade_pct' => $projectProgress['pontualidade_pct'] ?? null,
            ],
            'execucao' => [
                'score' => round($execucao_score, 1),
                'progresso' => $projectProgress['progresso'] ?? 0,
                'tarefas_total' => $projectProgress['total_tarefas'] ?? 0,
                'tarefas_concluidas' => $projectProgress['tarefas_concluidas'] ?? 0,
                'tarefas_pendentes' => $tarefasPendentes,
                'origem' => $projectRow ? 'projeto_vinculado' : 'anvi',
            ],
            'prazo' => [
                'score' => round($prazo_score, 1),
                'pontualidade_pct' => $projectProgress['pontualidade_pct'] ?? null,
                'tarefas_atrasadas' => $projectProgress['tarefas_atrasadas'] ?? 0,
                'acao' => ($projectProgress && $projectProgress['tarefas_atrasadas'] > 0)
                    ? 'Priorizar recuperação das tarefas atrasadas antes da aprovação.'
                    : 'Cronograma sem atrasos críticos identificados.',
            ],
            'risco' => [
                'score' => round($risco_score, 1),
                'alertas_financeiros' => count($alertas_financeiros),
                'tarefas_atrasadas' => $projectProgress['tarefas_atrasadas'] ?? 0,
                'dados_incompletos' => $dados_incompletos,
                'nivel' => $risco_score >= 75 ? 'Baixo' : ($risco_score >= 50 ? 'Moderado' : 'Alto'),
            ],
            'qualidade' => [
                'cobertura_testes' => $dados['qualidade']['cobertura_testes'] ?? 0,
                'score_codigo' => $dados['qualidade']['score_codigo'] ?? 0,
                'score' => round($qualidade_score, 1)
            ],
            'recursos' => [
                'equipe' => $dados['recursos']['equipe'] ?? 0,
                'especialistas' => $dados['recursos']['especialistas'] ?? 0,
                'score' => round($recursos_score, 1)
            ],
        ],
        'viabilidade' => [
            'score_geral' => round($viabilidade_geral, 1),
            'status' => $status_viabilidade,
            'recomendacao' => $viabilidade_geral >= 75 ? 'Projeto recomendado para implementação' : 'Recomenda-se revisar escopo e recursos',
            'scores_por_area' => [
                'financeiro' => round($financeiro_score, 1),
                'execucao' => round($execucao_score, 1),
                'prazo' => round($prazo_score, 1),
                'risco' => round($risco_score, 1),
                'recursos' => round($recursos_score, 1),
                'planejamento' => round($planejamento_score, 1),
                'qualidade' => round($qualidade_score, 1),
            ]
        ],
        'projeto_vinculado' => $projectRow ? [
            'id' => (int) $projectRow['id'],
            'nome' => (string) ($projectData['projectName'] ?? $projectData['name'] ?? ('Projeto #' . $projectRow['id'])),
            'cliente' => (string) ($projectData['cliente'] ?? ''),
            'status' => (string) ($projectData['manualStatus'] ?? $projectData['status'] ?? 'Pendente'),
            'fase' => (string) ($projectData['fase'] ?? ''),
            'lider' => (string) ($projectData['projectLeader'] ?? ''),
            'codigo' => (string) ($projectData['codigo'] ?? ''),
            'progresso' => $projectProgress['progresso'],
            'tarefas_total' => $projectProgress['total_tarefas'],
            'tarefas_concluidas' => $projectProgress['tarefas_concluidas'],
            'tarefas_atrasadas' => $projectProgress['tarefas_atrasadas'],
            'pontualidade_pct' => $projectProgress['pontualidade_pct'],
            'updated_at' => $projectRow['updated_at'] ?? null,
            'url' => 'Controle_de_projetos/index.php?projeto_id=' . (int) $projectRow['id'],
        ] : null,
        'decisao' => $decisao,
        'indicadores_financeiros' => [
            'margem_esperada_pct' => $financeiro_salvo['margem_esperada_pct'] ?? 0,
            'custo_total' => $financeiro_salvo['custo_total'] ?? 0,
            'preco_sugerido' => $financeiro_salvo['preco_sugerido'] ?? 0,
            'payback_meses' => $financeiro_salvo['payback_meses'] ?? 0,
            'roi_esperado_pct' => $financeiro_salvo['roi_esperado_pct'] ?? 0,
            'desvio_estimado_realizado_pct' => $financeiro_salvo['desvio_estimado_realizado_pct'] ?? 0,
        ],
        'alertas_financeiros' => $alertas_financeiros,
        'comparativo_revisoes' => $comparativo_revisoes,
        'compatibilidades' => [
            [
                'area' => 'Financeiro',
                'status' => $financeiro_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($financeiro_score, 1),
                'detalhes' => 'ROI de ' . ($financeiro_salvo['roi_esperado_pct'] ?? 0) . '% com payback em ' . ($financeiro_salvo['payback_meses'] ?? 0) . ' meses'
            ],
            [
                'area' => 'Planejamento',
                'status' => $planejamento_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($planejamento_score, 1),
                'detalhes' => $projectRow
                    ? 'Projeto vinculado com ' . $projectProgress['progresso'] . '% de avanço, ' . $projectProgress['tarefas_concluidas'] . '/' . $projectProgress['total_tarefas'] . ' tarefas concluídas e ' . $projectProgress['tarefas_atrasadas'] . ' atrasada(s)'
                    : 'Duração de ' . ($dados['planejamento']['duracao_meses'] ?? 0) . ' meses em ' . ($dados['planejamento']['fases'] ?? 0) . ' fases'
            ],
            [
                'area' => 'Execução',
                'status' => $execucao_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($execucao_score, 1),
                'detalhes' => $projectRow
                    ? $tarefasPendentes . ' tarefa(s) pendente(s) no projeto vinculado'
                    : 'Sem projeto vinculado para medir execução real'
            ],
            [
                'area' => 'Prazo',
                'status' => $prazo_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($prazo_score, 1),
                'detalhes' => ($projectProgress['tarefas_atrasadas'] ?? 0) . ' tarefa(s) atrasada(s)'
            ],
            [
                'area' => 'Risco',
                'status' => $risco_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($risco_score, 1),
                'detalhes' => 'Nível ' . ($risco_score >= 75 ? 'baixo' : ($risco_score >= 50 ? 'moderado' : 'alto')) . ' com ' . count($alertas_financeiros) . ' alerta(s)'
            ],
            [
                'area' => 'Qualidade',
                'status' => $qualidade_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($qualidade_score, 1),
                'detalhes' => 'Cobertura de testes ' . ($dados['qualidade']['cobertura_testes'] ?? 0) . '% com score de código ' . ($dados['qualidade']['score_codigo'] ?? 0)
            ],
            [
                'area' => 'Recursos',
                'status' => $recursos_score >= 80 ? 'compativel' : 'incompativel',
                'score' => round($recursos_score, 1),
                'detalhes' => 'Equipe de ' . ($dados['recursos']['equipe'] ?? 0) . ' pessoas com ' . ($dados['recursos']['especialistas'] ?? 0) . ' especialistas'
            ]
        ]
    ];
    
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao processar relatório',
        'mensagem' => $e->getMessage()
    ]);
}
?>
