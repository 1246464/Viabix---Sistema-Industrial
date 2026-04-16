# Sentry Integration - Monitoring & Error Tracking

## Visão Geral

O Viabix possui integração com **Sentry.io** para capturar, rastrear e analisar erros em tempo real. O sistema funciona com PHP puro (sem dependências externas) usando HTTP para comunicação com Sentry.

## Configuração

### 1. Criar Conta no Sentry

```bash
# Acesse:
https://sentry.io

# Crie uma compte ou faça login
# Selecione PHP como linguagem
# Copie o DSN fornecido

# Exemplo de DSN:
# https://your-public-key@sentry.io/your-project-id
```

### 2. Adicionar DSN ao Ambiente

**Arquivo: `.env`**
```bash
SENTRY_DSN=https://seu-public-key@sentry.io/seu-project-id
SENTRY_ENVIRONMENT=production
SENTRY_RELEASE=1.0.0
```

**Arquivo: `.env.local` (sobrescreve em deployments)**
```bash
SENTRY_DSN=https://seu-public-key@sentry.io/seu-project-id
SENTRY_ENVIRONMENT=production
```

### 3. Verificar Integração

Depois que o `.env` está configurado, qualquer erro no PHP será automaticamente enviado para Sentry.

---

## Uso Básico

### 1. Capturar Exceções (Automático)

Exceções não capturadas são automaticamente rastreadas:

```php
<?php
require_once 'api/config.php';

try {
    throw new Exception('Algo deu errado');
} catch (Exception $e) {
    // O handler global captura automaticamente
    throw $e;
}
```

### 2. Capturar Mensagens Manualmente

```php
<?php
require_once 'api/config.php';

// Log informativo
viabix_sentry_message('Usuário fez login com sucesso', 'info', 'auth.login');

// Log de aviso
viabix_sentry_message('Taxa de pagamento alerta levantada', 'warning', 'billing.alert');

// Log de erro
viabix_sentry_message('Falha ao conectar ao Asaas', 'error', 'billing.provider');
```

### 3. Adicionar Contexto do Usuário

```php
<?php
require_once 'api/config.php';
session_start();

// Depois de autenticar o usuário:
viabix_sentry_set_user(
    $_SESSION['user_id'],
    $_SESSION['email'],
    $_SESSION['username']
);
```

### 4. Adicionar Contexto do Tenant

Para operações SaaS, sempre rastrear o tenant:

```php
<?php
require_once 'api/config.php';

$tenantId = $_SESSION['tenant_id'] ?? null;
$tenantName = $_SESSION['tenant_nome'] ?? null;

if ($tenantId) {
    viabix_sentry_set_tenant($tenantId, $tenantName);
}
```

### 5. Adicionar Tags Customizadas

```php
<?php
require_once 'api/config.php';

// Tag simples
viabix_sentry_tag('feature', 'billing');
viabix_sentry_tag('component', 'asaas_integration');
viabix_sentry_tag('action', 'create_payment');

// Tags são úteis para buscar/filtrar no Sentry
```

### 6. Adicionar Breadcrumbs (Trilha de Eventos)

Breadcrumbs representam eventos que levaram a um erro:

```php
<?php
require_once 'api/config.php';

// Log de ação do usuário
viabix_sentry_breadcrumb('Usuário abriu página de billing', 'user-action', 'info');

// Log de operação interna
viabix_sentry_breadcrumb('Iniciando sincronização com Asaas', 'sync', 'info');

// Log com dados adicionais
viabix_sentry_breadcrumb(
    'Pagamento falhou',
    'payment',
    'error',
    [
        'invoice_id' => '123',
        'gateway_response' => 'Invalid card'
    ]
);
```

---

## Padrão Recomendado: API Endpoints

```php
<?php
/**
 * api/billing_invoices.php
 * 
 * Exemplo de padrão para endpoints com Sentry integrado
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

try {
    // 1. Validar autenticação
    $user = viabixRequireAuthenticatedSession();
    viabix_sentry_set_user($user['id'], $user['email'] ?? null, $user['nome'] ?? null);

    // 2. Validar tenant (SaaS)
    $tenantId = $user['tenant_id'] ?? null;
    if ($tenantId) {
        viabix_sentry_set_tenant($tenantId);
    }

    // 3. Log de ação
    viabix_sentry_breadcrumb('Carregando faturas do tenant', 'invoice.list', 'info');

    // 4. Lógica principal
    $stmt = $pdo->prepare('SELECT * FROM invoices WHERE tenant_id = ? ORDER BY created_at DESC');
    $stmt->execute([$tenantId]);
    $invoices = $stmt->fetchAll();

    // 5. Sucesso
    echo json_encode([
        'success' => true,
        'invoices' => $invoices,
    ]);

} catch (\PDOException $e) {
    // 6. Erro específico - adicionar tags
    viabix_sentry_tag('error_type', 'database');
    viabix_sentry_exception($e, 'error');
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro ao carregar faturas'
    ]);

} catch (\Exception $e) {
    // 7. Erro genérico
    viabix_sentry_exception($e, 'error');
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
```

