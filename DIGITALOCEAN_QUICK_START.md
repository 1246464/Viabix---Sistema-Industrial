# 🚀 VIABIX DIGITALOCEAN - 30 MINUTE QUICK START

**Tempo:** ~30 minutos | **Dificuldade:** Fácil | **Custo:** $40-50/mês

---

## OPÇÃO 1️⃣: AUTOMATED SETUP (Recomendado - 10 minutos)

### Passo 1: Criar Droplet

```
🌊 DigitalOcean Console → Droplets → Create Droplet

✅ Image: Ubuntu 22.04 LTS x64
✅ Plan: Basic ($20/mês)
   - 2GB Memory / 1vCPU / 50GB SSD
✅ Region: São Paulo (spa)
✅ Authentication: SSH key (crie uma nova se não tiver)
✅ Hostname: viabix-app-01
✅ Click "Create Droplet"

⏳ Aguarde ~60 segundos
⚠️  COPIE O IP: [ex: 192.0.2.123]
```

### Passo 2: Create Managed Database

```
🌊 DigitalOcean Console → Databases → Create Database

✅ Engine: MySQL 8.0
✅ Plan: Basic ($15/mês) com Standby Replica
✅ Region: São Paulo (MESMO do Droplet!)
✅ Name: viabix-prod-mysql
✅ Click "Create Database Cluster"

⏳ Aguarde ~5 minutos

⚠️  COPIE: Host, Port, User, Password
    (Password será usada no .env)
```

### Passo 3: Create S3 Storage (Spaces)

```
🌊 DigitalOcean Console → Spaces → Create Space

✅ Name: viabix-backups
✅ Region: São Paulo (spa)
✅ Click "Create"

Generate Spaces API Key:
✅ Console → Account → API
✅ "Spaces Keys" → "Generate New Key"

⚠️  COPIE: Key e Secret para .env
```

### Passo 4: Connect & Run Setup

```bash
# No seu computador local:

# SSH para Droplet
ssh root@[IP_DO_DROPLET]

# Uma vez conectado, execute o setup automatizado:
curl -s https://raw.githubusercontent.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash

# ⏳ Aguarde ~15 minutos (o script faz tudo)
# ✅ No final, o script irá imprimir instruções
```

### Passo 5: Configure .env

```bash
# No Droplet SSH:

nano /var/www/viabix/.env

# Edite valores essenciais:

APP_URL=https://app.viabix.com.br      # Use seu domínio

DB_HOST=[HOST_DA_MANAGED_DB]            # Ex: viab-xxx.c.db.ondigitalocean.com
DB_NAME=viabix_prod                     # Mesmo da criação
DB_USER=viabix_app                      # Mesmo da criação
DB_PASS=[DB_PASSWORD]                   # Mesma senha da criação

REDIS_HOST=localhost                    # Instalado via script
REDIS_PASSWORD=[gerar seguro]           # Mude de CHANGE_ME

# Email (SendGrid recomendado):
MAIL_PROVIDER=sendgrid
MAIL_SENDGRID_API_KEY=[sua_chave_api]   # Crie em sendgrid.com

# Billing (Asaas):
VIABIX_ASAAS_API_KEY=[sua_api_key]
VIABIX_ASAAS_WEBHOOK_TOKEN=[seu_token]

# Monitoring (Sentry):
SENTRY_DSN=[seu_dsn]                    # Crie em sentry.io

# S3 Spaces (para backups):
BACKUP_S3_BUCKET=viabix-backups
BACKUP_S3_REGION=spa                    # DigitalOcean region
BACKUP_S3_KEY=[seu_spaces_key]
BACKUP_S3_SECRET=[seu_spaces_secret]

# Salvar: CTRL+O, ENTER, CTRL+X
```

### Passo 6: Teste & Valide

```bash
# No Droplet:

# 1. Validar configuração
php /var/www/viabix/api/validate_production_config.php

# Esperado: 🚀 READY FOR PRODUCTION DEPLOYMENT

# 2. Config DNS
# DigitalOcean Console → Networking → Domains
# Add: app.viabix.com.br (A record → IP do Droplet)

# 3. Teste acesso
curl https://app.viabix.com.br/api/healthcheck

# 4. Logs em tempo real
tail -f /var/log/apache2/viabix-error.log
```

---

## OPÇÃO 2️⃣: DOCKER COMPOSE (20 minutos)

Se preferir containerização:

### Passo 1-3: Mesmo acima (Droplet + MySQL + Spaces)

### Passo 4: SSH e Install Docker

```bash
ssh root@[IP_DO_DROPLET]

# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Docker Compose
curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
```

### Passo 5: Clone & Setup

```bash
git clone https://github.com/viabix/viabix.git /var/www/viabix
cd /var/www/viabix

# Criar .env
cp .env.production .env
nano .env  # Edite valores

# Criar dirs de logs
mkdir -p logs/apache logs/mysql logs/php

# Iniciar containers
docker-compose -f docker-compose.prod.yml up -d

# Verificar status
docker-compose ps

# Ver logs
docker-compose logs -f app
```

