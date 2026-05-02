-- ======================================================
-- VIABIX SAAS - MIGRACAO INCREMENTAL FASE 1 V3
-- Objetivo: adicionar tenant_id e email à tabela usuarios
-- Versão minimalista - foca apenas no essencial
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
CALL sp_add_column_if_missing('usuarios', 'email_verificado_em', 'DATETIME NULL');
CALL sp_add_column_if_missing('usuarios', 'ultima_troca_senha_em', 'DATETIME NULL');

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
-- CRIAR INDICES PARA PERFORMANCE
-- ======================================================

-- Indices em usuarios
ALTER TABLE usuarios ADD INDEX idx_tenant_id (tenant_id);
ALTER TABLE usuarios ADD INDEX idx_email (email);

-- ======================================================
-- LIMPEZA
-- ======================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;

-- Sucesso!
SELECT 'Migração Fase 1 completada com sucesso!' AS resultado;
