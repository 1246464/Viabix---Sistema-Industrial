<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

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
    
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.numero,
            a.revisao,
            a.cliente,
            a.projeto,
            a.produto,
            a.status,
            a.data_anvi,
            a.data_criacao,
            a.dados,
            a.dados_financeiros
        FROM anvis a
        WHERE a.id = ? AND a.tenant_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$anvi_id, $tenant_id]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anvi) {
        http_response_code(404);
        exit(json_encode(['erro' => 'ANVI não encontrado']));
    }
    
    // Decode JSON fields
    $dados = json_decode($anvi['dados'] ?? '{}', true);
    $dados_financeiros = json_decode($anvi['dados_financeiros'] ?? '{}', true);
    $financeiro_salvo = is_array($dados_financeiros) ? $dados_financeiros : [];
    
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
    
    // Qualidade
    $cobertura = $dados['qualidade']['cobertura_testes'] ?? 0;
    $score_code = $dados['qualidade']['score_codigo'] ?? 0;
    $qualidade_score = ($cobertura + $score_code) / 2;
    
    // Recursos
    $equipe = $dados['recursos']['equipe'] ?? 0;
    $especialistas = $dados['recursos']['especialistas'] ?? 0;
    $recursos_score = min(100, $equipe * 10 + $especialistas * 15);
    
    // Overall viability
    $viabilidade_geral = ($financeiro_score + $planejamento_score + $qualidade_score + $recursos_score) / 4;
    $status_viabilidade = $viabilidade_geral >= 75 ? 'VIÁVEL' : ($viabilidade_geral >= 50 ? 'PARCIAL' : 'NÃO VIÁVEL');
    
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
            'titulo' => 'Planejamento com baixa maturidade',
            'acao' => 'Detalhar fases e duração para reduzir incerteza de execução.'
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
    if (!$fases && !$duracao) {
        $dados_incompletos[] = 'planejamento';
    }
    if (!$cobertura && !$score_code) {
        $dados_incompletos[] = 'qualidade';
    }
    if (!$equipe && !$especialistas) {
        $dados_incompletos[] = 'recursos';
    }

    $temCritico = count(array_filter($alertas_financeiros, fn($alerta) => ($alerta['severidade'] ?? '') === 'critico')) > 0;
    if ($temCritico || $financeiro_score < 50 || $viabilidade_geral < 50) {
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
    $stmt = $pdo->prepare("
        SELECT id, revisao, data_atualizacao, dados_financeiros
        FROM anvis
        WHERE numero = ? AND tenant_id = ? AND id <> ?
        ORDER BY data_atualizacao DESC
        LIMIT 1
    ");
    $stmt->execute([$anvi['numero'], $tenant_id, $anvi['id']]);
    $revisao_anterior = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($revisao_anterior) {
        $financeiro_anterior = json_decode($revisao_anterior['dados_financeiros'] ?? '{}', true);
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
                'fases' => $dados['planejamento']['fases'] ?? 0,
                'score' => round($planejamento_score, 1)
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
                'planejamento' => round($planejamento_score, 1),
                'qualidade' => round($qualidade_score, 1),
                'recursos' => round($recursos_score, 1),
            ]
        ],
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
                'detalhes' => 'Duração de ' . ($dados['planejamento']['duracao_meses'] ?? 0) . ' meses em ' . ($dados['planejamento']['fases'] ?? 0) . ' fases'
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
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => 'Erro ao processar relatório',
        'mensagem' => $e->getMessage()
    ]);
}
?>
