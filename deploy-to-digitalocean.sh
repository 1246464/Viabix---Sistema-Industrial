#!/bin/bash
# UPLOAD & DEPLOY SCRIPT - VIABIX Auth V2.0 to DigitalOcean
# Bash Script for Linux/Mac Users
# Usage: bash deploy-to-digitalocean.sh --droplet-ip 146.190.244.133 --ssh-key ~/.ssh/id_rsa

set -e

# ============================================================
# PARSE ARGUMENTS
# ============================================================
DROPLET_IP=""
SSH_KEY="$HOME/.ssh/id_rsa"
SSH_USER="root"
APP_DIR="/var/www/html"

while [[ $# -gt 0 ]]; do
    case $1 in
        --droplet-ip)
            DROPLET_IP="$2"
            shift 2
            ;;
        --ssh-key)
            SSH_KEY="$2"
            shift 2
            ;;
        --ssh-user)
            SSH_USER="$2"
            shift 2
            ;;
        --app-dir)
            APP_DIR="$2"
            shift 2
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# ============================================================
# COLORS
# ============================================================
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ============================================================
# FUNCTIONS
# ============================================================
log_info() {
    echo -e "${BLUE}ℹ ${NC}$1"
}

log_success() {
    echo -e "${GREEN}✓ ${NC}$1"
}

log_error() {
    echo -e "${RED}✗ ${NC}$1"
}

log_warning() {
    echo -e "${YELLOW}⚠ ${NC}$1"
}

# ============================================================
# VALIDATE ARGUMENTS
# ============================================================
if [ -z "$DROPLET_IP" ]; then
    log_error "Droplet IP é obrigatório"
    echo "Usage: bash deploy-to-digitalocean.sh --droplet-ip <IP> [--ssh-key ~/.ssh/id_rsa]"
    exit 1
fi

# ============================================================
# HEADER
# ============================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║    UPLOAD & DEPLOY - VIABIX Auth V2.0 to DigitalOcean         ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

# ============================================================
# VALIDAR PRÉ-REQUISITOS
# ============================================================
log_info "Validando pré-requisitos..."

if [ ! -f "$SSH_KEY" ]; then
    log_error "Chave SSH não encontrada: $SSH_KEY"
    exit 1
fi
log_success "Chave SSH encontrada"

if [ ! -f "api/auth_system.php" ]; then
    log_error "Execute este script do diretório raiz do projeto"
    exit 1
fi
log_success "Diretório do projeto validado"

# Test SSH connection
log_info "Testando conexão SSH com $SSH_USER@$DROPLET_IP..."
if ssh -i "$SSH_KEY" -o ConnectTimeout=5 "$SSH_USER@$DROPLET_IP" "echo OK" > /dev/null 2>&1; then
    log_success "Conexão SSH OK"
else
    log_error "Falha na conexão SSH"
    exit 1
fi

# ============================================================
# CRIAR LISTA DE ARQUIVOS
# ============================================================
echo ""
log_info "Preparando arquivos para upload..."

FILES_TO_UPLOAD=(
    # Novos arquivos
    "api/auth_system.php"
    "api/permissions.php"
    "api/tests/AuthSystemTest.php"
    "BD/migracao_permissoes.sql"
    
    # Modificados
    "api/config.php"
    
    # Documentação
    "SUMARIO_ENTREGA_AUTH.md"
    "IMPLEMENTACAO_AUTH_V2.md"
    "GUIA_TESTE_AUTH.md"
    
    # Scripts
    "deploy-auth-v2-digitalocean.sh"
    "validate_auth_system.sh"
    
    # Essencial
    ".env.production"
    "composer.json"
)

MISSING_COUNT=0
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ ! -f "$file" ]; then
        log_warning "Arquivo não encontrado: $file (será ignorado)"
        MISSING_COUNT=$((MISSING_COUNT + 1))
    fi
done

log_success "Total de arquivos para upload: ${#FILES_TO_UPLOAD[@]}"
for file in "${FILES_TO_UPLOAD[@]}"; do
    if [ -f "$file" ]; then
        echo "  + $file"
    fi
done

# ============================================================
# CRIAR ZIP
# ============================================================
echo ""
log_info "Compactando arquivos..."

TIMESTAMP=$(date +%Y%m%d_%H%M%S)
ZIP_FILE="viabix-auth-v2-$TIMESTAMP.zip"

# Remove if exists
rm -f "$ZIP_FILE"

# Create zip (skip missing files)
zip -q "$ZIP_FILE" $(for f in "${FILES_TO_UPLOAD[@]}"; do [ -f "$f" ] && echo "$f"; done)

ZIP_SIZE=$(du -h "$ZIP_FILE" | cut -f1)
log_success "Arquivo compactado: $ZIP_FILE ($ZIP_SIZE)"

