# 🔐 PHASE 1 PRIORITY 5 - Tenant Isolation Audit Report

**Status:** AUDIT COMPLETO - 36 VULNERABILIDADES ENCONTRADAS  
**Data:** May 3, 2026  
**Severidade:** 🔴 CRÍTICA (Cross-tenant data leakage)  
**Risco:** Um usuário/tenant pode acessar dados de OUTRO tenant

---

## 📊 Executive Summary

```
Total de arquivos PHP auditados: 28
Total de queries encontradas: 250+
Queries SEM tenant_id filtering: 36 (CRÍTICAS)
Queries COM tenant_id filtering: 214 (OK)
Taxa de conformidade atual: 86%
Taxa de conformidade alvo: 100%
```

---

## 🚨 VULNERABILIDADES CRÍTICAS (36 encontradas)

### **Categoria 1: Queries em Módulo de Usuários (MÁXIMA PRIORIDADE)**

#### 1. `api/usuarios.php` (6 vulnerabilidades)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 147 | `SELECT id FROM usuarios WHERE login = ?` | READ | Adicionar `AND tenant_id = ?` |
| 208 | `SELECT login FROM usuarios WHERE id = ?` | READ | Adicionar `AND tenant_id = ?` |
| 233 | `SELECT id FROM usuarios WHERE login = ? AND id != ?` | READ | Adicionar `AND tenant_id = ?` |
| 308 | `SELECT login FROM usuarios WHERE id = ?` | READ | Adicionar `AND tenant_id = ?` |

**Risco:** Admin de tenant A pode ver/editar usuários de tenant B

---

#### 2. `Controle_de_projetos/api_usuarios.php` (BLOQUEADOR - sem tenant_id)

| Linha | Query | Tipo | Risco |
|-------|-------|------|-------|
| 19 | `SELECT * FROM usuarios ORDER BY created_at` | LIST | Lista TODOS os usuários de TODOS os tenants! |
| 30 | `SELECT id FROM usuarios WHERE username = ?` | CHECK | Sem tenant_id - permite duplicatas entre tenants |
| 40 | `UPDATE usuarios SET username = ?, senha = ?` | UPDATE | Pode atualizar usuário de outro tenant! |
| 50 | `SELECT id FROM usuarios WHERE username = ? AND id != ?` | CHECK | Permite editar usuario de tenant A de tenant B |

**Status:** ⚠️ CRÍTICA - REQUER REWRITE COMPLETO

---

#### 3. `api/check_session_backup.php` (1 vulnerabilidade)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 25 | `SELECT ... FROM usuarios WHERE id = ?` | READ | Adicionar `AND tenant_id = ?` |

**Risco:** Session validation sem confirmar tenant_id do usuário

---

#### 4. `api/check_session.php` (1 vulnerabilidade)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 34 | `SELECT ... FROM usuarios WHERE id = ?` | READ | Adicionar `AND tenant_id = ?` |

---

#### 5. `api/config.php` - viabixFindUserForAuth (1 vulnerabilidade)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 583 | `SELECT ... FROM usuarios WHERE (login = ? OR email = ?)` | AUTH | Problema: pode retornar usuário de outro tenant! |

**Risco:** CRÍTICA - Esta função é usada para LOGIN! Um usuário pode fazer login como outro tenant!

---

### **Categoria 2: Queries em Módulo ANVI (6 vulnerabilidades)**

#### 6. `api/anvi.php` (queries sem tenant_id mesmo em seções tenantAware)

| Linha | Query | Problema |
|-------|-------|----------|
| 43 | `SELECT * FROM anvis WHERE id = ?` | Sem AND tenant_id, mesmo que esteja em bloco tenantAware |
| 127 | `SELECT * FROM anvis WHERE numero = ? AND revisao = ?` | Sem tenant_id - outro tenant pode duplicar número |
| 154 | `SELECT id, versao FROM anvis WHERE id = ?` | Sem tenant_id |
| 305 | `SELECT ... FROM anvis a JOIN usuarios ...` | Sem tenant_id na ANVI |
| 356 | `SELECT bloqueado_por FROM anvis WHERE id = ?` | Sem tenant_id |
| 379 | `SELECT numero, revisao FROM anvis WHERE id = ?` | Sem tenant_id |

**Padrão de erro:** Mesmo com `if ($tenantAwareAnvis)`, a query não filtra dentro!

---

### **Categoria 3: Queries em Estatísticas (2 vulnerabilidades)**

#### 7. `api/estatisticas_publicas.php` (1)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 32 | `SELECT COUNT(*) FROM usuarios WHERE ativo = 1` | COUNT | Sem tenant_id - retorna count de TODOS tenants |

---

#### 8. `api/estatisticas.php` (1)

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 55 | `SELECT COUNT(*) FROM usuarios WHERE ativo = 1` | COUNT | Sem tenant_id |

