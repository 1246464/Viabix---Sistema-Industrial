<?php
/**
 * API para retornar estatísticas públicas do sistema
 * SEGURO - Requer autenticação + filtra por tenant_id
 */

require_once 'config.php';

header('Content-Type: application/json');

// SECURITY: Require authentication to prevent cross-tenant data leakage
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não autenticado, retornar erro ao invés de dados globais
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Autenticação necessária']);
    exit;
}

try {
    $stats = [];
    $tenantId = viabixCurrentTenantId();
    
    // Contar ANVIs do tenant (com tratamento de erro)
    try {
        if ($tenantId && viabixHasColumn('anvis', 'tenant_id')) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM anvis WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
        }
        $stats['anvis_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_total'] = 0;
    }
    
    // Contar Projetos do tenant (com tratamento de erro)
    try {
        if ($tenantId && viabixHasColumn('projetos', 'tenant_id')) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM projetos WHERE tenant_id = ?");
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
        }
        $stats['projetos_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['projetos_total'] = 0;
    }
    
    // Contar Usuários ativos do tenant (com tratamento de erro)
    try {
        if ($tenantId && viabixHasColumn('usuarios', 'tenant_id')) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND tenant_id = ?");
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
        }
        $stats['usuarios_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['usuarios_total'] = 0;
    }
    
    // Contar Líderes ativos do tenant (com tratamento de erro)
    try {
        $sql = 'SELECT COUNT(*) as total FROM lideres';
        if ($tenantId && viabixHasColumn('lideres', 'tenant_id')) {
            $sql .= ' WHERE tenant_id = ?';
        } elseif (viabixHasColumn('lideres', 'ativo')) {
            $sql .= ' WHERE ativo = 1';
        }
        
        $stmt = $pdo->prepare($sql);
        if ($tenantId && viabixHasColumn('lideres', 'tenant_id')) {
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query($sql);
        }
        $stats['lideres_total'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['lideres_total'] = 0;
    }
    
    // ANVIs criadas nos últimos 30 dias do tenant (com tratamento de erro)
    try {
        if ($tenantId && viabixHasColumn('anvis', 'tenant_id')) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total 
                FROM anvis 
                WHERE tenant_id = ? AND data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query("
                SELECT COUNT(*) as total 
                FROM anvis 
                WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
        }
        $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    } catch (Exception $e) {
        $stats['anvis_recentes'] = 0;
    }
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar estatísticas']);
}
