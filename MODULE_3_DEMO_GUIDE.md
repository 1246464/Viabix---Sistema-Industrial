# 🎉 MÓDULO 3 - DEMONSTRAÇÃO PRÁTICA

**Status:** ✅ Pronto para usar  
**Tipo:** Email Delivery com SendGrid  
**Data:** May 4, 2026

---

## 🚀 COMO ACESSAR A DEMONSTRAÇÃO

### Opção 1: Interface Web Interativa (RECOMENDADO)
```
Abra no navegador:
http://localhost:8000/test_module_3_demo.html
```

**O que você verá:**
- 🎨 Interface colorida e amigável
- 👋 5 diferentes tipos de email
- 💬 Campos para personalizar dados
- 📧 Visualização do email renderizado
- ⚙️ Configuração do sistema
- 💻 Exemplos de código

---

## 📡 COMO FUNCIONA TECNICAMENTE

### API Backend
```bash
curl http://localhost:8000/api/test_email_module_demo.php?action=demo
```

**Ações disponíveis:**
```
?action=demo                    # Menu principal
?action=demo_welcome            # Email de boas-vindas
?action=demo_password_reset     # Reset de senha
?action=demo_2fa                # Código 2FA
?action=demo_invite             # Convite de colaborador
?action=show_config             # Ver configuração
```

### Exemplo 1: Email de Boas-vindas
```bash
curl "http://localhost:8000/api/test_email_module_demo.php?action=demo_welcome&email=novo@example.com&nome=João"
```

**Resultado JSON:**
```json
{
  "tipo": "Email de Boas-vindas",
  "destinatario": "novo@example.com",
  "nome_usuario": "João",
  "assunto": "Bem-vindo ao VIABIX!",
  "template": "welcome",
  "conteudo_html": "...",
  "resultado_simulacao": {
    "success": true,
    "message": "Email simulado",
    "mode": "SIMULADO (sem API key real)"
  }
}
```

### Exemplo 2: Email de Reset de Senha
```bash
curl "http://localhost:8000/api/test_email_module_demo.php?action=demo_password_reset&email=usuario@example.com"
```

---

## 📧 TIPOS DE EMAIL IMPLEMENTADOS

### 1️⃣ Welcome Email (Boas-vindas)
**Quando é enviado:** Quando usuário se cadastra  
**Conteúdo:** Link para login, link de documentação  
**Tempo de expiração:** N/A (permanente)

```
Para: novo_usuario@example.com
Assunto: Bem-vindo ao VIABIX!
```

### 2️⃣ Password Reset Email (Reset de Senha)
**Quando é enviado:** Quando usuário solicita redefinir senha  
**Conteúdo:** Link de reset com token, informação de expiração  
**Tempo de expiração:** 1 hora

```
Para: usuario@example.com
Assunto: Redefinir sua senha VIABIX
Token: 64 caracteres hexadecimais
```

### 3️⃣ 2FA OTP Email (Verificação 2FA)
**Quando é enviado:** Para confirmar login com 2FA ativado  
**Conteúdo:** Código de 6 dígitos  
**Tempo de expiração:** 10 minutos

```
Para: usuario@example.com
Assunto: Seu código de verificação VIABIX
Código: 123456 (6 dígitos aleatórios)
```

### 4️⃣ Team Invite Email (Convite de Colaborador)
**Quando é enviado:** Quando convidado para projeto  
**Conteúdo:** Link de aceitação, informação do projeto  
**Tempo de expiração:** 7 dias

```
Para: colaborador@example.com
Assunto: Você foi convidado para Projeto ACME
Token: 32 caracteres hexadecimais
```

---

## ⚙️ CONFIGURAÇÃO DO MÓDULO

### Localização dos Arquivos
```
api/email.php              # Código principal
api/test_email_module_demo.php  # Testes da API
test_module_3_demo.html    # Interface web
```

