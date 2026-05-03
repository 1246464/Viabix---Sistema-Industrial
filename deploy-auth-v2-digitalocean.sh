#!/bin/bash
# DEPLOY AUTH V2 - VIABIX to DigitalOcean
# This script deploys the new authentication and authorization system
# Run: ssh root@YOUR_DROPLET_IP << 'EOFSETUP'
# [paste this script]
# EOFSETUP

set -e

APP_DIR="/var/www/html"
BACKUP_DIR="/var/backups/viabix"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║    DEPLOY AUTH V2.0 - VIABIX DigitalOcean                      ║"
echo "║    Sistema Centralizado de Autenticação e Autorização          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# ============================================================
# 0. PRÉ-REQUISITOS
# ============================================================
echo -e "${BLUE}[PRÉ-CHECK] Validando pré-requisitos...${NC}"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}✗ Este script deve rodar como root${NC}"
   exit 1
fi

# Check directories
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}✗ Diretório $APP_DIR não encontrado${NC}"
    exit 1
fi

# Check PHP
if ! command -v php &> /dev/null; then
    echo -e "${RED}✗ PHP não instalado${NC}"
    exit 1
fi

PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9.]+')
echo -e "${GREEN}✓ PHP $PHP_VERSION encontrado${NC}"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo -e "${RED}✗ MySQL client não instalado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ MySQL client encontrado${NC}"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo -e "${YELLOW}⚠ Composer não encontrado - instalando...${NC}"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi
echo -e "${GREEN}✓ Composer encontrado${NC}"

# ============================================================
# 1. CRIAR BACKUP
# ============================================================
echo ""
echo -e "${BLUE}[1/8] Criando backup da versão anterior...${NC}"

mkdir -p "$BACKUP_DIR"

# Backup arquivos PHP críticos
backup_files=(
    "api/config.php"
    "api/usuarios.php"
    "api/anvi.php"
    "api/admin_saas.php"
    "Controle_de_projetos/auth.php"
    "Controle_de_projetos/usuarios_manager.php"
)

for file in "${backup_files[@]}"; do
    if [ -f "$APP_DIR/$file" ]; then
        mkdir -p "$BACKUP_DIR/$TIMESTAMP/$(dirname $file)"
        cp "$APP_DIR/$file" "$BACKUP_DIR/$TIMESTAMP/$file"
    fi
done

echo -e "${GREEN}✓ Backup criado em: $BACKUP_DIR/$TIMESTAMP${NC}"

# ============================================================
# 2. VALIDAR SINTAXE DOS ARQUIVOS NOVOS
# ============================================================
echo ""
echo -e "${BLUE}[2/8] Validando sintaxe dos arquivos novos...${NC}"

NEW_FILES=(
    "api/auth_system.php"
    "api/config.php"
    "api/permissions.php"
    "api/tests/AuthSystemTest.php"
)

# Create temp directory for new files
TEMP_DIR=$(mktemp -d)
trap "rm -rf $TEMP_DIR" EXIT

for file in "${NEW_FILES[@]}"; do
    # You would normally receive these files from git or download
    # For now, we'll create a placeholder that will be replaced
    echo "File $file needs to be deployed"
done

echo -e "${YELLOW}⚠ Arquivos novos serão sincronizados do Git...${NC}"

# ============================================================
# 3. SINCRONIZAR DO GIT (SE USAR GIT)
# ============================================================
echo ""
echo -e "${BLUE}[3/8] Sincronizando com repositório Git...${NC}"

cd "$APP_DIR"

# Check if git repo
if [ -d ".git" ]; then
    echo "Pulling latest changes..."
    git stash
    git pull origin main || git pull origin master
    echo -e "${GREEN}✓ Git sincronizado${NC}"
else
    echo -e "${YELLOW}⚠ Não é um repositório Git - copie os arquivos manualmente${NC}"
    echo "Arquivos necessários:"
    echo "  - api/auth_system.php (NEW)"
    echo "  - api/config.php (UPDATED)"
    echo "  - api/permissions.php (NEW)"
    echo "  - BD/migracao_permissoes.sql (NEW)"
    echo ""
    read -p "Pressione ENTER após copiar os arquivos..."
fi

# ============================================================
# 4. VALIDAR SINTAXE PHP
# ============================================================
echo ""
echo -e "${BLUE}[4/8] Validando sintaxe PHP...${NC}"

syntax_errors=0

for file in api/auth_system.php api/config.php api/permissions.php; do
    if [ -f "$file" ]; then
        if php -l "$file" > /dev/null 2>&1; then
            echo -e "${GREEN}✓ $file${NC}"
        else
            echo -e "${RED}✗ Erro de sintaxe em $file:${NC}"
            php -l "$file"
            syntax_errors=$((syntax_errors + 1))
        fi
    fi
done

if [ $syntax_errors -gt 0 ]; then
    echo -e "${RED}✗ Encontrados $syntax_errors erros de sintaxe${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Sintaxe PHP validada${NC}"

# ============================================================
# 5. INSTALAR DEPENDÊNCIAS
# ============================================================
echo ""
echo -e "${BLUE}[5/8] Instalando dependências Composer...${NC}"

