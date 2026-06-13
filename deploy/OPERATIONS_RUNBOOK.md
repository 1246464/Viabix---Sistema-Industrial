# Viabix Operations Runbook

## Objetivo

Este runbook padroniza o fluxo GitHub -> DigitalOcean, reduz risco de deploy e deixa rollback, healthcheck e monitoramento claros.

## Ambientes

Use dois ambientes separados:

- `staging`: branch `homolog`, banco separado, `APP_ENV=staging`, `APP_DEBUG=false`, URL de homologacao.
- `production`: branch `main`, banco de producao, `APP_ENV=production`, `APP_DEBUG=false`, URL publica.

Nunca reutilize o banco de producao em homologacao.

## Secrets do GitHub Actions

Configure em cada environment do GitHub:

- `DROPLET_IP`: IP do servidor.
- `DROPLET_USER`: usuario SSH.
- `DROPLET_SSH_KEY`: chave privada SSH.
- `DROPLET_SSH_PORT`: porta SSH, opcional.
- `APP_DIR`: caminho no servidor, padrao `/var/www/viabix`.
- `HEALTHCHECK_URL`: URL completa do ready check, exemplo `https://app.viabix.com.br/api/healthcheck.php?scope=ready`.

## Deploy Padrao

1. Abra PR para `homolog`.
2. Ao fazer merge em `homolog`, o workflow faz deploy em staging.
3. Valide a checklist pos-deploy em staging.
4. Abra PR de `homolog` para `main`.
5. Ao fazer merge em `main`, o workflow faz deploy em production.

O deploy no servidor executa:

- backup pre-deploy de banco e arquivos;
- fetch do commit alvo;
- checkout/reset do commit alvo;
- limpeza de arquivos nao versionados preservando `.env`, `logs` e `uploads`;
- Composer em producao quando existir `composer.lock`;
- scripts leves de indices quando disponiveis;
- reload de PHP-FPM/Apache;
- healthcheck `ready`;
- rollback automatico para o commit anterior se o healthcheck falhar.

## Deploy Manual no Servidor

```bash
cd /var/www/viabix
APP_DIR=/var/www/viabix \
BRANCH=main \
TARGET_REF=origin/main \
HEALTHCHECK_URL=https://app.viabix.com.br/api/healthcheck.php?scope=ready \
bash deploy/release.sh
```

## Rollback

Rollback de codigo:

```bash
cd /var/www/viabix
bash deploy/release.sh --rollback <commit-ou-tag>
```

Restauracao de banco, quando necessaria:

```bash
gunzip -c /var/backups/viabix/database/<arquivo>.sql.gz | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
```

Use restauracao de banco somente quando a falha envolver migracao ou corrupcao de dados. Para falha visual/API comum, rollback de codigo costuma bastar.

## Checklist Pos-Deploy

Execute apos cada deploy:

```bash
curl -fsS https://app.viabix.com.br/api/healthcheck.php?scope=live
curl -fsS https://app.viabix.com.br/api/healthcheck.php?scope=ready
curl -I https://app.viabix.com.br/login.html
curl -I https://app.viabix.com.br/dashboard.html
tail -n 80 /var/log/viabix/deploy.log
tail -n 80 /var/www/viabix/logs/error.log
```

Validacao funcional minima:

- login web abre sem erro;
- dashboard principal carrega;
- dashboard de viabilidade abre;
- uma ANVI existente abre em modo leitura/edicao conforme permissao;
- checkout/billing nao retorna erro 500 quando acessado por usuario autorizado.

## Healthcheck

Endpoints:

- `/api/healthcheck.php?scope=live`: valida apenas que PHP esta respondendo.
- `/api/healthcheck.php?scope=ready`: valida ambiente, banco, schema minimo, logs e Redis.

O deploy deve usar `ready`. Balanceadores e uptime checks simples podem usar `live`.

## Monitoramento de Endpoints

Instale no cron:

```cron
*/5 * * * * BASE_URL=https://app.viabix.com.br /var/www/viabix/deploy/monitor-endpoints.sh >/dev/null 2>&1
```

Logs:

```bash
tail -f /var/log/viabix/endpoint-monitor.log
```

Configure alerta externo para falhas repetidas no `ready` check e para crescimento de erros em:

- `/var/www/viabix/logs/error.log`
- `/var/log/apache2/viabix-error.log`
- `/var/log/viabix/deploy.log`
- `/var/log/viabix/endpoint-monitor.log`

## Backups

O deploy chama `deploy/backup-viabix.sh` antes de atualizar. Tambem mantenha backup agendado diario:

```cron
0 2 * * * APP_DIR=/var/www/viabix /var/www/viabix/deploy/backup-viabix.sh >> /var/log/viabix/backup.log 2>&1
```

Backups ficam em:

- `/var/backups/viabix/database`
- `/var/backups/viabix/app`

Teste restauracao pelo menos uma vez por mes em staging.
