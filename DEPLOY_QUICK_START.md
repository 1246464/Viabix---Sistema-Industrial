# 🚀 QUICK DEPLOY - VIABIX Auth V2.0 to DigitalOcean

**Tempo estimado:** 10 minutos  
**Dificuldade:** Fácil

---

## 📋 PRÉ-REQUISITOS

1. ✅ Droplet DigitalOcean já criado (Ubuntu 22.04 LTS)
2. ✅ IP do Droplet (ex: 146.190.244.133)
3. ✅ Chave SSH configurada (privada no seu PC)
4. ✅ Acesso SSH ao droplet

---

## 🪟 WINDOWS (PowerShell)

### Passo 1: Abrir PowerShell

```powershell
# No Windows, abrir PowerShell como administrador
# Ou no VS Code Terminal
```

### Passo 2: Executar Script de Deploy

```powershell
# Substituir IP e caminho da chave SSH conforme necessário
.\deploy-to-digitalocean.ps1 `
  -DropletIP "146.190.244.133" `
  -SSHKey "C:\Users\seu_usuario\.ssh\id_rsa"

# Ou usar padrões (se chave estiver em ~/.ssh/id_rsa)
.\deploy-to-digitalocean.ps1 -DropletIP "146.190.244.133"
```

### Passo 3: Acompanhar o Deploy

O script automaticamente:
- ✅ Valida conexão SSH
- ✅ Compacta arquivos novos
- ✅ Faz upload para /tmp/viabix-deploy
- ✅ Extrai os arquivos
- ✅ Copia para /var/www/html/
- ✅ Ajusta permissões
- ✅ Valida arquivos

---

## 🐧 LINUX / MAC (Bash)

### Passo 1: Abrir Terminal

```bash
cd /xampp/htdocs/ANVI
# ou seu diretório do projeto
```

### Passo 2: Executar Script

```bash
# Usar IP do seu droplet
bash deploy-to-digitalocean.sh --droplet-ip 146.190.244.133 --ssh-key ~/.ssh/id_rsa

# Ou simpler (se chave estiver no padrão)
bash deploy-to-digitalocean.sh --droplet-ip 146.190.244.133
```

### Passo 3: Acompanhar

Mesma coisa que Windows - script automático faz tudo.

---

## ✅ O Que o Script Faz

1. **Validação**
   - Checa se chave SSH existe
   - Testa conexão com droplet
   - Verifica arquivos necessários

2. **Preparação**
   - Lista arquivos para upload (13 arquivos)
   - Cria ZIP compactado
   - Mostra tamanho total

3. **Upload**
   - SCP dos arquivos para /tmp/viabix-deploy
   - Mostra progresso

4. **Deploy no Servidor**
   - Extrai arquivos
   - Copia para /var/www/html/api/
   - Copia para /var/www/html/BD/
   - Ajusta permissões (www-data)
   - Limpa arquivos temporários

5. **Validação**
   - Verifica se arquivos chegaram
   - Checa sintaxe PHP
   - Relata sucesso/erros

---

## 📦 Arquivos Transferidos

```
Novo:
  ✓ api/auth_system.php (700+ linhas)
  ✓ api/permissions.php (novo endpoint)
  ✓ api/tests/AuthSystemTest.php (testes)
  ✓ BD/migracao_permissoes.sql (7 tabelas)

Modificado:
  ✓ api/config.php (+1 linha de require)

Documentação:
  ✓ SUMARIO_ENTREGA_AUTH.md
  ✓ IMPLEMENTACAO_AUTH_V2.md
  ✓ GUIA_TESTE_AUTH.md

Scripts:
  ✓ deploy-auth-v2-digitalocean.sh
  ✓ validate_auth_system.sh

Config:
  ✓ .env.production (read-only no upload)
  ✓ composer.json
```

---

## 🎯 APÓS O UPLOAD

### 1️⃣ Conectar ao Servidor

```bash
ssh root@146.190.244.133
# ou com chave
ssh -i ~/.ssh/id_rsa root@146.190.244.133
```

### 2️⃣ Executar Migração SQL

```bash
cd /var/www/html

# Source .env para pegar credenciais
source .env.production

# Executar migração
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME < BD/migracao_permissoes.sql

# Ou manualmente se preferir
mysql -h db-mysql-xxx.h.db.ondigitalocean.com \
      -u doadmin \
      -p \
      --ssl-mode=REQUIRED \
      viabix_db < BD/migracao_permissoes.sql
```

### 3️⃣ Verificar Tabelas Criadas

```bash
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME \
  -e "SHOW TABLES LIKE 'roles%'; SHOW TABLES LIKE 'permissions%';"

# Esperado:
# +-----------+
# | Tables_in_viabix_db |
# +-----------+
# | roles |
# | permissions |
# | role_permissions |
# ...
```

### 4️⃣ Testar Endpoint

