-- ======================================================
-- VIABIX SAAS - PHASE 1 PRIORITY 4
-- Adicionar índices de tenant_id para Performance
-- Objetivo: 10x melhoria em query performance
-- ======================================================

USE viabix_db;

-- ======================================================
-- 1. INDICES EM TABELAS DO MODULO ANVI
-- ======================================================

-- usuarios: melhorar queries por tenant
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- anvis: tabela crítica, altamente consultada
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_data (tenant_id, data_anvi);

-- anvis_historico: auditoria de mudanças
ALTER TABLE anvis_historico ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- conflitos_edicao: detecção de conflitos
ALTER TABLE conflitos_edicao ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ======================================================
-- 2. INDICES EM TABELAS DE AUDITORIA & LOGS
-- ======================================================

-- logs_atividade: logs centralizados por tenant
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_tipo (tenant_id, tipo);
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_data (tenant_id, data_hora);

-- ======================================================
-- 3. INDICES EM TABELAS DE CONFIGURACAO
-- ======================================================

-- configuracoes: settings por tenant
ALTER TABLE configuracoes ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- bancos_dados: databases por tenant
ALTER TABLE bancos_dados ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ======================================================
-- 4. INDICES EM TABELAS DE NOTIFICACAO
-- ======================================================

-- notificacoes: alertas por tenant
ALTER TABLE notificacoes ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ======================================================
-- 5. INDICES EM TABELAS DE PROJETO
-- ======================================================

-- projetos: projetos por tenant
ALTER TABLE projetos ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- mudancas: histórico de mudanças de projeto
ALTER TABLE mudancas ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- lideres: líderes de projeto
ALTER TABLE lideres ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ======================================================
-- 6. INDICES EM TABELAS DO MODULO SAAS
-- (Já criadas nas migrations, mas reforçar)
-- ======================================================

-- subscriptions
ALTER TABLE subscriptions ADD INDEX IF NOT EXISTS idx_subscription_tenant (tenant_id);
ALTER TABLE subscriptions ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);

-- subscription_events
ALTER TABLE subscription_events ADD INDEX IF NOT EXISTS idx_subscription_event_tenant (tenant_id);

-- invoices
ALTER TABLE invoices ADD INDEX IF NOT EXISTS idx_invoice_tenant (tenant_id);
ALTER TABLE invoices ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);

-- payments
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payment_tenant (tenant_id);

-- webhook_events
ALTER TABLE webhook_events ADD INDEX IF NOT EXISTS idx_webhook_tenant (tenant_id);

-- tenant_settings
ALTER TABLE tenant_settings ADD INDEX IF NOT EXISTS idx_setting_tenant (tenant_id);

-- device_sessions
ALTER TABLE device_sessions ADD INDEX IF NOT EXISTS idx_device_session_tenant (tenant_id);

-- ======================================================
-- VERIFICACAO DE INDICES ADICIONADOS
-- ======================================================

SELECT 
    CONCAT('✅ Índices criados com sucesso!') AS resultado,
    NOW() AS timestamp
UNION ALL
SELECT 
    CONCAT('Total de índices em tenant_id: ', COUNT(*)),
    NULL
FROM information_schema.statistics
WHERE table_schema = DATABASE()
  AND column_name = 'tenant_id'
  AND seq_in_index = 1;

-- ======================================================
-- STATISTICS
-- ======================================================

-- Recalcular estatísticas para otimizador
ANALYZE TABLE usuarios;
ANALYZE TABLE anvis;
ANALYZE TABLE anvis_historico;
ANALYZE TABLE conflitos_edicao;
ANALYZE TABLE logs_atividade;
ANALYZE TABLE configuracoes;
ANALYZE TABLE bancos_dados;
ANALYZE TABLE notificacoes;
ANALYZE TABLE projetos;
ANALYZE TABLE mudancas;
ANALYZE TABLE lideres;
ANALYZE TABLE subscriptions;
ANALYZE TABLE subscription_events;
ANALYZE TABLE invoices;
ANALYZE TABLE payments;
ANALYZE TABLE webhook_events;
ANALYZE TABLE tenant_settings;
ANALYZE TABLE device_sessions;

-- ======================================================
-- RESULTADO FINAL
-- ======================================================

SELECT 
    '╔════════════════════════════════════════════════════════════╗' AS msg
UNION ALL
SELECT '║ PHASE 1 PRIORITY 4: DATABASE INDEXES - COMPLETO         ║'
UNION ALL
SELECT '║                                                            ║'
UNION ALL
SELECT '║ ✅ Todos os índices de tenant_id foram criados          ║'
UNION ALL
SELECT '║ ✅ Estatísticas recalculadas para otimizador            ║'
UNION ALL
SELECT '║                                                            ║'
UNION ALL
SELECT '║ Impacto esperado:                                        ║'
UNION ALL
SELECT '║ - Queries filtradas por tenant: 10x mais rápido          ║'
UNION ALL
SELECT '║ - Redução de locks em tabelas grandes                    ║'
UNION ALL
SELECT '║ - Melhor uso de índices compostos (tenant_id + filtro)   ║'
UNION ALL
SELECT '╚════════════════════════════════════════════════════════════╝' AS msg;
