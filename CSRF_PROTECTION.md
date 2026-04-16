# CSRF Protection - Cross-Site Request Forgery

## Visão Geral

O Viabix possui proteção contra **Cross-Site Request Forgery (CSRF)** usando tokens únicos por sessão. Cada requisição POST/PUT/DELETE valida um token que é gerado e armazenado na sessão.

---

## Como Funciona

### 1. Token Geração
- Token único é criado quando a sessão inicia
- 64 caracteres aleatórios via `random_bytes(32)`
- Regenerado a cada 1 hora automaticamente
- Armazenado em `$_SESSION['_csrf_token']`

### 2. Validação
- Frontend envia token junto com dados (3 locais possíveis):
  - Campo POST: `_csrf_token`
  - JSON body: `_csrf_token`
  - Header HTTP: `X-CSRF-Token`
- Backend valida com `hash_equals()` (comparação segura)
- Erro 403 se token inválido ou ausente

### 3. AJAX Automático
- Script JavaScript intercepta requisições fetch e XMLHttpRequest
- Adiciona automaticamente header `X-CSRF-Token` em POST/PUT/DELETE
- Sem necessidade de código manual em cada chamada AJAX

---

## Implementação no Backend

### Em Formulários HTML

Use o helper para incluir o campo:

```html
<form method="POST" action="api/processar.php">
    <?php echo viabixCsrfField(); ?>
    
    <input type="text" name="username">
    <button type="submit">Enviar</button>
</form>
```

Gera:
```html
<input type="hidden" name="_csrf_token" value="abc123...">
```

### Em Endpoints POST

**Opção 1: Validação manual**
```php
<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        viabixValidateCsrfToken();
    } catch (RuntimeException $e) {
        http_response_code(403);
        echo json_encode(['error' => 'Token inválido']);
        exit;
    }
    
    // Processar requisição...
}
```

**Opção 2: Middleware automático**
```php
<?php
require_once 'config.php';
viabixValidateCsrfMiddleware(); // Valida apenas POST/PUT/DELETE

// Seu código aqui...
```

**Opção 3: Helper para POST-only**
```php
<?php
require_once 'config.php';
viabixRequirePostWithCsrf(); // Garante POST E CSRF

// Seu código aqui...
```

---

## Implementação no Frontend

### Em Formulários HTML (Automático)

1. **Carregar token ao abrir página:**
```html
<script>
    async function carregarCsrfToken() {
        const response = await fetch('api/check_session.php', {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.csrf_token) {
            document.getElementById('csrfToken').value = data.csrf_token;
        }
    }
    
    window.addEventListener('DOMContentLoaded', carregarCsrfToken);
</script>
```

2. **Adicionar campo hidden:**
```html
<form id="myForm">
    <input type="hidden" id="csrfToken" name="_csrf_token" value="">
    <input type="text" name="username">
    <button>Enviar</button>
</form>
```

3. **Enviar com token:**
```html
<script>
    document.getElementById('myForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const response = await fetch('api/processar.php', {
            method: 'POST',
            body: JSON.stringify(Object.fromEntries(formData))
        });
    });
</script>
```

### Em AJAX (Automático)

O script `viabixCsrfAjaxScript()` intercepta **automaticamente** todas as requisições:

```php
<head>
    <script>
        <?php echo viabixCsrfAjaxScript(); ?>
    </script>
</head>
```

Depois, use fetch/AJAX normalmente:

```javascript
// fetch - token adicionado automaticamente
fetch('api/criar-fatura.php', {
    method: 'POST',
    body: JSON.stringify({ id: 123 })
});

// jQuery/AJAX - token adicionado automaticamente
$.ajax({
    url: 'api/criar-fatura.php',
    method: 'POST',
    data: { id: 123 }
});
```

---

## API de Funções

### Backend (PHP)

```php
// Obter token CSRF atual
viabixGetCsrfToken()                    // → string

// Inicializar CSRF na sessão
viabixInitializeCsrfProtection()        // → void

// Gerar campo HTML hidden
viabixCsrfField()                       // → string

// Validar token (lança RuntimeException se inválido)
viabixValidateCsrfToken()               // → void

// Middleware automático
viabixValidateCsrfMiddleware()          // → void (pula GET/HEAD/OPTIONS)

// Helper para POST + CSRF
viabixRequirePostWithCsrf()             // → void

// Responder JSON com token
viabixRespondWithCsrfToken()            // → void (exit)

// Gerar script JavaScript
viabixCsrfAjaxScript()                  // → string
```