```bash
# 1. Fazer login primeiro
curl -X POST http://seu_dominio/ANVI/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","senha":"sua_senha"}' \
  -c cookies.txt

# 2. Consultar permissões
curl -X GET http://seu_dominio/ANVI/api/permissions.php \
  -b cookies.txt

# Esperado: JSON com permissões do usuário
```

### 5️⃣ Verificar Logs

```bash
# Logs de erro PHP
tail -f /var/www/html/logs/error.log

# Logs de auditoria (novos)
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME \
  -e "SELECT user_id, action, created_at FROM audit_logs LIMIT 10;"
```

---

## 🚨 TROUBLESHOOTING

### Erro: "Permission denied (publickey)"

**Causa:** SSH key não está configurada corretamente

```bash
# Verificar se tem chave privada
ls ~/.ssh/id_rsa

# Se não tiver, gerar
ssh-keygen -t ed25519 -f ~/.ssh/id_rsa -N ""

# Copiar chave pública para droplet
ssh-copy-id -i ~/.ssh/id_rsa root@146.190.244.133
```

### Erro: "Conectando SSH" timeout

**Causa:** Firewall bloqueando porta 22

Checklist:
- [ ] Droplet está ligado (checking console)
- [ ] Firewall do DigitalOcean permite porta 22 (TCP)
- [ ] Seu PC não está bloqueando SSH outbound

### Erro: "PHP Syntax Error in auth_system.php"

**Causa:** Arquivo corrompido no upload

Solução:
```bash
# Verificar arquivo
php -l /var/www/html/api/auth_system.php

# Se erro, re-download e upload manual
```

### Erro: "MySQL Connection failed"

**Causa:** Credenciais incorretas em .env.production

Solução:
```bash
# Editar .env.production
nano /var/www/html/.env.production

# Verificar:
DB_HOST=db-mysql-xxx.h.db.ondigitalocean.com
DB_USER=doadmin
DB_PASS=sua_senha
DB_NAME=viabix_db

# Testar conexão
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT 1;"
```

---

## 📊 Verificar Status do Deploy

```bash
# Conectado ao servidor
ssh root@146.190.244.133

# 1. Arquivos presentes?
ls -la /var/www/html/api/auth_system.php
ls -la /var/www/html/api/permissions.php
ls -la /var/www/html/BD/migracao_permissoes.sql

# 2. Permissões corretas?
ls -l /var/www/html/api/*.php | head -3

# 3. Sintaxe OK?
php -l /var/www/html/api/auth_system.php

# 4. Banco de dados?
mysql -h $DB_HOST -u $DB_USER -p$DB_PASS $DB_NAME -e "SELECT COUNT(*) FROM roles;"

# 5. Logs?
tail /var/www/html/logs/error.log
```

---

## 🎓 Exemplos de Testes

### Teste 1: Endpoint Simples

```bash
# Sem autenticação (deve falhar)
curl -i http://seu_dominio/ANVI/api/permissions.php

# Esperado: HTTP 401 (Não autenticado)
```

### Teste 2: Com Autenticação

```bash
# 1. Login
curl -X POST http://seu_dominio/ANVI/api/login.php \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","senha":"senha123"}' \
  -c /tmp/cookies.txt

# 2. Usar sessão
curl -X GET http://seu_dominio/ANVI/api/permissions.php \
  -b /tmp/cookies.txt

# Esperado: HTTP 200 + JSON com permissões
```

### Teste 3: Verificar Auditoria

```bash
# SSH no servidor
ssh root@146.190.244.133

# Query auditoria
mysql -e "SELECT user_id, action, details FROM audit_logs LIMIT 5;" viabix_db

# Esperado: Registros de operações
```

---

## 📝 Checklist Pós-Deploy

- [ ] Script executado sem erros
- [ ] SSH conectou ao droplet
- [ ] Arquivos foram copiados
- [ ] Sintaxe PHP validada
- [ ] Migração SQL executada
- [ ] Tabelas criadas no banco
- [ ] Endpoint /api/permissions responde
- [ ] Logs não têm erros críticos
- [ ] Auditoria está registrando

---

## 🆘 SUPORTE

Se algo der errado:

1. **Logs do deploy:**
   - Windows: Copiar output do PowerShell
   - Linux/Mac: Salvar com `script -a deploy.log`

2. **Logs do servidor:**
   ```bash
   ssh root@146.190.244.133 'tail -50 /var/www/html/logs/error.log'
   ```

3. **Status de banco:**
   ```bash
   ssh root@146.190.244.133 'mysql -h $DB_HOST -u $DB_USER -p$DB_PASS viabix_db -e "SHOW TABLES;"'
   ```

---

## ✨ Parabéns!

Se chegou até aqui, seu **Auth V2.0 está em produção no DigitalOcean!** 🎉

**Próximos passos:**
1. Atualizar frontend para usar `/api/permissions`
2. Testar IDOR prevention
3. Monitorar auditoria
4. Preparar backups automáticos

**Tempo total estimado:** ~10 minutos  
**Risco:** MUITO BAIXO (arquivos antigos preservados em backup)

---

**Status:** ✅ Ready for Production