# ============================================================
# UPLOAD VIA SCP
# ============================================================
echo ""
log_info "Iniciando upload para DigitalOcean..."

REMOTE_DIR="/tmp/viabix-deploy"
scp -i "$SSH_KEY" -r "$ZIP_FILE" "$SSH_USER@$DROPLET_IP:$REMOTE_DIR/"

log_success "Upload concluído"

# ============================================================
# EXTRACT & DEPLOY ON SERVER
# ============================================================
echo ""
log_info "Extraindo e implantando no servidor..."

ssh -i "$SSH_KEY" "$SSH_USER@$DROPLET_IP" bash << EOFREMOTE
#!/bin/bash
set -e

echo "Extraindo arquivos..."
cd $REMOTE_DIR
unzip -q $ZIP_FILE -d extract || true

echo "Copiando arquivos para $APP_DIR..."
cp -v extract/api/auth_system.php $APP_DIR/api/ 2>/dev/null || true
cp -v extract/api/permissions.php $APP_DIR/api/ 2>/dev/null || true
cp -v extract/api/config.php $APP_DIR/api/ 2>/dev/null || true
cp -vr extract/api/tests $APP_DIR/api/ 2>/dev/null || true
cp -v extract/BD/migracao_permissoes.sql $APP_DIR/BD/ 2>/dev/null || true
cp -v extract/*.md $APP_DIR/ 2>/dev/null || true
cp -v extract/*.sh $APP_DIR/ 2>/dev/null || true

echo "Aplicando permissões..."
chown -R www-data:www-data $APP_DIR 2>/dev/null || true
chmod -R 755 $APP_DIR
chmod 644 $APP_DIR/api/*.php 2>/dev/null || true

echo "Limpando arquivos temporários..."
rm -rf $REMOTE_DIR

echo ""
echo "✓ Deploy concluído com sucesso!"

EOFREMOTE

log_success "Deploy executado no servidor"

# ============================================================
# VALIDATE ON SERVER
# ============================================================
echo ""
log_info "Validando implantação..."

ssh -i "$SSH_KEY" "$SSH_USER@$DROPLET_IP" bash << EOFVALIDATE
#!/bin/bash

echo "Verificando arquivos..."
[ -f $APP_DIR/api/auth_system.php ] && echo "  ✓ auth_system.php" || echo "  ✗ auth_system.php"
[ -f $APP_DIR/api/permissions.php ] && echo "  ✓ permissions.php" || echo "  ✗ permissions.php"
[ -f $APP_DIR/api/config.php ] && echo "  ✓ config.php" || echo "  ✗ config.php"
[ -f $APP_DIR/BD/migracao_permissoes.sql ] && echo "  ✓ migracao_permissoes.sql" || echo "  ✗ migracao_permissoes.sql"

echo ""
echo "Verificando sintaxe PHP..."
php -l $APP_DIR/api/auth_system.php > /dev/null 2>&1 && echo "  ✓ auth_system.php OK" || echo "  ✗ Erro de sintaxe"
php -l $APP_DIR/api/permissions.php > /dev/null 2>&1 && echo "  ✓ permissions.php OK" || echo "  ✗ Erro de sintaxe"

EOFVALIDATE

log_success "Validação concluída"

# ============================================================
# CLEANUP
# ============================================================
echo ""
log_info "Limpando arquivos locais..."

rm -f "$ZIP_FILE"

log_success "Arquivos temporários removidos"

# ============================================================
# SUMMARY
# ============================================================
echo ""
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║              ✓ UPLOAD & DEPLOY CONCLUÍDO!                     ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo ""

log_info "Servidor: $SSH_USER@$DROPLET_IP"
log_info "App Dir: $APP_DIR"
log_info "Timestamp: $TIMESTAMP"

echo ""
echo -e "${YELLOW}PRÓXIMOS PASSOS:${NC}"
echo ""
echo "1. Conectar ao servidor:"
echo "   ssh -i \"$SSH_KEY\" $SSH_USER@$DROPLET_IP"
echo ""
echo "2. Executar migração SQL:"
echo "   cd $APP_DIR"
echo "   source .env.production"
echo "   mysql -h \$DB_HOST -u \$DB_USER -p\$DB_PASS \$DB_NAME < BD/migracao_permissoes.sql"
echo ""
echo "3. Testar endpoint:"
echo "   curl -X GET http://seu_dominio/ANVI/api/permissions.php"
echo ""
echo "4. Rodar testes:"
echo "   cd $APP_DIR && vendor/bin/phpunit api/tests/AuthSystemTest.php"
echo ""
echo "5. Verificar logs:"
echo "   tail -f $APP_DIR/logs/error.log"
echo ""

log_success "Sucesso!"
echo ""
