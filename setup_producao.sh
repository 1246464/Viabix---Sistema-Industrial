#!/bin/bash

# SCRIPT DE CONFIGURAÇÃO E DIAGNÓSTICO - VIABIX DigitalOcean
# Execute no servidor: bash setup_producao.sh

set -e

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     VIABIX - Setup Produção DigitalOcean                       ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

APP_DIR="/var/www/html"
cd "$APP_DIR"

# ============================================================
# 1. VERIFICAR ESTRUTURA
# ============================================================
echo "[1/6] Verificando Estrutura..."
if [ ! -d "$APP_DIR" ]; then
    echo "✗ Diretório $APP_DIR não existe!"
    exit 1
fi
echo "✓ Diretório OK"

# ============================================================
# 2. CRIAR/ATUALIZAR .env
# ============================================================
echo "[2/6] Configurando .env..."

cat > "$APP_DIR/.env" << 'ENVFILE'
# VIABIX - Production Environment
# DigitalOcean Managed MySQL

APP_ENV=production
APP_DEBUG=false
APP_NAME=Viabix
APP_VERSION=1.0.0
APP_TIMEZONE=America/Sao_Paulo

# URLs
APP_URL=https://viabix.com.br
APP_API_URL=https://viabix.com.br/api

# Database Configuration (DigitalOcean Managed MySQL)
DB_HOST=db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com
DB_PORT=25060
DB_NAME=defaultdb
DB_USER=doadmin
DB_PASS=YOUR_DATABASE_PASSWORD_HERE
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_SSL_ENABLED=true
DB_SSL_VERIFY=true

# Session Configuration
SESSION_NAME=viabix_session
SESSION_LIFETIME=28800
SESSION_IDLE_TIMEOUT=3600
SESSION_SECURE=true
SESSION_HTTPONLY=true
SESSION_SAMESITE=Strict
SESSION_DRIVER=file
SESSION_USE_STRICT_MODE=true

# Security
PASSWORD_MIN_LENGTH=12
HASH_ALGO=argon2id
LOCKOUT_ENABLED=true
LOCKOUT_ATTEMPTS=5
LOCKOUT_DURATION=900

# Two-Factor Authentication
TWO_FACTOR_ENABLED=false
TWO_FACTOR_REQUIRED=false

# Billing
VIABIX_BILLING_PROVIDER=manual
VIABIX_ASAAS_ENV=sandbox
VIABIX_ASAAS_API_KEY=
VIABIX_ASAAS_WEBHOOK_TOKEN=

# Monitoring
SENTRY_DSN=
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=1.0.0

# CORS
CORS_ALLOWED_ORIGINS=https://viabix.com.br

# Rate Limiting
RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_WINDOW=300
RATE_LIMIT_API_MAX=100
RATE_LIMIT_API_WINDOW=60

# Email
MAIL_FROM_ADDRESS=noreply@viabix.com.br
MAIL_FROM_NAME=Viabix
MAIL_DRIVER=log
ENVFILE

echo "✓ .env configurado"

# ============================================================
# 3. TESTAR CONEXÃO COM BANCO
# ============================================================
echo "[3/6] Testando Conexão com Banco de Dados..."

DB_HOST=$(grep DB_HOST "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
DB_PORT=$(grep DB_PORT "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
DB_USER=$(grep DB_USER "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
DB_PASS=$(grep DB_PASS "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')
DB_NAME=$(grep DB_NAME "$APP_DIR/.env" | cut -d= -f2 | tr -d ' ')

echo "Conectando em: $DB_HOST:$DB_PORT"
echo "Usuário: $DB_USER"
echo "Banco: $DB_NAME"
echo ""

if mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1 as test;" 2>/dev/null; then
    echo "✓ Conexão com BD OK"
    
    # Verificar tabelas
    TABLES=$(mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    echo "✓ Tabelas no banco: $TABLES"
else
    echo "✗ Erro ao conectar no banco de dados!"
    echo "Verificando conectividade..."
    timeout 5 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null && echo "✓ Porta acessível" || echo "✗ Porta não acessível"
    exit 1
fi

# ============================================================
# 4. VERIFICAR PERMISSÕES
# ============================================================
echo ""
echo "[4/6] Verificando Permissões..."

if [ -d "$APP_DIR/logs" ]; then
    if [ -w "$APP_DIR/logs" ]; then
        echo "✓ Diretório logs: gravável"
    else
        echo "⚠ Diretório logs: NÃO gravável"
        sudo chown -R www-data:www-data "$APP_DIR/logs"
        echo "✓ Permissões corrigidas"
    fi
else
    mkdir -p "$APP_DIR/logs"
    chown -R www-data:www-data "$APP_DIR/logs"
    echo "✓ Diretório logs criado"
fi

# ============================================================
# 5. TESTAR ENDPOINTS PHP
# ============================================================
echo ""
echo "[5/6] Testando Endpoints..."

if [ -f "$APP_DIR/api/check_session.php" ]; then
    echo "✓ check_session.php existe"
    
    # Teste rápido com PHP CLI
    TEST_OUTPUT=$(php "$APP_DIR/api/check_session.php" 2>&1 | head -c 100)
    if echo "$TEST_OUTPUT" | grep -q "logado"; then
        echo "✓ check_session.php retorna JSON"
    else
        echo "⚠ check_session.php pode ter erro:"
        php "$APP_DIR/api/check_session.php" 2>&1 | head -5
    fi
else
    echo "✗ check_session.php não encontrado"
fi

# ============================================================
# 6. VERIFICAR LOGS
# ============================================================
echo ""
echo "[6/6] Verificando Logs..."

if [ -f /var/log/apache2/error.log ]; then
    echo "✓ Log do Apache existe"
    echo ""
    echo "Últimas 10 linhas de erro:"
    tail -10 /var/log/apache2/error.log | sed 's/^/  /'
else
    echo "⚠ Log do Apache não encontrado"
fi

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                   SETUP CONCLUÍDO                              ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo "Próximos passos:"
echo "1. Acesse: https://viabix.com.br/login.html"
echo "2. Se houver erro 500, execute: tail -50 /var/log/apache2/error.log"
echo "3. Para diagnosticar: curl https://viabix.com.br/api/healthcheck.php"
