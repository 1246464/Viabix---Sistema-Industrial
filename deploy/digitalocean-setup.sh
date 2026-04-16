#!/bin/bash
################################################################################################
# 🌊 VIABIX DIGITALOCEAN AUTOMATED SETUP SCRIPT
# 
# Este script configura tudo automaticamente em um Droplet DigitalOcean
# 
# PRÉ-REQUISITOS:
# 1. Crear um Droplet Ubuntu 22.04 (2GB RAM mínimo)
# 2. Ter acesso SSH (via key-based auth)
# 3. Ter um API token do DigitalOcean
# 
# USO:
#   curl https://raw.githubusercontent.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash
#   
#   OU manualmente:
#   chmod +x digitalocean-setup.sh
#   sudo ./digitalocean-setup.sh
#
################################################################################################

set -e  # Exit on error

# Color output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
REPO_URL="https://github.com/viabix/viabix.git"
APP_DIR="/var/www/viabix"
APP_USER="www-data"
APP_GROUP="www-data"
PHP_VERSION="8.2"
DOMAIN="${DOMAIN:-app.viabix.com.br}"

echo -e "${BLUE}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  🌊 VIABIX DIGITALOCEAN AUTOMATED SETUP                        ║"
echo "║  Ubuntu 22.04 • PHP 8.2 • MySQL • Redis                        ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}\n"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ Este script deve ser executado como root${NC}"
   exit 1
fi

echo -e "${YELLOW}📋 PRÉ-REQUISITOS${NC}"
echo "Domain: $DOMAIN"
echo "App Directory: $APP_DIR"
echo "PHP Version: $PHP_VERSION"
echo ""

# ============================================================================
# 1. SYSTEM UPDATE
# ============================================================================
echo -e "${BLUE}[1/12]${NC} ${YELLOW}Atualizando sistema...${NC}"

apt-get update -qq
apt-get upgrade -y -qq

# Install basic tools
apt-get install -y -qq \
    curl wget git zip unzip \
    build-essential \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release

echo -e "${GREEN}✅ Sistema atualizado${NC}\n"

# ============================================================================
# 2. PHP 8.2 INSTALLATION
# ============================================================================
echo -e "${BLUE}[2/12]${NC} ${YELLOW}Instalando PHP 8.2...${NC}"

# Add ondrej PHP PPA
add-apt-repository -y ppa:ondrej/php -q
apt-get update -qq

# Install PHP and extensions
apt-get install -y -qq \
    php$PHP_VERSION \
    php$PHP_VERSION-fpm \
    php$PHP_VERSION-cli \
    php$PHP_VERSION-mysql \
    php$PHP_VERSION-redis \
    php$PHP_VERSION-curl \
    php$PHP_VERSION-gd \
    php$PHP_VERSION-xml \
    php$PHP_VERSION-mbstring \
    php$PHP_VERSION-json \
    php$PHP_VERSION-bcmath \
    php$PHP_VERSION-zip \
    php$PHP_VERSION-opcache \
    php$PHP_VERSION-dev

echo -e "${GREEN}✅ PHP 8.2 instalado${NC}\n"

# ============================================================================
# 3. APACHE INSTALLATION
# ============================================================================
echo -e "${BLUE}[3/12]${NC} ${YELLOW}Instalando Apache...${NC}"

apt-get install -y -qq apache2

# Enable modules
a2enmod rewrite
a2enmod proxy
a2enmod proxy_fcgi
a2enmod setenvif
a2enmod ssl
a2enmod headers
a2enmod http2

echo -e "${GREEN}✅ Apache instalado${NC}\n"

# ============================================================================
# 4. REDIS INSTALLATION
# ============================================================================
echo -e "${BLUE}[4/12]${NC} ${YELLOW}Instalando Redis...${NC}"

apt-get install -y -qq redis-server php$PHP_VERSION-redis

systemctl enable redis-server
systemctl start redis-server

echo -e "${GREEN}✅ Redis instalado${NC}\n"

# ============================================================================
# 5. COMPOSER INSTALLATION
# ============================================================================
echo -e "${BLUE}[5/12]${NC} ${YELLOW}Instalando Composer...${NC}"

EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo -e "${RED}❌ Composer checksum inválido${NC}"
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

echo -e "${GREEN}✅ Composer instalado${NC}\n"

# ============================================================================
# 6. APPLICATION SETUP
# ============================================================================
echo -e "${BLUE}[6/12]${NC} ${YELLOW}Fazendo clone da aplicação...${NC}"

# Create directory
mkdir -p $APP_DIR
cd $APP_DIR

# Clone repository
git clone $REPO_URL .

# Set permissions
chown -R $APP_USER:$APP_GROUP $APP_DIR
chmod -R 755 $APP_DIR
chmod -R 775 $APP_DIR/api
chmod -R 775 $APP_DIR/uploads

# Create necessary directories
mkdir -p /var/log/viabix
mkdir -p /var/cache/viabix
mkdir -p /var/uploads/viabix

chown -R $APP_USER:$APP_GROUP /var/log/viabix /var/cache/viabix /var/uploads/viabix
chmod -R 775 /var/log/viabix /var/cache/viabix /var/uploads/viabix

echo -e "${GREEN}✅ Aplicação clonada${NC}\n"

# ============================================================================
# 7. ENVIRONMENT CONFIGURATION
# ============================================================================
echo -e "${BLUE}[7/12]${NC} ${YELLOW}Configurando ambiente...${NC}"

# Copy .env template
cp $APP_DIR/.env.production $APP_DIR/.env

# Generate secure keys
ENCRYPTION_KEY=$(openssl rand -base64 32)
DB_PASSWORD=$(openssl rand -base64 20)
REDIS_PASSWORD=$(openssl rand -base64 16)

# Update .env with DigitalOcean-specific values
sed -i "s|APP_URL=.*|APP_URL=https://$DOMAIN|g" $APP_DIR/.env
sed -i "s|APP_ENV=.*|APP_ENV=production|g" $APP_DIR/.env
sed -i "s|DB_HOST=.*|DB_HOST=localhost|g" $APP_DIR/.env
sed -i "s|DB_PASS=CHANGE_ME.*|DB_PASS=$DB_PASSWORD|g" $APP_DIR/.env
sed -i "s|ENCRYPTION_KEY=CHANGE_ME.*|ENCRYPTION_KEY=$ENCRYPTION_KEY|g" $APP_DIR/.env
sed -i "s|REDIS_PASSWORD=CHANGE_ME.*|REDIS_PASSWORD=$REDIS_PASSWORD|g" $APP_DIR/.env
sed -i "s|MAIL_PROVIDER=.*|MAIL_PROVIDER=sendgrid|g" $APP_DIR/.env
sed -i "s|SESSION_SECURE=.*|SESSION_SECURE=true|g" $APP_DIR/.env

chmod 400 $APP_DIR/.env

echo -e "${GREEN}✅ Ambiente configurado${NC}\n"

# ============================================================================
# 8. MYSQL INSTALLATION
# ============================================================================
echo -e "${BLUE}[8/12]${NC} ${YELLOW}Instalando MySQL Server...${NC}"

apt-get install -y -qq mysql-server

# Start MySQL
systemctl enable mysql
systemctl start mysql

# Create database and user
mysql -e "CREATE DATABASE viabix_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER 'viabix_app'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -e "GRANT ALL PRIVILEGES ON viabix_prod.* TO 'viabix_app'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

# Import schema
mysql -u viabix_app -p"$DB_PASSWORD" viabix_prod < $APP_DIR/BD/viabix_saas_multitenant.sql

echo -e "${GREEN}✅ MySQL instalado e configurado${NC}\n"

# ============================================================================
# 9. SSL CERTIFICATE (Let's Encrypt)
# ============================================================================
echo -e "${BLUE}[9/12]${NC} ${YELLOW}Instalando SSL (Let's Encrypt)...${NC}"

apt-get install -y -qq certbot python3-certbot-apache

# Request certificate (assuming DNS already points to this server)
certbot certonly --apache -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN

echo -e "${GREEN}✅ SSL Certificate instalado${NC}\n"