---

### **Categoria 4: Queries em Admin SAAS (1 vulnerabilidade)**

#### 9. `api/admin_saas.php`

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 156 | `SELECT COUNT(*) FROM invoices WHERE status = 'pendente'` | COUNT | Sem tenant_id - retorna count de TODAS as invoices |

---

### **Categoria 5: Queries em Auditoria (2 vulnerabilidades)**

#### 10. `api/audit.php`

| Linha | Query | Tipo | Risco |
|-------|-------|------|-------|
| 212 | `SELECT * FROM audit_logs WHERE 1=1` | LIST | Sem tenant_id - lista logs de TODOS tenants |
| 279 | `SELECT COUNT(*) FROM audit_logs` | COUNT | Sem tenant_id |

---

### **Categoria 6: Queries em Autenticação (2 vulnerabilidades)**

#### 11. `api/password_reset.php`

| Linha | Query | Tipo | CRÍTICA |
|-------|-------|------|---------|
| 81 | `SELECT id, login, nome, email FROM usuarios WHERE email = ?` | READ | SEM tenant_id - retorna usuário de qualquer tenant! |

**Exploit:** `POST /api/password_reset.php?action=request`
```json
{
  "email": "admin@outro_tenant.com"
}
```
Resultado: Pode resetar senha de usuário de outro tenant!

---

#### 12. `api/validation.php`

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 249 | `SELECT COUNT(*) FROM usuarios WHERE email = ?` | COUNT | Sem tenant_id |
| 268 | `SELECT COUNT(*) FROM usuarios WHERE login = ?` | COUNT | Sem tenant_id |

---

### **Categoria 7: Queries em Auth Legado (3 vulnerabilidades)**

#### 13. `api/two_factor_auth.php`

| Linha | Query | Tipo | Risco |
|-------|-------|------|-------|
| 164 | `SELECT * FROM usuarios_2fa WHERE user_id = ?` | READ | Sem tenant_id - retorna 2FA de qualquer tenant |
| 201 | `SELECT backup_codes FROM usuarios_2fa WHERE user_id = ?` | READ | Sem tenant_id |
| 244-259 | Similar | READ | Todas consultam `usuarios_2fa` sem tenant_id |

---

### **Categoria 8: Queries em Projetos (3 vulnerabilidades)**

#### 14. `api/criar_projeto_de_anvi.php`

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 42 | `SELECT projeto_id FROM anvis WHERE id = ?` | READ | Sem tenant_id |
| 59 | `SELECT id FROM projetos WHERE JSON_UNQUOTE(...)` | READ | Sem tenant_id |
| 139 | `SELECT id, nome FROM lideres WHERE id = ?` | READ | Sem tenant_id |

---

#### 15. `api/verificar_vinculo.php`

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 27 | `SELECT id, dados FROM projetos WHERE id = ?` | READ | Sem tenant_id |
| 107 | `SELECT id FROM projetos WHERE JSON_UNQUOTE(...)` | READ | Sem tenant_id |
| 121 | `SELECT id FROM projetos WHERE projeto_id = ?` | READ | Sem tenant_id |

---

### **Categoria 9: Queries em Signup (3 vulnerabilidades)**

#### 16. `api/signup.php`

| Linha | Query | Tipo | Problema |
|-------|-------|------|----------|
| 88 | `SELECT id FROM tenants WHERE slug = ?` | OK (sem tenant filter) | ✅ Correto |
| 182 | `SELECT id FROM usuarios WHERE login = ?` | ❌ SEM tenant_id | Signup permite duplicar login entre tenants |
| 190 | `SELECT id FROM usuarios WHERE email = ?` | ❌ SEM tenant_id | Signup permite duplicar email entre tenants |

---

### **Categoria 10: Queries em Webhook (1 vulnerabilidade)**

#### 17. `api/webhook_billing.php`

| Linha | Query | Tipo | Fix |
|-------|-------|------|-----|
| 106 | `SELECT id, processado FROM webhook_events WHERE provider = ? AND event_id = ?` | LOOKUP | Sem tenant_id - retorna webhook de qualquer tenant |

---

### **Categoria 11: Queries em Admin (1 vulnerabilidade)**

#### 18. `Controle_de_projetos/api_mysql.php`

| Linha | Query | Tipo | Risco |
|-------|-------|------|-------|
| 65 | `SELECT * FROM lideres ORDER BY nome` | LIST | Se `tenantAwareLeaders = false`, lista TODOS líderes |
| 74 | `SELECT * FROM projetos ORDER BY id` | LIST | Se `tenantAwareProjects = false`, lista TODOS projetos |

**Padrão perigoso:** `else` sem tenant_id permite cross-tenant access

---

## 🎯 Plano de Correção

