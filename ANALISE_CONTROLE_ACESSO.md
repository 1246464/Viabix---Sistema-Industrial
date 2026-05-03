# 🔐 Análise de Problemas de Controle de Acesso - VIABIX

**Data:** 03/05/2026  
**Status:** ⚠️ CRÍTICO - Múltiplas falhas de segurança e autorização

---

## 📋 Resumo Executivo

O sistema tem **sérios problemas de lógica de controle de acesso** que permitem:
- ❌ Usuários não autenticados acessarem recursos
- ❌ Usuários comuns acessarem áreas de admin
- ❌ Falta de validação de permissões em várias APIs
- ❌ Dados multi-tenant expostos entre tenants
- ❌ Sem identificação/autenticação em pontos críticos
- ❌ Vulnerabilidades de IDOR (Insecure Direct Object Reference)

---

## 🔴 PROBLEMAS CRÍTICOS (Prioridade Alta)

### 1. Falta de Autenticação em Pontos de Entrada (CRITICAL)

**Problema:** Páginas não verificam autenticação ANTES de renderizar conteúdo

**Exemplo - `Controle_de_projetos/index.php`:**
```php
<?php
require_once 'auth.php';  // ← Só aqui faz check
$usuario = getUsuario();
?>
<!DOCTYPE html>...       // ← Mas já começou a renderizar!
```

**Risco:** Se `auth.php` falhar (exceção, redirect não funciona), página renderiza com JavaScript não autorizado.

**Evidência:**
```javascript
// index.php line 2204
<?php if (isAdmin()): ?>  // ← Permissão checada no SERVIDOR
// Mas isso fica no HTML/JavaScript do CLIENTE
```

**Impacto:** Um usuário comum vê o botão "Gerenciar Usuários" mesmo sem permissão.

---

### 2. Duplicação de Verificação de Autenticação (LOGIC ERROR)

**Problema:** Dois sistemas de autenticação paralelos:

1. **Sistema 1 - Controle_de_projetos:**
   - `auth.php` com `checkAuth()`, `isAdmin()`, `checkLevel()`
   - Session name: `viabix_session`
   - Níveis: `admin`, `usuario`, `visitante`

2. **Sistema 2 - API/Config:**
   - `config.php` com `viabixRequireAuthenticatedSession()`, `viabixRequireAdminSession()`
   - Session name: `SESSION_NAME` (também `viabix_session`)
   - Níveis: mesmos, mas manipulação diferente

**Problema:** Scripts não sabem qual usar!

```php
// Em Controle_de_projetos/usuarios_manager.php
function checkLevel($requiredLevel) {  // ← Define aqui
    $userLevel = $levels[$_SESSION['user_level']] ?? 0;
}

// Em api/config.php
function checkLevel($requiredLevel) {  // ← Define aqui também (CONFLITO!)
    $userLevel = $levels[$_SESSION['user_level']] ?? 0;
}
```

---

### 3. Gerenciamento de Usuários SEM Proteção Adequada

**Arquivo:** `Controle_de_projetos/usuarios_manager.php`

```php
<?php
require_once 'auth.php';

// Apenas admin pode acessar esta página
if (!isAdmin()) {
    header('Location: index.php');
    exit;
}
```

**Problema 1:** Redireciona para `index.php`, mas:
- Não limpa sessão
- Não tira dados sensíveis da memória
- JavaScript já foi carregado no cliente

**Problema 2:** Não verifica se usuário ainda está autenticado

```php
$usuario = getUsuario();  // ← Pode ser NULL!
// Usar $usuario sem verificar...
```

---

### 4. API `/api/usuarios.php` Sem Isolamento Multi-Tenant (CRITICAL)

**Arquivo:** `api/usuarios.php`

```php
$sql = "SELECT id, login, nome, nivel, ativo FROM usuarios";
$params = [];
if ($tenantAwareUsuarios) {
    $sql .= " WHERE tenant_id = ?";
    $params[] = $tenant_id;
}
```

**Problema:** 
- Se `tenantAwareUsuarios` for FALSE, lista TODOS os usuários de TODOS os tenants!
- Nenhuma validação que `tenant_id` é realmente do usuário logado

**Cenário Malicioso:**
```javascript
// Usuario do tenant B faz:
fetch('api/usuarios.php', { credentials: 'include' })
// Se tenant_id NOT está no WHERE, vê usuários do tenant A, C, etc!
```

