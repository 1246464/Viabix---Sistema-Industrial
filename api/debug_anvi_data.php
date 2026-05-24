<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

try {
    $anvi_id = $_GET['anvi_id'] ?? 'ANVI-20260509233923-001';
    $tenant_id = $_SESSION['tenant_id'] ?? 'admin';
    
    // 1. Check if ANVI exists and show all its data
    $stmt = $pdo->prepare("
        SELECT * FROM anvis WHERE id = ? AND tenant_id = ?
    ");
    $stmt->execute([$anvi_id, $tenant_id]);
    $anvi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$anvi) {
        http_response_code(404);
        exit(json_encode([
            'erro' => 'ANVI não encontrado',
            'anvi_id_procurado' => $anvi_id,
            'tenant_id' => $tenant_id
        ]));
    }
    
    // Decode JSON fields
    $dados = json_decode($anvi['dados'] ?? '{}', true);
    $dados_financeiros = json_decode($anvi['dados_financeiros'] ?? '{}', true);
    
    echo json_encode([
        'status' => 'OK',
        'anvi_encontrado' => true,
        'anvi_id' => $anvi['id'],
        'tenant_id' => $anvi['tenant_id'],
        'anvi_data' => [
            'numero' => $anvi['numero'],
            'revisao' => $anvi['revisao'],
            'cliente' => $anvi['cliente'],
            'projeto' => $anvi['projeto'],
            'produto' => $anvi['produto'],
            'status' => $anvi['status'],
            'projeto_id' => $anvi['projeto_id'],
            'data_anvi' => $anvi['data_anvi'],
            'data_criacao' => $anvi['data_criacao'],
        ],
        'anvi_dados_json' => $dados,
        'anvi_dados_financeiros' => $dados_financeiros,
        'projeto_id_vinculado' => $anvi['projeto_id']
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
