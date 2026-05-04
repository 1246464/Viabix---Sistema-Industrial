# 🔴 Redis Rate Limiting - Implementação Concluída

**Status:** ✅ IMPLEMENTADO  
**Data:** May 3, 2026  
**Severidade:** 🟠 ALTO (Segurança)  
**Risco Mitigado:** Brute force, DDoS, API abuse

---

## 📋 O que foi implementado?

### 1. Sistema de Rate Limiting com Redis
**Arquivo:** `api/rate_limit.php`

- ✅ **Redis como storage principal** - Persistente, distribuído, rápido
- ✅ **Fallback automático para $_SESSION** - Se Redis indisponível (desenvolvimento)
- ✅ **Inicialização automática** - Em `api/config.php`
- ✅ **Suporte a múltiplos bancos Redis** - Para não conflitar com sessões

### 2. Funções Refatoradas

```php
// IP-based rate limiting (brute force protection)
viabixCheckIpRateLimit($action, $max_attempts, $window_seconds)

// User-based rate limiting (API throttling)
viabixCheckUserRateLimit($user_id, $endpoint, $max_requests, $window_seconds)

// Inicialização de Redis
viabixInitializeRedis()
```

Cada uma retorna:
```php
[
    'allowed' => true/false,
    'attempts' => 1,
    'reset_in' => 300  // segundos até resetar
]
```

### 3. Tratamento Inteligente

```
1. Tenta Redis primeiro
   ↓ Se sucesso → Usa Redis (persistente)
   ↓ Se erro → Fallback automático

2. Se Redis falhar
   ↓ Usa $_SESSION (em memória, por sessão)
   ↓ Logs de erro para investigação

3. System funciona sempre
   ↓ Seguro em produção (Redis)
   ↓ Flexível em desenvolvimento (Session)
```

### 4. Configuração por Ambiente

**Arquivos atualizados:**
- `.env.production` - Variáveis para DigitalOcean
- `.env.example` - Template para novos ambientes
- `api/config.php` - Inicialização de Redis

---

## 🚀 Como Configurar no DigitalOcean

### Opção A: Usar Redis Gerenciado do DigitalOcean (RECOMENDADO)

**Passo 1: Criar Redis Database**

1. Acesse seu painel: https://cloud.digitalocean.com/
2. **Create** → **Databases** → **Redis**
3. Configure:
   - Name: `viabix-redis`
   - Version: `7.x` (latest)
   - Region: Mesma do seu Droplet
   - Size: **$15/mês** (5GB starter)

**Passo 2: Obter Credenciais**

Após criado, você terá:
```
Host: db-xxxxx.c.db.ondigitalocean.com
Port: 25061
Password: xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Passo 3: Configurar no DigitalOcean**

Via SSH:
```bash
ssh root@seu_ip_digitalocean

cd /var/www/viabix
nano .env.production
```

**Adicionar/atualizar:**
```env
REDIS_ENABLED=true
REDIS_HOST=db-xxxxx.c.db.ondigitalocean.com
REDIS_PORT=25061
REDIS_PASSWORD=xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
REDIS_DB=1
```

**Salvar:** `Ctrl+X` → `Y` → `Enter`

### Opção B: Redis no Próprio Droplet

**Passo 1: Instalar Redis**

```bash
ssh root@seu_ip_digitalocean

sudo apt-get update
sudo apt-get install -y redis-server

# Verificar instalação
redis-cli ping
# Output: PONG
```

**Passo 2: Configurar para Segurança**

```bash
sudo nano /etc/redis/redis.conf
```

**Adicionar linhas:**
```
requirepass SENHA_MUITO_FORTE_AQUI
maxmemory 512mb
maxmemory-policy allkeys-lru
```

**Salvar e reiniciar:**
```bash
sudo systemctl restart redis-server
```

**Passo 3: Configurar no Viabix**

```bash
cd /var/www/viabix
nano .env.production
```

**Adicionar:**
```env
REDIS_ENABLED=true
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=SENHA_MUITO_FORTE_AQUI
REDIS_DB=1
```

---

## 🧪 Como Testar Localmente

### Teste 1: Verificar Conexão Redis

```bash
# Terminal 1: Iniciar Redis (se local)
redis-server

# Terminal 2: Testar conexão
php api/test_redis_rate_limiting.php
```

**Output esperado:**
```
TEST 1: Redis Connection
------------------------
Redis Config: localhost:6379
[1] Redis Connection: ✅ CONNECTED
Redis Version: 7.0.0
```

### Teste 2: Simular Brute Force

```bash
# Localizar arquivo de teste
php api/test_redis_rate_limiting.php
```

**Procure por TEST 4:**
```
TEST 4: Rate Limiting Simulation
Attempt 1: ✅ ALLOWED (count: 1/3)
Attempt 2: ✅ ALLOWED (count: 2/3)
Attempt 3: ✅ ALLOWED (count: 3/3)
Attempt 4: ❌ BLOCKED (count: 4/3)
Attempt 5: ❌ BLOCKED (count: 5/3)

Brute Force Protection: ✅ WORKING
```

### Teste 3: Performance

```php
// php api/test_redis_rate_limiting.php
// Procure por TEST 5:
Operations: 1000
Time: 0.124s
Speed: 8064 ops/sec
Performance: ✅ EXCELLENT (>5000 ops/sec)
```

---

## 📊 Detalhes Técnicos

### Estrutura de Chaves Redis

```redis
# IP-based rate limits
rl_ip_<hash>_login          → 1 (TTL: 300s)
rl_ip_<hash>_signup         → 1 (TTL: 300s)
rl_ip_<hash>_api            → 2 (TTL: 60s)