if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✓ Dependências instaladas${NC}"
else
    echo -e "${YELLOW}⚠ composer.json não encontrado${NC}"
fi

# ============================================================
# 6. EXECUTAR MIGRAÇÃO SQL
# ============================================================
echo ""
echo -e "${BLUE}[6/8] Executando migração SQL...${NC}"

# Load database credentials from .env
if [ -f ".env.production" ]; then
    source .env.production
elif [ -f ".env" ]; then
    source .env
else
    echo -e "${RED}✗ Arquivo .env não encontrado${NC}"
    exit 1
fi

# Validate database variables
if [ -z "$DB_HOST" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ] || [ -z "$DB_NAME" ]; then
    echo -e "${RED}✗ Variáveis de banco de dados não configuradas em .env${NC}"
    exit 1
fi

# Test database connection
if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Conexão com banco de dados OK${NC}"
else
    echo -e "${RED}✗ Erro ao conectar no banco de dados${NC}"
    exit 1
fi

# Execute migration
if [ -f "BD/migracao_permissoes.sql" ]; then
    echo "Executando migração..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "BD/migracao_permissoes.sql"
    echo -e "${GREEN}✓ Migração SQL executada${NC}"
else
    echo -e "${RED}✗ BD/migracao_permissoes.sql não encontrado${NC}"
    exit 1
fi

# Verify tables
echo "Verificando tabelas criadas..."
tables=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SHOW TABLES LIKE 'roles%'" | wc -l)

if [ $tables -gt 1 ]; then
    echo -e "${GREEN}✓ Tabelas de permissões criadas${NC}"
else
    echo -e "${YELLOW}⚠ Verifique se as tabelas foram criadas${NC}"
fi

# ============================================================
# 7. VALIDAÇÃO DE DEPLOY
# ============================================================
echo ""
echo -e "${BLUE}[7/8] Validando arquivos implantados...${NC}"

validation_ok=1

# Check core files
for file in api/auth_system.php api/config.php api/permissions.php; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file FALTANDO${NC}"
        validation_ok=0
    fi
done

# Check migration
if [ -f "BD/migracao_permissoes.sql" ]; then
    echo -e "${GREEN}✓ BD/migracao_permissoes.sql${NC}"
else
    echo -e "${YELLOW}⚠ BD/migracao_permissoes.sql${NC}"
fi

# Check tests
if [ -f "api/tests/AuthSystemTest.php" ]; then
    echo -e "${GREEN}✓ api/tests/AuthSystemTest.php${NC}"
else
    echo -e "${YELLOW}⚠ api/tests/AuthSystemTest.php (não necessário em produção)${NC}"
fi

# ============================================================
# 8. AJUSTAR PERMISSÕES
# ============================================================
echo ""
echo -e "${BLUE}[8/8] Ajustando permissões de arquivos...${NC}"

# Set ownership
chown -R www-data:www-data "$APP_DIR"

# Set permissions
chmod -R 755 "$APP_DIR"
chmod -R 644 "$APP_DIR/api"/*.php
chmod -R 644 "$APP_DIR/api/tests"/*.php
chmod 755 "$APP_DIR/api"
chmod 755 "$APP_DIR/BD"
chmod 644 "$APP_DIR/BD"/*.sql

# Logs directory
if [ -d "$APP_DIR/logs" ]; then
    chmod -R 777 "$APP_DIR/logs"
fi

echo -e "${GREEN}✓ Permissões ajustadas${NC}"

# ============================================================
# RESULTADO FINAL
# ============================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║                    ✓ DEPLOY CONCLUÍDO                          ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}Resumo do Deploy:${NC}"
echo "  Versão: Auth System v2.0"
echo "  Timestamp: $TIMESTAMP"
echo "  Backup: $BACKUP_DIR/$TIMESTAMP"
echo "  Banco de Dados: $DB_HOST/$DB_NAME"
echo ""
echo -e "${YELLOW}PRÓXIMOS PASSOS:${NC}"
echo ""
echo "1. Testar endpoint de permissões:"
echo "   curl -X GET http://seu_dominio/ANVI/api/permissions.php -b 'PHPSESSID=seu_cookie'"
echo ""
echo "2. Verificar logs de auditoria:"
echo "   mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e 'SELECT * FROM audit_logs LIMIT 5;'"
echo ""
echo "3. Rodar testes (se tiver PHPUnit):"
echo "   cd $APP_DIR && vendor/bin/phpunit api/tests/AuthSystemTest.php"
echo ""
echo "4. Monitorar em produção:"
echo "   tail -f $APP_DIR/logs/security_events.log"
echo ""
echo -e "${BLUE}Documentação:${NC}"
echo "  - SUMARIO_ENTREGA_AUTH.md"
echo "  - IMPLEMENTACAO_AUTH_V2.md"
echo "  - GUIA_TESTE_AUTH.md"
echo ""
echo "Backup anterior preservado em: $BACKUP_DIR/$TIMESTAMP"
echo ""

# Optional: Send email notification
if command -v mail &> /dev/null; then
    echo "Deploy Auth V2 concluído em $(date)" | \
        mail -s "VIABIX Deploy Auth V2 - Sucesso" admin@example.com
fi

exit 0
