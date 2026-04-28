#!/bin/bash

# ============================================================
# DIAGNÓSTICO COMPLETO - VIABIX Production DigitalOcean
# Execute: bash diagnostico_completo.sh
# ============================================================

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

clear

echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║   DIAGNÓSTICO COMPLETO - VIABIX DigitalOcean                   ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""

# ============================================================
# 1. ENCONTRAR O DIRETÓRIO CORRETO
# ============================================================
echo -e "${YELLOW}[1/10] Localizando diretório da aplicação...${NC}"

APP_DIR=""
if [ -f /var/www/viabix/api/config.php ]; then
    APP_DIR="/var/www/viabix"
    echo -e "${GREEN}✓ Encontrado em /var/www/viabix${NC}"
elif [ -f /var/www/html/api/config.php ]; then
    APP_DIR="/var/www/html"
    echo -e "${GREEN}✓ Encontrado em /var/www/html${NC}"
else
    echo -e "${RED}✗ Diretório não encontrado!${NC}"
    echo "Procurando..."
    find / -name "config.php" -path "*/api/*" 2>/dev/null | head -3
    exit 1
fi

echo "  Diretório: $APP_DIR"
cd "$APP_DIR"

# ============================================================
# 2. VERIFICAR ESTRUTURA
# ============================================================
echo ""
echo -e "${YELLOW}[2/10] Verificando estrutura de arquivos...${NC}"

FILES=(
    "api/config.php"
    "api/login.php"
    "api/check_session.php"
    "login.html"
    ".env"
    "bootstrap_env.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$APP_DIR/$file" ]; then
        echo -e "${GREEN}✓${NC} $file"
    else
        echo -e "${RED}✗${NC} $file (FALTANDO!)"
    fi
done

# ============================================================
# 3. VERIFICAR ARQUIVO .env
# ============================================================
echo ""
echo -e "${YELLOW}[3/10] Verificando arquivo .env...${NC}"

