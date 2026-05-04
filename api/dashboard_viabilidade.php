<?php
/**
 * MÓDULO 3: Dashboard de Compatibilidade/Viabilidade Integrada
 * Adaptado para dados REAIS do projeto VIABIX
 * 
 * Analisa incompatibilidades em um projeto ANVI:
 * - Financeiro (Invoices, Subscriptions)
 * - Planejamento (Histórico, Logs)
 * - Qualidade (Erros em logs)
 * - Recursos (Usuários, Configurações)
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Validar entrada
$anvi_id = $_GET['anvi_id'] ?? '';
if (empty($anvi_id)) {
    http_response_code(400);
    exit(json_encode(['erro' => 'ANVI ID é obrigatório']));
}

// Verificar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['erro' => 'Não autenticado']));
}

// Usar tenant_id se disponível, senão usar admin
$tenant_id = viabixCurrentTenantId() ?? 'admin';

try {
    $report = [];
    
    // ========================================
    // 1. DADOS DO ANVI (ESTRUTURA JSON) + PROJETO ASSOCIADO
    // ========================================
    
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
            a.data_atualizacao,
            a.projeto_id,
            JSON_EXTRACT(a.dados, '$.financeiro') as financeiro_json,
            JSON_EXTRACT(a.dados, '$.planejamento') as planejamento_json,
            JSON_EXTRACT(a.dados, '$.qualidade') as qualidade_json,
            JSON_EXTRACT(a.dados, '$.recursos') as recursos_json,
            JSON_EXTRACT(a.dados_financeiros, '$.investimento_total') as investimento_total,
            JSON_EXTRACT(a.dados_financeiros, '$.roi_esperado_pct') as roi_esperado,
            JSON_EXTRACT(a.dados_financeiros, '$.payback_meses') as payback_meses,
            JSON_EXTRACT(a.dados_financeiros, '$.duracao_meses') as duracao_meses,
            JSON_EXTRACT(a.dados_financeiros, '$.riscos_identificados') as riscos_identificados,
            p.id as projeto_id_real,
            p.orcamento as orcamento_planejado,
            p.progresso as progresso_percentual,
            JSON_EXTRACT(p.dados_financeiros_reais, '$.custo_real') as custo_real,
            JSON_EXTRACT(p.dados_financeiros_reais, '$.data_fim_estimada') as data_fim_estimada
        FROM anvis a
        LEFT JOIN projetos p ON a.projeto_id = p.id
        WHERE a.id = ?
        LIMIT 1
    ");
    $stmt->execute([$anvi_id]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anvi) {
        http_response_code(404);
        exit(json_encode(['erro' => 'ANVI não encontrado']));
    }
    
    // Decodificar JSONs
    $financeiro_data = json_decode($anvi['financeiro_json'] ?? '{}', true);
    $planejamento_data = json_decode($anvi['planejamento_json'] ?? '{}', true);
    $qualidade_data = json_decode($anvi['qualidade_json'] ?? '{}', true);
    $recursos_data = json_decode($anvi['recursos_json'] ?? '{}', true);
    
    $report['anvi'] = [
        'id' => $anvi['id'],
        'numero' => $anvi['numero'],
        'revisao' => $anvi['revisao'],
        'cliente' => $anvi['cliente'],
        'projeto' => $anvi['projeto'],
        'status' => $anvi['status'],
        'data_anvi' => $anvi['data_anvi'],
        'data_criacao' => $anvi['data_criacao'],
        'data_atualizacao' => $anvi['data_atualizacao'],
    ];
    
    // ========================================
    // 2. ANÁLISE FINANCEIRA (De Invoices/Subscriptions)
    // ========================================
    
    $compatibilidade_financeira = [];
    $severidade_financeira = 'success';
    
    // 2.1 Status de Pagamento
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_invoices,
            SUM(CASE WHEN status = 'paga' THEN 1 ELSE 0 END) as invoices_pagas,
            SUM(CASE WHEN status = 'vencida' THEN 1 ELSE 0 END) as invoices_vencidas,
            SUM(valor_total) as valor_total,
            SUM(valor_pago) as valor_pago
        FROM invoices
        WHERE tenant_id = ?
    ");
    $stmt->execute([$tenant_id]);
    $invoices = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($invoices && $invoices['total_invoices'] > 0) {
        $valor_total = floatval($invoices['valor_total'] ?? 0);
        $valor_pago = floatval($invoices['valor_pago'] ?? 0);
        $percentual_pago = $valor_total > 0 ? ($valor_pago / $valor_total * 100) : 0;
        $invoices_vencidas = intval($invoices['invoices_vencidas'] ?? 0);
        
        $compatibilidade_financeira[] = [
            'item' => 'Status de Pagamento',
            'status' => $invoices_vencidas == 0 && $percentual_pago >= 90 ? 'OK' : 'ATENÇÃO',
            'valor_total' => $valor_total,
            'valor_pago' => $valor_pago,
            'percentual_pago' => round($percentual_pago, 2),
            'invoices_vencidas' => $invoices_vencidas,
            'mensagem' => $invoices_vencidas > 0 ? 
                         $invoices_vencidas . ' fatura(s) vencida(s)' :
                         round($percentual_pago, 1) . '% pago',
            'severidade' => $invoices_vencidas > 3 ? 'error' : ($invoices_vencidas > 0 ? 'warning' : 'success'),
        ];
        
        if ($invoices_vencidas > 3) {
            $severidade_financeira = 'error';
        } elseif ($invoices_vencidas > 0) {
            $severidade_financeira = 'warning';
        }
    }
    
    // 2.2 Status da Assinatura
    $stmt = $pdo->prepare("
        SELECT status, valor_contratado
        FROM subscriptions
        WHERE tenant_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$tenant_id]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($subscription) {
        $status_ok = $subscription['status'] === 'ativa';
        
        $compatibilidade_financeira[] = [
            'item' => 'Assinatura',
            'status' => $status_ok ? 'OK' : 'ATENÇÃO',
            'subscription_status' => ucfirst($subscription['status']),
            'valor_contratado' => floatval($subscription['valor_contratado'] ?? 0),
            'mensagem' => $status_ok ? 'Assinatura ativa' : 'Assinatura: ' . $subscription['status'],
            'severidade' => $status_ok ? 'success' : 'warning',
        ];
        
        if (!$status_ok) {
            $severidade_financeira = 'warning';
        }
    }
    
    // Dados JSON do ANVI (se tiver)
    if (!empty($financeiro_data) && is_array($financeiro_data)) {
        if (isset($financeiro_data['orcamento']) && isset($financeiro_data['gasto'])) {
            $orcamento = floatval($financeiro_data['orcamento']);
            $gasto = floatval($financeiro_data['gasto']);
            $percentual = $orcamento > 0 ? ($gasto / $orcamento * 100) : 0;
            
            $compatibilidade_financeira[] = [
                'item' => 'Orçamento do Projeto (JSON)',
                'status' => $percentual <= 100 ? 'OK' : 'ATENÇÃO',
                'orcamento' => $orcamento,
                'gasto' => $gasto,
                'percentual' => round($percentual, 2),
                'mensagem' => $percentual > 100 ? 
                             'Ultrapassou orçamento em ' . round($percentual - 100, 2) . '%' :
                             'Dentro do orçamento (' . round($percentual, 1) . '%)',
                'severidade' => $percentual > 100 ? 'error' : ($percentual > 80 ? 'warning' : 'success'),
            ];
            
            if ($percentual > 100) {
                $severidade_financeira = 'error';
            } elseif ($percentual > 80) {
                $severidade_financeira = 'warning';
            }
        }
    }
    
    $report['financeiro'] = [
        'severidade' => $severidade_financeira,
        'compatibilidades' => $compatibilidade_financeira,
    ];
    
    // ========================================
    // 3. ANÁLISE DE PLANEJAMENTO (De Histórico/Logs)
    // ========================================
    
    $compatibilidade_planejamento = [];
    $severidade_planejamento = 'success';
    
    // 3.1 Histórico de Atualizações
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_mudancas,
            DATE(data_atualizacao) as ultima_mudanca
        FROM anvis_historico
        WHERE anvi_id = ?
        GROUP BY DATE(data_atualizacao)
        ORDER BY data_atualizacao DESC
        LIMIT 1
    ");
    $stmt->execute([$anvi_id]);
    $historico = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($historico) {
        $ultima_mudanca = new DateTime($historico['ultima_mudanca']);
        $dias_sem_atualizacao = (new DateTime())->diff($ultima_mudanca)->days;
        $total_mudancas = intval($historico['total_mudancas'] ?? 0);
        
        $compatibilidade_planejamento[] = [
            'item' => 'Atividade do Projeto',
            'status' => $dias_sem_atualizacao <= 30 ? 'OK' : 'ATENÇÃO',
            'total_mudancas' => $total_mudancas,
            'dias_sem_atualizacao' => $dias_sem_atualizacao,
            'ultima_mudanca' => $historico['ultima_mudanca'],
            'mensagem' => $dias_sem_atualizacao > 60 ? 
                         'Sem atualizações há ' . $dias_sem_atualizacao . ' dias' :
                         $total_mudancas . ' mudança(s) registrada(s)',
            'severidade' => $dias_sem_atualizacao > 60 ? 'error' : ($dias_sem_atualizacao > 30 ? 'warning' : 'success'),
        ];
        
        if ($dias_sem_atualizacao > 60) {
            $severidade_planejamento = 'error';
        } elseif ($dias_sem_atualizacao > 30) {
            $severidade_planejamento = 'warning';
        }
    }
    
    // 3.2 Progresso (se existir em JSON)
    if (!empty($planejamento_data) && is_array($planejamento_data)) {
        if (isset($planejamento_data['progresso'])) {
            $progresso = intval($planejamento_data['progresso']);
            $dias_decorridos = isset($planejamento_data['dias_decorridos']) ? 
                              intval($planejamento_data['dias_decorridos']) : 0;
            $dias_totais = isset($planejamento_data['dias_totais']) ? 
                          intval($planejamento_data['dias_totais']) : 1;
            
            $tempo_esperado = $dias_totais > 0 ? ($dias_decorridos / $dias_totais * 100) : 0;
            $atraso = $progresso < $tempo_esperado ? 
                     round(($tempo_esperado - $progresso) / 100 * $dias_totais) : 0;
            
            $compatibilidade_planejamento[] = [
                'item' => 'Cronograma vs Progresso',
                'status' => abs($atraso) <= 7 ? 'OK' : 'ATENÇÃO',
                'tempo_esperado' => round($tempo_esperado, 2),
                'progresso_real' => $progresso,
                'atraso_dias' => $atraso,
                'mensagem' => $atraso > 0 ? 
                             'Atrasado ' . $atraso . ' dias' :
                             ($atraso < 0 ? 'Adiantado ' . abs($atraso) . ' dias' : 'No prazo'),
                'severidade' => abs($atraso) > 30 ? 'error' : (abs($atraso) > 7 ? 'warning' : 'success'),
            ];
            
            if (abs($atraso) > 30) {
                $severidade_planejamento = 'error';
            } elseif (abs($atraso) > 7) {
                $severidade_planejamento = 'warning';
            }
        }
    }
    
    $report['planejamento'] = [
        'severidade' => $severidade_planejamento,
        'compatibilidades' => $compatibilidade_planejamento,
    ];
    
    // ========================================
    // 4. ANÁLISE DE QUALIDADE (De Logs de Erros)
    // ========================================
    
    $compatibilidade_qualidade = [];
    $severidade_qualidade = 'success';
    
    // 4.1 Taxa de Erros em Logs
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_logs,
            SUM(CASE WHEN tipo = 'erro' THEN 1 ELSE 0 END) as logs_erro,
            SUM(CASE WHEN tipo = 'aviso' THEN 1 ELSE 0 END) as logs_aviso,
            SUM(CASE WHEN tipo = 'info' THEN 1 ELSE 0 END) as logs_info
        FROM logs_atividade
        WHERE anvi_id = ? AND data_hora > DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $stmt->execute([$anvi_id]);
    $logs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($logs && $logs['total_logs'] > 0) {
        $taxa_erro = ($logs['logs_erro'] / $logs['total_logs'] * 100);
        
        $compatibilidade_qualidade[] = [
            'item' => 'Taxa de Erros (últimos 30 dias)',
            'status' => $taxa_erro <= 5 ? 'OK' : 'ATENÇÃO',
            'total_logs' => $logs['total_logs'],
            'logs_erro' => $logs['logs_erro'],
            'logs_aviso' => $logs['logs_aviso'],
            'taxa_erro' => round($taxa_erro, 2),
            'mensagem' => round($taxa_erro, 1) . '% de erros (' . $logs['logs_erro'] . ' total)',
            'severidade' => $taxa_erro > 10 ? 'error' : ($taxa_erro > 5 ? 'warning' : 'success'),
        ];
        
        if ($taxa_erro > 10) {
            $severidade_qualidade = 'error';
        } elseif ($taxa_erro > 5) {
            $severidade_qualidade = 'warning';
        }
    }
    
    // 4.2 Dados de Qualidade no JSON (se existir)
    if (!empty($qualidade_data) && is_array($qualidade_data)) {
        if (isset($qualidade_data['testes_total']) && isset($qualidade_data['testes_passou'])) {
            $testes_total = intval($qualidade_data['testes_total']);
            $testes_passou = intval($qualidade_data['testes_passou']);
            $taxa_sucesso = $testes_total > 0 ? ($testes_passou / $testes_total * 100) : 0;
            
            $compatibilidade_qualidade[] = [
                'item' => 'Cobertura de Testes',
                'status' => $taxa_sucesso >= 95 ? 'OK' : 'ATENÇÃO',
                'testes_total' => $testes_total,
                'testes_passou' => $testes_passou,
                'taxa_sucesso' => round($taxa_sucesso, 2),
                'mensagem' => round($taxa_sucesso, 1) . '% de testes passando',
                'severidade' => $taxa_sucesso < 80 ? 'error' : ($taxa_sucesso < 95 ? 'warning' : 'success'),
            ];
            
            if ($taxa_sucesso < 80) {
                $severidade_qualidade = 'error';
            } elseif ($taxa_sucesso < 95) {
                $severidade_qualidade = 'warning';
            }
        }
    }
    
    $report['qualidade'] = [
        'severidade' => $severidade_qualidade,
        'compatibilidades' => $compatibilidade_qualidade,
    ];
    
    // ========================================
    // 5. ANÁLISE DE RECURSOS
    // ========================================
    
    $compatibilidade_recursos = [];
    $severidade_recursos = 'success';
    
    // 5.1 Usuários Ativos
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_usuarios
        FROM usuarios
        WHERE ativo = 1
    ");
    $stmt->execute();
    $usuarios = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_usuarios = intval($usuarios['total_usuarios'] ?? 0);
    
    $compatibilidade_recursos[] = [
        'item' => 'Usuários Ativos',
        'status' => $total_usuarios > 0 ? 'OK' : 'ATENÇÃO',
        'usuarios_ativos' => $total_usuarios,
        'mensagem' => $total_usuarios . ' usuário(s) ativo(s)',
        'severidade' => $total_usuarios == 0 ? 'error' : 'success',
    ];
    
    if ($total_usuarios == 0) {
        $severidade_recursos = 'error';
    }
    
    // 5.2 Status de Configurações
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as configs_total
        FROM configuracoes
        WHERE ativo = 1
    ");
    $stmt->execute();
    $configs = $stmt->fetch(PDO::FETCH_ASSOC);
    $configs_ativas = intval($configs['configs_total'] ?? 0);
    
    $compatibilidade_recursos[] = [
        'item' => 'Configurações Ativas',
        'status' => $configs_ativas > 0 ? 'OK' : 'ATENÇÃO',
        'configs_ativas' => $configs_ativas,
        'mensagem' => $configs_ativas . ' configuração(ões)',
        'severidade' => $configs_ativas > 0 ? 'success' : 'warning',
    ];
    
    if ($configs_ativas == 0) {
        $severidade_recursos = 'warning';
    }
    
    // 5.3 Dados de Recursos no JSON
    if (!empty($recursos_data) && is_array($recursos_data)) {
        if (isset($recursos_data['disponibilidade'])) {
            $disponibilidade = intval($recursos_data['disponibilidade']);
            
            $compatibilidade_recursos[] = [
                'item' => 'Disponibilidade de Infraestrutura',
                'status' => $disponibilidade >= 90 ? 'OK' : 'ATENÇÃO',
                'disponibilidade' => $disponibilidade,
                'mensagem' => $disponibilidade . '% disponível',
                'severidade' => $disponibilidade < 80 ? 'error' : ($disponibilidade < 90 ? 'warning' : 'success'),
            ];
            
            if ($disponibilidade < 80) {
                $severidade_recursos = 'error';
            } elseif ($disponibilidade < 90) {
                $severidade_recursos = 'warning';
            }
        }
    }
    
    $report['recursos'] = [
        'severidade' => $severidade_recursos,
        'compatibilidades' => $compatibilidade_recursos,
    ];
    
    // ========================================
    // 6. ANÁLISE FINANCEIRA EXPANDIDA (ROI, Payback, Variâncias)
    // ========================================
    
    $analise_financeira_expandida = [];
    
    // 6.1 Dados do ANVI (Investimento esperado)
    if (!empty($anvi['investimento_total'])) {
        $investimento_total = floatval($anvi['investimento_total']);
        $roi_esperado = floatval($anvi['roi_esperado'] ?? 0);
        $payback_meses = floatval($anvi['payback_meses'] ?? 0);
        $duracao_meses = floatval($anvi['duracao_meses'] ?? 0);
        
        $analise_financeira_expandida['planejado'] = [
            'investimento_total' => $investimento_total,
            'roi_esperado_pct' => $roi_esperado,
            'payback_meses' => $payback_meses,
            'duracao_meses' => $duracao_meses,
            'receita_esperada' => $payback_meses > 0 ? round($investimento_total / $payback_meses, 2) : 0,
        ];
    }
    
    // 6.2 Dados do Projeto (Custo realizado)
    if (!empty($anvi['custo_real'])) {
        $custo_real = floatval($anvi['custo_real']);
        $orcamento_planejado = floatval($anvi['orcamento_planejado'] ?? 0);
        $variancia_orcamentaria = $orcamento_planejado > 0 ? 
                                 round(($custo_real - $orcamento_planejado) / $orcamento_planejado * 100, 2) :
                                 0;
        
        $analise_financeira_expandida['realizado'] = [
            'custo_real' => $custo_real,
            'orcamento_planejado' => $orcamento_planejado,
            'variancia_orcamentaria_pct' => $variancia_orcamentaria,
            'status_orcamento' => abs($variancia_orcamentaria) <= 5 ? 'OK' : 
                                 ($variancia_orcamentaria < 0 ? 'ABAIXO' : 'ACIMA'),
        ];
        
        if (isset($analise_financeira_expandida['planejado']['investimento_total'])) {
            $roi_real = round(($custo_real - $analise_financeira_expandida['planejado']['investimento_total']) / 
                             $analise_financeira_expandida['planejado']['investimento_total'] * 100, 2);
            $analise_financeira_expandida['realizado']['roi_real_pct'] = $roi_real;
        }
    }
    
    // 6.3 Timeline - Variância
    if (!empty($anvi['data_fim_estimada'])) {
        $data_fim_planejada = new DateTime($anvi['data_atualizacao']);
        $data_fim_estimada = new DateTime($anvi['data_fim_estimada']);
        $variancia_dias = $data_fim_planejada->diff($data_fim_estimada)->days;
        
        if ($data_fim_estimada < $data_fim_planejada) {
            $variancia_dias = -$variancia_dias;
        }
        
        $analise_financeira_expandida['timeline'] = [
            'data_fim_planejada' => $anvi['data_atualizacao'],
            'data_fim_estimada' => $anvi['data_fim_estimada'],
            'variancia_dias' => $variancia_dias,
            'status_timeline' => $variancia_dias == 0 ? 'NO PRAZO' : 
                               ($variancia_dias > 0 ? 'ATRASADO' : 'ADIANTADO'),
        ];
    }
    
    $report['financeiro_expandido'] = $analise_financeira_expandida;
    
    // ========================================
    // 7. RISCOS E ETAPAS
    // ========================================
    
    // 7.1 Riscos do Projeto
    if (!empty($anvi['projeto_id_real'])) {
        $stmt = $pdo->prepare("
            SELECT 
                id,
                descricao,
                severidade,
                probabilidade,
                impacto_financeiro,
                exposicao,
                status
            FROM projeto_riscos
            WHERE projeto_id = ? AND status != 'resolvido'
            ORDER BY exposicao DESC
            LIMIT 10
        ");
        $stmt->execute([$anvi['projeto_id_real']]);
        $riscos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $report['riscos'] = [
            'total' => count($riscos),
            'por_severidade' => [
                'critica' => count(array_filter($riscos, fn($r) => $r['severidade'] === 'critica')),
                'alta' => count(array_filter($riscos, fn($r) => $r['severidade'] === 'alta')),
                'media' => count(array_filter($riscos, fn($r) => $r['severidade'] === 'media')),
                'baixa' => count(array_filter($riscos, fn($r) => $r['severidade'] === 'baixa')),
            ],
            'exposicao_total' => array_sum(array_column($riscos, 'exposicao')),
            'lista' => $riscos,
        ];
    }
    
    // 7.2 Etapas do Projeto
    if (!empty($anvi['projeto_id_real'])) {
        $stmt = $pdo->prepare("
            SELECT 
                numero,
                descricao,
                data_inicio_planejada,
                data_fim_planejada,
                data_inicio_real,
                data_fim_real,
                percentual_completo,
                status
            FROM projeto_etapas
            WHERE projeto_id = ?
            ORDER BY numero ASC
        ");
        $stmt->execute([$anvi['projeto_id_real']]);
        $etapas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_etapas = count($etapas);
        $etapas_concluidas = count(array_filter($etapas, fn($e) => $e['status'] === 'concluida'));
        $progresso_etapas = $total_etapas > 0 ? round(($etapas_concluidas / $total_etapas) * 100, 2) : 0;
        
        $report['etapas'] = [
            'total' => $total_etapas,
            'concluidas' => $etapas_concluidas,
            'em_andamento' => count(array_filter($etapas, fn($e) => $e['status'] === 'em_andamento')),
            'planejadas' => count(array_filter($etapas, fn($e) => $e['status'] === 'planejada')),
            'progresso_pct' => $progresso_etapas,
            'lista' => $etapas,
        ];
    }
    
    // ========================================
    // 8. SCORE DE VIABILIDADE GERAL
    // ========================================
    
    $scores = [
        'financeiro' => $severidade_financeira == 'error' ? 40 : ($severidade_financeira == 'warning' ? 70 : 100),
        'planejamento' => $severidade_planejamento == 'error' ? 40 : ($severidade_planejamento == 'warning' ? 70 : 100),
        'qualidade' => $severidade_qualidade == 'error' ? 40 : ($severidade_qualidade == 'warning' ? 70 : 100),
        'recursos' => $severidade_recursos == 'error' ? 40 : ($severidade_recursos == 'warning' ? 70 : 100),
    ];
    
    $score_geral = round(array_sum($scores) / count($scores), 1);
    
    // Ajuste por riscos
    if (!empty($report['riscos'])) {
        $risco_critico_count = $report['riscos']['por_severidade']['critica'] ?? 0;
        if ($risco_critico_count > 0) {
            $score_geral = $score_geral * 0.8; // Reduz 20% se houver riscos críticos
        }
    }
    
    $report['viabilidade'] = [
        'score_geral' => round($score_geral, 1),
        'scores_por_area' => $scores,
        'status' => $score_geral >= 80 ? 'VIÁVEL' : ($score_geral >= 60 ? 'VIÁVEL COM RESSALVAS' : 'NÃO VIÁVEL'),
        'recomendacao' => $score_geral >= 80 ? 
                         'Projeto pode prosseguir normalmente' :
                         ($score_geral >= 60 ? 
                          'Projeto pode prosseguir, mas com atenção às áreas em amarelo/vermelho' :
                          'Projeto requer correções antes de prosseguir'),
    ];
    
    // ========================================
    // 9. RECOMENDAÇÕES EXPANDIDAS
    // ========================================
    
    $recomendacoes = [];
    
    // Recomendações de Financeiro
    foreach ($report['financeiro']['compatibilidades'] as $comp) {
        if ($comp['severidade'] == 'error') {
            $recomendacoes[] = [
                'area' => 'Financeiro',
                'problema' => $comp['item'],
                'acao' => $comp['mensagem'],
                'prioridade' => 'ALTA',
                'tipo' => 'financeiro',
            ];
        }
    }
    
    // Recomendações de Variância Orçamentária
    if (!empty($report['financeiro_expandido']['realizado']) && 
        $report['financeiro_expandido']['realizado']['variancia_orcamentaria_pct'] > 10) {
        $recomendacoes[] = [
            'area' => 'Financeiro',
            'problema' => 'Overspend - Custos acima do planejado',
            'acao' => 'Revisar despesas e implementar controles de custos',
            'prioridade' => 'ALTA',
            'tipo' => 'variancia_orcamentaria',
            'valor_variancia' => $report['financeiro_expandido']['realizado']['variancia_orcamentaria_pct'],
        ];
    }
    
    // Recomendações de Timeline
    if (!empty($report['financeiro_expandido']['timeline']) && 
        abs($report['financeiro_expandido']['timeline']['variancia_dias']) > 7) {
        $dias = $report['financeiro_expandido']['timeline']['variancia_dias'];
        $recomendacoes[] = [
            'area' => 'Planejamento',
            'problema' => $dias > 0 ? 'Projeto atrasado' : 'Projeto adiantado',
            'acao' => $dias > 0 ? 'Aumentar recursos ou ajustar escopo' : 'Avaliar oportunidade de encerrar antecipadamente',
            'prioridade' => abs($dias) > 14 ? 'ALTA' : 'MÉDIA',
            'tipo' => 'timeline',
            'variancia_dias' => $dias,
        ];
    }
    
    // Recomendações de Riscos
    if (!empty($report['riscos'])) {
        if ($report['riscos']['por_severidade']['critica'] > 0) {
            $recomendacoes[] = [
                'area' => 'Riscos',
                'problema' => 'Riscos críticos identificados',
                'acao' => 'Executar plano de mitigação imediatamente',
                'prioridade' => 'CRÍTICA',
                'tipo' => 'riscos_criticos',
                'quantidade' => $report['riscos']['por_severidade']['critica'],
            ];
        }
        
        if ($report['riscos']['exposicao_total'] > 0) {
            $recomendacoes[] = [
                'area' => 'Riscos',
                'problema' => 'Exposição financeira em riscos',
                'acao' => 'Revisar e executar planos de contingência',
                'prioridade' => 'ALTA',
                'tipo' => 'exposicao_risco',
                'exposicao_total' => $report['riscos']['exposicao_total'],
            ];
        }
    }
    
    // Recomendações de Etapas
    if (!empty($report['etapas']) && $report['etapas']['total'] > 0) {
        if ($report['etapas']['progresso_pct'] < 50 && 
            !empty($report['financeiro_expandido']['timeline']) &&
            $report['financeiro_expandido']['timeline']['variancia_dias'] > 0) {
            $recomendacoes[] = [
                'area' => 'Planejamento',
                'problema' => 'Progresso lento e projeto atrasado',
                'acao' => 'Revisar roadmap e aumentar velocidade de execução',
                'prioridade' => 'ALTA',
                'tipo' => 'progresso_lento',
                'progresso_pct' => $report['etapas']['progresso_pct'],
            ];
        }
    }
    
    // Recomendações de Planejamento
    foreach ($report['planejamento']['compatibilidades'] as $comp) {
        if ($comp['severidade'] == 'error') {
            $recomendacoes[] = [
                'area' => 'Planejamento',
                'problema' => $comp['item'],
                'acao' => $comp['mensagem'],
                'prioridade' => 'ALTA',
                'tipo' => 'planejamento',
            ];
        }
    }
    
    $report['recomendacoes'] = $recomendacoes;
    
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao processar relatório']);
}
