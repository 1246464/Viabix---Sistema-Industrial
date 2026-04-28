#!/bin/bash

# DIAGNÓSTICO VIABIX - DigitalOcean
# Execute: bash diagnostico.sh

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║     VIABIX PRODUCTION DIAGNOSTICS - DigitalOcean               ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# 1. STATUS DOS SERVIÇOS
echo "[1/8] STATUS DOS SERVIÇOS"
echo "======================================"
systemctl status apache2 --no-pager | head -3
echo ""
systemctl status mysql --no-pager | head -3
echo ""
systemctl status redis-server --no-pager | head -3 2>/dev/null || echo "Redis: não configurado"
echo ""

# 2. VERSÕES
echo "[2/8] VERSÕES"
echo "======================================"
php -v 2>/dev/null | head -1
mysql --version 2>/dev/null
redis-cli --version 2>/dev/null || echo "Redis: não instalado"
apache2ctl -v 2>/dev/null | head -1
echo ""

# 3. DIRETÓRIO DA APLICAÇÃO
echo "[3/8] ESTRUTURA DE DIRETÓRIOS"
echo "======================================"
ls -la /var/www/html/ | head -12
echo ""

# 4. ARQUIVO .env
echo "[4/8] CONFIGURAÇÃO .env"
echo "======================================"
if [ -f /var/www/html/.env ]; then
    echo "✓ .env encontrado"
    grep -E "APP_ENV|DB_HOST|DB_NAME|DB_USER|SESSION_DRIVER" /var/www/html/.env
else
    echo "✗ .env NÃO ENCONTRADO"
fi
if [ -f /var/www/html/.env.production ]; then
    echo ""
    echo "✓ .env.production encontrado (PRIMEIROS 20 LINHAS)"
    head -20 /var/www/html/.env.production
fi
echo ""

# 5. TESTE DE CONEXÃO COM BANCO
echo "[5/8] TESTE DE CONEXÃO COM BANCO"
echo "======================================"
if [ -f /var/www/html/.env ]; then
    DB_HOST=$(grep DB_HOST /var/www/html/.env | cut -d= -f2 | tr -d ' ')
    DB_USER=$(grep DB_USER /var/www/html/.env | cut -d= -f2 | tr -d ' ')
    DB_PASS=$(grep DB_PASS /var/www/html/.env | cut -d= -f2 | tr -d ' ')
    DB_NAME=$(grep DB_NAME /var/www/html/.env | cut -d= -f2 | tr -d ' ')
    
    echo "Tentando conectar em: $DB_HOST"
    echo "Usuário: $DB_USER"
    echo "Banco: $DB_NAME"
    echo ""
    
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1 as conexao_ok;" 2>&1 | head -5
fi
echo ""

# 6. LOGS
echo "[6/8] LOGS DE ERRO (ÚLTIMAS 20 LINHAS)"
echo "======================================"
if [ -f /var/log/apache2/error.log ]; then
    tail -20 /var/log/apache2/error.log
else
    echo "Arquivo error.log não encontrado"
fi
if [ -f /var/www/html/logs/error.log ]; then
    echo ""
    echo "--- LOGS DE APP ---"
    tail -10 /var/www/html/logs/error.log
fi
echo ""

# 7. PHP CONFIG
echo "[7/8] CONFIGURAÇÃO PHP"
echo "======================================"
php -i 2>/dev/null | grep -E "^(Config File|display_errors|error_reporting|default_charset)" | head -10
echo ""
php -m | grep -E "^(pdo|mysql|curl|json|redis)" 
echo ""

# 8. DISK USAGE
echo "[8/8] USO DE DISCO"
echo "======================================"
df -h / | tail -1
echo ""
du -sh /var/www/html/ 2>/dev/null
du -sh /var/log/ 2>/dev/null
echo ""

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                   FIM DO DIAGNÓSTICO                           ║"
echo "╚════════════════════════════════════════════════════════════════╝"
