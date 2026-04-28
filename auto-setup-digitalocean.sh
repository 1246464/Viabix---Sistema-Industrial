#!/bin/bash
# AUTOMATIC SETUP - VIABIX Production DigitalOcean
# Run: ssh root@146.190.244.133 << 'EOFSETUP'
# [paste this script]
# EOFSETUP

set -e

APP_DIR="/var/www/html"

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║         AUTO-SETUP VIABIX - DigitalOcean MySQL                 ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

cd "$APP_DIR"

# ============================================================
# 1. TESTAR CONEXÃO COM BANCO
# ============================================================
echo -e "${YELLOW}[1/7] Testando conexão com MySQL...${NC}"

DB_HOST="db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com"
DB_PORT="25060"
DB_USER="doadmin"
DB_PASS="YOUR_DATABASE_PASSWORD_HERE"
DB_NAME="defaultdb"

if mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Conexão com MySQL OK${NC}"
else
    echo -e "${RED}✗ Erro ao conectar no MySQL${NC}"
    echo "Verificando se a porta está acessível..."
    timeout 5 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null && \
        echo -e "${YELLOW}⚠ Porta acessível mas erro na autenticação${NC}" || \
        echo -e "${RED}✗ Porta NÃO acessível (firewall?)${NC}"
    exit 1
fi

# ============================================================
# 2. BACKUP .env ANTERIOR
# ============================================================
echo ""
echo -e "${YELLOW}[2/7] Fazendo backup...${NC}"

if [ -f "$APP_DIR/.env" ]; then
    cp "$APP_DIR/.env" "$APP_DIR/.env.backup.$(date +%s)"
    echo -e "${GREEN}✓ Backup do .env anterior criado${NC}"
fi

# ============================================================
# 3. CRIAR NOVO .env
# ============================================================
echo ""
echo -e "${YELLOW}[3/7] Criando .env...${NC}"

cat > "$APP_DIR/.env" << 'ENVFILE'
APP_ENV=production
APP_DEBUG=false
APP_NAME=Viabix
APP_TIMEZONE=America/Sao_Paulo

APP_URL=https://viabix.com.br
APP_API_URL=https://viabix.com.br/api

DB_HOST=db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com
DB_PORT=25060
DB_NAME=defaultdb
DB_USER=doadmin
DB_PASS=YOUR_DATABASE_PASSWORD_HERE
DB_CHARSET=utf8mb4

SESSION_NAME=viabix_session
SESSION_LIFETIME=28800
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict
SESSION_DRIVER=file

VIABIX_BILLING_PROVIDER=manual
VIABIX_ASAAS_ENV=sandbox
VIABIX_ASAAS_API_KEY=
VIABIX_ASAAS_WEBHOOK_TOKEN=

SENTRY_DSN=
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=1.0.0

CORS_ALLOWED_ORIGINS=https://viabix.com.br

RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_WINDOW=300

MAIL_FROM_ADDRESS=noreply@viabix.com.br
MAIL_FROM_NAME=Viabix
ENVFILE

echo -e "${GREEN}✓ .env criado com credentials DigitalOcean${NC}"

# ============================================================
# 4. TESTAR CONFIG.PHP
# ============================================================
echo ""
echo -e "${YELLOW}[4/7] Testando configuração PHP...${NC}"

TEST_RESULT=$(php -r "
require_once '$APP_DIR/api/config.php';
try {
    \$stmt = \$pdo->prepare('SELECT 1 as test');
    \$stmt->execute();
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

if [ "$TEST_RESULT" = "OK" ]; then
    echo -e "${GREEN}✓ API config.php conecta corretamente${NC}"
else
    echo -e "${RED}✗ Erro $TEST_RESULT${NC}"
    exit 1
fi

# ============================================================
# 5. CRIAR/PERMISSIONAR DIRETÓRIOS
# ============================================================
echo ""
echo -e "${YELLOW}[5/7] Ajustando permissões...${NC}"

mkdir -p "$APP_DIR/logs" "$APP_DIR/templates" "$APP_DIR/uploads"
chown -R www-data:www-data "$APP_DIR/logs" "$APP_DIR/templates" "$APP_DIR/uploads"
chmod -R 755 "$APP_DIR/logs" "$APP_DIR/templates"
chmod -R 775 "$APP_DIR/uploads" 2>/dev/null || true

echo -e "${GREEN}✓ Diretórios criados e permissões OK${NC}"

# ============================================================
# 6. REINICIAR APACHE
# ============================================================
echo ""
echo -e "${YELLOW}[6/7] Reiniciando Apache...${NC}"

systemctl restart apache2 2>&1 | grep -E "Restarting|restarted|OK" || echo "Apache restart executado"
sleep 2
echo -e "${GREEN}✓ Apache reiniciado${NC}"

# ============================================================
# 7. TESTAR ENDPOINTS
# ============================================================
echo ""
echo -e "${YELLOW}[7/7] Testando endpoints...${NC}"

# Teste local com CLI
TEST=$(php "$APP_DIR/api/check_session.php" 2>&1 | head -c 50)
if echo "$TEST" | grep -q "logado\|csrf"; then
    echo -e "${GREEN}✓ check_session.php OK${NC}"
else
    echo -e "${YELLOW}⚠ check_session.php requer testar via HTTP${NC}"
fi

# ============================================================
# RESUMO
# ============================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                   SETUP COMPLETADO!                            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Próximos passos:${NC}"
echo "1. Acesse: https://viabix.com.br/login.html"
echo "2. Faça login com admin / senha"
echo "3. Se erro 500: tail -50 /var/log/apache2/error.log"
echo ""
echo -e "${GREEN}Informações do setup:${NC}"
echo "  Banco: $DB_HOST:$DB_PORT"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  App Dir: $APP_DIR"
echo ""
