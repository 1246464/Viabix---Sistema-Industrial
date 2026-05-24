-- =======================================================
-- MIGRAÇÃO: SISTEMA DE PERMISSÕES COMPLEXO
-- =======================================================
-- Esta migração adiciona suporte a permissões baseadas em
-- roles (papéis) e actions (ações) por recurso.
-- Modelo: 1 tenant = 1 usuário (simplificado)
-- =======================================================

USE viabix_db;

-- =======================================================
-- 1. TABELA: ROLES (Papéis padrão do sistema)
-- =======================================================
CREATE TABLE IF NOT EXISTS roles (
    id VARCHAR(36) PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_role_per_tenant (tenant_id, name),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant (tenant_id),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 2. TABELA: PERMISSIONS (Permissões disponíveis)
-- =======================================================
CREATE TABLE IF NOT EXISTS permissions (
    id VARCHAR(36) PRIMARY KEY,
    resource VARCHAR(100) NOT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_permission (resource, action),
    INDEX idx_resource (resource),
    INDEX idx_action (action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 3. TABELA: ROLE_PERMISSIONS (Associação role -> permission)
-- =======================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id VARCHAR(36) NOT NULL,
    permission_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 4. TABELA: USER_ROLES (Associação user -> role per tenant)
-- =======================================================
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    role_id VARCHAR(36) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 5. TABELA: USER_CUSTOM_PERMISSIONS (Override de permissões)
-- =======================================================
-- Permite dar/remover permissões individuais sem mudar role
CREATE TABLE IF NOT EXISTS user_custom_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    permission_id VARCHAR(36) NOT NULL,
    grant_type ENUM('grant', 'deny') DEFAULT 'grant',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY uk_user_permission (user_id, permission_id),
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_grant_type (grant_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 6. TABELA: AUDIT_LOGS (Auditoria de ações sensíveis)
-- =======================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id VARCHAR(36) NOT NULL,
    user_id VARCHAR(36),
    action VARCHAR(100) NOT NULL,
    details JSON,
    affected_resource_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tenant (tenant_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_resource (affected_resource_id),
    FULLTEXT INDEX idx_search (action, affected_resource_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 7. TABELA: SECURITY_EVENTS (Eventos de segurança)
-- =======================================================
CREATE TABLE IF NOT EXISTS security_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    user_id VARCHAR(36),
    ip_address VARCHAR(45),
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_event_type (event_type),
    INDEX idx_user (user_id),
    INDEX idx_ip (ip_address),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 8. INSERIR PERMISSÕES PADRÃO
-- =======================================================

-- USUARIOS
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'usuarios', 'view', 'Ver lista de usuários'),
(UUID(), 'usuarios', 'create', 'Criar novo usuário'),
(UUID(), 'usuarios', 'update', 'Editar dados de usuário'),
(UUID(), 'usuarios', 'delete', 'Deletar usuário'),
(UUID(), 'usuarios', 'change_password', 'Mudar senha de outro usuário');

-- ANVIS
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'anvis', 'view', 'Ver ANVIs'),
(UUID(), 'anvis', 'create', 'Criar ANVI'),
(UUID(), 'anvis', 'update', 'Editar ANVI'),
(UUID(), 'anvis', 'delete', 'Deletar ANVI'),
(UUID(), 'anvis', 'export', 'Exportar ANVI');

-- PROJETOS
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'projetos', 'view', 'Ver projetos'),
(UUID(), 'projetos', 'create', 'Criar projeto'),
(UUID(), 'projetos', 'update', 'Editar projeto'),
(UUID(), 'projetos', 'delete', 'Deletar projeto');

-- RELATORIOS
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'relatorios', 'view', 'Ver relatórios'),
(UUID(), 'relatorios', 'create', 'Criar relatório'),
(UUID(), 'relatorios', 'export', 'Exportar relatório');

-- CONFIGURACOES
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'configuracoes', 'view', 'Ver configurações'),
(UUID(), 'configuracoes', 'update', 'Editar configurações'),
(UUID(), 'configuracoes', 'backup', 'Executar backup');

-- ADMIN SAAS
INSERT IGNORE INTO permissions (id, resource, action, description) VALUES
(UUID(), 'admin_saas', 'view_tenants', 'Ver painel SaaS'),
(UUID(), 'admin_saas', 'change_plan', 'Mudar plano de tenant'),
(UUID(), 'admin_saas', 'suspend_tenant', 'Suspender tenant'),
(UUID(), 'admin_saas', 'view_webhooks', 'Ver webhooks'),
(UUID(), 'admin_saas', 'reprocess_webhook', 'Reprocessar webhook');

-- =======================================================
-- 9. CRIAR ROLES PADRÃO (Executar uma vez por tenant novo)
-- =======================================================
-- NOTA: Execute este script para cada novo tenant!
-- Replace {TENANT_ID} com o ID real do tenant

-- ROLE: ADMIN (todas as permissões)
-- INSERT INTO roles (id, tenant_id, name, description, is_default) VALUES
-- (UUID(), '{TENANT_ID}', 'admin', 'Administrador - Acesso completo', TRUE);

-- ROLE: EDITOR (pode criar/editar, não pode deletar usuários)
-- INSERT INTO roles (id, tenant_id, name, description, is_default) VALUES
-- (UUID(), '{TENANT_ID}', 'editor', 'Editor - Pode criar e editar ANVIs e projetos', FALSE);

-- ROLE: VISUALIZADOR (apenas view)
-- INSERT INTO roles (id, tenant_id, name, description, is_default) VALUES
-- (UUID(), '{TENANT_ID}', 'visualizador', 'Visualizador - Apenas leitura', FALSE);

-- ROLE: VISITANTE (minimal access)
-- INSERT INTO roles (id, tenant_id, name, description, is_default) VALUES
-- (UUID(), '{TENANT_ID}', 'visitante', 'Visitante - Acesso limitado', FALSE);

-- =======================================================
-- 10. ASSOCIAR PERMISSÕES AOS ROLES
-- =======================================================
-- NOTA: Execute isto APÓS criar os roles acima!

-- ADMIN: todas as permissões
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT r.id, p.id FROM roles r, permissions p 
-- WHERE r.tenant_id = '{TENANT_ID}' AND r.name = 'admin';

-- EDITOR: usuários:view + anvis:*, projetos:*, relatorios:*
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT r.id, p.id FROM roles r, permissions p 
-- WHERE r.tenant_id = '{TENANT_ID}' AND r.name = 'editor'
-- AND (
--     (p.resource = 'usuarios' AND p.action = 'view') OR
--     (p.resource IN ('anvis', 'projetos', 'relatorios'))
-- );

-- VISUALIZADOR: view apenas
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT r.id, p.id FROM roles r, permissions p 
-- WHERE r.tenant_id = '{TENANT_ID}' AND r.name = 'visualizador'
-- AND p.action = 'view';

-- VISITANTE: minimal
-- INSERT INTO role_permissions (role_id, permission_id)
-- SELECT r.id, p.id FROM roles r, permissions p 
-- WHERE r.tenant_id = '{TENANT_ID}' AND r.name = 'visitante'
-- AND (p.resource IN ('anvis', 'projetos') AND p.action = 'view');

-- =======================================================
-- 11. ADICIONAR COLUNA user_role À TABELA usuarios
-- =======================================================
-- Se não existir, adiciona coluna de role padrão
ALTER TABLE usuarios ADD COLUMN IF NOT EXISTS role VARCHAR(50) DEFAULT 'visualizador'
AFTER nivel;

-- Copiar nivel para role se vazio (compatibilidade)
UPDATE usuarios SET role = CASE 
    WHEN nivel = 'admin' THEN 'admin'
    WHEN nivel = 'usuario' THEN 'editor'
    WHEN nivel = 'visitante' THEN 'visitante'
    ELSE 'visualizador'
END WHERE role IS NULL OR role = '';

-- =======================================================
-- 12. VIEW ÚTIL: user_permissions (denormalized view)
-- =======================================================
CREATE OR REPLACE VIEW user_permissions AS
SELECT 
    ur.user_id,
    p.resource,
    p.action,
    'role' AS permission_source
FROM user_roles ur
JOIN role_permissions rp ON rp.role_id = ur.role_id
JOIN permissions p ON p.id = rp.permission_id
UNION ALL
SELECT 
    ucp.user_id,
    p.resource,
    p.action,
    'custom' AS permission_source
FROM user_custom_permissions ucp
JOIN permissions p ON p.id = ucp.permission_id
WHERE ucp.grant_type = 'grant'
UNION ALL
SELECT 
    u.id,
    p.resource,
    p.action,
    'admin' AS permission_source
FROM usuarios u, permissions p
WHERE u.role = 'admin';

-- =======================================================
-- FIM DA MIGRAÇÃO
-- =======================================================
