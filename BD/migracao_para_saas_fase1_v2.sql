-- ======================================================
-- VIABIX SAAS - MIGRACAO INCREMENTAL FASE 1 V2
-- Objetivo: adicionar tenant_id e email à tabela usuarios
-- Versão revisada com verificação de tabelas opcionais
-- ======================================================

USE viabix_db;

-- ======================================================
-- HELPERS DE MIGRACAO
-- ======================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DELIMITER //
CREATE PROCEDURE sp_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- ======================================================
-- ADICIONAR COLUNAS ESSENCIAIS NA TABELA USUARIOS
-- ======================================================

-- Colunas para multi-tenancy
CALL sp_add_column_if_missing('usuarios', 'tenant_id', 'CHAR(36) NULL AFTER login');

-- Colunas para email
CALL sp_add_column_if_missing('usuarios', 'email', 'VARCHAR(160) NULL AFTER login');

-- Colunas de auditoria
CALL sp_add_column_if_missing('usuarios', 'email_verificado_em', 'DATETIME NULL AFTER ativo');
CALL sp_add_column_if_missing('usuarios', 'ultima_troca_senha_em', 'DATETIME NULL AFTER ultimo_acesso');

-- ======================================================
-- ADICIONAR COLUNAS EM TABELAS OPCIONAIS (SE EXISTIREM)
-- ======================================================

CALL sp_add_column_if_missing('anvis', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('anvis', 'projeto_id', 'INT NULL AFTER tenant_id');

CALL sp_add_column_if_missing('conflitos_edicao', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('logs_atividade', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('logs_atividade', 'entidade', 'VARCHAR(60) NULL AFTER acao');
CALL sp_add_column_if_missing('logs_atividade', 'entidade_id', 'VARCHAR(80) NULL AFTER entidade');
CALL sp_add_column_if_missing('anvis_historico', 'tenant_id', 'CHAR(36) NULL AFTER id');

CALL sp_add_column_if_missing('configuracoes', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('bancos_dados', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('bancos_dados', 'escopo', 'ENUM(\'global\', \'tenant\') NOT NULL DEFAULT \'global\' AFTER tenant_id');

CALL sp_add_column_if_missing('notificacoes', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('mudancas', 'tenant_id', 'CHAR(36) NULL AFTER id');

CALL sp_add_column_if_missing('lideres', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('projetos', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('projetos', 'criado_por', 'VARCHAR(36) NULL AFTER dados');
CALL sp_add_column_if_missing('projetos', 'atualizado_por', 'VARCHAR(36) NULL AFTER criado_por');

-- ======================================================
-- TABELAS DE TENANT E BILLING (já criadas por viabix_saas_billing_fix.sql)
-- ======================================================

-- Não precisamos recrear essas tabelas pois já existem

-- ======================================================
-- DADOS INICIAIS DE PLANO E TENANT LEGADO
-- ======================================================

INSERT IGNORE INTO plans (
    id, codigo, nome, descricao, preco_mensal, preco_anual,
    features_json, ativo, criado_em, atualizado_em
) VALUES (
    '00000000-0000-0000-0000-000000000099',
    'legacy',
    'Plano Legado',
    'Plano para usuários migrados do sistema legado',
    0.00,
    0.00,
    '{"users":999,"projects":999,"storage":"unlimited"}',
    1,
    NOW(),
    NOW()
);

INSERT IGNORE INTO tenants (
    id, slug, nome, descricao, status, plano_id, criado_em, atualizado_em
) VALUES (
    '00000000-0000-0000-0000-000000000001',
    'legacy-default',
    'Tenant Legado Padrão',
    'Tenant padrão para usuários legados sem tenant específico',
    'ativo',
    '00000000-0000-0000-0000-000000000099',
    NOW(),
    NOW()
);

INSERT IGNORE INTO subscriptions (
    id, tenant_id, plano_id, data_inicio, data_fim, status, renovacao_automatica, criado_em, atualizado_em
) VALUES (
    '00000000-0000-0000-0000-000000000098',
    '00000000-0000-0000-0000-000000000001',
    '00000000-0000-0000-0000-000000000099',
    NOW(),
    DATE_ADD(NOW(), INTERVAL 1 YEAR),
    'ativo',
    1,
    NOW(),
    NOW()
);

-- ======================================================
-- ATUALIZAR USUARIOS COM TENANT_ID E EMAIL
-- ======================================================

-- Preencher tenant_id para usuários legados
UPDATE usuarios
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Gerar emails baseado no login se não existe
UPDATE usuarios
SET email = CONCAT(login, '@legacy.local')
WHERE (email IS NULL OR email = '')
  AND login IS NOT NULL
  AND login <> '';

-- ======================================================
-- ATUALIZAR TABELAS OPCIONAIS COM TENANT_ID
-- ======================================================

-- Atualizar anvis se a tabela existir
UPDATE anvis
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar conflitos_edicao se a tabela existir
UPDATE conflitos_edicao
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar logs_atividade se a tabela existir
UPDATE logs_atividade
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar anvis_historico se a tabela existir
UPDATE anvis_historico
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar configuracoes se a tabela existir
UPDATE configuracoes
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar bancos_dados se a tabela existir
UPDATE bancos_dados
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar notificacoes se a tabela existir
UPDATE notificacoes
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar mudancas se a tabela existir
UPDATE mudancas
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar lideres se a tabela existir
UPDATE lideres
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- Atualizar projetos se a tabela existir
UPDATE projetos
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

-- ======================================================
-- CRIAR INDICES PARA PERFORMANCE
-- ======================================================

-- Indices em usuarios
ALTER TABLE usuarios ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE usuarios ADD INDEX idx_email (email);

-- ======================================================
-- LIMPEZA E FINALIZAÇÃO
-- ======================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;

-- Sucesso!
SELECT 'Migração Fase 1 completada com sucesso!' AS resultado;
