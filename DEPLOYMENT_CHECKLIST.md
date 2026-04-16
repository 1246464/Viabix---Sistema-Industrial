# 📋 VIABIX PRODUCTION DEPLOYMENT CHECKLIST

Antes de fazer deployment para produção, passe por este checklist item por item.

**Estimado:** 2-4 horas para configuração completa

---

## ✅ PRÉ-DEPLOYMENT (Antes de colocar online)

### 1. Configuração de Ambiente
- [ ] `.env.production` criado e preenchido com todos os valores
- [ ] Nenhum valor contém "CHANGE_ME" no `.env`
- [ ] `.env` possui permissões 0600 (`chmod 400 .env`)
- [ ] `.env` adicionado ao `.gitignore` e commitado com remove `git update-index --assume-unchanged`
- [ ] Validador passou: `php api/validate_production_config.php` ✅

### 2. Database
- [ ] Banco de dados criado no host de produção
- [ ] Usuário MySQL criado com permissões limitadas (não root)
- [ ] Conexão testada: `mysql -h HOST -u USER -p DB < BD/viabix_saas_multitenant.sql`
- [ ] Backup de teste restaurado e funciona
- [ ] Replicação ativada (se usando HA)
- [ ] Slow query log habilitado

### 3. Segurança (CRÍTICO)
- [ ] HTTPS/TLS 1.3+ configurado
- [ ] Certificado SSL válido (não self-signed)
- [ ] HSTS preload iniciado
- [ ] WAF/DDoS ativado (Cloudflare ou AWS)
- [ ] Firewall configurado (porta 443 aberta, outras fechadas)
- [ ] SSH key-based auth configurado (sem senha)
- [ ] 2FA ativado para admin accounts
- [ ] API keys rotacionadas (Sentry, SendGrid, Asaas, AWS)
- [ ] Backup encryption ativado

### 4. Performance & Reliability
- [ ] Redis configurado e testado
- [ ] PHP-FPM tuned (pm.max_children >= 20)
- [ ] Apache/Nginx optimized
- [ ] Static assets compression (gzip)
- [ ] Database query caching ativado
- [ ] CDN configurado (Cloudflare/CloudFront)
- [ ] Load testing feito: `ab -c 100 -n 10000 URL` ✅

### 5. Monitoring & Alerting
- [ ] Sentry iniciado (SENTRY_DSN configurado)
- [ ] New Relic / Datadog integrado (opcional)
- [ ] Log centralization configurada
- [ ] Alertas email/Slack configurados
- [ ] Monitoring dashboard setup
- [ ] Heartbeat monitoring ativado
- [ ] Disk space monitoring configurado

### 6. Backups & Disaster Recovery
- [ ] Backup automático cronjob criado
- [ ] S3 bucket para backups criado
- [ ] Lifecycle policy S3 (retenção 90 dias)
- [ ] Teste de restore feito com sucesso
- [ ] DR plan documentado
- [ ] RTO/RPO targets definidos e documentados

### 7. Infrastructure
- [ ] Servidor dimensionado adequadamente (RAM, CPU, Disk)
- [ ] Bandwidth suficiente disponível
- [ ] DNS propagado (check com `nslookup`)
- [ ] Mail server / SendGrid configurado e testado
- [ ] A/B testing ou blue-green deployment setup (se aplicável)