# ============================================================================
# 10. APACHE VIRTUALHOST CONFIGURATION
# ============================================================================
echo -e "${BLUE}[10/12]${NC} ${YELLOW}Configurando Apache VirtualHost...${NC}"

cat > /etc/apache2/sites-available/viabix.conf << 'EOF'
<VirtualHost *:80>
    ServerName DOMAIN_PLACEHOLDER
    Redirect permanent / https://DOMAIN_PLACEHOLDER/
</VirtualHost>

<VirtualHost *:443>
    ServerName DOMAIN_PLACEHOLDER
    ServerAlias www.DOMAIN_PLACEHOLDER
    
    DocumentRoot /var/www/viabix
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/DOMAIN_PLACEHOLDER/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/DOMAIN_PLACEHOLDER/privkey.pem
    SSLProtocol TLSv1.2 TLSv1.3
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # PHP-FPM Configuration
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    # Directory Configuration
    <Directory /var/www/viabix>
        AllowOverride All
        Require all granted
        
        # Deny access to .env and other sensitive files
        <FilesMatch "^\.env|^\.git|^composer\.|^package\.json">
            Deny from all
        </FilesMatch>
    </Directory>
    
    # Logging
    ErrorLog /var/log/apache2/viabix-error.log
    CustomLog /var/log/apache2/viabix-access.log combined
</VirtualHost>
EOF

# Replace domain placeholder
sed -i "s/DOMAIN_PLACEHOLDER/$DOMAIN/g" /etc/apache2/sites-available/viabix.conf

# Disable default site and enable viabix
a2dissite 000-default
a2ensite viabix

# Test configuration
apache2ctl configtest

# Restart Apache
systemctl restart apache2

echo -e "${GREEN}✅ VirtualHost configurado${NC}\n"

# ============================================================================
# 11. PHP-FPM OPTIMIZATION
# ============================================================================
echo -e "${BLUE}[11/12]${NC} ${YELLOW}Otimizando PHP-FPM...${NC}"

cat > /etc/php/$PHP_VERSION/fpm/pool.d/viabix.conf << 'EOF'
[viabix]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000
pm.max_requests_grace_period = 30s

request_terminate_timeout = 30s
EOF

# Update php.ini
sed -i 's/memory_limit = .*/memory_limit = 512M/' /etc/php/$PHP_VERSION/fpm/php.ini
sed -i 's/max_execution_time = .*/max_execution_time = 30/' /etc/php/$PHP_VERSION/fpm/php.ini
sed -i 's/upload_max_filesize = .*/upload_max_filesize = 50M/' /etc/php/$PHP_VERSION/fpm/php.ini
sed -i 's/post_max_size = .*/post_max_size = 50M/' /etc/php/$PHP_VERSION/fpm/php.ini

# Restart PHP-FPM
systemctl restart php$PHP_VERSION-fpm

echo -e "${GREEN}✅ PHP-FPM otimizado${NC}\n"

# ============================================================================
# 12. BACKUP CRONJOB
# ============================================================================
echo -e "${BLUE}[12/12]${NC} ${YELLOW}Configurando backups automáticos...${NC}"

# Create backup script
cat > /usr/local/bin/viabix-backup.sh << 'BACKUP_SCRIPT'
#!/bin/bash

BACKUP_DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="/var/backups/viabix/$BACKUP_DATE"
DB_HOST="localhost"
DB_USER="viabix_app"
DB_PASS=$(grep DB_PASS /var/www/viabix/.env | cut -d= -f2)
DB_NAME="viabix_prod"
S3_BUCKET="your-bucket-name"
AWS_REGION="nyc3"  # DigitalOcean Spaces region

mkdir -p $BACKUP_DIR

echo "[$(date)] Starting backup: $BACKUP_DATE"

# Database backup
mysqldump -h $DB_HOST -u $DB_USER -p"$DB_PASS" $DB_NAME | gzip > $BACKUP_DIR/database.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/application.tar.gz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  --exclude=cache \
  /var/www/viabix

# Upload to DigitalOcean Spaces (S3-compatible)
# Requires: apt-get install awscli
aws s3 cp $BACKUP_DIR s3://$S3_BUCKET/backups/$BACKUP_DATE --recursive \
  --endpoint-url https://$AWS_REGION.digitaloceanspaces.com

