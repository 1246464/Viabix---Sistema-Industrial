# Billing, Checkout e Webhooks

Este guia cobre a base de billing implementada no projeto para a fase atual.

## Arquivos Principais

- [billing.html](billing.html)
- [api/subscription_current.php](api/subscription_current.php)
- [api/billing_invoices.php](api/billing_invoices.php)
- [api/checkout_create.php](api/checkout_create.php)
- [api/webhook_billing.php](api/webhook_billing.php)

## O que já funciona

1. consulta da assinatura atual do tenant logado
2. listagem das faturas do tenant
3. geração de checkout manual/mock para troca ou ativação de plano
4. geração de cobrança real no Asaas quando a chave estiver configurada
4. criação de fatura pendente
5. processamento de webhook para:
   - pagamento confirmado
   - pagamento falho
   - assinatura cancelada
  - eventos de cobrança do Asaas (`PAYMENT_*`)

## Fluxo atual

1. usuário entra em [billing.html](billing.html)
2. a tela carrega assinatura atual e faturas
3. o usuário escolhe um plano
4. [api/checkout_create.php](api/checkout_create.php) atualiza a assinatura e gera uma fatura pendente
5. um webhook posterior confirma ou falha a cobrança
6. o backend atualiza `invoices`, `payments`, `subscriptions` e `tenants`

## Configuração do Asaas

As variáveis abaixo controlam a integração real:

```env
VIABIX_BILLING_PROVIDER=manual
VIABIX_ASAAS_ENV=sandbox
VIABIX_ASAAS_API_KEY=$aact_YOUR_KEY
VIABIX_ASAAS_WEBHOOK_TOKEN=seu-token-opcional-de-webhook
```

Notas:

1. `VIABIX_ASAAS_API_KEY` habilita o checkout real do Asaas.
2. `VIABIX_ASAAS_ENV` aceita `sandbox` ou `production`.
3. `VIABIX_ASAAS_WEBHOOK_TOKEN` é opcional, mas recomendado para validar a origem do webhook.
4. Se a chave não estiver configurada, o sistema continua usando `manual` como fallback.

## Limitação atual

O checkout real atual do Asaas cria uma cobrança avulsa por ciclo em `payments`.

Isso resolve:

- criação oficial da cobrança
- redirecionamento do cliente para a URL do Asaas
- confirmação automática via webhook

Ainda não resolve:

- assinatura recorrente nativa no Asaas
- renovação automática sem nova geração de cobrança local
- tokenização de cartão

Isso significa:

- a fatura é gerada no banco
- a lógica de ativação já existe
- a cobrança real sai para o Asaas quando configurado
- sem chave, o sistema volta para modo manual

Essa etapa foi feita assim de propósito para você validar o produto e a regra comercial antes de amarrar a integração com Asaas, Stripe, Iugu ou outro provedor.

## Exemplo de criação de checkout

Endpoint:

- `POST /api/checkout_create.php`

Payload:

```json
{
  "plan_code": "pro",
  "cycle": "mensal",
  "provider": "auto"
}
```

Retorno esperado:

- sucesso do checkout
- dados do plano
- dados da fatura gerada
- URL oficial do Asaas quando habilitado
- URL local de billing quando em fallback manual

## Fluxo com Asaas

1. o backend cria ou reaproveita o customer do tenant no Asaas
2. o sistema gera uma fatura local pendente
3. o sistema cria a cobrança no endpoint `/payments` do Asaas
4. a fatura local recebe `gateway_invoice_id`, `url_cobranca` e vencimento reais
5. a tela de billing abre a URL oficial retornada pelo Asaas
6. o webhook do Asaas chama [api/webhook_billing.php](api/webhook_billing.php)
7. o backend converte `PAYMENT_*` para eventos internos (`invoice.paid`, `payment.failed`, `payment.refunded`, `invoice.pending`)

## Exemplo de webhook de pagamento confirmado

Endpoint:

- `POST /api/webhook_billing.php`

Payload:

```json
{
  "provider": "manual",
  "event_id": "evt_manual_001",
  "event_type": "invoice.paid",
  "invoice_id": 1,
  "amount": 697.00,
  "method": "pix",
  "gateway_payment_id": "pay_manual_001"
}
```

Efeito esperado:

1. fatura vai para `paga`
2. payment é gravado como `confirmado`
3. assinatura vai para `ativa`
4. tenant vai para `ativo`

## Exemplo de webhook do Asaas

Endpoint:

- `POST /api/webhook_billing.php`

Payload simplificado recebido do Asaas:

```json
{
  "event": "PAYMENT_RECEIVED",
  "payment": {
    "id": "pay_123456789",
    "customer": "cus_123456789",
    "value": 697,
    "billingType": "UNDEFINED",
    "externalReference": "invoice:15|tenant:9c5b1fd5-1c56-4a0d-88f7-4e9bf70a7bb3|subscription:577efede-7209-41fd-964d-b9d679ee2aab"
  }
}
```

Mapeamento interno atual:

1. `PAYMENT_CREATED`, `PAYMENT_UPDATED`, `PAYMENT_CHECKOUT_VIEWED` -> `invoice.pending`
2. `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED` -> `invoice.paid`
3. `PAYMENT_OVERDUE`, `PAYMENT_DELETED` -> `payment.failed`
4. `PAYMENT_REFUNDED`, `PAYMENT_CHARGEBACK_REQUESTED` -> `payment.refunded`

## Exemplo de webhook de falha

```json
{
  "provider": "manual",
  "event_id": "evt_manual_002",
  "event_type": "payment.failed",
  "invoice_id": 1,
  "amount": 697.00,
  "method": "boleto",
  "gateway_payment_id": "pay_manual_002"
}
```

Efeito esperado:

1. fatura vai para `vencida`
2. payment é gravado como `falhou`
3. assinatura vai para `inadimplente`
4. tenant vai para `inadimplente`

## Exemplo de cancelamento

```json
{
  "provider": "manual",
  "event_id": "evt_manual_003",
  "event_type": "subscription.canceled",
  "invoice_id": 1
}
```

## Próximo encaixe com recorrência real

O passo seguinte mais natural é evoluir do `payment` avulso para assinatura recorrente nativa do Asaas:

1. criar `subscription` no Asaas em vez de cobrança única
2. salvar `gateway_subscription_id` real em `subscriptions`
3. gerar renovações automáticas sem nova ação manual do cliente
4. expandir o webhook para eventos de assinatura do Asaas

## Melhor candidato para o seu caso

Para operação no Brasil, o encaixe mais pragmático tende a ser:

1. Asaas
2. Iugu
3. Pagar.me

Se quiser expansão internacional, Stripe tende a ser o mais forte.