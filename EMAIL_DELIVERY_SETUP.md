# 📧 Email Delivery (SendGrid) - Implementação Concluída

**Status:** ✅ IMPLEMENTADO  
**Data:** May 3, 2026  
**Severidade:** 🟠 ALTO (Funcionalidade crítica)  
**Risco Mitigado:** Onboarding quebrado, senha irrecuperável

---

## 📋 O que foi implementado?

### 1. **Sistema de Envio de Email**
**Arquivo:** `api/email.php` (já existia, expandido)

- ✅ SMTP (fallback padrão)
- ✅ SendGrid API (recomendado)
- ✅ Mailgun API (alternativa)
- ✅ Templates personalizáveis (DB ou arquivos)
- ✅ Queue para envio assíncrono

### 2. **Integração com Signup**
**Arquivo:** `api/signup.php` (modificado)

- ✅ Envia welcome email após novo cadastro
- ✅ Tratamento de erro (não bloqueia signup)
- ✅ Logging de falhas para debug

### 3. **Sistema de Reset de Senha**
**Arquivo:** `api/password_reset.php` (NOVO)

**3 Endpoints:**

```
POST   /api/password_reset.php?action=request
       → Gera token, envia email de reset

GET    /api/password_reset.php?action=validate&token=xxxxx
       → Valida se token ainda é válido

POST   /api/password_reset.php?action=reset
       → Completa reset com nova senha
```

### 4. **Templates de Email**
**Pasta:** `templates/email/`

- ✅ `welcome.php` - Boas-vindas
- ✅ `password_reset.php` - Reset de senha
- ✅ `email_verification.php` - Verificação de email
- ✅ `payment_confirmation.php` - Confirmação de pagamento
- ✅ `invoice.php` - Fatura enviada

### 5. **Testes**
**Arquivo:** `api/test_email_delivery.php` (NOVO)

```bash
# Ver configuração
php api/test_email_delivery.php

# Enviar teste
php api/test_email_delivery.php seu-email@example.com
```

---

## 🚀 Setup no DigitalOcean (Recomendado: SendGrid)

### **Passo 1: Criar Conta SendGrid (Gratuita)**

1. Acesse: https://sendgrid.com/
2. Click **"Sign Up Free"**
3. Preencha dados
4. Confirme email
5. Login no dashboard

### **Passo 2: Gerar API Key**

1. Dashboard → **Settings** → **API Keys**
2. Click **"Create API Key"**
3. Name: `Viabix Production`
4. Permissions: **Restricted Access**
   - ✅ Mail Send
   - ✅ Templates (para templates dinâmicos futuros)
5. Click **"Create & Copy"**
6. **Guardar em local seguro!**

**Seu API Key:**
```
SG.xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### **Passo 3: Configurar no DigitalOcean**

```bash
# Conectar via SSH
ssh root@seu_ip_digitalocean

# Editar arquivo de configuração
nano /var/www/viabix/.env.production
```

**Encontrar:**
```env
MAIL_SENDGRID_API_KEY=CHANGE_ME_SENDGRID_API_KEY
```

**Substituir por:**
```env
MAIL_SENDGRID_API_KEY=SG.seu_api_key_aqui
```

**Salvar:** `Ctrl+X` → `Y` → `Enter`

### **Passo 4: Reiniciar PHP**

```bash
sudo systemctl restart php8.2-fpm

# Verificar se reiniciou OK
sudo systemctl status php8.2-fpm
```

### **Passo 5: Testar Envio**

```bash
# Conectar via SSH
ssh root@seu_ip_digitalocean

# Rodar teste
cd /var/www/viabix
php api/test_email_delivery.php seu-email@example.com
```

**Você deve receber um email com o assunto "Bem-vindo à Viabix! 🎉"**

---

## 🔧 Fluxo de Envio de Emails

### **1. Signup → Welcome Email**

```
User submete signup
    ↓
Sistema cria tenant + usuário
    ↓
viabixSendWelcomeEmail() chamada
    ↓
