# 🌊 VIABIX DIGITALOCEAN DEPLOYMENT GUIDE

**Nível:** Intermediário | **Tempo:** 1-2 horas | **Custo:** $20-30/mês

---

## 📊 ARQUITETURA RECOMENDADA

```
┌─────────────────────────────────────────────────────────┐
│         DigitalOcean Infrastructure                     │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────────────┐        Firewall              │
│  │  Droplet 2GB     │◄─────────────────────────────┤
│  │  Ubuntu 22.04    │  - Port 80/443 (HTTP/HTTPS) │
│  │  - PHP 8.2       │  - Port 22 (SSH)            │
│  │  - Apache 2.4    │                             │
│  │  - Redis         │                             │
│  └──────┬───────────┘                              │
│         │                                          │
│         ├─ MySQL Managed Database                  │
│         │  └─ Backups automáticos                  │
│         │                                          │
│         ├─ DigitalOcean Spaces (S3-compatible)     │
│         │  └─ Application backups                  │
│         │                                          │
│         └─ CDN (Cloudflare grátis)                 │
│            └─ Cache + DDoS protection              │
│                                                     │
└─────────────────────────────────────────────────────────┘
```

---

## 💾 COMPONENTES & CUSTOS

| Componente | Tipo | Custo | Notas |
|-----------|------|-------|-------|
| **Droplet** | 2GB RAM, 1 vCPU, 50GB SSD | $20/mês | Aplicação + Redis + PHP-FPM |
| **Managed MySQL DB** | Basic (Standby), 1GB | $15/mês | Backups automáticos, alta disponibilidade |
| **Spaces Storage** | 250GB | $5/mês | S3-compatible, backups + static files |
| **Floating IP** (opcional) | Static IP | $6/mês | Para failover (não necessário inicialmente) |
| **Monitoring** | Nativo | Grátis | DigitalOcean integrado |
| **CDN** | Cloudflare | Grátis | Cache + DDoS protection |
| **SSL Certificate** | Let's Encrypt | Grátis | Via Certbot automático |
| **TOTAL** | | **$40-50/mês** | Altamente escalável |

---

## 🚀 PASSO-A-PASSO SETUP

### FASE 1: CREATE INFRASTRUCTURE (No DigitalOcean Console)

#### 1.1 Create Droplet

```
1. DigitalOcean Console → Droplets → Create Droplet

2. Choose Image: Ubuntu 22.04 LTS x64

3. Choose Plan:
   ✅ RECOMMENDED: Basic - $20/month
   - 2 GB Memory
   - 1 vCPU
   - 50 GB SSD Disk

4. Choose Datacenter Region:
   São Paulo (syd-1)
   
5. Authentication:
   ☑️ SSH key
   (Create new or use existing)
   
6. Hostname: viabix-app-01

7. Tags: production viabix

8. Click "Create Droplet"

⏳ Aguarde ~60 segundos para criação
```

**Guarde:** IP do Droplet (ex: 192.0.2.123)

#### 1.2 Create Managed MySQL Database

```
1. DigitalOcean Console → Databases → Create Database

2. Choose Engine: MySQL 8.0

3. Choose Plan:
   ✅ RECOMMENDED: Basic - $15/month
   - Standby Replica
   - 1 GB RAM
   - Automatic backups

4. Choose Region:
   São Paulo (spa-1) - MESMO region do Droplet!

5. Name: viabix-prod-mysql

6. Click "Create Database Cluster"

⏳ Aguarde ~5 minutos

7. Após criação:
   - Copie "Connection String"
   - Copie "Host", "Port", "User", "Password"
```

**Guarde:** Credenciais MySQL completas

#### 1.3 Create Spaces (Object Storage)

```
1. DigitalOcean Console → Spaces → Create Space

2. Choose Region: São Paulo (spa)

3. Name: viabix-backups

4. Click "Create Space"

5. Generate API Key:
   - Account → API → Tokens/Keys
   - Generate New Spaces Key
   - Salve "Key" e "Secret"
```

**Guarde:** Spaces API credentials

#### 1.4 Configure Firewall

