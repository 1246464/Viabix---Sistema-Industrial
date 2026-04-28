#!/bin/bash
# Teste rápido de conexão com o MySQL do DigitalOcean

DB_HOST="db-mysql-tor1-97153-do-user-35989295-0.h.db.ondigitalocean.com"
DB_PORT="25060"
DB_USER="doadmin"
DB_PASS="YOUR_DATABASE_PASSWORD_HERE"
DB_NAME="defaultdb"

echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  TESTE DE CONEXÃO - MySQL DigitalOcean                         ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

echo "Testando conectividade com o banco..."
echo "Host: $DB_HOST:$DB_PORT"
echo "User: $DB_USER"
echo "Database: $DB_NAME"
echo ""

# Teste 1: Conectividade básica (timeout 5s)
echo "[1] Testando conectividade na porta..."
timeout 5 bash -c "cat < /dev/null > /dev/tcp/$DB_HOST/$DB_PORT" 2>/dev/null
if [ $? -eq 0 ]; then
    echo "✓ Porta $DB_PORT acessível"
else
    echo "✗ Porta não acessível (firewall?)"
    exit 1
fi

echo ""

# Teste 2: Conexão MySQL com SSL
echo "[2] Conectando no MySQL com SSL..."
if mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1 as test;" 2>/dev/null; then
    echo "✓ Conexão OK!"
else
    echo "✗ Erro ao conectar"
    echo "Tentando sem SSL..."
    mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1 as test;" 2>&1
fi

echo ""

# Teste 3: Listar bancos
echo "[3] Bancos de dados disponíveis:"
mysql --ssl-mode=REQUIRED -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -p"$DB_PASS" -e "SHOW DATABASES;" 2>/dev/null | head -10

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║               Se tudo passou, execute:                         ║"
echo "║  ssh root@146.190.244.133 'bash setup_producao.sh'             ║"
echo "╚════════════════════════════════════════════════════════════════╝"
