<?php
/**
 * API para retornar estatísticas públicas do sistema
 * Não requer autenticação
 */

require_once 'config.php';

header('Content-Type: application/json');

try {
    $stats = [];
    
    // Contar ANVIs totais (com tratamento de erro)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
        $stats['anvis_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_total'] = 0;
    }
    
    // Contar Projetos totais (com tratamento de erro)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
        $stats['projetos_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['projetos_total'] = 0;
    }
    
    // Contar Usuários totais (com tratamento de erro)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        $stats['usuarios_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['usuarios_total'] = 0;
    }
    
    // Contar Líderes ativos (com tratamento de erro)
    try {
        $sql = 'SELECT COUNT(*) as total FROM lideres';
        if (viabixHasColumn('lideres', 'ativo')) {
            $sql .= ' WHERE ativo = 1';
        }
        $stmt = $pdo->query($sql);
        $stats['lideres_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['lideres_total'] = 0;
    }
    
    // ANVIs criadas nos últimos 30 dias (com tratamento de erro)
    try {
        $stmt = $pdo->query("
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_recentes'] = 0;
    }
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar estatísticas: ' . $e->getMessage()]);
}