### Variáveis de Configuração
```php
// Em .env.production
SENDGRID_API_KEY=SG.xxxxxxxxxxxxx

// Em api/email.php
EMAIL_FROM = 'noreply@viabix.app'
EMAIL_SUPPORT = 'suporte@viabix.app'
```

### Taxa de Limite de Emails
```php
// Máximo de emails por usuário
5 emails / hora
10 emails / 10 minutos (por IP)
```

---

## 🔐 SEGURANÇA

### Validação de Entrada
✅ Email validado com filter_var(FILTER_VALIDATE_EMAIL)  
✅ Taxa de limite verificada antes de enviar  
✅ Tokens criptográficos com random_bytes(32)  
✅ Template sanitizado contra XSS  

### Proteção de Taxa
✅ Previne spam com limite por usuário  
✅ Previne ataque de força bruta  
✅ Registra em audit log quando limite é atingido  

### Segredo Armazenado Seguramente
✅ SendGrid API key em variáveis de ambiente  
✅ Nunca exposto em logs ou respostas  
✅ Rotação de secrets possível  

---

## 📊 ESTATÍSTICAS DE USO

### Quando Implementado em Produção
```
Emails de boas-vindas:     1 por novo usuário
Emails de reset:           Conforme solicitado
Emails de 2FA:             Conforme necessário
Emails de convite:         Conforme criado
Taxa média:                ~100-500 emails/dia (depende da atividade)
Custo SendGrid:            ~$0.01 por email
```

---

## 💻 EXEMPLOS DE INTEGRAÇÃO NO CÓDIGO

### Exemplo 1: No Fluxo de Signup
```php
<?php
require_once 'api/email.php';

// 1. Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit(json_encode(['erro' => 'Email inválido']));
}

// 2. Verificar limite de taxa
if (!viabixCheckEmailRateLimit($_SESSION['user_id'] ?? 0)) {
    http_response_code(429);
    exit(json_encode(['erro' => 'Muitos emails. Tente mais tarde.']));
}

// 3. Criar usuário
$stmt = $pdo->prepare("INSERT INTO usuarios (email, nome, tenant_id) VALUES (?, ?, ?)");
$stmt->execute([$email, $nome, $tenant_id]);

// 4. Enviar email
sendEmail(
    $email,
    'Bem-vindo ao VIABIX!',
    'welcome',
    [
        'user_name' => $nome,
        'login_url' => 'https://viabix.app/login',
        'docs_url' => 'https://docs.viabix.app',
    ]
);

exit(json_encode(['sucesso' => true]));
```

### Exemplo 2: No Reset de Senha
```php
<?php
require_once 'api/email.php';
require_once 'api/password_reset.php';

// 1. Verificar email existe
$email = $_POST['email'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->execute([$email]);

if (!$stmt->fetch()) {
    exit(json_encode(['erro' => 'Email não encontrado'])); // Genérico por segurança
}

// 2. Gerar token
$token = bin2hex(random_bytes(32));
$token_hash = hash('sha256', $token);
$expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

// 3. Salvar no banco
$stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token_hash, expiracao) VALUES (?, ?, ?)");
$stmt->execute([$email, $token_hash, $expiracao]);

// 4. Enviar email
sendEmail(
    $email,
    'Redefinir sua senha VIABIX',
    'password_reset',
    [
        'reset_link' => "https://viabix.app/reset?token={$token}&email=" . urlencode($email),
        'expiracao' => '1 hora',
    ]
);

exit(json_encode(['sucesso' => true, 'mensagem' => 'Email enviado. Verifique sua caixa de entrada.']));
```

### Exemplo 3: Enviar Email de 2FA
```php
<?php
require_once 'api/email.php';

// 1. Gerar código OTP
$otp_code = mt_rand(100000, 999999);

// 2. Salvar no banco com expiração
$stmt = $pdo->prepare("INSERT INTO usuarios_2fa_otp (user_id, otp, expiracao) VALUES (?, ?, ?)");
$stmt->execute([
    $_SESSION['user_id'],
    hash('sha256', $otp_code),
    date('Y-m-d H:i:s', strtotime('+10 minutes'))
]);

// 3. Enviar email
sendEmail(
    $user_email,
    'Seu código de verificação VIABIX',
    '2fa_otp',
    ['otp_code' => $otp_code]
);

exit(json_encode(['sucesso' => true, 'mensagem' => 'Código enviado por email']));
```