```
1. DigitalOcean Console → Networking → Firewalls

2. Create Firewall Inbound Rules:
   ✅ SSH (22)          - Your IP only
   ✅ HTTP (80)         - All IPs (0.0.0.0/0)
   ✅ HTTPS (443)       - All IPs (0.0.0.0/0)
   
3. Outbound Rules:
   ✅ Allow All (default)
   
4. Apply to:
   - Select seu Droplet
   - Select MySQL Database
```

#### 1.5 Configure DNS

```
1. Registrar seu domínio (ex: GoDaddy, Namecheap)

2. Apoint nameservers para DigitalOcean:
   ns1.digitalocean.com
   ns1.digitalocean.com
   ns3.digitalocean.com

3. DigitalOcean Console → Networking → Domains

4. Add Domain: app.viabix.com.br

5. Create A Record:
   - Name: app
   - Type: A
   - Value: [IP do seu Droplet]
   - TTL: 3600

6. Create CNAME (opcional para www):
   - Name: www
   - Type: CNAME
   - Value: app.viabix.com.br
   - TTL: 3600

7. Espere propagação (2-48 horas)

Teste: nslookup app.viabix.com.br
```

---

### FASE 2: SETUP APPLICATION

#### 2.1 Connect to Droplet via SSH

```bash
# A partir do seu computador local

# SSH via key
ssh root@[DROPLET_IP]

# Ou customizado se uso nome
ssh root@viabix-app-01.example.com
```

#### 2.2 Run Automated Setup Script

```bash
# Opção A: Debug (interativo)
curl https://raw.githubusercontent.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash

# Opção B: Silent (background)
curl -s https://raw.githubusercontent.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash &

# Monitor progress
tail -f /var/log/syslog
```

