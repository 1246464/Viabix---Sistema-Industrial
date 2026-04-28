#!/bin/bash

# SCRIPT FIX - Correção da conexão DigitalOcean MySQL
APP_DIR="/var/www/viabix"
cd "$APP_DIR"

echo "═══════════════════════════════════════════════"
echo "  FIX - Viabix Production DigitalOcean"
echo "═══════════════════════════════════════════════"
echo ""

# 1. Atualizar .env com DB_PORT
echo "[1] Atualizando .env com DB_PORT..."
cat > .env << 'EOF'
APP_ENV=production
APP_DEBUG=false

DB_HOST=db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com
DB_PORT=25060
DB_NAME=defaultdb
DB_USER=doadmin
DB_PASS=YOUR_DATABASE_PASSWORD_HERE
DB_CHARSET=utf8mb4

SESSION_NAME=viabix_session
SESSION_LIFETIME=28800
SESSION_SECURE=true
SESSION_SAMESITE=Strict

VIABIX_BILLING_PROVIDER=manual
EOF

echo "✓ .env atualizado"
cat .env | sed 's/^/  /'

# 2. Testar PHP config
echo ""
echo "[2] Testando PHP config.php..."
TEST=$(php -r "
error_reporting(E_ALL);
ini_set('display_errors', '0');
try {
    require_once 'api/config.php';
    \$stmt = \$pdo->prepare('SELECT 1 as test');
    \$stmt->execute();
    echo 'OK';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>&1)

if [ "$TEST" = "OK" ]; then
    echo "✓ Config OK - Conexão com BD funcionando!"
else
    echo "✗ Erro: $TEST"
    exit 1
fi

# 3. Testar check_session
echo ""
echo "[3] Testando check_session.php..."
php api/check_session.php 2>&1 | head -200

# 4. Reiniciar Apache
echo ""
echo "[4] Reiniciando Apache..."
systemctl restart apache2
sleep 2
echo "✓ Apache reiniciado"

# 5. Testar via HTTP
echo ""
echo "[5] Testando via HTTPS..."
echo "  GET /api/check_session.php:"
curl -s https://viabix.com.br/api/check_session.php 2>&1 | head -c 200
echo ""
echo ""
echo "  POST /api/login.php:"
curl -s -X POST https://viabix.com.br/api/login.php -H "Content-Type: application/json" -d '{"login":"test","senha":"test"}' 2>&1 | head -c 200
echo ""

echo ""
echo "═══════════════════════════════════════════════"
echo "  Setup concluído!"
echo "  Acesse: https://viabix.com.br/login.html"
echo "═══════════════════════════════════════════════"
