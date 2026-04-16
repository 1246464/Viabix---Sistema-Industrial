# 🌊 VIABIX DIGITALOCEAN DEPLOYMENT

**Seu SaaS pronto para produção em DigitalOcean**

[![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen)](.)
[![License](https://img.shields.io/badge/License-MIT-blue)](.)
[![Support](https://img.shields.io/badge/Support-24%2F7-orange)](.)

---

## 📖 QUICK NAVIGATION

### 🚀 **Iniciante? Comece aqui:**
→ **[DIGITALOCEAN_QUICK_START.md](DIGITALOCEAN_QUICK_START.md)** - 30 minutos setup

### 📚 **Documentação Completa:**
→ **[DIGITALOCEAN_DEPLOYMENT.md](DIGITALOCEAN_DEPLOYMENT.md)** - Guia detalhado com todas as opções

### ✅ **Antes de Produção:**
→ **[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)** - Checklist de validação

### 🛠️ **Precisa de ajuda?**
→ **[PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)** - Troubleshooting + referência técnica

---

## 📁 ARQUIVOS CRIADOS

### Deploy Scripts

```
deploy/
├── digitalocean-setup.sh              ⚡ Setup automático (15 min)
├── digitalocean-preflight-check.sh    ✅ Validação pré-deploy
└── README.md                           📖 Instruções
```

**Use:**
```bash
# SSH para seu Droplet
ssh root@[IP_DO_DROPLET]

# Run automated setup
bash deploy/digitalocean-setup.sh

# OU

# Via curl (one-liner)
curl https://raw.github.com/viabix/viabix/main/deploy/digitalocean-setup.sh | bash
```

### Documentação

```
├── DIGITALOCEAN_QUICK_START.md        ⚡ 30-min start (COMECE AQUI!)
├── DIGITALOCEAN_DEPLOYMENT.md         📖 Guia completo
├── PRODUCTION_DEPLOYMENT.md           📘 Referência técnica
├── DEPLOYMENT_CHECKLIST.md            ✅ Checklist validação
│
└── docker-compose.prod.yml            🐳 Docker option
└── .env.production                    🔑 Template config
```

### Command Line Tools

```bash
# Menu interativo com todas as operações
bash deploy-commander.sh

# Validar antes de produção
bash deploy/digitalocean-preflight-check.sh

# Validar configuração .env
php api/validate_production_config.php
```

---

## 🚀 3 WAYS TO DEPLOY

### **OPÇÃO 1: AUTOMATED (Recomendado - 30 min)**

```bash
# 1. Create Droplet no DigitalOcean Console
#    - Ubuntu 22.04
#    - 2GB RAM / $20/mth
#    - SSH key auth

# 2. Create Managed Database (MySQL)
#    - $15/mth
#    - Standby replica

# 3. Create Spaces (Object Storage)
#    - $5/mth
#    - Para backups

# 4. SSH to Droplet
ssh root@[IP]

# 5. Run setup script
curl https://raw.github.com/viabix/viabix/deploy/digitalocean-setup.sh | bash

# 6. Fill .env values
nano /var/www/viabix/.env

# 7. Validate
php /var/www/viabix/api/validate_production_config.php

# ✅ Done!
```

### **OPÇÃO 2: MANUAL STEP-BY-STEP (1-2 hours)**

Ver [DIGITALOCEAN_DEPLOYMENT.md](DIGITALOCEAN_DEPLOYMENT.md) para instruções passo-a-passo.

### **OPÇÃO 3: DOCKER COMPOSE (20 min)**

```bash
# Instalar Docker
curl -fsSL https://get.docker.com | sh

# Clone e setup
git clone https://github.com/viabix/viabix.git
cd viabix
cp .env.production .env

# Start containers
docker-compose -f docker-compose.prod.yml up -d

# ✅ Done!
```

---

## 💰 CUSTOS (Estimado)

| Componente | Custo | Notas |
|-----------|-------|-------|
| **Droplet 2GB** | $20/mth | App + PHP + Redis |
| **Managed MySQL** | $15/mth | Auto-backup, failover |
| **Spaces 250GB** | $5/mth | S3-compatible backups |
| Floating IP (opt) | $6/mth | Para failover high-availability |
| **TOTAL** | **$40-50/mth** | Altamente escalável |

**Grátis incluído:**
- ✅ Let's Encrypt SSL
- ✅ DigitalOcean monitoring
- ✅ Cloudflare CDN
- ✅ DNS management

---

## ⚡ QUICK START (30 MINUTOS)

### Passo 1: Infrastructure Setup

**DigitalOcean Console:**
```
1. Create Droplet (Ubuntu 22.04, 2GB, SSH key)
2. Create Managed Database (MySQL)
3. Create Spaces (S3-compatible storage)
4. Configure Firewall (22, 80, 443)
5. Setup DNS (A record pointing to Droplet)
```
⏳ **~15 minutos**

### Passo 2: Application Setup

**SSH to Droplet:**
```bash
ssh root@[DROPLET_IP]

# Automated setup
curl https://raw.github.com/viabix/viabix/deploy/digitalocean-setup.sh | bash

# Configure
nano /var/www/viabix/.env

# Validate
php /var/www/viabix/api/validate_production_config.php
```
⏳ **~15 minutos**

### Resultado: ✅ Sistema em produção!

---

## 📊 ARCHITECTURE

```
┌──────────────────────────────────────────────────┐
│         DigitalOcean Infrastructure               │
├──────────────────────────────────────────────────┤
│                                                  │
│  ┌────────────────────┐       Firewall      │
│  │   Droplet 2GB      │◄─(Ports 22,80,443)│
│  │   Ubuntu 22.04     │                     │
│  │ - PHP 8.2                                │
│  │ - Apache 2.4                             │
│  │ - Redis 7                                │
│  └─────┬──────────────┘                     │
│        │                                    │
│        ├─ MySQL Managed DB                 │
│        │  └─ Standby replica              │
│        │  └─ Automated backups            │
│        │                                   │
│        ├─ Spaces (S3-compatible)          │
│        │  └─ Application backups          │
│        │  └─ Static files                 │
│        │                                   │
│        └─ Cloudflare CDN (grátis)         │
│           └─ Cache + DDoS                 │
│                                            │
└──────────────────────────────────────────────────┘
```

---

## ✅ VALIDATION CHECKLIST

Antes de usar em **produção**, verifique:

```bash
# 1. Executar validador automático
php /var/www/viabix/api/validate_production_config.php

# 2. Executar preflight check
bash /var/www/viabix/deploy/digitalocean-preflight-check.sh

# 3. Test endpoint
curl -I https://app.viabix.com.br/api/healthcheck

# 4. Revisar logs
tail -50 /var/log/apache2/viabix-error.log
```

**Esperado: ✅ READY FOR PRODUCTION DEPLOYMENT**

---

## 🔄 MAINTENANCE

### Backups

```bash
# Manual backup
/usr/local/bin/viabix-backup.sh

# Automatico
# Cron runs daily at 2 AM UTC
# Stored in DigitalOcean Spaces

# Restore
aws s3 cp s3://viabix-backups/... . \
  --endpoint-url https://spa.digitaloceanspaces.com
```

### Updates

```bash
# Update code
cd /var/www/viabix && git pull origin main

# Update dependencies  
composer install --no-dev --optimize-autoloader

# Restart services
systemctl restart apache2 php8.2-fpm
```

### Monitoring

```bash
# Real-time logs
tail -f /var/log/viabix/error.log

# Sentry (application errors)
# → https://sentry.io (configure in .env)

# DigitalOcean monitoring
# → Console → Droplet → Monitoring
```

---

## 🆘 TROUBLESHOOTING

### Issue: "Connection refused" to database

```bash
# Test conexão
mysql -h [DB_HOST] -u viabix_app -p

# Verify .env
grep DB_ /var/www/viabix/.env

# Check DigitalOcean firewall
# → Console → Firewalls → Check Droplet has access
```

### Issue: "Service Unavailable" (503)

```bash
# Restart services
systemctl restart apache2 php8.2-fpm

# Check PHP errors
tail -50 /var/log/php8.2-fpm.log

# Check Apache errors
tail -50 /var/log/apache2/viabix-error.log
```

### Issue: High memory/CPU

```bash
# Monitor
top -b -n 1 | head -20

# Check slow queries
mysql -h [HOST] -u [USER] -p -e "SET GLOBAL slow_query_log = 'ON';"

# Increase resources or upgrade Droplet
```

**More:** Veja [TROUBLESHOOTING](PRODUCTION_DEPLOYMENT.md#troubleshooting)

---

## 📞 SUPPORT & RESOURCES

**Documentation:**
- 📖 [Quick Start (30 min)](DIGITALOCEAN_QUICK_START.md)
- 📖 [Full Guide](DIGITALOCEAN_DEPLOYMENT.md)
- 📖 [Checklist](DEPLOYMENT_CHECKLIST.md)
- 📖 [Reference](PRODUCTION_DEPLOYMENT.md)

**DigitalOcean:**
- https://docs.digitalocean.com
- https://docs.digitalocean.com/products/databases/mysql/
- https://docs.digitalocean.com/products/spaces/

**Tools:**
- Sentry: https://sentry.io (errors)
- Cloudflare: https://cloudflare.com (CDN + DDoS)
- SendGrid: https://sendgrid.com (email)

---

## 🎯 NEXT STEPS

## 1️⃣  **Quick Setup** (Recommended)
```bash
→ Follow DIGITALOCEAN_QUICK_START.md
→ ~30 minutes to production
```

## 2️⃣ **Configure & Validate**
```bash
→ Fill .env with your values
→ Run: php api/validate_production_config.php
→ Run: bash deploy/digitalocean-preflight-check.sh
```

## 3️⃣ **Monitor & Maintain**
```bash
→ Watch logs: tail -f /var/log/viabix/error.log
→ Sentry integration: sentry.io
→ DigitalOcean monitoring: built-in
```

## 4️⃣ **Scale As Needed**
```bash
CPU/Memory > 80%? → Upgrade Droplet size
Database slow? → Upgrade DB plan
Need failover? → Add Load Balancer
```

---

## 📝 VERSION INFO

- **Version:** 1.0.0
- **Updated:** April 13, 2024
- **Status:** Production Ready ✅
- **Tested:** Ubuntu 22.04 LTS
- **PHP:** 8.2+
- **MySQL:** 8.0+

---

## 📄 LICENSE

MIT License - Veja LICENSE file

---

**🚀 You're ready to deploy!**

Qualquer dúvida, comece pelo [DIGITALOCEAN_QUICK_START.md](DIGITALOCEAN_QUICK_START.md)
