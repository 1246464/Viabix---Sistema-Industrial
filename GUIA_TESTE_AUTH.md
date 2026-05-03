# 🧪 GUIA DE TESTE - Sistema de Autenticação v2.0

**Data:** 03/05/2026  
**Objetivo:** Validar que o novo sistema de auth está funcionando corretamente

---

## ✅ Pré-requisitos

- [ ] PHP 8.0+
- [ ] MySQL 5.7+
- [ ] Composer (para PHPUnit)
- [ ] XAMPP/LAMP running
- [ ] Acesso ao banco `viabix_db`

---

## 🚀 TESTE 1: Validação de Sintaxe PHP

```bash
cd c:\xampp\htdocs\ANVI

# Verificar se arquivos estão corretos
php -l api/auth_system.php
php -l api/config.php
php -l api/permissions.php
php -l api/tests/AuthSystemTest.php

# Esperado: "No syntax errors detected in file"
```

---

## 🚀 TESTE 2: Execução de Migração SQL

```bash
# Executar migração de permissões
mysql -u root -p viabix_db < BD/migracao_permissoes.sql

# Verificar se tabelas foram criadas
mysql -u root -p viabix_db -e "SHOW TABLES LIKE 'roles%'; SHOW TABLES LIKE 'permissions%'; SHOW TABLES LIKE 'audit%';"

# Esperado:
# +-----------+
# | Tables_in_viabix_db |
# +-----------+
# | roles |
# | permissions |
# | role_permissions |
# | user_roles |
# | user_custom_permissions |
# | audit_logs |
# | security_events |
# +-----------+
```

---

## 🚀 TESTE 3: Testes Unitários (PHPUnit)

### 3.1: Instalar PHPUnit

```bash
cd c:\xampp\htdocs\ANVI

# Se ainda não tiver composer.json
composer init

# Instalar PHPUnit
composer require --dev phpunit/phpunit ^9.0
```

### 3.2: Rodar Testes

```bash
vendor/bin/phpunit api/tests/AuthSystemTest.php

# Esperado: ✓ 35 tests passed (pode variar conforme testes)
```

### 3.3: Teste Individual (Opcional)

```bash
# Rodar apenas um teste
vendor/bin/phpunit --filter testHasPermissionAdminHasAll api/tests/AuthSystemTest.php

# Esperado: ✓ 1 test passed
```

---

## 🚀 TESTE 4: Teste de API - Endpoint `/api/permissions`

### 4.1: Login no Sistema

Primeiro, fazer login normalmente em `login.html` e pegar a sessão.

```bash
curl -X POST http://localhost/ANVI/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","senha":"senha123"}' \
  -c cookies.txt

# Esperado (HTTP 200):
# {
#   "success": true,
#   "message": "Login bem-sucedido",
#   "user": {
#     "id": "...",
#     "nome": "Admin",
#     "role": "admin"
#   }
# }
```

### 4.2: Consultar Permissões

```bash
# Use o cookie da sessão anterior
curl -X GET http://localhost/ANVI/api/permissions.php \
  -b cookies.txt

# Esperado (HTTP 200):
# {
#   "success": true,
#   "user": {
#     "id": "...",
#     "nome": "Admin",
#     "role": "admin",
#     "tenant_id": "..."
#   },
#   "permissions": [
#     "usuarios:view",
#     "usuarios:create",
#     "usuarios:update",
#     ...
#     "admin_saas:view_tenants"
#   ]
# }
```

### 4.3: Teste Sem Autenticação

```bash
# Sem cookies
curl -X GET http://localhost/ANVI/api/permissions.php

# Esperado (HTTP 401):
# {
#   "success": false,
#   "error": "Não autenticado. Faça login novamente."
# }
```

---

## 🚀 TESTE 5: Teste de Segurança - IDOR Prevention

### 5.1: Criar 2 Usuários em Tenants Diferentes

**Tenant A:**
- Admin A
- User A

**Tenant B:**
- Admin B
- User B

```bash
# (Simulado - no seu banco)
# Admin A: id=uuid-1, tenant_id=tenant-a
# Admin B: id=uuid-2, tenant_id=tenant-b
```

### 5.2: Tentar IDOR (Deve Falhar)

