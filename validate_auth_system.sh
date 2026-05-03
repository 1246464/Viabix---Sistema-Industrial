#!/bin/bash
# =======================================================
# SCRIPT DE VALIDAÇÃO: Sistema de Auth
# =======================================================
# Use para testar se o novo sistema está funcionando

set -e

echo "🔍 Validando Sistema de Autenticação..."
echo ""

# 1. Verificar se arquivos existem
echo "✅ Verificando arquivos..."
[ -f "api/auth_system.php" ] && echo "  ✓ api/auth_system.php" || echo "  ✗ api/auth_system.php MISSING"
[ -f "api/tests/AuthSystemTest.php" ] && echo "  ✓ api/tests/AuthSystemTest.php" || echo "  ✗ api/tests/AuthSystemTest.php MISSING"
[ -f "BD/migracao_permissoes.sql" ] && echo "  ✓ BD/migracao_permissoes.sql" || echo "  ✗ BD/migracao_permissoes.sql MISSING"

echo ""
echo "✅ Verificando sintaxe PHP..."

# Verificar sintaxe de auth_system.php
php -l api/auth_system.php > /dev/null && echo "  ✓ api/auth_system.php (sintaxe OK)" || echo "  ✗ api/auth_system.php (ERROR)"

echo ""
echo "✅ Próximos passos:"
echo ""
echo "1. Executar migração do banco:"
echo "   mysql -u root viabix_db < BD/migracao_permissoes.sql"
echo ""
echo "2. Rodar testes unitários:"
echo "   cd /path/to/ANVI"
echo "   composer install  # Se não tiver PHPUnit"
echo "   vendor/bin/phpunit api/tests/AuthSystemTest.php"
echo ""
echo "3. Incluir auth_system.php no config.php:"
echo "   require_once __DIR__ . '/auth_system.php';"
echo ""
