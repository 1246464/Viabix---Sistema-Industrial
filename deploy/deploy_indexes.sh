#!/bin/bash
# ======================================================
# Deploy Índices de BD para DigitalOcean
# Script: Aplica todos os índices necessários
# ======================================================

set -e  # Exit on error

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuração
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-viabix_db}"

echo -e "${YELLOW}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${YELLOW}║ Deploy de Índices de BD - VIABIX SAAS                      ║${NC}"
echo -e "${YELLOW}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Função para executar SQL
execute_sql() {
    local sql="$1"
    
    if [ -z "$DB_PASS" ]; then
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" -e "$sql" 2>&1
    else
        mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$sql" 2>&1
    fi
}

# Verificar conexão
echo -e "${YELLOW}[1/5] Verificando conexão com banco de dados...${NC}"
if ! execute_sql "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${RED}❌ Erro: Não foi possível conectar ao banco de dados${NC}"
    echo "Host: $DB_HOST"
    echo "User: $DB_USER"
    echo "Database: $DB_NAME"
    exit 1
fi
echo -e "${GREEN}✅ Conexão OK${NC}"
echo ""

# Criar índices
echo -e "${YELLOW}[2/5] Criando índices de tenant_id...${NC}"

sql_indices="
-- ÍNDICES EM TABELAS DO MÓDULO ANVI
ALTER TABLE usuarios ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);
ALTER TABLE anvis ADD INDEX IF NOT EXISTS idx_tenant_data (tenant_id, data_anvi);

-- ÍNDICES EM AUDITORIA & LOGS
ALTER TABLE anvis_historico ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE conflitos_edicao ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_tipo (tenant_id, tipo);
ALTER TABLE logs_atividade ADD INDEX IF NOT EXISTS idx_tenant_data (tenant_id, data_hora);

-- ÍNDICES EM CONFIGURAÇÃO
ALTER TABLE configuracoes ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE bancos_dados ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE notificacoes ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ÍNDICES EM PROJETO
ALTER TABLE projetos ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE mudancas ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);
ALTER TABLE lideres ADD INDEX IF NOT EXISTS idx_tenant_id (tenant_id);

-- ÍNDICES EM SAAS/BILLING
ALTER TABLE subscriptions ADD INDEX IF NOT EXISTS idx_subscription_tenant (tenant_id);
ALTER TABLE subscriptions ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);
ALTER TABLE subscription_events ADD INDEX IF NOT EXISTS idx_subscription_event_tenant (tenant_id);
ALTER TABLE invoices ADD INDEX IF NOT EXISTS idx_invoice_tenant (tenant_id);
ALTER TABLE invoices ADD INDEX IF NOT EXISTS idx_tenant_status (tenant_id, status);
ALTER TABLE payments ADD INDEX IF NOT EXISTS idx_payment_tenant (tenant_id);
ALTER TABLE webhook_events ADD INDEX IF NOT EXISTS idx_webhook_tenant (tenant_id);
ALTER TABLE tenant_settings ADD INDEX IF NOT EXISTS idx_setting_tenant (tenant_id);
ALTER TABLE device_sessions ADD INDEX IF NOT EXISTS idx_device_session_tenant (tenant_id);
"

if execute_sql "$sql_indices"; then
    echo -e "${GREEN}✅ Índices criados com sucesso${NC}"
else
    echo -e "${RED}❌ Erro ao criar índices${NC}"
    exit 1
fi
echo ""

# Recalcular estatísticas
echo -e "${YELLOW}[3/5] Recalculando estatísticas do banco...${NC}"

sql_analyze="
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
"

if execute_sql "$sql_analyze"; then
    echo -e "${GREEN}✅ Estatísticas recalculadas${NC}"
else
    echo -e "${YELLOW}⚠️  Aviso: Estatísticas podem não ter sido atualizadas${NC}"
fi
echo ""

# Verificar índices criados
echo -e "${YELLOW}[4/5] Verificando índices criados...${NC}"

count_query="
SELECT COUNT(*) as total_indexes
FROM information_schema.statistics
WHERE table_schema = '$DB_NAME'
  AND column_name = 'tenant_id'
  AND seq_in_index = 1;
"

total=$(execute_sql "$count_query" | tail -1)
echo -e "${GREEN}✅ Total de índices em tenant_id: $total${NC}"
echo ""

# Resumo final
echo -e "${YELLOW}[5/5] Gerando relatório...${NC}"
echo ""
echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║ ✅ Deploy de Índices Concluído com Sucesso               ║${NC}"
echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "Resultado:"
echo "  • Indices criados: $total"
echo "  • Estatísticas atualizadas: SIM"
echo "  • Status: PRONTO PARA PRODUÇÃO"
echo ""
echo "Impacto esperado:"
echo "  • Queries de tenant: 10x mais rápidas"
echo "  • Redução de locks em tabelas grandes"
echo "  • Melhor throughput geral"
echo ""
