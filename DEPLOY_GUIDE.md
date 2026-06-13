# Guia de Deploy Viabix

O deploy padrao agora e GitHub -> DigitalOcean, com backup automatico antes de atualizar, healthcheck depois do deploy e rollback automatico quando o healthcheck falha.

## Fluxo Recomendado

1. Fazer merge para `homolog`.
2. Validar staging.
3. Fazer merge para `main`.
4. GitHub Actions executa o deploy em producao.

Branches:

- `homolog`: ambiente de homologacao.
- `main`: ambiente de producao.

## Preparar o Servidor

No servidor, a aplicacao deve estar em `/var/www/viabix` ou no caminho definido por `APP_DIR`.

Arquivos importantes:

- `deploy/release.sh`: deploy e rollback.
- `deploy/backup-viabix.sh`: backup de banco e arquivos.
- `deploy/monitor-endpoints.sh`: monitoramento simples de endpoints.
- `deploy/OPERATIONS_RUNBOOK.md`: checklist operacional completo.

## Secrets do GitHub

Configure no ambiente `staging` e `production`:

- `DROPLET_IP`
- `DROPLET_USER`
- `DROPLET_SSH_KEY`
- `DROPLET_SSH_PORT` opcional
- `APP_DIR` opcional, padrao `/var/www/viabix`
- `HEALTHCHECK_URL`

Exemplo de `HEALTHCHECK_URL`:

```text
https://app.viabix.com.br/api/healthcheck.php?scope=ready
```

## Deploy Manual

```bash
cd /var/www/viabix
APP_DIR=/var/www/viabix \
BRANCH=main \
TARGET_REF=origin/main \
HEALTHCHECK_URL=https://app.viabix.com.br/api/healthcheck.php?scope=ready \
bash deploy/release.sh
```

## Rollback

```bash
cd /var/www/viabix
bash deploy/release.sh --rollback <commit-ou-tag>
```

Para restaurar banco, use um backup de `/var/backups/viabix/database` somente se a falha envolver dados ou migracao.

## Pos-Deploy

```bash
curl -fsS https://app.viabix.com.br/api/healthcheck.php?scope=live
curl -fsS https://app.viabix.com.br/api/healthcheck.php?scope=ready
curl -I https://app.viabix.com.br/login.html
curl -I https://app.viabix.com.br/dashboard.html
tail -n 80 /var/log/viabix/deploy.log
tail -n 80 /var/www/viabix/logs/error.log
```

## Monitoramento

Cron sugerido:

```cron
*/5 * * * * BASE_URL=https://app.viabix.com.br /var/www/viabix/deploy/monitor-endpoints.sh >/dev/null 2>&1
```

Veja detalhes no [runbook operacional](deploy/OPERATIONS_RUNBOOK.md).
