<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'api/config.php';

// Simular sessão
$_SESSION['user_id'] = 1;
$_SESSION['tenant_id'] = 'admin';

// Testar a mesma query do dashboard_viabilidade.php
try {
    $anvi_id = 1;
    $tenant_id = $_SESSION['tenant_id'] ?? 'admin';
    
    echo "Testando ANVI ID: $anvi_id\n";
    echo "Tenant ID: $tenant_id\n\n";
    
    // Query do dashboard
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
        echo "❌ ANVI não encontrado";
        exit;
    }
    
    echo "✅ ANVI encontrado! ID: " . $anvi['id'] . "\n";
    echo "  Número: " . $anvi['numero'] . "\n";
    echo "  Cliente: " . $anvi['cliente'] . "\n";
    echo "  Status: " . $anvi['status'] . "\n";
    
} catch (PDOException $e) {
    echo "❌ Erro SQL: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