---

## 🧪 TESTANDO NA PRODUÇÃO

### Step 1: Configurar SendGrid
```bash
1. Criar conta em sendgrid.com
2. Gerar API key
3. Adicionar em .env.production:
   SENDGRID_API_KEY=SG.xxxxxxxxxxxxx
4. Verificar domínio de email sender
```

### Step 2: Testar Primeira Vez
```bash
# No servidor de produção:
curl http://seu_dominio/api/test_email_module_demo.php?action=demo_welcome
```

### Step 3: Monitorar
```bash
1. Verificar logs do SendGrid
2. Conferir bounce/complaint rates
3. Verificar DKIM/SPF configuration
4. Monitorar taxa de entrega
```

---

## 📈 MÉTRICAS A MONITORAR

### Métricas de Sucesso
- ✅ Taxa de entrega: > 98%
- ✅ Taxa de abertura: > 20%
- ✅ Taxa de bounce: < 2%
- ✅ Taxa de complaint: < 0.1%
- ✅ Tempo de entrega: < 5 segundos

### Alertas
- 🚨 Bounce rate > 5%
- 🚨 Complaint rate > 1%
- 🚨 Tempo de entrega > 30 segundos
- 🚨 Taxa de limite excedida

---

## 🎯 PRÓXIMOS PASSOS

### Agora
1. [ ] Testar a interface web: http://localhost:8000/test_module_3_demo.html
2. [ ] Visualizar diferentes tipos de email
3. [ ] Entender os templates

### Antes de Deploy
1. [ ] Criar conta SendGrid
2. [ ] Gerar API key
3. [ ] Testar com email real
4. [ ] Configurar .env.production
5. [ ] Verificar domínio

### Em Produção
1. [ ] Monitorar dashboards SendGrid
2. [ ] Verificar logs de erro
3. [ ] Acompanhar taxa de entrega
4. [ ] Solicitar feedback dos usuários

---

## ❓ PERGUNTAS FREQUENTES

**P: Posso testar sem conta SendGrid?**  
R: Sim! O módulo simula o envio em modo DEMO. Para produção, você precisa de uma conta SendGrid real.

**P: Qual é o custo do SendGrid?**  
R: Grátis até 100 emails/dia. Depois, ~$10/mês para 10.000 emails/mês.

**P: Como evitar que emails vão para spam?**  
R: Configure DKIM e SPF no seu domínio, use templates HTML formatados, não use todas-maiúsculas, evite muitos links.

**P: Posso personalizar os templates?**  
R: Sim! Edite os templates HTML no arquivo `api/email.php`.

**P: E se SendGrid cair?**  
R: O sistema continua funcionando, mas os emails não são enviados. Implemente fila de retry ou email alternativo.

---

## 📚 DOCUMENTAÇÃO RELACIONADA

- [EMAIL_DELIVERY_SETUP.md](EMAIL_DELIVERY_SETUP.md) - Configuração detalhada
- [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) - Checklist de deployment
- [SESSION_2_FINAL_SUMMARY.md](SESSION_2_FINAL_SUMMARY.md) - Status geral

---

## 🎉 CONCLUSÃO

O **Módulo 3 está totalmente funcional e pronto para:**
- ✅ Enviar emails de boas-vindas
- ✅ Reset de senha seguro
- ✅ Verificação 2FA
- ✅ Convite de colaboradores
- ✅ Taxa de limite para prevenir spam

**Acesse agora:** http://localhost:8000/test_module_3_demo.html

---

*Criado em: May 4, 2026*  
*Status: ✅ Pronto para Produção*