Template renderizado com variáveis (nome, login_url)
    ↓
Enviado via SendGrid API
    ↓
Email recebido em segundos
```

### **2. Password Reset**

```
User clica "Esqueci a Senha" em login.html
    ↓
POST /api/password_reset.php?action=request
    {email: "user@example.com"}
    ↓
Sistema gera token (64 chars hex)
    ↓
Armazena em BD com TTL 1 hora
    ↓
Envia email com link:
   https://app.viabix.com/reset-password.html?token=xxxxx
    ↓
User clica link, valida token
    ↓
POST /api/password_reset.php?action=reset
    {token: "xxxxx", password: "nova_senha"}
    ↓
Senha atualizada, token marcado como usado
    ↓
User faz login com nova senha
```

### **3. Outros Emails**

```
viabixSendVerificationEmail()   → Email verification
viabixSendPaymentConfirmationEmail() → Payment receipt
viabixSendInvoiceEmail()        → Invoice sent
```

---

## 📊 Template System

### **Estrutura de Template**

```php
<?php
return [
    'subject' => 'Bem-vindo, {{name}}! 🎉',
    'html' => '<html>...</html>',  // HTML email
    'text' => 'Plain text version'  // Fallback plain text
];
?>
```

### **Variáveis Interpoladas**

```php
// Uso
viabixSendEmail('user@example.com', 'welcome', [
    'name' => 'João',
    'login_url' => 'https://app.viabix.com/login'
]);

// No template
Hello {{name}}, click here: {{login_url|html}}
//       ↑ escaped          ↑ raw HTML
```

### **Customizar Templates**

**Editar arquivo:**
```bash
nano /var/www/viabix/templates/email/welcome.php
```

**Ou adicionar ao DB (futuro):**
```sql
INSERT INTO email_templates (name, subject, html_body, active)
VALUES ('welcome', 'Bem-vindo!', '<html>...</html>', 1);
```

---

## 🧪 Testes Manuais

### **Teste 1: Verificar Configuração**

```bash
php api/test_email_delivery.php
```

**Output esperado:**
```
TEST 1: Email Configuration
Provider: sendgrid
From: Viabix <noreply@viabix.com.br>
SendGrid API Key: ✅ CONFIGURED

TEST 3: Email Templates
Template welcome.php: ✅ EXISTS
Template password_reset.php: ✅ EXISTS
...

✅ Email system is READY
```

### **Teste 2: Enviar Email de Teste**

```bash
php api/test_email_delivery.php seu-email@example.com
```

**Output esperado:**
```
Sending test welcome email to: seu-email@example.com
✅ Email sent successfully!
Message ID: sg_test_12345
```

**Você deve receber um email em < 5 segundos**

### **Teste 3: Verificar Bounce Rate**

No dashboard SendGrid:
- Vá em **Analytics**
- Procure por **Bounces, Clicks, Opens**
- Taxa de entrega deve ser > 95%

---

## 🔒 Segurança

### **Password Reset Security**

✅ **Tokens gerados com:**
- `random_bytes(32)` - Criptograficamente seguro
- 64 caracteres hexadecimais
- Impossível adivinhar

✅ **Armazenamento:**
- Hash SHA256 do token (nunca plaintext)
- Token comparado com hash_equals (timing-safe)

✅ **Expiração:**
- 1 hora TTL
- Pode-se resetar múltiplas vezes durante o período
- Após usar, token fica marcado como `used_at`
- Tokens antigos deletados automaticamente

✅ **Rate Limiting:**
- 3 tentativas por hora por IP
- Previne brute force

### **Email Security**

✅ **Validação:**
- `filter_var($email, FILTER_VALIDATE_EMAIL)`
- Rejeita emails inválidos

✅ **Headers:**
- Reply-To configurado
- From name verificado

✅ **Logging:**
- Falhas de envio logadas para debug
- Registradas no Sentry para monitoramento

---

## ⚡ Performance

### **SendGrid**

| Métrica | Valor |
|---------|-------|
| Latência | <100ms |
| Taxa de entrega | >99% |
| Throughput | 1000+ emails/min |
| Confiabilidade | 99.99% uptime |
| Free tier | 100 emails/dia |

### **Otimizações**

```php
// Email queue para envio assíncrono
viabixQueueEmail($email, 'template', $data);