# User-based rate limits
rl_user_123_api             → 45 (TTL: 60s)
rl_user_123_endpoint/action → 10 (TTL: 300s)
```

### Operações Redis Usadas

| Operação | Uso | TTL |
|----------|-----|-----|
| `SETEX` | Primeiro acesso (set count = 1) | Personalizável |
| `INCR` | Incrementar contador | Mantém TTL |
| `GET` | Obter contador atual | N/A |
| `TTL` | Obter tempo restante | N/A |
| `DEL` | Limpar (admin) | N/A |

### Fallback para $_SESSION

Se Redis falhar:
```php
// Usa $_SESSION['rate_limit']['rl_ip_xxx_login'] = [
//     'count' => 5,
//     'window_start' => 1234567890,
//     'action' => 'login'
// ]
```

**Limitações:**
- Reseta a cada nova sessão
- Não funciona entre múltiplos servidores
- Menos seguro que Redis
- Apenas para desenvolvimento

---

## 🔧 Configuração Avançada

### Ajustar Limites de Rate Limiting

**Em `.env.production`:**
```env
# Login (5 tentativas em 5 minutos)
RATE_LIMIT_LOGIN_MAX=5
RATE_LIMIT_LOGIN_WINDOW=300

# Signup (3 tentativas em 5 minutos)
RATE_LIMIT_SIGNUP_MAX=3
RATE_LIMIT_SIGNUP_WINDOW=300

# API (100 requisições por usuário em 1 minuto)
RATE_LIMIT_API_MAX=100
RATE_LIMIT_API_WINDOW=60
```

### Usar Redis Múltiplos Bancos

```env
# DB 0 = Sessions (SESSION_REDIS_DB=0)
# DB 1 = Rate Limits (REDIS_DB=1)  <- Diferente!
# DB 2 = Cache (futuro)
```

Isso evita colisão de chaves.

### Monitorar Redis em Produção

```bash
ssh root@seu_ip_digitalocean

# Conectar a Redis CLI
redis-cli -h localhost -p 6379 -a SENHA

# Ver todas as chaves
keys rl_*

# Ver valor de uma chave
get rl_ip_xxx_login

# Ver TTL
ttl rl_ip_xxx_login

# Contar rate limits ativos
dbsize
```

---

## ⚠️ Troubleshooting

### Problema: "Redis connection failed"

**Causas:**
1. Redis não instalado
2. Redis não running
3. Host/porta errada
4. Senha errada

**Solução:**
```bash
# Verificar se está rodando
redis-cli ping

# Se erro, iniciar:
redis-server

# Se não instalado:
sudo apt-get install redis-server
```

### Problema: "Using fallback: Session-based rate limiting"

**Significa:**
- Redis não conectou
- Sistema usando $_SESSION (MENOS SEGURO)

**Verificar:**
```bash
php api/test_redis_rate_limiting.php
# Procure por: "Redis Connection: ❌"
```

### Problema: Rate limits não resetam

**Causa:** `REDIS_DB` conflitando com `SESSION_REDIS_DB`

**Solução:**
```env
# .env.production
SESSION_REDIS_DB=0
REDIS_DB=1  # ← Diferente!
```

### Problema: "Too many connections" error

**Causa:** Muitas conexões Redis abertas

**Solução:**
```bash
# Em redis.conf, aumentar:
maxclients 10000
```

---

## 📈 Performance Esperado

### Com Redis

- **Latência:** <2ms por check
- **Throughput:** 5000+ ops/segundo
- **Persistência:** ✅ Entre reinicializações
- **Distribuído:** ✅ Funciona com múltiplos servidores

### Com Session Fallback

- **Latência:** <1ms por check
- **Throughput:** 10000+ ops/segundo
- **Persistência:** ❌ Reseta com sessão
- **Distribuído:** ❌ Só no servidor local

---

## 🔒 Checklist de Segurança

- [x] Redis com autenticação de senha
- [x] Conexão timeout configurado (5 segundos)
- [x] TTL automático nas chaves (evita memory leak)
- [x] Fallback seguro se Redis falhar
- [x] Logs de erro para auditoria
- [x] Suporte a múltiplos bancos (evita colisão)
- [x] Teste de performance incluído
- [ ] **TODO:** Configurar REDIS_PASSWORD no DigitalOcean
- [ ] **TODO:** Habilitar Redis persistence (RDB/AOF)
- [ ] **TODO:** Monitorar memória Redis em produção

---

## 📚 Documentação Relacionada

- [PHASE_1_IMMEDIATE_ACTIONS.md](PHASE_1_IMMEDIATE_ACTIONS.md) - Próxima tarefa (Email)
- [WEBHOOK_VALIDATION_SETUP.md](WEBHOOK_VALIDATION_SETUP.md) - Priority 1 (já feita)
- [.env.production](.env.production) - Configurações completas

---

## ✅ Próxima Tarefa (Priority 3)

**Email Delivery (SendGrid)**
- Duração: 2 semanas
- Risco: Onboarding quebrado
- Arquivo: `api/email.php`

Ver [PHASE_1_IMMEDIATE_ACTIONS.md](PHASE_1_IMMEDIATE_ACTIONS.md) para detalhes.

---

## 🎯 Resumo do que foi feito

| Item | Status | Detalhes |
|------|--------|----------|
| Redis connection | ✅ | Implementado com fallback |
| IP-based rate limiting | ✅ | Login/signup protection |
| User-based rate limiting | ✅ | API throttling |
| Configuração .env | ✅ | Production + example |
| Inicialização automática | ✅ | Em config.php |
| Testes | ✅ | test_redis_rate_limiting.php |
| Documentação | ✅ | Este arquivo |
| DigitalOcean setup | 📖 | Instruções acima |

**Implementação completa!** 🚀