---

## Padrão Recomendado: Funções Utilitárias

```php
<?php
/**
 * Wrapper para operação Asaas com tratamento de erros
 */
function viabixAsaasPaymentWithTracking($payload) {
    $sentry = ViabixSentry::getInstance();
    
    try {
        viabix_sentry_breadcrumb('Iniciando pagamento Asaas', 'asaas.payment', 'info', [
            'amount' => $payload['value'] ?? 0,
        ]);

        $response = viabixCreateAsaasPayment(...);

        viabix_sentry_breadcrumb('Pagamento Asaas criado com sucesso', 'asaas.payment', 'info', [
            'payment_id' => $response['id'] ?? null,
        ]);

        return $response;

    } catch (\Exception $e) {
        viabix_sentry_tag('error_source', 'asaas');
        viabix_sentry_exception($e, 'error', [
            'payload' => $payload,
        ]);

        throw $e;
    }
}
```

---

## Eventos Automáticos Capturados

O sistema capture automaticamente:

✅ **Exceções PHP não capturadas**
✅ **Erros fatais** (E_ERROR, E_PARSE, etc)
✅ **Warnings e Notices** (configurável por nível)
✅ **Conexão PDO falha**
✅ **Sessions**: user_id, tenant_id
✅ **Request**: URL, método HTTP, headers (sanitizado)
✅ **Environment**: SO, versão PHP
✅ **Breadcrumbs**: até 100 eventos anteriores

---

## Níveis de Severidade

Use os níveis apropriados para cada situação:

```php
// Info - operação normal
viabix_sentry_message('Backup iniciado', 'info', 'backup');

// Warning - algo inesperado mas recuperável
viabix_sentry_message('Falha ao conectar ao cache, usando fallback', 'warning', 'cache');

// Error - operação falhou
viabix_sentry_message('Pagamento recusado', 'error', 'billing');

// Fatal - sistema comprometido
viabix_sentry_message('Banco de dados desconectado', 'fatal', 'database');
```

---

## Categorias Recomendadas

As categorias ajudam a organizar eventos no Sentry:

- `auth.*` - Autenticação e autorização
- `billing.*` - Operações de faturamento
- `payment.*` - Processamento de pagamentos
- `webhook.*` - Eventos de webhook
- `database.*` - Operações de banco
- `sync.*` - Sincronização de dados
- `export.*` - Exportações (PDF, Excel, etc)
- `api.*` - Chamadas de API externas
- `session.*` - Gerenciamento de sessão
- `error.*` - Erros do PHP

---

## Filtragem e Busca no Sentry

**Buscar por usuário:**
```
user.id:12345
```

**Buscar por tenant:**
```
tags:tenant_id:00000000-0000-0000-0000-000000000001
```

**Buscar por tag:**
```
tags:feature:billing
```

**Buscar por categorias de erro:**
```
category:billing.payment
```

**Buscar por período:**
```
timestamp:[2026-04-01 TO 2026-04-09]
```

---

## Debugging Localmente

Se não tiver DSN configurada, Sentry fica desabilitada:

```php
// Isso é seguro - Sentry checka se está habilitada
viabix_sentry_message('Esta mensagem só é enviada se DSN está configurada');
```

Para testar localmente sem Sentry:

```bash
# .env.local (para desenvolvimento)
SENTRY_DSN=
```

---

## Performance

O Sentry foi implementado para **Performance**:

✅ **Async**: eventos são enviados em background (não bloqueia requests)
✅ **Lightweight**: ~300 linhas de código PHP, sem dependências
✅ **Throttled**: apenas 1 breadcrumb por segundo máximo
✅ **Timeout**: espera no máximo 5 segundos para enviar

---

## Troubleshooting

### Eventos não aparecem no Sentry

1. **Verificar DSN**
   ```php
   php -r "require 'api/config.php'; echo getenv('SENTRY_DSN');"
   ```

2. **Testar cURL**
   ```php
   php -r "echo function_exists('curl_init') ? 'OK' : 'ERRO';"
   ```

3. **Verificar logs locais**
   ```bash
   tail -f logs/error.log
   ```

4. **Ativar debug em `.env.local`**
   ```
   APP_DEBUG=true
   ```

### Eventos de teste

Para forçar envio de evento de teste:

```php
<?php
require_once 'api/config.php';

// Teste de mensagem
viabix_sentry_message('Teste de configuração Sentry', 'info', 'test');

// Teste de exceção
try {
    throw new Exception('Erro de teste intencional');
} catch (Exception $e) {
    viabix_sentry_exception($e, 'info');
}

echo "Evento enviado. Verifique Sentry em 10 segundos.";
```

---

## Próximas Etapas

1. **Alertas**: Configure alertas no Sentry para tendências de erros
2. **Release Tracking**: Use `SENTRY_RELEASE` para rastrear versões
3. **Performance Monitoring**: Ative SPM (Sentry Performance Monitoring)
4. **Integração com Slack**: Notifique erros críticos no Slack
