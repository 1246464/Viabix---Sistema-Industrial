<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // Usar tenant_id admin (precisa estar criado no banco primeiro)
    $tenant_id = 'admin';
    
    // Verificar se já existe ANVI para este tenant
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM anvis WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $count = $stmt->fetch()['count'];
    
    if ($count > 0) {
        // Listar ANVIs existentes
        $stmt = $pdo->prepare("SELECT id, numero, cliente, projeto FROM anvis WHERE tenant_id = ? LIMIT 5");
        $stmt->execute([$tenant_id]);
        $anvis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'status' => 'info',
            'mensagem' => 'Já existem ' . $count . ' ANVI(s) no banco para este tenant',
            'total_anvis' => $count,
            'anvis_existentes' => $anvis
        ]);
        exit;
    }
    
    // Gerar ID único para ANVI
    $anvi_id = 'ANVI-' . date('YmdHis') . '-001';
    
    // Preparar dados JSON com estrutura correcta
    $dados = json_encode([
        'financeiro' => [
            'orcamento' => 150000,
            'gasto' => 87500,
            'margem_prevista' => 35,
            'margem_realizada' => 42,
            'investimento_total' => 150000,
            'roi_esperado_pct' => 45,
            'payback_meses' => 18
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
    
    // Inserir ANVI com schema CORRETO (sem dados_financeiros)
    $stmt = $pdo->prepare("
        INSERT INTO anvis (
            id, tenant_id, numero, revisao, cliente, projeto, produto, 
            status, volume_mensal, data_anvi, dados, criado_por
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $anvi_id,           // id
        $tenant_id,         // tenant_id
        'ANVI-2026-001',    // numero
        1,                  // revisao
        'Empresa XYZ',      // cliente
        'Sistema de Gestão', // projeto
        'Software SaaS',    // produto
        'em-andamento',     // status
        1000,               // volume_mensal
        date('Y-m-d'),      // data_anvi
        $dados,             // dados (JSON com tudo)
        'user-admin'        // criado_por
    ]);
    
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'ANVI de teste criado com sucesso!',
        'anvi_id' => $anvi_id,
        'numero' => 'ANVI-2026-001',
        'cliente' => 'Empresa XYZ',
        'projeto' => 'Sistema de Gestão',
        'tenant_id' => $tenant_id,
        'proximo_passo' => 'Acesse https://viabix.com.br/dashboard_viabilidade.html e clique em "Carregar Análise" com ID: ' . $anvi_id
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
