# 🚀 VIABIX PRODUCTION DEPLOYMENT GUIDE

## 📋 Overview

Este guia descreve como configurar o Viabix para produção usando o arquivo `.env.production`. 

**Nível de Criticidade:** ⚠️ **CRÍTICO** - Configuração incorreta pode comprometer segurança e performance.

---

## ✅ PRÉ-REQUISITOS

### Hardware
```
CPU:    2+ cores (t3.medium on AWS, Droplet 2GB on DigitalOcean)
RAM:    2GB mínimo (4GB recomendado)
Disco:  50GB SSD mínimo (fast I/O crucial)
Uptime: Suportar 99.9% (99.9% uptime SLA)
Rede:   10 Mbps mínimo (pode variar conforme usuários)
```

### Software Stack
```
✅ PHP 8.2+ (settings: memory_limit=512M, max_execution_time=30s)
✅ MySQL 8.0 ou MariaDB 10.6+ (UTF-8 mb4 support)
✅ Redis (para cache/sessions/rate-limiting)
✅ OpenSSL 1.1.1+ (para SSL/TLS)
✅ Apache 2.4 ou Nginx 1.24+
```

### Network Requirements
```
✅ HTTPS/TLS 1.3+ obrigatório
✅ Domínio registrado (dns pré-aquecido)
✅ Certificado SSL válido (useLet's Encrypt grátis)
✅ CDN configurado (Cloudflare recomendado)
✅ Firewall/WAF ativado
```

---

## 🔧 CONFIGURAÇÃO PASSO-A-PASSO

### 1. CLONE E PREPARAR AMBIENTE

```bash
# Clone do repositório
git clone https://github.com/viabix/viabix.git /var/www/viabix
cd /var/www/viabix

# Criar arquivo .env a partir do template
cp .env.production .env

# ⚠️ NUNCA fazer commit de .env para git
git update-index --assume-unchanged .env
```

### 2. PREENCHER VALORES SENSÍVEIS

```bash
# Editar .env com seus valores reais
nano .env

# OBRIGATÓRIO - Substituir TODOS os valores "CHANGE_ME":
# - DB_PASS (gerar senha forte: openssl rand -base64 32)
# - MAIL_SENDGRID_API_KEY
# - VIABIX_ASAAS_API_KEY
# - VIABIX_ASAAS_WEBHOOK_TOKEN
# - SENTRY_DSN
# - REDIS_PASSWORD
# - AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY
# - ENCRYPTION_KEY (gerar: openssl rand -base64 32)
# - etc...

# Validar arquivo
php -l .env  # ou usar parser
```

### 3. DATABASE PREPARATION

```bash
# Conectar ao MySQL remoto
mysql -h prod-mysql.example.com -u root -p

# Criar usuário e banco (em production)
CREATE DATABASE viabix_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'viabix_app'@'%' IDENTIFIED BY 'STRONG_PASSWORD_32_CHARS';
GRANT ALL PRIVILEGES ON viabix_prod.* TO 'viabix_app'@'%';
GRANT PROCESS ON *.* TO 'viabix_app'@'%';  -- Para replication
FLUSH PRIVILEGES;

# Importar schema
mysql -h prod-mysql.example.com -u viabix_app -p viabix_prod < /var/www/viabix/BD/viabix_saas_multitenant.sql

# Verificar conexão
php -r "
require 'api/config.php';
try {
    \$pdo->query('SELECT 1');
    echo '✅ Database connected successfully\n';
} catch (PDOException \$e) {
    echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
}
"
```

### 4. PERMISSIONS & FILE STRUCTURE

```bash
# Definir propriedade e permissões
chown -R www-data:www-data /var/www/viabix
chmod -R 755 /var/www/viabix
chmod -R 775 /var/www/viabix/api

# Criar diretórios para logs, cache, uploads
mkdir -p /var/log/viabix /var/cache/viabix /var/uploads/viabix
chown -R www-data:www-data /var/log/viabix /var/cache/viabix /var/uploads/viabix
chmod -R 755 /var/log/viabix /var/cache/viabix /var/uploads/viabix

# Proteger .env
chmod 400 /var/www/viabix/.env
```

### 5. REDIS SETUP (Cache & Sessions)

```bash
# Instalar Redis
# Ubuntu/Debian
apt-cache search redis | grep server
apt-get install redis-server php-redis -y

# Configurar Redis para autoinício
systemctl enable redis-server
systemctl start redis-server
systemctl status redis-server

# Testar conexão
redis-cli -h redis-cache.example.com -p 6379 PING
# Esperado: PONG

# Verificar espaço/uso
redis-cli INFO stats
```

### 6. BACKUPS AUTOMÁTICOS (AWS S3)

