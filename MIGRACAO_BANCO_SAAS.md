# Migração de Banco para SaaS

Este arquivo acompanha [BD/migracao_para_saas_fase1.sql](BD/migracao_para_saas_fase1.sql).

## Objetivo da Fase 1

Preparar o banco atual para virar SaaS sem exigir, no mesmo passo, a refatoração completa do login e de todas as queries.

Em termos práticos, essa fase faz quatro coisas:

1. cria as tabelas de tenancy e billing
2. adiciona `tenant_id` nas tabelas operacionais
3. cria um tenant legado para backfill dos dados atuais
4. mantém o login legado funcional enquanto o código ainda não foi adaptado

## Arquivos Relacionados

- [BD/migracao_para_saas_fase1.sql](BD/migracao_para_saas_fase1.sql)
- [BD/viabix_saas_multitenant.sql](BD/viabix_saas_multitenant.sql)
- [MODELO_BANCO_SAAS.md](MODELO_BANCO_SAAS.md)
- [ARQUITETURA_SAAS_ASSINATURA.md](ARQUITETURA_SAAS_ASSINATURA.md)

## O que a migração altera

### Tabelas novas

- `plans`
- `tenants`
- `subscriptions`
- `subscription_events`
- `invoices`
- `payments`
- `webhook_events`
- `tenant_settings`
- `device_sessions`

### Tabelas alteradas

- `usuarios`
- `anvis`
- `conflitos_edicao`
- `logs_atividade`
- `anvis_historico`
- `configuracoes`
- `bancos_dados`
- `notificacoes`
- `mudancas`
- `lideres`
- `projetos`

## Backfill executado

O script cria um tenant legado com id fixo:

- `00000000-0000-0000-0000-000000000001`

Depois disso, todos os registros existentes nas tabelas operacionais passam a apontar para esse tenant.

Isso permite migrar o banco agora e adaptar o código depois, sem perder integridade.

## Decisão importante da Fase 1

O script nao remove a estratégia atual de login baseada em `login` global.

Motivo:

- o código atual ainda não resolve tenant no login
- remover essa premissa agora quebraria autenticação antes da refatoração do backend

Ou seja, nesta fase o banco fica pronto para SaaS, mas o código ainda opera como se existisse um único tenant legado.

## Ordem recomendada de execução

1. backup completo do banco atual
2. execução do SQL de migração em homologação
3. validação dos dados legados no tenant `legacy`
4. ajuste do backend para ler `tenant_id`
5. ajuste do login para resolver tenant
6. só depois liberar onboarding de novos clientes SaaS

## Validações após a execução

Verifique pelo menos:

1. existência das tabelas `tenants`, `plans` e `subscriptions`
2. `tenant_id` preenchido em `usuarios`, `anvis` e `projetos`
3. tenant legado criado corretamente
4. assinatura inicial criada para o tenant legado
5. integridade dos vínculos `anvis <-> projetos`

## Consultas úteis de verificação

```sql
SELECT * FROM tenants;

SELECT id, login, tenant_id, email
FROM usuarios
ORDER BY data_criacao DESC;

SELECT id, numero, revisao, tenant_id, projeto_id
FROM anvis
ORDER BY data_criacao DESC;

SELECT id, nome, tenant_id, anvi_id, status
FROM projetos
ORDER BY created_at DESC;

SELECT s.id, s.status, t.slug, p.codigo
FROM subscriptions s
JOIN tenants t ON t.id = s.tenant_id
JOIN plans p ON p.id = s.plan_id;
```

## Limitações desta fase

Esta migração não resolve sozinha:

- filtro por tenant no PHP
- billing real com gateway
- bloqueio por assinatura no login
- cadastro de novas empresas no onboarding
- autenticação por email e tenant

## Próximo passo recomendado

Depois de aplicar a Fase 1, o passo técnico correto é refatorar autenticação e sessão para carregar:

1. `tenant_id`
2. status do tenant
3. status da assinatura
4. permissões do plano