// Processa via cron a cada 5 minutos
// php process-email-queue.php
```

---

## 🐛 Troubleshooting

### **Problema: "SendGrid API Key not configured"**

**Solução:**
```bash
ssh root@seu_ip
nano /var/www/viabix/.env.production
# Encontrar MAIL_SENDGRID_API_KEY e preencher
# Salvar e reiniciar:
sudo systemctl restart php8.2-fpm
```

### **Problema: Email não chega (vai para spam)**

**Causas:**
1. SPF/DKIM/DMARC não configurado
2. Domain reputation baixa
3. Links suspeitos no email

**Solução:**
1. Configurar SPF record no DNS:
   ```
   v=spf1 sendgrid.net ~all
   ```

2. Usar DKIM (SendGrid → Settings → DKIM Authentication)

3. Verificar templates para links suspeitos

### **Problema: "Rate limit exceeded" em password reset**

**Significa:** Usuário tentou resetar 4+ vezes em 1 hora

**Solução normal:**
- Aguardar 1 hora, ou
- Usar rota de admin para resetar manualmente

---

## 📈 Monitoramento

### **SendGrid Dashboard**

**Vá em:** https://app.sendgrid.com/

**Métricas importantes:**
- **Bounce Rate** - % de emails rejeitados (ideal <0.5%)
- **Click Rate** - % que clicaram em links (ideal >5%)
- **Open Rate** - % que abriram (ideal >20%)
- **Spam Report Rate** - % marcados como spam (ideal <0.1%)

### **Alertar sobre falhas**

```sql
-- Query para detectar emails não entregues
SELECT count(*) FROM email_queue 
WHERE sent_at IS NULL 
AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Se count > 0, algo está errado
```

---

## 📚 APIs de Email

### **Enviar Welcome**
```php
viabixSendWelcomeEmail($email, $name, $login_url);
```

### **Enviar Password Reset**
```php
viabixSendPasswordResetEmail($email, $name, $token, $reset_url);
```

### **Enviar Genérico**
```php
viabixSendEmail($to, $template, [
    'name' => 'João',
    'custom_var' => 'value'
]);
```

### **Queue para Depois**
```php
viabixQueueEmail($to, $template, $data, $delay_seconds = 300);
```

---

## ✅ Checklist Phase 1

- [x] Webhook signature validation (Priority 1)
- [x] Redis rate limiting (Priority 2)
- [x] Email delivery (Priority 3) ← **VOCÊ ESTÁ AQUI**
- [ ] Database indexes (Priority 4)
- [ ] Tenant isolation audit (Priority 5)

---

## 🎯 Próxima Tarefa (Priority 4)

**Database Indexes on tenant_id**
- Duração: 1 dia
- Risco: Query performance degradation
- Comando SQL simples, impact imediato

Ver [PHASE_1_IMMEDIATE_ACTIONS.md](PHASE_1_IMMEDIATE_ACTIONS.md) para detalhes.

---

## 📞 Suporte SendGrid

- **Website:** https://sendgrid.com/
- **Docs:** https://docs.sendgrid.com/
- **Status:** https://status.sendgrid.com/
- **Pricing:** Free (100/dia), Paid ($19.95+/mês)

---

## 🎉 Resumo

| Item | Status |
|------|--------|
| Email framework | ✅ Pronto |
| SendGrid integration | ✅ Pronto |
| Welcome emails | ✅ Implementado |
| Password reset | ✅ Implementado |
| Templates | ✅ 5 templates prontos |
| Testes | ✅ Test suite completo |
| Documentação | ✅ Completa |
| DigitalOcean setup | 📖 Acima |

**🚀 Priority 3 COMPLETA!**