```bash
# Instalar AWS CLI
# Ubuntu/Debian
apt-get install awscli -y

# Configurar credenciais
aws configure
# AWS Access Key ID: [cole seu BACKUP_S3_KEY]
# AWS Secret Access Key: [cole seu BACKUP_S3_SECRET]
# Default region: sa-east-1
# Default output: json

# Criar S3 bucket
aws s3 mb s3://viabix-backups --region sa-east-1

# Testar upload
echo "test" | aws s3 cp - s3://viabix-backups/test.txt

# Configurar bucket lifecycle (retenção automática)
aws s3api put-bucket-lifecycle-configuration \
  --bucket viabix-backups \
  --lifecycle-configuration file:///tmp/lifecycle.json
```

Conteúdo de `/tmp/lifecycle.json`:
```json
{
  "Rules": [{
    "Id": "Delete old backups",
    "Status": "Enabled",
    "Prefix": "backups/",
    "Expiration": {"Days": 90}
  }]
}
```

### 7. SSL/TLS CERTIFICATE

```bash
# Instalar Certbot (Let's Encrypt)
apt-get install certbot python3-certbot-apache -y

# Gerar certificado
certbot certonly --apache -d app.viabix.com.br -d api.viabix.com.br

# Auto-renewal
certbot renew --dry-run
systemctl enable certbot.timer
```

### 8. WEB SERVER CONFIGURATION

#### Apache VirtualHost
```apache
<VirtualHost *:443>
    ServerName app.viabix.com.br
    ServerAlias api.viabix.com.br www.viabix.com.br
    
    DocumentRoot /var/www/viabix
    
    # SSL/TLS
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/app.viabix.com.br/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/app.viabix.com.br/privkey.pem
    SSLProtocol TLSv1.2 TLSv1.3
    SSLCipherSuite HIGH:!aNULL:!MD5
    SSLHonorCipherOrder on
    
    # Performance
    <FilesMatch "\.php$">
        SetHandler "proxy:unix:/run/php/php8.2-fpm.sock|fcgi://localhost"
    </FilesMatch>
    
    # Security Headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Logging
    ErrorLog /var/log/apache2/viabix-error.log
    CustomLog /var/log/apache2/viabix-access.log combined
    
    # .htaccess override
    <Directory /var/www/viabix>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

# HTTP to HTTPS redirect
<VirtualHost *:80>
    ServerName app.viabix.com.br
    ServerAlias *.viabix.com.br
    Redirect permanent / https://app.viabix.com.br/
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name app.viabix.com.br api.viabix.com.br;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name app.viabix.com.br api.viabix.com.br;
    root /var/www/viabix;
    index index.php;
    
    # SSL
    ssl_certificate /etc/letsencrypt/live/app.viabix.com.br/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.viabix.com.br/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Security Headers
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-Frame-Options "DENY" always;
    
    # Logging
    access_log /var/log/nginx/viabix-access.log;
    error_log /var/log/nginx/viabix-error.log;
    
    # PHP FPM
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 9. PHP-FPM TUNING

```ini
[viabix]
user = www-data
group = www-data
listen = /run/php/php8.2-fpm.sock

; Performance
pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 20
pm.max_requests = 1000
pm.max_requests_grace_period = 30s

; Limits
request_terminate_timeout = 30s
max_requests = 100000

; Logging
slowlog = /var/log/php8.2-fpm-slow.log
request_slowlog_timeout = 5s
```

### 10. CRON JOBS (Backups, Reports, Cleanup)

```bash
# Editar crontab
crontab -e

# Adicionar jobs:

# Backup diário às 2 AM
0 2 * * * /usr/local/bin/viabix-backup.sh >> /var/log/viabix/backup.log 2>&1

# Limpeza de logs antigos
0 3 * * * find /var/log/viabix -name "*.log" -mtime +30 -delete

# Limpeza de sessões expiradas
0 */6 * * * redis-cli -h redis-cache.example.com FLUSHDB >> /var/log/viabix/redis-cleanup.log 2>&1

# Verificação de saúde (healthcheck)
*/5 * * * * curl -f https://api.viabix.com.br/api/healthcheck || systemctl restart apache2

# Limpeza de arquivos temporários
0 4 * * * find /var/uploads/viabix -type f -mtime +7 -delete
```

Script de backup `/usr/local/bin/viabix-backup.sh`:
```bash
#!/bin/bash

BACKUP_DATE=$(date +%Y-%m-%d_%H-%M-%S)
BACKUP_DIR="/var/backups/viabix/$BACKUP_DATE"
DB_HOST="prod-mysql.example.com"
DB_USER="viabix_app"
DB_PASS="YOUR_DB_PASSWORD"
DB_NAME="viabix_prod"
S3_BUCKET="viabix-backups"

