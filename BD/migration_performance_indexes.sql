-- ======================================================
-- VIABIX - Migration: Índices de Performance para Listagem
-- Complementa phase1_add_tenant_indexes.sql
-- Execute APÓS phase1_add_tenant_indexes.sql
-- ======================================================

USE viabix_db;

-- ======================================================
-- 1. ANVIS — índice composto para listagem paginada
--    SELECT * FROM anvis WHERE tenant_id=? ORDER BY data_atualizacao DESC LIMIT ? OFFSET ?
-- ======================================================
ALTER TABLE anvis
    ADD INDEX IF NOT EXISTS idx_tenant_updated (tenant_id, data_atualizacao);

-- ======================================================
-- 2. PROJETOS — índice composto para listagem paginada
--    SELECT * FROM projetos WHERE tenant_id=? ORDER BY id LIMIT ? OFFSET ?
--    (já coberto pelo PRIMARY KEY em id + idx_tenant_id; nenhum índice extra necessário)
-- ======================================================

-- ======================================================
-- 3. INVOICES — índice composto (tenant + status + due_date)
--    Usado em queries de faturas vencidas no dashboard
-- ======================================================
ALTER TABLE invoices
    ADD INDEX IF NOT EXISTS idx_tenant_status_due (tenant_id, status, due_date);

-- ======================================================
-- 4. SUBSCRIPTIONS — índice composto (tenant + status)
--    Usado em SELECT ... ORDER BY created_at DESC LIMIT 1
-- ======================================================
ALTER TABLE subscriptions
    ADD INDEX IF NOT EXISTS idx_tenant_status_created (tenant_id, status, created_at);

-- ======================================================
-- 5. AUDIT_LOGS — índice para tenant + created_at
--    Tabela de auditoria criada em migration_audit_logs.sql
-- ======================================================
ALTER TABLE audit_logs
    ADD INDEX IF NOT EXISTS idx_tenant_created (tenant_id, created_at);

-- ======================================================
-- 6. MUDANCAS (Controle de Projetos) — índice composto
--    SELECT * FROM mudancas WHERE tenant_id=? AND id>? ORDER BY id LIMIT 10 (SSE loop)
-- ======================================================
ALTER TABLE mudancas
    ADD INDEX IF NOT EXISTS idx_tenant_id_seq (tenant_id, id);

-- ======================================================
-- RECALCULAR ESTATÍSTICAS
-- ======================================================
ANALYZE TABLE anvis;
ANALYZE TABLE invoices;
ANALYZE TABLE subscriptions;
ANALYZE TABLE mudancas;

SELECT 'migration_performance_indexes.sql executado com sucesso!' AS resultado, NOW() AS ts;
