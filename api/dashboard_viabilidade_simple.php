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
    $tenant_id = $_SESSION['tenant_id'] ?? 'admin';
    
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
    
    // Calculate compatibility scores
    $financeiro_score = 0;
    $planejamento_score = 0;
    $qualidade_score = 0;
    $recursos_score = 0;
    
    // Financeiro
    $roi = $dados_financeiros['roi_esperado_pct'] ?? 0;
    $payback = $dados_financeiros['payback_meses'] ?? 0;
    $financeiro_score = min(100, ($roi / 150) * 70 + (12 / max($payback, 1)) * 30);
    
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
                'investimento' => $dados['financeiro']['investimento'] ?? 0,
                'margem' => $dados['financeiro']['margem'] ?? 0,
                'investimento_total' => $dados_financeiros['investimento_total'] ?? 0,
                'roi_esperado' => $dados_financeiros['roi_esperado_pct'] ?? 0,
                'payback_meses' => $dados_financeiros['payback_meses'] ?? 0,
                'duracao_meses' => $dados_financeiros['duracao_meses'] ?? 0,
                'riscos' => $dados_financeiros['riscos_identificados'] ?? [],
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
            'recomendacao' => $viabilidade_geral >= 75 ? 'Projeto recomendado para implementação' : 'Recomenda-se revisar escopo e recursos'
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