---

### 5. Falta de Validação de Propriedade de Recurso (IDOR)

**Arquivo:** `api/anvi.php`

```php
case 'GET':
    if (isset($_GET['id'])) {
        if ($tenantAwareAnvis) {
            $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$_GET['id'], $tenant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ?");
            $stmt->execute([$_GET['id']]);  // ← BUG! Sem tenant_id!
        }
```

**Risco IDOR:** Um usuário do tenant 1 acessa ANVI do tenant 2:
```bash
curl 'https://viabix.com.br/api/anvi.php?id=ANVI-2025-001'
# Sem validação tenant_id, pode retornar dados do outro tenant!
```

---

### 6. Sem Autenticação no "Gerenciar Usuários" da API

**Arquivo:** `Controle_de_projetos/api_usuarios.php`

```php
<?php
require_once 'auth.php';
require_once 'config.php';

header('Content-Type: application/json');

// Apenas admin pode gerenciar usuários
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}
```

**Problema:** A sessão é iniciada em `auth.php`:
```php
// Em auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_name('viabix_session');
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}
```

**Mas:** Não há CSRF token validation! Um formulário malicioso pode:
```html
<!-- Em site atacante -->
<form action="https://viabix.com.br/Controle_de_projetos/api_usuarios.php" method="POST">
    <input name="action" value="create">
    <input name="username" value="novo_admin">
    <input name="senha" value="123456">
    <input name="nivel" value="admin">
</form>
<script>document.forms[0].submit();</script>
```

---

### 7. Sem Validação do `tenant_id` Passado Pelo Cliente

**Arquivo:** `Controle_de_projetos/auth.php`

```php
function tenantAtivo() {
    $tenantStatus = $_SESSION['tenant_status'] ?? null;
    $subscriptionStatus = $_SESSION['subscription_status'] ?? null;
    // ...
}
```

**Problema:** Aqui está bem. MAS:

**Arquivo:** `api/config.php`

```php
function viabixCurrentTenantId() {
    if (!isset($_SESSION['tenant_id'])) {
        return null;
    }
    return $_SESSION['tenant_id'];
}
```

**Risco:** Um usuário pode:
1. Fazer login no tenant A
2. Modificar a sessão (via JavaScript malicioso, CSRF, etc)
3. Mudar `$_SESSION['tenant_id']` para B
4. Acessar dados do tenant B!

---

### 8. Controle de Acesso Baseado em JavaScript (CLIENT-SIDE)

**Arquivo:** `Controle_de_projetos/index.php`

```html
<?php if (isAdmin()): ?>
    <button onclick="abrirGerenciadorUsuarios()">Gerenciar Usuários</button>
<?php endif; ?>
```

**Problema:** Isso é apenas UI! Um usuário comum pode:

```javascript
// No console do navegador
document.getElementById('user-role').innerText = 'admin';
// Ou via DevTools, adicionar o botão de volta
```

**Não faz nada**, já que o backend faz check. MAS:
- Confunde desenvolvedores
- Criadores pode pensar que está protegido quando não está
- Mistura lógica cliente/servidor

---

## 🟠 PROBLEMAS MÉDIOS (Prioridade Média)

### 9. Sem Validação de Entrada em Operações Críticas

**Arquivo:** `Controle_de_projetos/api_usuarios.php`

```php
case 'create':
    $username = $_POST['username'] ?? '';
    $senha = $_POST['senha'] ?? '';
    $nome = $_POST['nome'] ?? '';
    $nivel = $_POST['nivel'] ?? 'visualizador';
    
    if (empty($username) || empty($senha) || empty($nome)) {
        // ...
    }
    
    // MAS: Nenhuma validação que $nivel é válido!
    // Um admin poderia fazer:
    // $nivel = 'super_admin' (não existe, mas que tenta?)
```

**Risco:** SQL Injection (se não usar prepared statements, mas aqui usa).

---

### 10. Sem Tenta de Rate Limiting em APIs Críticas

**Arquivo:** `api/usuarios.php`, `Controle_de_projetos/api_usuarios.php`

```php
// Nenhum rate limiting!
// Um atacante pode fazer:
for (let i = 0; i < 10000; i++) {
    fetch('api/usuarios.php', { credentials: 'include' });
}
```

**Comparação com Login:** `api/login.php` tem rate limiting:
```php
$rate_limit_check = viabixCheckIpRateLimit('login', 5, 300);
if (!$rate_limit_check['allowed']) { exit; }
```

