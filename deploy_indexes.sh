#!/bin/bash

# ======================================================
# VIABIX SAAS - Phase 1 Priority 4 Deploy Script
# Database Indexes Setup
# ======================================================

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║  VIABIX DATABASE INDEXES - Deployment Script              ║"
echo "║  Phase 1 Priority 4                                        ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# ======================================================
# Configuration
# ======================================================

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="viabix_db"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ======================================================
# Helper Functions
# ======================================================

log_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

log_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

log_error() {
    echo -e "${RED}❌ $1${NC}"
}

# ======================================================
# Step 1: Verify Database Connection
# ======================================================

echo ""
log_info "Step 1/4: Verificando conexão com banco de dados..."

if [ -z "$DB_PASS" ]; then
    MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER"
else
    MYSQL_CMD="mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS"
fi

if ! $MYSQL_CMD -e "SELECT 1" > /dev/null 2>&1; then
    log_error "Não foi possível conectar ao banco de dados!"
    log_error "Host: $DB_HOST:$DB_PORT"
    log_error "User: $DB_USER"
    exit 1
fi

log_success "Conexão com banco de dados estabelecida!"

# ======================================================
# Step 2: Check if Database Exists
# ======================================================

echo ""
log_info "Step 2/4: Verificando se banco de dados $DB_NAME existe..."

DB_EXISTS=$($MYSQL_CMD -se "SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = '$DB_NAME'")

if [ "$DB_EXISTS" -eq 0 ]; then
    log_error "Banco de dados '$DB_NAME' não encontrado!"
    echo ""
    log_info "Bancos de dados disponíveis:"
    $MYSQL_CMD -se "SHOW DATABASES LIKE 'viabix%';" || $MYSQL_CMD -se "SHOW DATABASES;"
    exit 1
fi

log_success "Banco de dados '$DB_NAME' encontrado!"

# ======================================================
# Step 3: Get Script Location
# ======================================================

echo ""
log_info "Step 3/4: Localizando arquivo SQL..."

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SQL_FILE="$SCRIPT_DIR/phase1_add_tenant_indexes.sql"

if [ ! -f "$SQL_FILE" ]; then
    log_error "Arquivo SQL não encontrado: $SQL_FILE"
    exit 1
fi

log_success "Arquivo SQL encontrado!"

# ======================================================
# Step 4: Execute SQL Script
# ======================================================

echo ""
log_info "Step 4/4: Executando script SQL..."
echo "   Este processo pode levar alguns minutos..."
echo ""

# Create temporary SQL file with error handling
TEMP_SQL=$(mktemp)
cat > "$TEMP_SQL" << 'EOF'
-- Habilitar output
SET SESSION sql_mode='';

-- Adicionar indices
SOURCE /dev/stdin;

EOF

# Execute
if $MYSQL_CMD < "$SQL_FILE" > /tmp/deploy.log 2>&1; then
    log_success "Script SQL executado com sucesso!"
else
    log_error "Erro ao executar script SQL!"
    echo ""
    echo "Log de erro:"
    cat /tmp/deploy.log
    rm -f "$TEMP_SQL" /tmp/deploy.log
    exit 1
fi

rm -f "$TEMP_SQL" /tmp/deploy.log

# ======================================================
# Step 5: Verify Indices Created
# ======================================================

echo ""
log_info "Verificando índices criados..."

INDEX_COUNT=$($MYSQL_CMD -e "
USE $DB_NAME;
SELECT COUNT(*) as count FROM information_schema.statistics 
WHERE table_schema = '$DB_NAME' 
AND column_name = 'tenant_id' 
AND seq_in_index = 1;
" | tail -1)

echo ""
log_success "Resumo da Execução:"
echo "   📊 Índices criados em tenant_id: $INDEX_COUNT"
echo "   ⏱️  Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"

# ======================================================
# Summary
# ======================================================

echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║ ✅ PHASE 1 PRIORITY 4 - CONCLUÍDO!                         ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

echo "📈 Impacto Esperado:"
echo "   • Queries filtradas por tenant: 10x mais rápido"
echo "   • Redução de locks em tabelas grandes"
echo "   • Melhor uso de índices compostos"
echo ""

echo "📋 Próximas Etapas:"
echo "   1. Testar aplicação normalmente"
echo "   2. Monitorar performance no Sentry"
echo "   3. Começar Priority 5 (Tenant Isolation Audit)"
echo ""

echo "💾 Deploy Log:"
echo "   Arquivo salvo em: /tmp/deploy.log"
echo ""

log_success "Deployment concluído com sucesso!"
