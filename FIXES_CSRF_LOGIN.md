# Login & CSRF Fixes - Viabix

## Problemas Corrigidos

### 1. **JSON Parse Error** ❌ → ✅
**Problema:** `JSON.parse: unexpected character at line 1 column 1`
- `check_session.php` retornava 204 No Content sem JSON
- JavaScript tentava fazer `.json()` em resposta vazia

**Solução:**
- ✅ Adicionado `http_response_code(200)` em check_session.php
- ✅ Melhorado tratamento de resposta em login.html com validação de `Content-Type`

**Arquivos modificados:**
- `api/check_session.php` - Linha final: Garantir status 200 OK
- `login.html` - Função `carregarCsrfToken()`: Validar Content-Type antes de parse

---

### 2. **CSRF Token Validation Failing** ❌ → ✅
**Problema:** `POST login.php retorna 403 Forbidden`
- O token CSRF não estava sendo validado corretamente
- `viabixValidateCsrfToken()` tentava ler `php://input` novamente
- Primeira leitura em login.php consumia o stream
- Segunda leitura em csrf.php retornava empty string

**Solução:**
- ✅ Nova função `viabixValidateCsrfTokenWithInput()` que aceita input já decodificado
- ✅ login.php agora chama `viabixValidateCsrfTokenWithInput($input)` com dados já decodificados
- ✅ Inicializa CSRF protection com `viabixInitializeCsrfProtection()`

**Arquivos modificados:**
- `api/login.php` - Usar `viabixValidateCsrfTokenWithInput($input)`
- `api/csrf.php` - Nova função `viabixValidateCsrfTokenWithInput()`

---

### 3. **Melhor Tratamento de Erros** ✅
**Adições:**
- ✅ Validação de header `Content-Type: application/json`
- ✅ Validação de corpo vazio antes de JSON parse
- ✅ Mensagens de erro mais específicas (403, 429, etc)
- ✅ Validação de CSRF token antes de tentar login

**Arquivo modificado:**
- `login.html` - Funções `carregarCsrfToken()`, `fazerLogin()`, `verificarSessao()`

---

## Como Sincronizar com DigitalOcean

### Opção 1: Cópia Manual (Recomendado)
```bash
# SSH para DigitalOcean
ssh root@146.190.244.133

# Faz backup dos arquivos
cd /var/www/viabix/api
cp login.php login.php.bak
cp csrf.php csrf.php.bak
cp check_session.php check_session.php.bak

# Copia os arquivos corrigidos
# Use SCP ou FTP para:
# - login.html (raiz)
# - api/login.php
# - api/csrf.php
# - api/check_session.php
# - api/diagnostico_csrf.php (novo)
```

### Opção 2: Git (Se usar versionamento)
```bash
git add -A
git commit -m "Fix CSRF token validation and JSON response handling"
git push origin main
# Fazer deploy no DigitalOcean
```

### Opção 3: Download Direto
Acesse via SFTP com credenciais e baixe:
```
/var/www/viabix/api/login.php
/var/www/viabix/api/csrf.php
/var/www/viabix/api/check_session.php
/var/www/viabix/login.html (se usar versão do servidor)
```

---

## Validar Correções

### Local (XAMPP)
```
http://localhost/api/diagnostico_csrf.php
```

### DigitalOcean
```
https://viabix.com.br/api/diagnostico_csrf.php
```

**Deve retornar:**
```json
{
  "csrf": {
    "token_exists": true,
    "token_length": 64,
    "initialized": true
  },
  "response": {
    "json_valid": true,
    "status_code": 200
  }
}
```

---

## Teste de Login Completo

### 1. Limpar cache do navegador
- F12 → Aplicação → Armazenamento → Limpar tudo

### 2. Recarregar login.html
```
http://localhost/login.html
OU
https://viabix.com.br/login.html
```

### 3. Verificar console (F12)
Não deve haver erros:
- ❌ "JSON.parse: unexpected character"
- ❌ "can't access property 'catch'"

### 4. Tentar login
```
Usuário: admin
Senha: [sua senha]
```

### 5. Verificar resposta (Network tab)
- POST login.php: Status **200** (não 403)
- Response: JSON válido com `"success": true`

---

## Differenças entre Local e DigitalOcean

| Aspecto | Local | DigitalOcean |
|---------|-------|--------------|
| PHP | 8.2+ (XAMPP) | 8.2+ (ajustar conforme versão) |
| Sessions | `/tmp` (default) | Redis (recomendado) |
| APP_ENV | development | production |
| TESTING_MODE | Pode estar TRUE | FALSE |
| Headers CORS | Permissivos (dev) | Restritivos |

**Se ainda com 403 após correção:**
1. Verificar `.env` - TESTING_MODE deve ser `false` em produção
2. Verificar session handler (Redis vs arquivo)
3. Verificar permissões de arquivo em `/var/www/viabix/api/`

---

## Troubleshooting Rápido

| Erro | Solução |
|------|---------|
| "CSV token ausente" | Recarregar página (carregarCsrfToken não rodou) |
| 403 Forbidden | Verificar `.env` TESTING_MODE=false |
| JSON parse error | Limpar cache, recarregar |
| 204 No Content | Substituir check_session.php |
| CORS error | Verificar CORS_ALLOWED_ORIGINS em .env |

---

## Arquivos Criados/Modificados

| Arquivo | Status | Motivo |
|---------|--------|--------|
| `login.html` | ✏️ Modificado | Melhor handling de erros |
| `api/login.php` | ✏️ Modificado | Usar input decodificado |
| `api/csrf.php` | ✏️ Modificado | Nova função |
| `api/check_session.php` | ✏️ Modificado | Garantir status 200 |
| `api/diagnostico_csrf.php` | 🆕 Criado | Diagnóstico de problemas |

---

## Próximas Ações

1. ✅ Deploy para DigitalOcean
2. ⏳ Teste completo de login
3. ⏳ Monitorar logs (`/var/log/viabix/error.log`)
4. ⏳ Validar com `diagnostico_csrf.php` em ambos ambientes

---

**Última atualização:** 2025-04-17
