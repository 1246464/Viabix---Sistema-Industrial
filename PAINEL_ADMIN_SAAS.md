# Painel Admin SaaS

Este arquivo descreve a área administrativa interna criada para operar o modelo SaaS.

## Arquivos

- [admin_saas.html](admin_saas.html)
- [api/admin_saas.php](api/admin_saas.php)

## Objetivo

Dar à operação interna uma forma de controlar:

1. tenants
2. status operacional das contas
3. assinatura associada ao tenant
4. plano atual
5. últimos eventos de assinatura
6. últimas faturas
7. saúde da integração de billing
8. últimos webhooks recebidos
9. últimos pagamentos registrados

## O que a tela já faz

### Visão geral

- total de tenants
- quantos estão ativos
- quantos estão em trial
- quantos estão inadimplentes

### Saúde da integração

O painel agora também mostra:

- provedor padrão ativo no ambiente
- se o Asaas está habilitado ou em fallback manual
- ambiente atual do Asaas (`sandbox` ou `production`)
- se o token opcional de webhook está configurado
- total de webhooks nas últimas 24h
- total de webhooks pendentes
- total de webhooks com erro
- total de pagamentos falhos
- total de faturas pendentes
- timestamp do último webhook recebido
- timestamp do último pagamento confirmado

Além disso, lista:

- últimos webhooks globais do sistema
- últimos pagamentos globais do sistema

Na grade global de webhooks, agora existem filtros por:

- provider
- status (`processado`, `pendente`, `com erro`)
- período (`24h`, `7d`, `30d`, total)

Na grade global de pagamentos, agora existem filtros por:

- provider/gateway
- status (`confirmado`, `falhou`, `pendente`, `estornado`)
- período (`24h`, `7d`, `30d`, total)

### Lista de contas

Para cada tenant, mostra:

- empresa
- slug
- e-mail financeiro
- status do tenant
- status da assinatura
- plano atual
- ciclo
- usuários ativos
- total de ANVIs
- total de projetos
- quantidade de faturas vencidas

Na grade de tenants, agora existem filtros por:

- status da conta
- plano
- período de criação da conta (`24h`, `7d`, `30d`, total)
- busca textual por empresa, slug, plano ou e-mail financeiro

### Detalhe da conta

Ao abrir um tenant, mostra:

- dados principais do tenant
- status operacional
- plano atual
- vigência/trial
- usuários recentes
- eventos recentes da assinatura
- últimas faturas
- últimos pagamentos do tenant
- últimos webhooks vinculados ao tenant

### Ações administrativas

Já implementadas:

1. ativar conta
2. suspender conta
3. marcar inadimplência
4. cancelar conta
5. trocar plano
6. reprocessar webhook com erro

### Reprocessamento de webhook

Quando um webhook fica com `erro_processamento`, o painel agora permite:

- reexecutar o evento a partir do payload persistido em `webhook_events`
- atualizar a situação do webhook no próprio painel
- recarregar a visão global e o detalhe do tenant após a tentativa

Escopo atual:

- funciona para webhooks com erro já persistidos no banco
- usa o `provider`, `event_type` e `payload` já armazenados
- não faz replay automático em lote

## Regras de acesso

A API [api/admin_saas.php](api/admin_saas.php) exige:

- sessão autenticada
- nível `admin`

## Observação importante

Este painel administra a camada SaaS do produto.

Ele não substitui ainda:

- gestão operacional detalhada de usuários do tenant
- suporte financeiro manual completo
- reconciliação bancária
- auditoria avançada de billing
- replay automático de webhook

## Próximos encaixes naturais

1. botões para reprocessar cobrança
2. botão para gerar cobrança avulsa
3. abertura do detalhe completo da fatura
4. filtros avançados por renovação, inadimplência e uso na visão de tenants
5. indicadores de churn, MRR e conversão trial -> pago