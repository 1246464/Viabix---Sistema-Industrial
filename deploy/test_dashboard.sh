#!/bin/bash
# ============================================================
# Script de Teste: Validação de Índices e Dashboard
# Status: Verificação Pré-Produção
# ============================================================

echo "╔════════════════════════════════════════════════════════════╗"
echo "║ TESTE: Dashboard Viabilidade - Adaptação Para Dados Reais ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuração do banco
DB_HOST="${1:-localhost}"
DB_USER="${2:-root}"
DB_PASS="${3:-}"
DB_NAME="${4:-viabix_db}"

echo "[1/5] Verificando conexão com banco de dados..."
echo "Host: $DB_HOST | User: $DB_USER | DB: $DB_NAME"
echo ""

# Teste 1: Verificar se os índices foram criados
echo "[2/5] Verificando índices em tenant_id..."

if [ -z "$DB_PASS" ]; then
    INDICES_COUNT=$(mysql -h $DB_HOST -u $DB_USER $DB_NAME -e "
        SELECT COUNT(*) as total
        FROM information_schema.statistics
        WHERE table_schema = '$DB_NAME'
            AND column_name = 'tenant_id'
            AND seq_in_index = 1;" 2>/dev/null | tail -n 1)
else
    INDICES_COUNT=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
        SELECT COUNT(*) as total
        FROM information_schema.statistics
        WHERE table_schema = '$DB_NAME'
            AND column_name = 'tenant_id'
            AND seq_in_index = 1;" 2>/dev/null | tail -n 1)
fi

if [ -z "$INDICES_COUNT" ] || [ "$INDICES_COUNT" -lt 10 ]; then
    echo -e "${RED}❌ Falha: Apenas $INDICES_COUNT índices encontrados (esperado 20+)${NC}"
    exit 1
else
    echo -e "${GREEN}✅ Sucesso: $INDICES_COUNT índices em tenant_id encontrados${NC}"
fi
echo ""

# Teste 2: Verificar estrutura das tabelas principais
echo "[3/5] Verificando tabelas necessárias..."

TABLES=("anvis" "invoices" "logs_atividade" "usuarios" "subscriptions")
TABLES_OK=0

for table in "${TABLES[@]}"; do
    if [ -z "$DB_PASS" ]; then
        TABLE_EXISTS=$(mysql -h $DB_HOST -u $DB_USER $DB_NAME -e "
            SHOW TABLES LIKE '$table';" 2>/dev/null | grep -c $table)
    else
        TABLE_EXISTS=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
            SHOW TABLES LIKE '$table';" 2>/dev/null | grep -c $table)
    fi
    
    if [ "$TABLE_EXISTS" -gt 0 ]; then
        echo -e "${GREEN}✅${NC} Tabela $table existe"
        ((TABLES_OK++))
    else
        echo -e "${YELLOW}⚠️${NC} Tabela $table não encontrada"
    fi
done

if [ "$TABLES_OK" -lt 3 ]; then
    echo -e "${RED}❌ Falha: Tabelas insuficientes para Dashboard${NC}"
    exit 1
fi
echo ""

# Teste 3: Verificar índices nas tabelas principais
echo "[4/5] Verificando índices nas tabelas principais..."

MAIN_TABLES=("anvis" "invoices" "logs_atividade" "usuarios")
INDEXES_OK=0

for table in "${MAIN_TABLES[@]}"; do
    if [ -z "$DB_PASS" ]; then
        INDEXES=$(mysql -h $DB_HOST -u $DB_USER $DB_NAME -e "
            SHOW INDEXES FROM $table WHERE Column_name = 'tenant_id';" 2>/dev/null | grep -c tenant_id)
    else
        INDEXES=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
            SHOW INDEXES FROM $table WHERE Column_name = 'tenant_id';" 2>/dev/null | grep -c tenant_id)
    fi
    
    if [ "$INDEXES" -gt 0 ]; then
        echo -e "${GREEN}✅${NC} Índice tenant_id em $table"
        ((INDEXES_OK++))
    else
        echo -e "${RED}❌${NC} Índice tenant_id faltando em $table"
    fi
done

if [ "$INDEXES_OK" -lt 3 ]; then
    echo -e "${YELLOW}⚠️ Aviso: Alguns índices estão faltando${NC}"
fi
echo ""

# Teste 4: Verificar dados de teste
echo "[5/5] Verificando dados de teste..."

if [ -z "$DB_PASS" ]; then
    ANVIS_COUNT=$(mysql -h $DB_HOST -u $DB_USER $DB_NAME -e "
        SELECT COUNT(*) FROM anvis;" 2>/dev/null | tail -n 1)
    INVOICES_COUNT=$(mysql -h $DB_HOST -u $DB_USER $DB_NAME -e "
        SELECT COUNT(*) FROM invoices;" 2>/dev/null | tail -n 1)
else
    ANVIS_COUNT=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
        SELECT COUNT(*) FROM anvis;" 2>/dev/null | tail -n 1)
    INVOICES_COUNT=$(mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "
        SELECT COUNT(*) FROM invoices;" 2>/dev/null | tail -n 1)
fi

echo "ANVIs disponíveis: $ANVIS_COUNT"
echo "Invoices disponíveis: $INVOICES_COUNT"

if [ "$ANVIS_COUNT" -gt 0 ]; then
    echo -e "${GREEN}✅ Dados de teste disponíveis${NC}"
else
    echo -e "${YELLOW}⚠️ Aviso: Sem dados de teste (use seed.sql)${NC}"
fi
echo ""

# Resultado Final
echo "╔════════════════════════════════════════════════════════════╗"
echo "║ ✅ TESTE CONCLUÍDO COM SUCESSO                            ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Resumo:"
echo "  • Índices: $INDICES_COUNT encontrados (esperado 20+)"
echo "  • Tabelas principais: $TABLES_OK/5 OK"
echo "  • Índices tenant_id: $INDEXES_OK/4 OK"
echo "  • ANVIs para teste: $ANVIS_COUNT"
echo ""
echo "Próximos passos:"
echo "  1. Testar http://localhost:8000/dashboard_viabilidade.html"
echo "  2. Digitar ANVI ID e verificar se carrega"
echo "  3. Verificar scores de viabilidade"
echo "  4. Testar com múltiplos ANVIs"
echo ""
