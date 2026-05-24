<?php
/**
 * API para retornar estatísticas públicas agregadas da plataforma.
 * Dados agregados (sem tenant_id) são seguros para exibição pública.
 */

require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // cache 5 min — dados não são sensíveis

try {
    $stats = [];

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
        $stats['anvis_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_total'] = 0;
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
        $stats['projetos_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['projetos_total'] = 0;
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $stats['usuarios_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['usuarios_total'] = 0;
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM lideres");
        $stats['lideres_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['lideres_total'] = 0;
    }

    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total FROM anvis
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_recentes'] = 0;
    }

    echo json_encode($stats);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar estatísticas']);
}
