# Modelo de Banco SaaS

Este documento acompanha o SQL em [BD/viabix_saas_multitenant.sql](BD/viabix_saas_multitenant.sql) e traduz o desenho para o estado atual do projeto.

## Objetivo

Levar o banco atual de um modelo monoempresa para um modelo SaaS com:

- multi-tenant por empresa
- assinatura e cobrança
- controle de módulos por plano
- logs e auditoria por tenant
- suporte a app desktop com sessão validada online

## Relacionamentos Principais

1. `tenants` 1:N `usuarios`
2. `plans` 1:N `subscriptions`
3. `tenants` 1:N `subscriptions`
4. `subscriptions` 1:N `invoices`
5. `invoices` 1:N `payments`
6. `subscriptions` 1:N `subscription_events`
7. `tenants` 1:N `anvis`
8. `tenants` 1:N `projetos`
9. `tenants` 1:N `lideres`
10. `anvis` 0..1:N `projetos`
11. `usuarios` 1:N `logs_atividade`
12. `usuarios` 1:N `device_sessions`

## Mapeamento do Projeto Atual para o Modelo SaaS

### Tabelas que já existem e foram preservadas

- `usuarios`
- `anvis`
- `conflitos_edicao`
- `logs_atividade`
- `anvis_historico`
- `configuracoes`
- `bancos_dados`
- `notificacoes`
- `lideres`
- `projetos`
- `mudancas`

### Tabelas novas para monetização e tenancy

- `tenants`
- `plans`
- `subscriptions`
- `subscription_events`
- `invoices`
- `payments`
- `webhook_events`
- `tenant_settings`
- `device_sessions`

## Decisões de Adaptação

### 1. `usuarios` continua sendo a tabela principal de login

Para encaixar no seu código atual, o modelo não cria uma tabela separada de identidades globais. Cada usuário pertence a um `tenant_id`.

Isso simplifica a migração do sistema existente.

### 2. `projetos` continua aceitando `dados JSON`

O módulo de projetos hoje persiste grande parte da estrutura em JSON. O modelo novo mantém isso para reduzir retrabalho, mas já adiciona colunas filtráveis como:

- `tenant_id`
- `anvi_id`
- `cliente`
- `nome`
- `segmento`
- `lider_id`
- `codigo`
- `status`
- `progresso`
- `orcamento`

### 3. `configuracoes` e `bancos_dados` aceitam escopo global ou por empresa

Isso evita duplicar estrutura agora. Configuração global usa `tenant_id` nulo. Configuração do cliente usa `tenant_id` preenchido.

### 4. Assinatura não depende do desktop app

O modelo de cobrança fica todo no backend. O app desktop, quando existir, só consulta esse estado.

## Ordem de Migração Recomendada

1. Unificar definitivamente o módulo de projetos no mesmo banco
2. Adicionar `tenant_id` nas tabelas operacionais
3. Criar `tenants` e migrar usuários existentes para um tenant inicial
4. Criar `plans` e `subscriptions`
5. Implementar validação de assinatura no login
6. Integrar checkout e webhooks
7. Criar painel administrativo interno

## Ponto de Atenção no Estado Atual

Hoje há divergência entre schema e código em partes do projeto.

Exemplos:

- [api/database.sql](api/database.sql) define `anvis.id` como `VARCHAR(50)`
- [api/criar_projeto_de_anvi.php](api/criar_projeto_de_anvi.php) trata `anvi_id` como inteiro e espera colunas que não aparecem nesse schema
- [Controle_de_projetos/api_mysql.php](Controle_de_projetos/api_mysql.php) ainda trabalha com `projetos.dados` em uma estrutura própria do módulo
- [api/unificar_bancos.php](api/unificar_bancos.php) já aponta a direção correta de banco único, mas ainda sem multi-tenancy e billing

Antes de aplicar a camada SaaS, vale consolidar um único schema operacional real do sistema.

## O que este modelo resolve

- separação de dados por empresa
- bloqueio por inadimplência
- planos com limites e módulos
- histórico de assinatura
- rastreio de pagamentos
- preparação para app desktop com sessão online

## O que ele não resolve sozinho

- refatoração do código PHP para filtrar por `tenant_id`
- normalização do JSON de projetos
- integração com gateway de pagamento
- políticas de deploy, backup e observabilidade

## Próximo passo técnico mais importante

Depois deste modelo, o passo mais útil é criar a migração operacional do banco atual para esse desenho, começando por:

1. tenant inicial
2. coluna `tenant_id` nas tabelas existentes
3. ajuste de autenticação e sessão
4. filtro obrigatório por tenant em todas as consultas