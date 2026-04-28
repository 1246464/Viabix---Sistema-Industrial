#!/bin/bash
# Diagnóstico rápido - execute no servidor

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║           DIAGNÓSTICO RÁPIDO - Viabix DigitalOcean             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# 1. Diretório da aplicação
echo "[1] Verificando diretório da aplicação..."
if [ -d /var/www/html ]; then
    echo "✓ /var/www/html existe"
    ls -la /var/www/html/ | head -15
else
    echo "✗ /var/www/html NÃO EXISTE"
    echo "Alternativas:"
    find / -name "login.html" 2>/dev/null | head -5
fi

echo ""

# 2. .env
echo "[2] Verificando .env..."
if [ -f /var/www/html/.env ]; then
    echo "✓ .env existe"
    echo "Conteúdo:"
    cat /var/www/html/.env
else
    echo "✗ .env NÃO EXISTE - ESTE É O PROBLEMA!"
fi

echo ""

# 3. Testar conexão
echo "[3] Testando conexão com MySQL..."
mysql --ssl-mode=REQUIRED -h db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com -P 25060 -u doadmin -pAVNS_Ohxp_y8F0kYsDCJ5d2p defaultdb -e "SELECT 1;" 2>&1 && echo "✓ MySQL OK" || echo "✗ MySQL ERRO"

echo ""

# 4. Testar PHP
echo "[4] Testando PHP..."
php -v | head -1
php -r "echo 'PHP OK';" 2>&1

echo ""

# 5. Arquivo api/config.php
echo "[5] Verificando api/config.php..."
if [ -f /var/www/html/api/config.php ]; then
    echo "✓ config.php existe"
else
    echo "✗ config.php não encontrado"
fi

echo ""
echo "FIM DO DIAGNÓSTICO"