**Mas APIs de dados NÃO têm!**

---

### 11. Sem Auditoria em Operações Sensíveis

**Arquivo:** `Controle_de_projetos/api_usuarios.php`

```php
case 'create':
    // Cria usuário MAS:
    // - Nenhum log de quem criou
    // - Nenhum timestamp de quando
    // - Nenhuma notificação ao admin
```

**Problema:** Se houver violação, não há como rastrear.

---

### 12. Senha Armazenada em Plain Text em Respostas!

**Arquivo:** `Controle_de_projetos/api_usuarios.php`

```php
case 'create':
    echo json_encode([
        'success' => true,
        'usuario' => [
            'id' => $conn->insert_id,
            'username' => $username,
            'senha' => $senha,  // ← RETORNA A SENHA!
            'nome' => $nome,
            'nivel' => $nivel
        ]
    ]);
```

**Risco:** A senha é enviada em JSON na resposta HTTP. Se não for HTTPS, é interceptável!

---

### 13. Sem Validação que Usuário Pode Gerenciar Outro Usuário

**Arquivo:** `Controle_de_projetos/api_usuarios.php`

```php
case 'update':
    $id = $_POST['id'] ?? 0;
    // Apenas verifica se é admin, MAS:
    // - Não verifica se é admin DO MESMO TENANT!
    
    $stmt = $pdo->prepare("UPDATE usuarios SET ... WHERE id = ?");
```

**Cenário:** Admin do tenant A modifica usuário do tenant B!

---

## 🟡 PROBLEMAS MENORES (Prioridade Baixa)

### 14. Sem Versionamento de Autorização

Sem saber se o `nivel` mudou:
```php
// Usuário editado em time real, mas sessão dele continua com nível antigo
// Ele precisa fazer logout/login para ver mudança
```

### 15. Sem Revogação de Sessão

Não há como fazer logout forçado:
```bash
# Um admin desabilita um usuário MAS:
# - Session dele continua válida
# - Só expira após 8 horas
```

### 16. Sem Contexto de Tenant em Logs

```php
// logs_atividade registra usuario_id MAS:
// - Não registra tenant_id
// - Difícil auditar por tenant
```

---

## 📊 Matriz de Risco

| Problema | Severidade | Tipo | Impacto |
|----------|-----------|------|---------|
| 1. Sem autenticação em entrada | 🔴 CRÍTICO | Auth | Acesso não autorizado |
| 2. Duplicação de verificação | 🔴 CRÍTICO | Logic | Inconsistência |
| 3. Gerenciador sem proteção | 🔴 CRÍTICO | Auth | Modificação de usuários |
| 4. API sem isolamento tenant | 🔴 CRÍTICO | MultiTenant | Vazamento de dados |
| 5. IDOR em recursos | 🔴 CRÍTICO | Auth | Acesso cruzado |
| 6. Sem CSRF em API crítica | 🔴 CRÍTICO | CSRF | Forged requests |
| 7. Validação tenant fraca | 🔴 CRÍTICO | Auth | Escalação |
| 8. Controle JavaScript | 🟠 MÉDIO | Design | Confusão dev |
| 9. Sem validação entrada | 🟠 MÉDIO | Input | Injection |
| 10. Sem rate limiting | 🟠 MÉDIO | DoS | Brute force |
| 11. Sem auditoria | 🟠 MÉDIO | Compliance | Rastreamento |
| 12. Senha em resposta | 🟠 MÉDIO | Leakage | Exposição |
| 13. Sem tenant check | 🟠 MÉDIO | MultiTenant | Acesso cruzado |
| 14-16. Menores | 🟡 BAIXO | UX/Design | Experiência |

---

## 🛠️ Impacto nos Casos de Uso

### Caso 1: "Usuário Comum Acessa Gerenciar Usuários"

**Fluxo Malicioso:**
1. Usuário comum faz login ✅
2. Acessa `Controle_de_projetos/usuarios_manager.php` ❌
3. Página tenta redirect em `if (!isAdmin())` ⚠️
   - A PÁGINA JÁ FOI CARREGADA NO CLIENTE
   - Elementos HTML podem estar visíveis
4. Usuário abre DevTools e interage com formulários ocultos ❌

**Solução:** Verificar em ANTES de renderizar, não durante.

---