### Frontend (JavaScript)

```javascript
// Token é obtido automaticamente via meta tag ou input
// Interceptação automática de fetch e XMLHttpRequest
// Não há funções públicas - tudo é automático
```

---

## Padrões Recomendados

### Padrão 1: Formulário Tradicional

```html
<form id="form" method="POST" action="api/processar.php">
    <?php echo viabixCsrfField(); ?>
    
    <input type="email" name="email" required>
    <button>Enviar</button>
</form>

<script>
    // Token carregado automaticamente
    async function initForm() {
        const res = await fetch('api/check_session.php', { credentials: 'include' });
        const data = await res.json();
        if (data.csrf_token) {
            document.querySelector('[name="_csrf_token"]').value = data.csrf_token;
        }
    }
    window.addEventListener('DOMContentLoaded', initForm);
</script>
```

Backend:
```php
<?php
require_once 'config.php';
viabixRequirePostWithCsrf();

$email = $_POST['email'] ?? '';
// Processar...
```

### Padrão 2: API AJAX

```html
<button id="btn" onclick="salvarDados()">Salvar</button>

<script>
    <?php echo viabixCsrfAjaxScript(); ?>
    
    async function salvarDados() {
        const response = await fetch('api/salvar-dados.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dados: 'valor' })
        });
        // Token adicionado automaticamente no header
    }
</script>
```

Backend:
```php
<?php
require_once 'config.php';
viabixValidateCsrfMiddleware();

$input = json_decode(file_get_contents('php://input'), true);
// Processar...
```

### Padrão 3: Formulário JSON

```html
<form id="form" onsubmit="submitForm(event)">
    <input type="hidden" id="csrf" value="">
    <input type="text" name="username" required>
    <button>Enviar</button>
</form>

<script>
    // Carregar CSRF
    async function initForm() {
        const res = await fetch('api/check_session.php', { credentials: 'include' });
        const data = await res.json();
        document.getElementById('csrf').value = data.csrf_token || '';
    }
    
    async function submitForm(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);
        
        const response = await fetch('api/processar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': data.get('_csrf_token') },
            body: JSON.stringify(Object.fromEntries(data))
        });
    }
    
    window.addEventListener('DOMContentLoaded', initForm);
</script>
```

---

## Troubleshooting

### "CSRF token ausente"

**Causa:** Token não foi carregado ou campo está vazio.

**Solução:**
```html
<script>
    async function loadToken() {
        const res = await fetch('api/check_session.php', { credentials: 'include' });
        const data = await res.json();
        console.log('Token:', data.csrf_token);
        document.getElementById('csrfToken').value = data.csrf_token;
    }
    loadToken();
</script>
```

### "CSRF token mismatch"

**Causa:** Token foi alterado ou expirou (mais de 1 hora).

**Solução:** Recarregue a página para obter novo token.

### AJAX ainda menciona CSRF em produção

**Causa:** `viabixCsrfAjaxScript()` não foi incluído ou falhou.

**Solução:**
```php
<head>
    <script>
        <?php 
        if (!function_exists('viabixCsrfAjaxScript')) {
            echo "console.error('CSRF script não carregado');";
        } else {
            echo viabixCsrfAjaxScript(); 
        }
        ?>
    </script>
</head>
```

---

## Segurança

✅ **Implementado:**
- Token único por sessão
- Comparação com `hash_equals()` (timing-safe)
- Regeneração automática a cada 1 hora
- Sanitização de headers
- Rastreamento em Sentry
- Suporte a múltiplas fontes de token (POST, JSON, header)

⚠️ **Considerações:**
- Token armazenado em `$_SESSION` (não em cookie separado)
- Válido por sessão inteira (risco se sessão vazada)
- Recomenda-se usar HTTPS em produção + SameSite cookie
- AJAX automático funciona em domínio principal apenas

---

## Próximos Passos

1. ✅ CSRF protection (implementado)
2. 🔴 CORS Fix (próximo)
3. 🔴 Rate Limiting
4. 🔴 Email System
5. 🔴 Input Validation completa
