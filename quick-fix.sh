#!/bin/bash

# QUICK FIX - Comentar linhas problemáticas no config.php
APP_DIR="/var/www/viabix"
CONFIG_FILE="$APP_DIR/api/config.php"

echo "🔧 Corrigindo config.php..."
echo ""

# Backup
cp "$CONFIG_FILE" "$CONFIG_FILE.backup.$(date +%s)"
echo "✓ Backup criado"

# Remover a constante não existe: PDO::MYSQL_ATTR_SSL_MODE
# Isso vai comentar / remover as 3 linhas que usam ela

# Linha 1: if (defined('PDO::MYSQL_ATTR_SSL_MODE')) {
# Linha 2: $options[PDO::MYSQL_ATTR_SSL_MODE] = PDO::MYSQL_ATTR_SSL_PREFERRED;
# Linha 3: }

sed -i "/if (defined.*PDO::MYSQL_ATTR_SSL_MODE/,/^[[:space:]]*}$/d" "$CONFIG_FILE"

echo "✓ Linhas com PDO::MYSQL_ATTR_SSL_MODE removidas"
echo ""

# Testar
echo "Testando..."
cd "$APP_DIR"
TEST=$(php -r "
error_reporting(E_ALL);
ini_set('display_errors', '0');
try {
    require_once 'api/config.php';
    \$stmt = \$pdo->prepare('SELECT 1 as test');
    \$stmt->execute();
    echo 'SUCESSO';
} catch (Exception \$e) {
    echo 'ERRO: ' . \$e->getMessage();
}
" 2>&1)

if [ "$TEST" = "SUCESSO" ]; then
    echo "✓ Config OK!"
    echo ""
    echo "Reiniciando Apache..."
    systemctl restart apache2
    sleep 2
    echo "✓ Apache reiniciado"
    echo ""
    echo "Testando endpoint..."
    curl -s https://viabix.com.br/api/check_session.php | head -c 200
    echo ""
else
    echo "✗ Ainda há erro:"
    echo "$TEST"
    exit 1
fi
