# Go-Live Checklist

## Aplicação

1. Confirmar que `.env` de produção existe e está com `APP_ENV=production`.
2. Confirmar que `APP_DEBUG=false`.
3. Confirmar que `SESSION_SECURE=true` e que o domínio responde somente em HTTPS.
4. Validar login, dashboard, signup, billing e admin SaaS.
5. Validar [api/healthcheck.php](api/healthcheck.php) retornando `status: ok`.

## Banco e backup

1. Confirmar usuário MySQL dedicado sem uso de `root`.
2. Rodar backup manual com [deploy/backup-viabix.sh](deploy/backup-viabix.sh).
3. Instalar cron de backup com [deploy/backup-viabix.cron](deploy/backup-viabix.cron).
4. Confirmar retenção e restauração de um dump recente.
5. Ativar snapshots da VPS na Hetzner.

## Logs e observabilidade

1. Confirmar criação e escrita em `/var/www/viabix/logs/error.log`.
2. Instalar rotação com [deploy/logrotate-viabix.conf](deploy/logrotate-viabix.conf).
3. Verificar logs do Nginx ou Apache sem erros críticos após login e checkout.
4. Confirmar que o healthcheck responde em menos de 1 segundo.

## Billing

1. Validar `VIABIX_BILLING_PROVIDER=asaas` no `.env`.
2. Validar `VIABIX_ASAAS_API_KEY` e `VIABIX_ASAAS_WEBHOOK_TOKEN` com valores de produção.
3. Configurar webhook do Asaas para `/api/webhook_billing.php`.
4. Executar um pagamento real de baixo valor e validar criação de invoice, payment e evento.

## Segurança

1. Confirmar permissões 640 no `.env`.
2. Confirmar bloqueio de arquivos sensíveis no servidor web.
3. Confirmar TLS emitido e renovação automática com Certbot.
4. Restringir acesso SSH por chave e desabilitar senha na VPS.

## Operação

1. Definir responsável por monitorar billing, webhooks e backups.
2. Registrar rotina de restore de banco e rollback de deploy.
3. Congelar alterações estruturais no dia do go-live.
4. Fazer janela curta de validação pós-publicação com usuários internos.