if [ -f "$APP_DIR/.env" ]; then
    echo -e "${GREEN}✓ .env existe${NC}"
    echo ""
    echo "Conteúdo do .env:"
    cat "$APP_DIR/.env" | sed 's/^/  /'
    echo ""
    
    # Verificar vars críticas
    DB_HOST=$(grep "^DB_HOST=" "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
    DB_USER=$(grep "^DB_USER=" "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
    DB_PASS=$(grep "^DB_PASS=" "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
    DB_NAME=$(grep "^DB_NAME=" "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
    
    echo -e "Vars críticas:"
    echo -e "  DB_HOST: ${DB_HOST:0:30}..."
    echo -e "  DB_USER: $DB_USER"
    echo -e "  DB_PASS: ${DB_PASS:0:10}..."
    echo -e "  DB_NAME: $DB_NAME"
else
    echo -e "${RED}✗ .env NÃO EXISTE${NC}"
fi

# ============================================================
# 4. TESTAR CONEXÃO COM MYSQL
# ============================================================
echo ""
echo -e "${YELLOW}[4/10] Testando conexão com MySQL...${NC}"

if [ -n "$DB_HOST" ] && [ -n "$DB_USER" ] && [ -n "$DB_PASS" ]; then
    if mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P 25060 -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1 as test;" >/dev/null 2>&1; then
        echo -e "${GREEN}✓ Conexão com MySQL OK${NC}"
        
        # Contar tabelas
        TABLES=$(mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P 25060 -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
        echo "  Tabelas: $TABLES"
    else
        echo -e "${RED}✗ ERRO ao conectar MySQL${NC}"
        echo "  Testando conectividade na porta..."
        timeout 3 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/25060" 2>/dev/null && echo "  ✓ Porta acessível" || echo "  ✗ Porta não acessível"
    fi
else
    echo -e "${RED}✗ Credenciais do BD incompletas no .env${NC}"
fi

# ============================================================
# 5. TESTAR PHP CONFIG
# ============================================================
echo ""
echo -e "${YELLOW}[5/10] Testando PHP config.php...${NC}"

TEST_OUTPUT=$(php -r "
error_reporting(E_ALL);
ini_set('display_errors', '0');
try {
    require_once '$APP_DIR/api/config.php';
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR: ' . get_class(\$e) . ': ' . \$e->getMessage();
}
" 2>&1)

if [ "$TEST_OUTPUT" = "OK" ]; then
    echo -e "${GREEN}✓ PHP config.php carregou OK${NC}"
else
    echo -e "${RED}✗ Erro: $TEST_OUTPUT${NC}"
fi

# ============================================================
# 6. TESTAR ENDPOINTS COM PHP-CLI
# ============================================================
echo ""
echo -e "${YELLOW}[6/10] Testando endpoints com PHP-CLI...${NC}"

echo "  [6a] check_session.php:"
OUTPUT=$(php "$APP_DIR/api/check_session.php" 2>&1 | head -c 100)
if echo "$OUTPUT" | grep -q "logado"; then
    echo -e "    ${GREEN}✓ Retorna JSON válido${NC}"
else
    echo -e "    ${RED}✗ Erro: $OUTPUT${NC}"
fi

# ============================================================
# 7. TESTAR ENDPOINTS COM CURL
# ============================================================
echo ""
echo -e "${YELLOW}[7/10] Testando endpoints com CURL (HTTP)...${NC}"

echo "  [7a] GET /api/check_session.php:"
RESPONSE=$(curl -s -w "\n%{http_code}" https://viabix.com.br/api/check_session.php 2>&1 | tail -1)
if [ "$RESPONSE" = "200" ]; then
    echo -e "    ${GREEN}✓ HTTP 200${NC}"
    curl -s https://viabix.com.br/api/check_session.php 2>&1 | head -c 100
    echo ""
elif [ "$RESPONSE" = "500" ]; then
    echo -e "    ${RED}✗ HTTP 500 (Erro no servidor)${NC}"
else
    echo -e "    ${YELLOW}⚠ HTTP $RESPONSE${NC}"
fi

echo ""
echo "  [7b] POST /api/login.php:"
RESPONSE=$(curl -s -w "\n%{http_code}" -X POST https://viabix.com.br/api/login.php -H "Content-Type: application/json" -d '{"login":"test","senha":"test"}' 2>&1 | tail -1)
if [ "$RESPONSE" = "200" ]; then
    echo -e "    ${GREEN}✓ HTTP 200${NC}"
elif [ "$RESPONSE" = "500" ]; then
    echo -e "    ${RED}✗ HTTP 500 (Erro no servidor)${NC}"
else
    echo -e "    ${YELLOW}⚠ HTTP $RESPONSE${NC}"
fi

# ============================================================
# 8. VERIFICAR LOGS DO APACHE
# ============================================================
echo ""
echo -e "${YELLOW}[8/10] Verificando logs do Apache...${NC}"

if [ -f /var/log/apache2/error.log ]; then
    RECENT_ERRORS=$(tail -20 /var/log/apache2/error.log | grep -E "PHP|Fatal|Warning|Error" | head -5)
    if [ -n "$RECENT_ERRORS" ]; then
        echo -e "  ${RED}✗ Encontrados erros nos últimos 20 logs:${NC}"
        echo "$RECENT_ERRORS" | sed 's/^/    /'
    else
        echo -e "  ${GREEN}✓ Nenhum erro PHP recente${NC}"
    fi
else
    echo -e "  ${YELLOW}⚠ Log do Apache não encontrado${NC}"
fi

# ============================================================
# 9. VERIFICAR PERMISSÕES
# ============================================================
echo ""
echo -e "${YELLOW}[9/10] Verificando permissões...${NC}"

echo "  .env:"
ls -l "$APP_DIR/.env" | awk '{print "    " $1 " " $3 ":" $4}' || echo "    (não encontrado)"

echo "  api/:"
ls -ld "$APP_DIR/api" | awk '{print "    " $1 " " $3 ":" $4}' || echo "    (não encontrado)"

echo "  logs/:"
if [ -d "$APP_DIR/logs" ]; then
    ls -ld "$APP_DIR/logs" | awk '{print "    " $1 " " $3 ":" $4}'
    if [ -w "$APP_DIR/logs" ]; then
        echo -e "    ${GREEN}✓ Gravável${NC}"
    else
        echo -e "    ${RED}✗ Não gravável${NC}"
    fi
else
    echo "    (não existe)"
fi

# ============================================================
# 10. RESUMO
# ============================================================
echo ""
echo -e "${BLUE}╔════════════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║                     RESUMO DO DIAGNÓSTICO                       ║${NC}"
echo -e "${BLUE}╚════════════════════════════════════════════════════════════════╝${NC}"
echo ""
echo "App Directory: $APP_DIR"
echo "PHP Version: $(php -v | head -1)"
echo "Apache Status: $(systemctl is-active apache2 2>/dev/null || echo 'unknown')"
echo "MySQL Cliente: $(mysql --version 2>/dev/null)"
echo ""
echo -e "${YELLOW}Próximos passos se houver erro:${NC}"
echo "1. Verificar logs detalhados:"
echo "   tail -100 /var/log/apache2/error.log"
echo ""
echo "2. Testar PHP config diretamente:"
echo "   cd $APP_DIR && php -r \"require 'api/config.php'; echo 'OK';\" "
echo ""
echo "3. Reiniciar Apache:"
echo "   systemctl restart apache2"
echo ""
echo "4. Testar com curl:"
echo "   curl -v https://viabix.com.br/api/check_session.php"
echo ""
