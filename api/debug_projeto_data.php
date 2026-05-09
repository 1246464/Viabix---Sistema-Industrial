<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    $anvi_id = $_GET['anvi_id'] ?? 'ANVI-20260509233923-001';
    $tenant_id = $_SESSION['tenant_id'] ?? 'admin';
    
    // 1. Get ANVI to find project_id
    $stmt = $pdo->prepare("
        SELECT id, projeto_id, numero FROM anvis WHERE id = ? AND tenant_id = ?
    ");
    $stmt->execute([$anvi_id, $tenant_id]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anvi) {
        http_response_code(404);
        exit(json_encode([
            'erro' => 'ANVI não encontrado',
            'anvi_id' => $anvi_id
        ]));
    }
    
    $projeto_id = $anvi['projeto_id'];
    
    // 2. Get project if it exists
    $projeto_encontrado = false;
    $projeto_data = null;
    $colunas_existentes = [];
    
    if ($projeto_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM projetos WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([$projeto_id, $tenant_id]);
        $projeto_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $projeto_encontrado = $projeto_data !== false;
        
        if ($projeto_encontrado) {
            $colunas_existentes = array_keys($projeto_data);
        }
    }
    
    // 3. Check what columns exist in projetos table
    $stmt = $pdo->query("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'projetos' AND TABLE_SCHEMA = DATABASE()
    ");
    $todas_colunas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status' => 'OK',
        'anvi' => [
            'id' => $anvi['id'],
            'numero' => $anvi['numero'],
            'projeto_id' => $projeto_id
        ],
        'projeto_vinculado' => [
            'projeto_id' => $projeto_id,
            'encontrado' => $projeto_encontrado,
            'dados' => $projeto_encontrado ? [
                'id' => $projeto_data['id'] ?? null,
                'tenant_id' => $projeto_data['tenant_id'] ?? null,
                'orcamento' => $projeto_data['orcamento'] ?? 'NÃO EXISTE',
                'progresso' => $projeto_data['progresso'] ?? 'NÃO EXISTE',
                'dados_financeiros_reais' => $projeto_data['dados_financeiros_reais'] ?? 'NÃO EXISTE'
            ] : null
        ],
        'colunas_da_tabela_projetos' => $todas_colunas,
        'colunas_esperadas_pelo_dashboard' => [
            'p.id',
            'p.orcamento',
            'p.progresso',
            'p.dados_financeiros_reais'
        ],
        'colunas_faltantes' => array_diff(
            ['id', 'orcamento', 'progresso', 'dados_financeiros_reais'],
            $todas_colunas
        )
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'erro' => $e->getMessage(),
        'arquivo' => $e->getFile(),
        'linha' => $e->getLine()
    ]);
}
?>