**O script automaticamente:**
- ✅ Atualiza sistema (apt update/upgrade)
- ✅ Instala PHP 8.2 + extensões
- ✅ Instala Apache 2.4
- ✅ Instala Redis
- ✅ Instala Composer
- ✅ Clona repositório Viabix
- ✅ Configura .env
- ✅ Configura VirtualHost Apache
- ✅ Instala SSL (Let's Encrypt)
- ✅ Otimiza PHP-FPM
- ✅ Setup cronjob backups

**Duração:** ~10-15 minutos

#### 2.3 Manual Configuration (Alternative)

Se preferir controle total, faça manualmente:

```bash
# 1. SSH Into Droplet
ssh root@[DROPLET_IP]

# 2. Update System
apt-get update && apt-get upgrade -y

# 3. Install PHP
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get install -y php8.2-fpm php8.2-cli php8.2-mysql \
    php8.2-redis php8.2-curl php8.2-gd php8.2-xml \
    php8.2-mbstring php8.2-json php8.2-bcmath \
    apache2 redis-server composer certbot python3-certbot-apache

# 4. Enable Apache modules
a2enmod rewrite proxy proxy_fcgi setenvif ssl headers http2

# 5. Clone Application
git clone https://github.com/viabix/viabix.git /var/www/viabix
cd /var/www/viabix

# 6. Setup .env
cp .env.production .env
nano .env  # Edit with your values
chmod 400 .env

# 7. Configure Database Connection
# Edite .env com credenciais do Managed Database
DB_HOST=viabix-prod-mysql-xxx.c.db.ondigitalocean.com
DB_NAME=viabix_prod
DB_USER=viabix_app
DB_PASS=[senha do managed database]

# 8. Create Apache VirtualHost
# Ver config/apache-viabix.conf

# 9. Install SSL
certbot certonly --apache -d app.viabix.com.br

# 10. Start Services
systemctl restart apache2 php8.2-fpm
```

---

### FASE 3: CONFIGURE DATABASE

#### 3.1 Test Connection

```bash
# Testar acesso ao Managed Database
mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME]

# Se conectou, execute:
mysql> SELECT 1;
# Esperado: 1
```

#### 3.2 Import Schema

```bash
# Via SSH no Droplet:
mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < \
    /var/www/viabix/BD/viabix_saas_multitenant.sql

# Verificar tabelas criadas:
mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] -e "SHOW TABLES;"
```

#### 3.3 Create Backup

```bash
# Criar primeiro backup
mysqldump -h [DB_HOST] -u [DB_USER] -p [DB_NAME] > \
    /tmp/viabix-initial-backup.sql.gz
```

---

### FASE 4: CONFIGURE BACKUPS

#### 4.1 Setup DigitalOcean Spaces (S3)

```bash
# Instalar AWS CLI (no Droplet)
apt-get install -y awscli

# Configurar credentials
aws configure

# When prompted:
# AWS Access Key ID: [seu Spaces Key]
# AWS Secret Access Key: [seu Spaces Secret]
# Default region: [spa] (São Paulo)
# Default output format: json
```

#### 4.2 Create Backup Script

```bash
# Editar /usr/local/bin/viabix-backup.sh

# Substituir:
S3_BUCKET="viabix-backups"
AWS_REGION="spa"
--endpoint-url https://spa.digitaloceanspaces.com

# Testar:
/usr/local/bin/viabix-backup.sh

# Verificar S3
aws s3 ls s3://viabix-backups--endpoint-url https://spa.digitaloceanspaces.com
```

#### 4.3 Enable Automated Backups

```bash
# Editar crontab
crontab -e

# Adicionar:
0 2 * * * /usr/local/bin/viabix-backup.sh >> /var/log/viabix/backup.log 2>&1
```

---

### FASE 5: VALIDATION & LAUNCH

#### 5.1 Run Configuration Validator

```bash
# No Droplet:
ssh root@[DROPLET_IP]
php /var/www/viabix/api/validate_production_config.php

# Esperado: ✅ READY FOR PRODUCTION DEPLOYMENT
```

#### 5.2 Health Check

```bash
# Testar endpoint
curl -I https://app.viabix.com.br/api/healthcheck

# Esperado HTTP 200

# Com curl verbose para mais detalhes:
curl -v https://app.viabix.com.br/api/healthcheck
```

#### 5.3 SSL Configuration

```bash
# Verificar certificado SSL
curl https://app.viabix.com.br --insecure -v 2>&1 | grep SSL

# Check expiration
certbot certificates

# Auto-renewal validation
certbot renew --dry-run
```

#### 5.4 Performance Baseline

```bash
# Apache Bench Load Test
ab -c 100 -n 10000 https://app.viabix.com.br/

# Esperado:
# Requests per second:   [depends on plan]
# Time per request:      [should be < 500ms]
# Failed requests:       0
```

---

## 📊 MONITORAMENTO

### DigitalOcean Native Monitoring

```
DigitalOcean Console → Monitoring

Métricas disponíveis:
✅ CPU Usage
✅ Memory Usage
✅ Bandwidth
✅ Disk Read/Write
✅ Network I/O

Configure alertas para:
- CPU > 80%
- Memory > 85%
- Disk > 90%
```

### Application Monitoring

```bash
# Logs em tempo real
ssh root@[DROPLET_IP]

# Apache access logs
tail -f /var/log/apache2/viabix-access.log

# PHP error logs
tail -f /var/log/php8.2-fpm.log

# Application error logs
tail -f /var/log/viabix/error.log

# System syslog
tail -f /var/log/syslog
```

### Sentry Integration

```
1. Criar conta em https://sentry.io

2. Criar projeto para Viabix

3. Copie DSN:
   https://key@sentry.io/project-id

4. No Droplet, edite /var/www/viabix/.env:
   SENTRY_DSN=https://key@sentry.io/project-id

5. Restart Apache:
   systemctl restart apache2
```

---

## 🔄 SCALING (Quando crescer)

### Upgrade Droplet

```
1. DigitalOcean Console → Droplet → Resize

2. Escolha plano maior (4GB RAM):
   $40/mês em vez de $20/mês

3. Redimensionar (requer downtim ~60s)
```

### Scale Database

```
1. DigitalOcean Console → Database → Resize

2. Upgrade para plan maior com Replica
```

### Add Load Balancer

```
Quando tiver múltiplos Droplets:

1. DigitalOcean Console → Networking → Load Balancers

2. Create Load Balancer:
   - Region: São Paulo
   - Select múltiplos Droplets
   - Health check path: /api/healthcheck
```

### Managed CDN

```
Usar DigitalOcean CDN (integrado com Spaces):

1. DigitalOcean Console → Spaces → [seu space]
2. Enable CDN
3. Configurar CNAME no domínio
```

---

## 🛠️ MAINTENANCE

### Backup & Restore

```bash
# Listar backups
aws s3 ls s3://viabix-backups --endpoint-url https://spa.digitaloceanspaces.com

# Download backup
aws s3 cp s3://viabix-backups/backups/2024-04-13_02-00-00/database.sql.gz . \
    --endpoint-url https://spa.digitaloceanspaces.com

# Restaurar database
gunzip database.sql.gz
mysql -h [DB_HOST] -u [DB_USER] -p [DB_NAME] < database.sql
```

### SSL Renewal

```bash
# Automático via cron (certbot cuida)

# Manual renewal
certbot renew --force-renewal

# Check próxima expiração
certbot certificates
```

### Update Application

```bash
ssh root@[DROPLET_IP]
cd /var/www/viabix

git pull origin main

# Se houver schema changes:
# [Seu script de migration aqui]

systemctl restart apache2
```

---

## 📞 TROUBLESHOOTING

### "Can't connect to database"

```bash
# Test connection
mysql -h [DB_HOST] -u [DB_USER] -p

# Check firewall rules
# DigitalOcean Console → Firewalls
# Certifique que Managed Database tem acesso

# Verify .env values
grep DB_ /var/www/viabix/.env
```

### "High memory usage"

```bash
# Check processes
ssh root@[DROPLET_IP]
top

# Check PHP processes
ps aux | grep php-fpm | wc -l

# Reduce PHP-FPM workers if needed
# Edit: /etc/php/8.2/fpm/pool.d/viabix.conf
# Change: pm.max_children = 30 (from 50)
systemctl restart php8.2-fpm
```

### "SSL certificate error"

```bash
# Verify certificate
curl -I https://app.viabix.com.br

# Check expiration
certbot certificates

# Renewal test
certbot renew --dry-run

# Force renewal if needed
certbot renew --force-renewal
```

### "Backup failed"

```bash
# Check S3 credentials
aws s3 ls --endpoint-url https://spa.digitaloceanspaces.com

# Test backup script manually
/usr/local/bin/viabix-backup.sh

# Check logs
tail -f /var/log/viabix/backup.log
```

---

## 💰 COST OPTIMIZATION

### Initial Setup ($40-50/mês)
```
Droplet 2GB:          $20/mês
Managed MySQL:        $15/mês
Spaces (250GB):       $5/mês  
Floating IP:          $6/mês (opcional)
-----------
TOTAL                 $40-46/mês
```

### Cost Saving Tips
```
✅ Use Basic (Standby) database ao invés de HA ($10 vs $20)
✅ Use Standard SSD ao invés de Premium
✅ Disable Floating IP se não precisa failover
✅ Cloudflare grátis para CDN/DDoS
✅ Let's Encrypt grátis para SSL
✅ DigitalOcean monitoring grátis
```

### When to Upgrade
```
CPU > 80% consistently:    Upgrade Droplet size
Memory > 85% consistently: Upgrade Droplet RAM
Database slow:             Upgrade DB plan size
Storage > 80% used:        Upgrade Spaces
```

---

## 📚 USEFUL LINKS

- **DigitalOcean Docs:** https://docs.digitalocean.com
- **Managed Databases:** https://docs.digitalocean.com/products/databases/mysql/
- **Spaces Guide:** https://docs.digitalocean.com/products/spaces/
- **App Platform:** https://docs.digitalocean.com/products/app-platform/
- **Let's Encrypt:** https://letsencrypt.org
- **Sentry Setup:** https://docs.sentry.io

---

**Last Updated:** April 13, 2024  
**Version:** 1.0  
**Maintained By:** Viabix DevOps  
**Support:** support@viabix.com.br