---

## ❌ PROBLEMAS COMUNS & SOLUÇÕES

### Problema: "Connection refused" ao conectar BD

```bash
# Verificar conectividade
mysql -h [DB_HOST] -u viabix_app -p

# DigitalOcean Managed Database:
# 1. Copie a connection string correta do console
# 2. Verifique firewall rules:
#    Console → Firewalls → Certifique Droplet tem acesso
```

### Problema: SSL certificate not valid

```bash
# O script deveria ter criado automaticamente

# Se não criou:
ssh root@[IP]

certbot certonly --apache \
  -d app.viabix.com.br \
  -d www.viabix.com.br \
  --non-interactive \
  --agree-tos \
  --email admin@viabix.com.br

# Reiniciar Apache
systemctl restart apache2
```

### Problema: PHP memory exceeded

```bash
# Aumentar em .env / php.ini
PHP_MEMORY_LIMIT=512M

# Depois:
systemctl restart php8.2-fpm
```

---

## 📊 COMO MONITORAR

### Dashboard DigitalOcean (Grátis)

```
Console → Your Droplet → Monitoring

Ver em tempo real:
✅ CPU Usage
✅ Memory Usage
✅ Disk I/O
✅ Network
✅ Bandwidth
```

### Logs da Aplicação

```bash
ssh root@[IP]

# Apache access
tail -f /var/log/apache2/viabix-access.log

# Errors
tail -f /var/log/apache2/viabix-error.log

# PHP
tail -f /var/log/php8.2-fpm.log

# Application
tail -f /var/log/viabix/error.log
```

### Sentry (Errors)

```
1. Criar conta em sentry.io
2. Criar novo projeto
3. Copie DSN em .env: SENTRY_DSN=
4. Reiniciar: systemctl restart apache2
5. Errors aparecem automaticamente em sentry.io
```

---

## 🔄 BACKUP & RESTORE

### Criar Backup Manual

```bash
ssh root@[IP]

/usr/local/bin/viabix-backup.sh

# Verificar em S3
aws s3 ls s3://viabix-backups \
  --endpoint-url https://spa.digitaloceanspaces.com \
  --recursive
```

### Restaurar Database

```bash
# Download backup do Spaces
aws s3 cp s3://viabix-backups/backups/YYYY-MM-DD_HH-MM-SS/database.sql.gz . \
  --endpoint-url https://spa.digitaloceanspaces.com

# Descompactar
gunzip database.sql.gz

# Restaurar (isso sobrescreve o DB atual!)
mysql -h [DB_HOST] -u viabix_app -p viabix_prod < database.sql
```

---

## 🚀 FIRST STEPS IN PRODUCTION

### Task List (24 horas)

- [ ] Adicionar admin user:
  ```bash
  # Executar script admin creation
  php /var/www/viabix/api/create_admin.php
  ```

- [ ] Testar login:
  ```
  https://app.viabix.com.br/login
  Admin / password (padrão temporário)
  ```

- [ ] Configurar 2FA para admin

- [ ] Testar pagamento:
  ```bash
  # Não em produção diretamente!
  # Usar sandbox/test mode do Asaas
  ```

- [ ] Monitorar erros:
  ```
  Sentry.io → Check for errors
  ```

- [ ] Testar backup:
  ```bash
  # Rodar manualmente
  /usr/local/bin/viabix-backup.sh
  # Verificar download do backup
  ```

---

## 💰 CUSTO TOTAL

```
Droplet 2GB:              $20/mês
Managed MySQL:            $15/mês  
Spaces 250GB:             $5/mês
Floating IP (opt):        $6/mês
---
TOTAL:                    $40-46/mês
```

**Incluso grátis:**
- Let's Encrypt SSL
- DigitalOcean Monitoring
- Backups automáticos (MySQL)
- Cloudflare CDN (grátis)

---

## ✅ POST-DEPLOYMENT CHECKLIST

```
✅ DNS propagado (nslookup app.viabix.com.br)
✅ HTTPS funciona (curl -I https://app.viabix.com.br)
✅ API responde (/api/healthcheck)
✅ Database conecta (mysql -h HOST -u USER)
✅ Backups rodando (/usr/local/bin/viabix-backup.sh)
✅ Logs sendo coletados
✅ Monitoring ativo (DigitalOcean + Sentry)
✅ Email funciona (teste SendGrid)
✅ Admin pode fazer login
✅ SSL válido (certbot certificates)
```

---

## 📞 SUPORTE

**Docs:** https://docs.digitalocean.com  
**SSH Access Troubleshooting:** https://docs.digitalocean.com/products/droplets/resources/troubleshoot-access/  
**Managed MySQL:** https://docs.digitalocean.com/products/databases/mysql/  

---

**Pronto para produção! 🎉**

Próximas otimizações:
- [ ] Configurar CDN Cloudflare
- [ ] Add Load Balancer (se múltiplos Droplets)
- [ ] Monitoring 24/7 via Ops
- [ ] Disaster Recovery plano
