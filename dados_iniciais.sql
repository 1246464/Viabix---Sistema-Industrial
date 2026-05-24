USE viabix_db;

INSERT IGNORE INTO tenants (id, slug, nome_fantasia, status) 
VALUES ('admin', 'admin-tenant', 'Admin Tenant', 'ativo');

INSERT IGNORE INTO plans (id, codigo, nome, preco_mensal, status) 
VALUES ('plan-pro', 'pro', 'Pro', 99, 'ativo');

INSERT IGNORE INTO subscriptions (id, tenant_id, plan_id, status, gateway, valor_contratado) 
VALUES ('sub-001', 'admin', 'plan-pro', 'ativa', 'stripe', 99);

INSERT IGNORE INTO usuarios (id, tenant_id, login, email, senha, nome, nivel, ativo)
VALUES ('user-admin', 'admin', 'admin', 'admin@test.com', SHA2('123456', 256), 'Admin User', 'admin', 1);

SELECT COUNT(*) as total_tenants FROM tenants;
SELECT COUNT(*) as total_anvis FROM anvis;
SELECT COUNT(*) as total_usuarios FROM usuarios;
