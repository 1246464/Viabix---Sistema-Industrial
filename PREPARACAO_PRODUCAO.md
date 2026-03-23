# Preparação para Produção

## O que mudou

O projeto agora lê configuração sensível a partir de variáveis de ambiente centralizadas em `bootstrap_env.php`.

Arquivos migrados nesta etapa:

- `api/config.php`
- `Controle_de_projetos/config.php`
- `api/criar_db.php`
- `api/importar_db.php`
- `api/unificar_bancos.php`
- `renomear_banco_viabix.php`

## Arquivos de ambiente

- `.env.example`: modelo para staging/produção.
- `.env`: configuração local criada com os valores atuais para não quebrar o ambiente de desenvolvimento.

Em produção, use `.env` com credenciais próprias do servidor e mantenha `APP_DEBUG=false`.

## Variáveis principais

- `APP_ENV`: `development`, `staging` ou `production`
- `APP_DEBUG`: `true` ou `false`
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `DB_CHARSET`
- `SESSION_NAME`, `SESSION_LIFETIME`, `SESSION_SECURE`, `SESSION_SAMESITE`
- `VIABIX_BILLING_PROVIDER`
- `VIABIX_ASAAS_ENV`, `VIABIX_ASAAS_API_KEY`, `VIABIX_ASAAS_WEBHOOK_TOKEN`

## Regras recomendadas para produção

1. Defina `APP_ENV=production`.
2. Defina `APP_DEBUG=false`.
3. Ative HTTPS e use `SESSION_SECURE=true`.
4. Troque o usuário `root` por um usuário MySQL dedicado.
5. Gere senhas exclusivas para banco e gateway.
6. Não publique `.env` no repositório.

## Checklist antes de subir na Hetzner

1. Criar VPS Ubuntu com Nginx ou Apache, PHP 8.2+ e MySQL/MariaDB.
2. Configurar backup automático do banco e dos uploads.
3. Configurar HTTPS com proxy reverso ou Cloudflare.
4. Preencher `.env` do servidor com credenciais de produção.
5. Validar webhooks do Asaas apontando para a URL pública.
6. Revisar permissões da pasta `logs` e demais diretórios graváveis.

Arquivos adicionados para esta etapa:

- `api/healthcheck.php`
- `deploy/backup-viabix.sh`
- `deploy/backup-viabix.cron`
- `deploy/logrotate-viabix.conf`
- `GO_LIVE_CHECKLIST.md`