```bash
# Admin A loga
curl -X POST http://localhost/ANVI/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"login":"admin_a","senha":"123456"}' \
  -c cookies_a.txt

# Admin A tenta acessar dados de Admin B
curl -X GET "http://localhost/ANVI/api/usuarios.php?id=uuid-2" \
  -b cookies_a.txt

# Esperado (HTTP 404 ou 403):
# {
#   "error": "Usuário não encontrado"  # Porque uuid-2 não é do tenant-a
# }
```

---

## 🚀 TESTE 6: Teste de Autorização

### 6.1: Usuário sem Permissão

```bash
# User A (sem permissão 'usuarios:create') tenta criar usuário
curl -X POST http://localhost/ANVI/api/usuarios.php \
  -H "Content-Type: application/json" \
  -b cookies_a.txt \
  -d '{"login":"novo","nome":"Novo User","role":"editor"}'

# Esperado (HTTP 403):
# {
#   "success": false,
#   "error": "Acesso negado. Você não tem permissão para create em usuarios."
# }
```

### 6.2: Admin Com Permissão

```bash
# Admin A tenta criar usuário (deve funcionar)
curl -X POST http://localhost/ANVI/api/usuarios.php \
  -H "Content-Type: application/json" \
  -b cookies_a.txt \
  -d '{"login":"novo","nome":"Novo User","role":"editor"}'

# Esperado (HTTP 201):
# {
#   "success": true,
#   "message": "Usuário criado com sucesso",
#   "data": { "id": "...", "login": "novo", ... }
# }
```

---

## 🚀 TESTE 7: Teste de CSRF Protection

### 7.1: Requisição sem CSRF Token

```bash
# Tentar POST sem CSRF token
curl -X POST http://localhost/ANVI/api/usuarios.php \
  -H "Content-Type: application/json" \
  -b cookies_a.txt \
  -d '{"login":"test","nome":"Test"}'

# Esperado (HTTP 403):
# {
#   "success": false,
#   "error": "Validação CSRF falhou"
# }

# (Ou pode passar se TESTING_MODE está ativo)
```

---

## 🚀 TESTE 8: Teste de Auditoria

### 8.1: Verificar Logs

```bash
# Após fazer algumas operações, checar audit_logs
mysql -u root -p viabix_db -e "SELECT user_id, action, details, created_at FROM audit_logs ORDER BY created_at DESC LIMIT 5;"

# Esperado:
# | user_id     | action              | details                    | created_at          |
# |-------------|---------------------|----------------------------|---------------------|
# | uuid-1      | usuario.criado      | {"usuario_id":"..."}       | 2026-05-03 15:30:00 |
# | uuid-1      | permissions.consulted | {"action":"user_checked"} | 2026-05-03 15:29:00 |
```

---

## ✅ Checklist de Validação

- [ ] Sintaxe PHP: Todos os 3 arquivos OK
- [ ] Migração SQL: 7 tabelas criadas
- [ ] Testes PHPUnit: 35 testes passando
- [ ] Endpoint `/api/permissions`: Retorna JSON com permissões
- [ ] IDOR Prevention: Usuario A não acessa dados de B
- [ ] Autorização: Sem permissão = erro 403
- [ ] CSRF Protection: POST sem token = erro 403
- [ ] Auditoria: Operações registradas em `audit_logs`

---

## 🐛 Troubleshooting

### Problema: "Call to undefined function viabixRequireAuthentication()"

**Solução:** Verificar se `auth_system.php` está sendo incluído em `config.php`

```php
// Em api/config.php, após sentry.php:
require_once __DIR__ . '/auth_system.php';
```

### Problema: "Table 'viabix_db.roles' doesn't exist"

**Solução:** Executar migração SQL

```bash
mysql -u root -p viabix_db < BD/migracao_permissoes.sql
```

### Problema: Tests failing com "Parse error"

**Solução:** Verificar sintaxe

```bash
php -l api/tests/AuthSystemTest.php
php -l api/auth_system.php
```

### Problema: "Permission denied" ao rodar tests

**Solução:** Verificar permissões de arquivo

```bash
chmod 755 api/tests/
chmod 644 api/tests/AuthSystemTest.php
```

---

## 📞 Próximos Passos

1. ✅ Passar em todos os 8 testes acima
2. [ ] Atualizar `api/usuarios.php` (em progresso)
3. [ ] Atualizar `api/anvi.php` com IDOR prevention
4. [ ] Atualizar `Controle_de_projetos/` para usar novo auth
5. [ ] Criar CI/CD com testes automáticos
6. [ ] Deploy para produção

---

**Good Luck!** 🚀
