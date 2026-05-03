<?php
/**
 * =======================================================
 * API: PERMISSÕES DO USUÁRIO
 * =======================================================
 * 
 * Endpoint público para frontend buscar permissões
 * e renderizar UI condicionalmente (show/hide elements)
 * 
 * Uso:
 * GET /api/permissions
 *   - Retorna todas as permissões do usuário logado
 *   - Response: { "usuario_id": "...", "permissions": ["usuarios:view", ...] }
 * 
 * @version 1.0
 * @since 2026-05-03
 */

// ======================================================
// 1. SETUP (headers + config)
// ======================================================

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// ======================================================
// 2. AUTENTICAÇÃO (Problema #1)
// ======================================================
// Usar a nova função centralizada que valida tudo
$current_user = viabixRequireAuthentication(false);

// ======================================================
// 3. VALIDAR MÉTODO HTTP
// ======================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Apenas GET é permitido']);
    exit;
}

// ======================================================
// 4. PROCESSAR REQUISIÇÃO
// ======================================================

try {
    // Obter todas as permissões do usuário
    $permissions = viabixGetUserPermissions($current_user);
    
    // Construir resposta
    $response = [
        'success' => true,
        'user' => [
            'id' => $current_user['id'],
            'nome' => $current_user['nome'],
            'email' => $current_user['email'],
            'role' => $current_user['role'],
            'tenant_id' => $current_user['tenant_id'],
            'tenant_nome' => $current_user['tenant_nome'] ?? null,
        ],
        'permissions' => $permissions,
        // Helper para frontend verificar permissão
        'hasPermission' => function($resource, $action) use ($current_user) {
            return viabixHasPermission($resource, $action, $current_user);
        }
    ];
    
    // Registrar que alguém consultou suas permissões (auditoria)
    viabixLogAudit(
        $current_user['id'],
        $current_user['tenant_id'],
        'permissions.consulted',
        ['action' => 'user_checked_permissions'],
        null
    );
    
    // Retornar (removerf a closure da resposta)
    unset($response['hasPermission']);
    echo json_encode($response);
    
} catch (Exception $e) {
    viabix_sentry_exception($e, 'error');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Erro ao buscar permissões'
    ]);
    exit;
}

?>
