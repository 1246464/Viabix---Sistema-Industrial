# Deploy Hetzner

## Stack recomendada

- Ubuntu 24.04 LTS
- Nginx ou Apache
- PHP 8.2 FPM
- MariaDB 10.11+
- Certbot para TLS
- Cloudflare opcional na borda

## Estrutura sugerida

- Código: `/var/www/viabix`
- Nginx: `/etc/nginx/sites-available/viabix`
- Apache vhost: `/etc/apache2/sites-available/viabix.conf`
- PHP overrides: `/etc/php/8.2/fpm/conf.d/99-viabix.ini`
- Logs da aplicação: `/var/www/viabix/logs`

## Provisionamento base

```bash
sudo apt update
sudo apt install -y nginx php8.2-fpm php8.2-mysql php8.2-gd php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip mariadb-server certbot python3-certbot-nginx unzip
```

## Banco de dados

```sql
CREATE DATABASE viabix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'viabix_user'@'127.0.0.1' IDENTIFIED BY 'troque-esta-senha';
GRANT ALL PRIVILEGES ON viabix_db.* TO 'viabix_user'@'127.0.0.1';
FLUSH PRIVILEGES;
```

## Publicação do código

```bash
sudo mkdir -p /var/www/viabix
sudo rsync -av --delete /seu/pacote/ANVI/ /var/www/viabix/
sudo cp /var/www/viabix/.env.example /var/www/viabix/.env
sudo mkdir -p /var/www/viabix/logs
sudo chown -R www-data:www-data /var/www/viabix
sudo find /var/www/viabix -type d -exec chmod 755 {} \;
sudo find /var/www/viabix -type f -exec chmod 644 {} \;
sudo chmod 640 /var/www/viabix/.env
sudo chmod -R 775 /var/www/viabix/logs
```

Edite `/var/www/viabix/.env` e defina ao menos:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_NAME=viabix_db
DB_USER=viabix_user
DB_PASS=troque-esta-senha
SESSION_SECURE=true
VIABIX_BILLING_PROVIDER=asaas
VIABIX_ASAAS_ENV=production
VIABIX_ASAAS_API_KEY=troque-sua-chave
VIABIX_ASAAS_WEBHOOK_TOKEN=troque-seu-token
```

## Nginx

1. Copie [deploy/nginx-site.conf](deploy/nginx-site.conf) para `/etc/nginx/sites-available/viabix`.
2. Ajuste `server_name`, `root` e o socket do PHP-FPM se necessário.
3. Ative o site:

```bash
sudo ln -s /etc/nginx/sites-available/viabix /etc/nginx/sites-enabled/viabix
sudo nginx -t
sudo systemctl reload nginx
```

## Apache

1. Renomeie `.htaccess.txt` para `.htaccess` no diretório publicado se for usar Apache.
2. Copie [deploy/apache-vhost.conf](deploy/apache-vhost.conf) para `/etc/apache2/sites-available/viabix.conf`.
3. Ative módulos e site:

```bash
sudo a2enmod rewrite headers expires ssl
sudo a2ensite viabix.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

## TLS

```bash
sudo certbot --nginx -d app.seudominio.com -d www.app.seudominio.com
```

Se optar por Apache, substitua por `--apache`.

## PHP produção

1. Copie [deploy/php-prod.ini](deploy/php-prod.ini) para `/etc/php/8.2/fpm/conf.d/99-viabix.ini`.
2. Reinicie o FPM:

```bash
sudo systemctl restart php8.2-fpm
```

## Healthcheck

Use [api/healthcheck.php](api/healthcheck.php) como endpoint de monitoramento básico.

Exemplo:

```bash
curl -fsS https://app.seudominio.com/api/healthcheck.php
```

O retorno esperado é HTTP 200 com `status` igual a `ok` ou `warning`.

## Backup automático

1. Copie [deploy/backup-viabix.sh](deploy/backup-viabix.sh) para `/usr/local/bin/backup-viabix.sh`.
2. Torne executável:

```bash
sudo chmod +x /usr/local/bin/backup-viabix.sh
```

3. Instale o cron usando [deploy/backup-viabix.cron](deploy/backup-viabix.cron):

```bash
sudo cp /var/www/viabix/deploy/backup-viabix.cron /etc/cron.d/viabix
sudo systemctl restart cron
```

## Rotação de logs

1. Copie [deploy/logrotate-viabix.conf](deploy/logrotate-viabix.conf) para `/etc/logrotate.d/viabix`.
2. Teste a configuração:

```bash
sudo logrotate -d /etc/logrotate.d/viabix
```

## Pós-deploy

1. Importe o schema com `api/database.sql` ou rode o fluxo já utilizado na base.
2. Teste login, dashboard, signup, billing e admin SaaS.
3. Configure o webhook do Asaas para `/api/webhook_billing.php`.
4. Valide escrita em `/var/www/viabix/logs/error.log`.
5. Instale backup, cron e logrotate.
6. Execute o checklist final em [GO_LIVE_CHECKLIST.md](GO_LIVE_CHECKLIST.md).