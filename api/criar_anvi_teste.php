<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    
    // Verificar se já existe
    $result = $pdo->query("SELECT COUNT(*) as count FROM anvis");
    $count = $result->fetch()['count'];
    
    if ($count > 0) {
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Já existem ' . $count . ' ANVI(s) no banco',
            'anvis' => $count
        ]);
        exit;
    }
    
    // Criar ANVI com dados realistas
    $stmt = $pdo->prepare("
        INSERT INTO anvis (
            numero, revisao, cliente, projeto, produto, status,
            data_anvi, data_criacao, data_atualizacao,
            dados, dados_financeiros
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $dados = json_encode([
        'financeiro' => [
            'orcamento' => 150000,
            'gasto' => 87500,
            'margem_prevista' => 35,
            'margem_realizada' => 42
        ],
        'planejamento' => [
            'duracao_prevista_dias' => 180,
            'duracao_realizada_dias' => 165,
            'etapas_totais' => 5,
            'etapas_completas' => 3
        ],
        'qualidade' => [
            'testes_total' => 150,
            'testes_passou' => 142,
            'testes_falhados' => 8,
            'cobertura_percentual' => 94.67
        ],
        'recursos' => [
            'disponibilidade' => 98.5,
            'utilização' => 87,
            'pessoas_alocadas' => 12
        ]
    ]);
    
    $dados_financeiros = json_encode([
        'investimento_total' => 150000,
        'roi_esperado_pct' => 45,
        'payback_meses' => 18,
        'duracao_meses' => 36,
        'riscos_identificados' => ['critica' => 0, 'alta' => 2, 'media' => 5, 'baixa' => 8],
        'receita_esperada_mensal' => 4167,
        'custo_fixo_mensal' => 2500,
        'ponto_equilibrio_mes' => 8
    ]);
    
    $stmt->execute([
        'ANVI-2026-001',  // numero
        1,                // revisao
        'Empresa XYZ',    // cliente
        'Sistema de Gestão', // projeto
        'Software SaaS',  // produto
        'ativo',          // status
        date('Y-m-d'),    // data_anvi
        date('Y-m-d H:i:s'), // data_criacao
        date('Y-m-d H:i:s'), // data_atualizacao
        $dados,
        $dados_financeiros
    ]);
    
    $anvi_id = $pdo->lastInsertId();
    
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'ANVI criado com sucesso!',
        'anvi_id' => $anvi_id,
        'numero' => 'ANVI-2026-001',
        'cliente' => 'Empresa XYZ',
        'projeto' => 'Sistema de Gestão',
        'proximo_passo' => 'Acesse https://viabix.com.br/dashboard_viabilidade.html e clique em "Carregar Análise" com ID: ' . $anvi_id
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
