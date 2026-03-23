<?php
/**
 * auth.php - Autenticação Unificada Viabix
 * Compatível com ANVI e Controle de Projetos
 */

require_once __DIR__ . '/../api/config.php';

// Iniciar sessão com nome padronizado Viabix
if (session_status() === PHP_SESSION_NONE) {
    session_name('viabix_session');
    session_start();
}

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// Função para verificar se usuário tem permissão
function temPermissao($nivel_requerido) {
    $niveis = ['visitante' => 0, 'visualizador' => 1, 'usuario' => 2, 'lider' => 2, 'admin' => 3];
    
    // Compatibilidade: usar user_level ou nivel
    $nivel_usuario = $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? 'visitante';
    
    $nivel_usuario_num = $niveis[$nivel_usuario] ?? 0;
    $nivel_requerido_num = $niveis[$nivel_requerido] ?? 99;
    
    return $nivel_usuario_num >= $nivel_requerido_num;
}

// Função para verificar se é admin
function isAdmin() {
    $nivel = $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? '';
    return $nivel === 'admin';
}

// Função para verificar se é líder ou admin
function isLider() {
    $nivel = $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? '';
    return in_array($nivel, ['lider', 'usuario', 'admin']);
}

// Função para verificar se é apenas visualizador
function isVisualizador() {
    $nivel = $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? '';
    return in_array($nivel, ['visualizador', 'visitante']);
}

function tenantAtivo() {
    $tenantStatus = $_SESSION['tenant_status'] ?? null;
    $subscriptionStatus = $_SESSION['subscription_status'] ?? null;

    $tenantOk = !$tenantStatus || in_array($tenantStatus, ['trial', 'ativo'], true);
    $subscriptionOk = !$subscriptionStatus || in_array($subscriptionStatus, ['trial', 'ativa'], true);

    return $tenantOk && $subscriptionOk;
}

function featureHabilitada($feature) {
    $features = $_SESSION['features'] ?? [];
    return (bool) ($features[$feature] ?? false);
}

function requireFeature($feature) {
    if (!featureHabilitada($feature)) {
        http_response_code(403);
        exit('Modulo indisponivel para o plano atual.');
    }
}

// Retornar dados do usuário (compatível com ambos sistemas)
function getUsuario() {
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['user_login'] ?? $_SESSION['username'] ?? '',
        'nome' => $_SESSION['user_nome'] ?? $_SESSION['nome'] ?? '',
        'nivel' => $_SESSION['user_level'] ?? $_SESSION['nivel'] ?? 'visitante',
        'nivel_original' => $_SESSION['user_role_raw'] ?? $_SESSION['user_level'] ?? 'visitante',
        'tenant_id' => $_SESSION['tenant_id'] ?? null,
        'tenant_nome' => $_SESSION['tenant_nome'] ?? null,
        'tenant_slug' => $_SESSION['tenant_slug'] ?? null,
        'tenant_status' => $_SESSION['tenant_status'] ?? null,
        'subscription_status' => $_SESSION['subscription_status'] ?? null,
        'plan_code' => $_SESSION['plan_code'] ?? null,
        'plan_name' => $_SESSION['plan_name'] ?? null,
        'features' => $_SESSION['features'] ?? []
    ];
}

if (!tenantAtivo()) {
    viabixClearAuthenticatedSession();
    header('Location: ../login.html');
    exit;
}
?>
