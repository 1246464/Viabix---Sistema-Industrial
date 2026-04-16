<?php
/**
 * API para retornar estatísticas do sistema integrado
 */

require_once 'config.php';

header('Content-Type: application/json');

// Iniciar sessão se não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

try {
    $stats = [];
    $tenantId = viabixCurrentTenantId();
    $tenantAwareAnvis = viabixHasColumn('anvis', 'tenant_id') && $tenantId;
    $tenantAwareProjetos = viabixHasColumn('projetos', 'tenant_id') && $tenantId;
    $tenantAwareUsuarios = viabixHasColumn('usuarios', 'tenant_id') && $tenantId;
    $tenantAwareLideres = viabixHasColumn('lideres', 'tenant_id') && $tenantId;
    $leadersHasActive = viabixHasColumn('lideres', 'ativo');
    $projectsHasStatusColumn = viabixHasColumn('projetos', 'status');
    
    // Contar ANVIs
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM anvis WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
    }
    $stats['anvis'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Projetos
    if ($tenantAwareProjetos) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM projetos WHERE tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
    }
    $stats['projetos'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Usuários ativos
    if ($tenantAwareUsuarios) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1 AND tenant_id = ?");
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE ativo = 1");
    }
    $stats['usuarios'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar Líderes ativos
    if ($tenantAwareLideres) {
        $sql = 'SELECT COUNT(*) as total FROM lideres WHERE tenant_id = ?';
        if ($leadersHasActive) {
            $sql .= ' AND ativo = 1';
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tenantId]);
    } else {
        $sql = 'SELECT COUNT(*) as total FROM lideres';
        if ($leadersHasActive) {
            $sql .= ' WHERE ativo = 1';
        }
        $stmt = $pdo->query($sql);
    }
    $stats['lideres'] = $stmt->fetch()['total'] ?? 0;
    
    // Contar vínculos ANVI-Projeto
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM anvis
             WHERE projeto_id IS NOT NULL AND tenant_id = ?"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE projeto_id IS NOT NULL
        ");
    }
    $stats['vinculos'] = $stmt->fetch()['total'] ?? 0;
    
    // ANVIs por status
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) as total
             FROM anvis
             WHERE tenant_id = ?
             GROUP BY status"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT status, COUNT(*) as total 
            FROM anvis 
            GROUP BY status
        ");
    }
    $stats['anvis_por_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Projetos por status
    if ($projectsHasStatusColumn) {
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare(
                "SELECT status, COUNT(*) as total
                 FROM projetos
                 WHERE tenant_id = ?
                 GROUP BY status"
            );
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query(
                "SELECT status, COUNT(*) as total
                 FROM projetos
                 GROUP BY status"
            );
        }
    } else {
        if ($tenantAwareProjetos) {
            $stmt = $pdo->prepare(
                "SELECT COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), ''), 'Pendente') as status, COUNT(*) as total
                 FROM projetos
                 WHERE tenant_id = ?
                 GROUP BY status"
            );
            $stmt->execute([$tenantId]);
        } else {
            $stmt = $pdo->query(
                "SELECT COALESCE(NULLIF(JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), ''), 'Pendente') as status, COUNT(*) as total
                 FROM projetos
                 GROUP BY status"
            );
        }
    }
    $stats['projetos_por_status'] = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // ANVIs criadas nos últimos 30 dias
    if ($tenantAwareAnvis) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM anvis
             WHERE tenant_id = ?
               AND data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM anvis 
            WHERE data_criacao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
    }
    $stats['anvis_recentes'] = $stmt->fetch()['total'] ?? 0;
    
    // Projetos atualizados nos últimos 7 dias
    if ($tenantAwareProjetos) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) as total
             FROM projetos
             WHERE tenant_id = ?
               AND updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        $stmt->execute([$tenantId]);
    } else {
        $stmt = $pdo->query(" 
            SELECT COUNT(*) as total 
            FROM projetos 
            WHERE updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
    }
    $stats['projetos_recentes'] = $stmt->fetch()['total'] ?? 0;
    
    echo json_encode($stats);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao carregar estatísticas: ' . $e->getMessage()]);
}