mkdir -p $BACKUP_DIR

# Database backup
mysqldump -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/database.sql.gz

# Application files backup
tar -czf $BACKUP_DIR/application.tar.gz \
  --exclude=node_modules \
  --exclude=vendor \
  --exclude=.git \
  --exclude=cache \
  /var/www/viabix

# Upload to S3
aws s3 cp $BACKUP_DIR s3://$S3_BUCKET/backups/$BACKUP_DATE --recursive

# Cleanup local (keep 3 days)
find /var/backups/viabix -type d -mtime +3 -exec rm -rf {} \;

echo "Backup completed: $BACKUP_DATE"
```

### 11. MONITORING & ALERTING

```bash
# Instalar Datadog Agent (opcional, mas recomendado)
DD_AGENT_MAJOR_VERSION=7 DD_ENV=production \
  bash -c "$(curl -L https://s3.amazonaws.com/dd-agent/scripts/install_agent.sh)"

# OU Instalar New Relic PHP Agent
apt-get install newrelic-php5 -y
newrelic-install install

# OU usar Sentry (já configurado em SENTRY_DSN)
```

### 12. TESTING PRÉ-PRODUÇÃO

```bash
# Validar configuração PHP
php -r "echo phpinfo();" | grep -E "(memory_limit|max_execution_time|upload_max_filesize)"

# Testar conexão com todas as dependências
php /var/www/viabix/api/healthcheck.php

# Verificar permissões de arquivo
ls -la /var/www/viabix/.env

# Teste de carga (Apache Bench)
ab -c 100 -n 10000 https://app.viabix.com.br/

# Teste de SSL
curl -I --tlsv1.3 https://app.viabix.com.br

# Teste Security Headers
curl -I https://app.viabix.com.br | grep -E "(Strict-Transport-Security|X-Content-Type)"
```

### 13. DEPLOYMENT

```bash
# Option 1: Direct deployment
cd /var/www/viabix
git pull origin main

# Option 2: Blue-Green deployment (zero downtime)
# Ver: deploy/blue-green-deployment.sh

# Option 3: Container deployment (Docker)
# Ver: docker-compose.yml

# Post-deployment checks
php artisan migrate --force
php artisan cache:clear
php artisan config:cache
systemctl restart apache2 php8.2-fpm redis-server
```

---

## 📊 MONITORING CHECKLIST

```
✅ Database: queries < 100ms, connections pooled
✅ Redis: uptime > 99.9%, memory usage < 80%
✅ CPU: usage < 70% average
✅ Memory: usage < 80%
✅ Disk: usage < 85% (alert at 80%)
✅ Error Rate: < 1% of requests
✅ Response Time: p95 < 500ms, p99 < 1s
✅ SSL/TLS: A+ rating on SSL Labs
✅ Backups: daily, tested restoration monthly
✅ Logs: centralized, rotated, searchable
```

---

## 🛡️ SECURITY CHECKLIST

```
✅ HTTPS/TLS 1.3+ enabled
✅ HSTS preload submitted
✅ WAF rules configured (Cloudflare/AWS)
✅ DDoS protection active
✅ Rate limiting enabled
✅ 2FA required for admin accounts
✅ API keys rotated every 90 days
✅ Database encrypted at rest
✅ Backups encrypted
✅ Firewall rules restrictive
✅ SSH key-based auth only
✅ sudo access logged
✅ Failed login attempts logged
✅ Penetration testing done
✅ GDPR/LGPD compliance verified
```

---

## 🚨 TROUBLESHOOTING

### Issue: Database connection timeout
```bash
# Check database server status
mysql -h prod-mysql.example.com -u viabix_app -p -e "SELECT 1"

# Check firewall rules
telnet prod-mysql.example.com 3306

# Check .env DB_CONNECT_TIMEOUT setting
```

### Issue: High memory usage
```bash
# Check PHP-FPM processes
ps aux | grep php-fpm | wc -l

# Check Redis memory
redis-cli INFO memory

# Clear cache
redis-cli FLUSHALL
```

### Issue: Slow queries
```bash
# Enable slow query log
SET GLOBAL slow_query_log = 'ON';

# Check slow log
SHOW PROCESSLIST;

# Analyze query plan
EXPLAIN SELECT ...;
```

---

## 📞 SUPPORT & DOCUMENTATION

- **Docs:** https://docs.viabix.com.br
- **Status:** https://status.viabix.com.br
- **Support:** support@viabix.com.br
- **Emergency:** +55 (11) 98765-4321

---

**Last Updated:** April 13, 2024  
**Version:** 1.0.0  
**Maintained By:** DevOps Team