### Caso 2: "Admin Tenant A Vê Usuários Tenant B"

**Fluxo Malicioso:**
1. Admin A faz login → `session['tenant_id'] = 'tenant-a'` ✅
2. Acessa `/api/usuarios.php` 
3. Backend verifica `if ($tenantAwareUsuarios) { ... }`
4. **Se FALSE** → Lista TODOS os usuários ❌❌❌
5. Admin A vê credenciais de Admin B ❌

---

### Caso 3: "Usuário Modifica Session para Trocar Tenant"

**Fluxo Malicioso:**
1. Usuário comum do tenant A
2. Via JavaScript malicioso:
   ```javascript
   // Tenta modificar cookie de sessão
   // Não vai funcionar direto (httpOnly) MAS
   // Pode explorar CSRF:
   ```
3. Cria um formulário que muda `tenant_id`
4. Se validação for fraca, acessa tenant B ❌

---

## ✅ Recomendações de Correção

### Curto Prazo (URGENTE)

1. **Unificar Sistema de Auth**
   - Usar apenas `api/config.php` + suas funções
   - Remover `Controle_de_projetos/auth.php`
   - Garantir mesmo comportamento em todo app

2. **Validar Tenant em Toda API**
   ```php
   // NO COMEÇO DE CADA ENDPOINT
   $user = viabixRequireAuthenticatedSession();
   $tenant_id = $user['tenant_id'];
   
   // E sempre usar tenant_id em WHERE:
   $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE tenant_id = ? AND ...");
   ```

3. **CSRF Protection em APIs Críticas**
   ```php
   // Em usuarios_manager.php, api_usuarios.php
   if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
       viabixValidateCsrfToken();
   }
   ```

4. **Autenticação Antes de Renderizar**
   ```php
   <?php
   require_once 'auth.php';
   viabixRequireAuthenticatedSession();  // ANTES
   viabixRequireAdminSession();
   ?>
   <!DOCTYPE html>  <!-- DEPOIS -->
   ```

5. **Rate Limiting em APIs Sensíveis**
   ```php
   // Em api_usuarios.php
   $rate_limit = viabixCheckIpRateLimit('admin:usuarios', 30, 60);
   if (!$rate_limit['allowed']) { exit; }
   ```

### Médio Prazo

6. **Validação de Entrada Strict**
   - Whitelisting de valores (não blacklisting)
   - Tipo validation (enum para níveis)

7. **Auditoria de Operações Sensíveis**
   - Log em `logs_atividade` com tenant_id
   - Registrar quem, quando, o quê, resultado

8. **Sem Senhas em Respostas**
   - Remover campo `senha` de qualquer resposta JSON
   - Usar hash para comparação apenas

9. **Revogação de Sessão Imediata**
   - Ao desabilitar usuário
   - Ao trocar nível de permissão
   - Usar Redis com lista de sessões revogadas

10. **Testes de Autorização**
    - Unit tests para cada função de auth
    - Integration tests para cada endpoint
    - Security tests para IDOR, CSRF, etc

---

## 📁 Arquivos Afetados

```
CRÍTICO:
├── Controle_de_projetos/
│   ├── auth.php              ← Duplicado
│   ├── usuarios_manager.php  ← Sem proteção
│   └── api_usuarios.php      ← Sem CSRF
├── api/
│   ├── config.php            ← Duplicação
│   ├── usuarios.php          ← Sem isolamento tenant
│   ├── anvi.php              ← IDOR
│   └── admin_saas.php        ← Checar tenant isolation

MÉDIO:
├── dashboard.html            ← Controle JS
├── admin_saas.html
└── index.html
```

---

## 🎯 Próximos Passos

1. **Hoje:** Ler esta análise
2. **Amanhã:** Implementar correções CRÍTICAS
3. **Semana que vem:** Testes e validação
4. **Próximo mês:** Framework completo de autorização

---

## 📞 Questões Abertas

1. **Qual é a estrutura de tenants esperada?**
   - Um usuário = um tenant? Ou usuário pode ter múltiplos tenants?

2. **Qual modelo de permissões?**
   - Role-based (admin/user/viewer)?
   - Resource-based (quem pode editar cada ANVI)?

3. **Qual é a política de expiração de sessão?**
   - 8 horas? Renovado a cada ação?

4. **Há autenticação 2FA?**
   - Se sim, como validar?

---

**Próximo:** Aguardando decisão para implementar correções.