# Cleanup local (keep 3 days)
find /var/backups/viabix -type d -mtime +3 -exec rm -rf {} \;

echo "[$(date)] Backup completed: $BACKUP_DATE"
BACKUP_SCRIPT

chmod +x /usr/local/bin/viabix-backup.sh

# Create crontab entry
cat >> /var/spool/cron/crontabs/root << 'CRONTAB'

# Viabix system backups - Daily at 2 AM UTC
0 2 * * * /usr/local/bin/viabix-backup.sh >> /var/log/viabix/backup.log 2>&1

# Check healthcheck every 5 minutes
*/5 * * * * curl -f https://DOMAIN_PLACEHOLDER/api/healthcheck > /dev/null 2>&1 || systemctl restart apache2

# Cleanup old logs
0 3 * * * find /var/log/viabix -name "*.log" -mtime +30 -delete
CRONTAB

echo -e "${GREEN}✅ Backups configurados${NC}\n"

# ============================================================================
# SUMMARY
# ============================================================================

echo -e "${GREEN}"
echo "╔════════════════════════════════════════════════════════════════╗"
echo "║  ✅ SETUP COMPLETO!                                            ║"
echo "╚════════════════════════════════════════════════════════════════╝"
echo -e "${NC}\n"

echo -e "${YELLOW}📝 PRÓXIMOS PASSOS:${NC}\n"

echo "1️⃣  CONFIGURAR AMBIENTE"
echo "   nano /var/www/viabix/.env"
echo "   # Preencha valores sensíveis:"
echo "   # - MAIL_SENDGRID_API_KEY"
echo "   # - VIABIX_ASAAS_API_KEY"
echo "   # - SENTRY_DSN"
echo "   # - Outros integração..."
echo ""

echo "2️⃣  TESTAR CONFIGURAÇÃO"
echo "   php /var/www/viabix/api/validate_production_config.php"
echo ""

echo "3️⃣  ACESSAR APLICAÇÃO"
echo "   https://$DOMAIN"
echo ""

echo "4️⃣  CONFIGURAR BACKUPS"
echo "   # Edite e configure DigitalOcean Spaces"
echo "   # Atualize: S3_BUCKET, AWS_CREDENTIALS em /usr/local/bin/viabix-backup.sh"
echo ""

echo "5️⃣  CERTIFICADO SSL"
echo "   Auto-renewal configurado via certbot"
echo "   Certificado válido até: $(certbot certificates 2>/dev/null | grep Expiry || echo 'Check later')"
echo ""

echo -e "${YELLOW}📊 INFORMAÇÕES DO SISTEMA:${NC}\n"

echo "Aplicação: /var/www/viabix"
echo "Logs: /var/log/viabix"
echo "Backups: /var/backups/viabix"
echo "PHP-FPM Socket: /run/php/php8.2-fpm.sock"
echo "Database: viabix_prod (MySQL)"
echo "Cache: Redis (localhost:6379)"
echo ""

echo -e "${YELLOW}🔐 SEGURANÇA:${NC}\n"

echo "✅ HTTPS/TLS ativado"
echo "✅ Firewall (configure via DigitalOcean Console)"
echo "✅ SSH (key-based auth recomendado)"
echo "✅ Backups automáticos (diários 2 AM)"
echo "✅ PHP-FPM isolado (não root)"
echo ""

echo -e "${BLUE}💡 MONITORAMENTO:${NC}\n"

echo "Use DigitalOcean App Platform para monitoring:"
echo "  1. DigitalOcean Console → Monitoring"
echo "  2. Configure alertas para CPU/Memory/Disk"
echo "  3. Integre com Slack/Email"
echo ""

echo -e "${BLUE}🔑 CREDENTIALS (salve em lugar seguro):${NC}\n"

echo "DB Password: $DB_PASSWORD"
echo "Redis Password: $REDIS_PASSWORD"
echo "Encryption Key: $ENCRYPTION_KEY"
echo ""

echo -e "${GREEN}✨ Setup concluído com sucesso!${NC}\n"