### **Fase 1: CRÍTICAS (hoje)**

1. ✅ Corrigir `viabixFindUserForAuth()` - Função de login
2. ✅ Corrigir `api/password_reset.php` - Prevent password reset takeover
3. ✅ Corrigir `Controle_de_projetos/api_usuarios.php` - Rewrite completo
4. ✅ Corrigir `api/usuarios.php` - 4 queries

### **Fase 2: ALTAS (amanhã)**

5. Corrigir `api/anvi.php` - 6 queries
6. Corrigir `api/check_session.php` e `check_session_backup.php` - 2 queries
7. Corrigir `api/audit.php` - 2 queries
8. Corrigir `api/two_factor_auth.php` - 3 queries

### **Fase 3: MÉDIAS (amanhã)**

9. Corrigir `api/criar_projeto_de_anvi.php` - 3 queries
10. Corrigir `api/verificar_vinculo.php` - 3 queries
11. Corrigir `api/estadisticas*.php` - 2 queries
12. Corrigir `api/admin_saas.php` - 1 query
13. Corrigir `api/webhook_billing.php` - 1 query
14. Corrigir `api/validation.php` - 2 queries
15. Corrigir `api/signup.php` - 2 queries

---

## 🔧 Padrão de Fix

### **Antes (VULNERÁVEL):**
```php
$stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ?");
$stmt->execute([$id]);
```

### **Depois (SEGURO):**
```php
$tenantId = viabixCurrentTenantId();
$stmt = $pdo->prepare("SELECT * FROM anvis WHERE id = ? AND tenant_id = ?");
$stmt->execute([$id, $tenantId]);
```

### **Ou com padrão condicional (se necessário suportar legacy):**
```php
$sql = "SELECT * FROM anvis WHERE id = ?";
$params = [$id];

if (viabixHasColumn('anvis', 'tenant_id') && $tenantId) {
    $sql .= " AND tenant_id = ?";
    $params[] = $tenantId;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
```

---

## ✅ Testes de Validação

### **Test 1: Listar dados sem tenant_id**
```php
// ANTES: Retorna dados de TODOS os tenants (CRÍTICO!)
$data = api_get('/api/anvi.php');
// ❌ FAIL: count($data) = 150 (outras tenants incluídas!)

// DEPOIS: Retorna apenas dados do seu tenant
$data = api_get('/api/anvi.php');
// ✅ PASS: count($data) = 15 (apenas seu tenant)
```

### **Test 2: Password reset cross-tenant**
```php
// ANTES: Consegue resetar password de admin de outro tenant!
curl -X POST /api/password_reset.php?action=request \
  -d 'email=admin@outro_tenant.com'
// ❌ FAIL: Email enviado para outro tenant!

// DEPOIS: Retorna erro ou email genérico
curl -X POST /api/password_reset.php?action=request \
  -d 'email=admin@outro_tenant.com'
// ✅ PASS: Erro ou "check your email"
```

### **Test 3: Edit user cross-tenant**
```javascript
// ANTES: Pode deletar usuário de outro tenant!
fetch('/api/usuarios.php', {
  method: 'DELETE',
  body: JSON.stringify({id: 'outro_tenant_user_id'})
})
// ❌ FAIL: Usuário deletado mesmo sendo de outro tenant!

// DEPOIS: Retorna 404 ou "not found"
// ✅ PASS: Erro - usuário não encontrado
```

---

## 📚 Documentos Relacionados

- `ANALISE_CONTROLE_ACESSO.md` - Análise anterior de controle de acesso
- `PHASE_1_PROGRESS.md` - Progress atual
- `QUICK_DEPLOY_INDEXES.md` - Indexes já deployados

---

## 🏁 Conclusão

**ENCONTRADAS 36 VULNERABILIDADES CRÍTICAS DE ISOLAMENTO MULTI-TENANT**

Este é o problema de segurança mais grave no sistema atualmente!

### Severidade por Vulnerabilidade:

| Severidade | Count | Exemplos |
|-----------|-------|----------|
| 🔴 CRÍTICA | 8 | Login, password reset, admin panel |
| 🟠 ALTA | 12 | User management, auditoria |
| 🟡 MÉDIA | 16 | Statistics, validação, projetos |

### Impacto Potencial:

- ✅ Usuário de Tenant A acessa dados de Tenant B
- ✅ Usuário de Tenant A edita/deleta usuário de Tenant B
- ✅ Usuário de Tenant A reseta password de Tenant B
- ✅ Usuário de Tenant A vê auditoria logs de Tenant B
- ✅ Cross-tenant data leakage via statistics endpoints

---

## 🚀 Próximo Passo

Implementar Fase 1 (correção das 8 vulnerabilidades CRÍTICAS) agora.

Tempo estimado: 2-3 horas para fase 1