### 8. CI/CD & Deployment
- [ ] Deployment script criado
- [ ] Git branch strategy documentada (main, develop, feature/*)
- [ ] Rollback procedure documentada
- [ ] Staging environment similar à production
- [ ] Pre-deployment tests automatizados
- [ ] Post-deployment health checks

### 9. Documentation
- [ ] README atualizado com instruções de deploy
- [ ] Runbook criado (o que fazer se tudo quebrar)
- [ ] Architecture diagram documentado
- [ ] Security posture documento de compliance (GDPR/LGPD)
- [ ] Contact list definida (who to call when things break)

### 10. Team Preparation
- [ ] Oncall schedule definida (24/7 se critical)
- [ ] Team treinado em troubleshooting
- [ ] Escalation procedures definidas
- [ ] Communication plan para incidents

---

## 🔄 DEPLOYMENT DAY

### Hora 0: Preparação Final
```bash
# [30 minutos antes]

# Verificar status de todos os serviços
systemctl status apache2 php8.2-fpm redis-server mysql

# Executar validador final
php api/validate_production_config.php

# Coletar baseline de metrics
curl -s https://app.viabix.com.br/api/healthcheck | jq .

# Ativar maintenance mode
touch /var/www/viabix/.maintenance
```

### Hora 1-5min: Blue-Green Deployment (zero downtime)

**Option A: Blue-Green with Nginx**
```bash
# Deploy novo código em /var/www/viabix-green/
cd /var/www/viabix-green
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force

# Teste rápido
curl -s http://localhost:8081/api/healthcheck | jq .

# Switch nginx para green (restart apenas nginx)
# Editar /etc/nginx/sites-enabled/viabix (mudar upstream para gray)
sudo systemctl reload nginx
```

**Option B: Rolling Restart (com downtime mínimo)**
```bash
# Parar servidor
systemctl stop apache2

# Atualizar código
cd /var/www/viabix
git pull origin main
php artisan migrate --force

# Reiniciar
systemctl start apache2
systemctl restart php8.2-fpm
```

### Hora 5-15min: Verification
```bash
# Verificar respostas HTTP
curl -I https://app.viabix.com.br
# Esperado: HTTP/1.1 200 OK

# Verificar certificado SSL
openssl s_client -connect app.viabix.com.br:443 -tls1_3

# Verificar database
curl -s https://app.viabix.com.br/api/healthcheck | jq .database

# Verificar logs
tail -f /var/log/apache2/viabix-access.log
tail -f /var/log/apache2/viabix-error.log

# Smoke test - Login
curl -X POST https://app.viabix.com.br/api/login \
  -d '{"login":"admin","senha":"password"}' \
  -H "Content-Type: application/json"
```

### Hora 15min: Success Celebration 🎉
```bash
# Remover maintenance mode
rm /var/www/viabix/.maintenance

# Enviar notificação de sucesso
curl -X POST https://hooks.slack.com/services/YOUR/WEBHOOK \
  -d '{"text":"✅ Viabix production deployment successful"}'
```

---

## ⚪ PÓS-DEPLOYMENT (Primeiras 24h)

### Hora +1: (Imediatamente após)
- [ ] Monitorar Sentry/New Relic para erros
- [ ] Verificar CPU/Memory no servidor
- [ ] Revisar access logs para anomalias
- [ ] Testar login com múltiplos usuários
- [ ] Testar billing webhook
- [ ] Verificar envio de email

### Hora +4: (4 horas depois)
- [ ] Verificar backup executou com sucesso
- [ ] Rever dashboard de performance
- [ ] Confirmar SSL certificate (valid for 90 days)
- [ ] Rastreabilidade de algumas requisições em logs
- [ ] Testar 2FA com usuário teste

### Hora +24: (Próximo dia)
- [ ] Analisar erros do primeiro dia
- [ ] Revisar padrões de tráfego
- [ ] Confirmar backups rodaram 24 horas
- [ ] Documentar issues encontradas
- [ ] Optimization opportunities

---

## 🆘 ROLLBACK (Se algo quebrou)

### Opção 1: Rollback de Código (Git)
```bash
# Voltar para commit anterior
git revert HEAD
git push origin main

# Restart services
systemctl restart apache2 php8.2-fpm
```

### Opção 2: Voltar do Backup
```bash
# Restaurar database backup
mysql -u viabix_app -p viabix_prod < /var/backups/viabix/latest/database.sql.gz

# Restaurar arquivos
tar -xzf /var/backups/viabix/latest/application.tar.gz -C /var/www/viabix

# Restart
systemctl restart apache2 php8.2-fpm redis-server
```

### Opção 3: Blue-Green Rollback
```bash
# Voltar nginx para blue
sudo systemctl reload nginx

# Investigar o que quebrou no green em /var/www/viabix-green
```

---

## 🔍 TROUBLESHOOTING RÁPIDO

### Problema: "503 Service Unavailable"
```bash
# Verificar se PHP-FPM está rodando
systemctl status php8.2-fpm

# Reiniciar
systemctl restart php8.2-fpm apache2

# Verificar error logs
tail -50 /var/log/apache2/viabix-error.log
```

### Problema: "Database connection refused"
```bash
# Testar conexão
mysql -h prod-mysql.example.com -u viabix_app -p -e "SELECT 1"

# Verificar credenciais em .env
grep DB_ /var/www/viabix/.env

# Verificar firewall/security groups
telnet prod-mysql.example.com 3306
```

### Problema: High CPU/Memory
```bash
# Verificar processos
top -b -n 1 | head -20

# Verificar PHP-FPM
php-fpm -t  # Test config
systemctl status php8.2-fpm

# Aumentar memory/workers em .env
# Restart após mudança
systemctl restart php8.2-fpm
```

### Problema: SSL Certificate Error
```bash
# Verificar certificado
openssl x509 -in /etc/letsencrypt/live/app.viabix.com.br/cert.pem -text -noout

# Renovar se expirado
certbot renew --force-renewal
systemctl reload apache2
```

---

## 📞 EMERGENCY CONTACTS

Se tudo explodir, chame em ordem:

1. **Lead DevOps:** +55 11 98765-4321
2. **Backend Lead:** +55 11 98765-4322
3. **CTO:** +55 11 98765-4323
4. **Hosting Provider Support:** support@example.com (ticket + chat)

**Status Page:** https://status.viabix.com.br  
**Monitoring Dashboard:** https://monitoring.viabix.com.br  
**Incident Log:** https://logs.viabix.com.br

---

## 📊 SUCCESS METRICS

Após 24 horas, verificar:

```
✅ Error Rate: < 1% (baseline)
✅ Response Time: p95 < 500ms, p99 < 1s
✅ Uptime: 99.9% (ou melhor)
✅ SSL Grade: A+ on https://www.ssllabs.com/ssltest/
✅ No critical Sentry errors
✅ Database queries < 100ms
✅ Cache hit rate > 80%
✅ Zero data loss (verify backups)
✅ All customers can login
✅ Billing processing working
```

---

**Last Updated:** April 13, 2024  
**Maintained By:** DevOps Team  
**Questions?** Veja [PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)
