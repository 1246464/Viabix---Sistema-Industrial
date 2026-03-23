# 🚀 Viabix - Sistema Industrial Completo
## Análise de Viabilidade + Controle de Projetos
### Versão 7.1 - Modelo Industrial 10/10

![Viabix](https://via.placeholder.com/800x200/0a3d2e/ffffff?text=Viabix+Industrial+10/10)

---

## 📋 Sobre o Sistema

**Viabix** é uma plataforma integrada para gestão industrial completa, combinando:
- ✅ **ANVI** - Análise de Viabilidade Técnica e Econômica
- ✅ **Controle de Projetos** - Gestão completa de projetos industriais
- ✅ **Multiusuário** - Sistema com controle de acesso (Admin, Usuário, Visitante)
- ✅ **Banco Único** - Integração total via MySQL

Especializado para **indústria de vidros e componentes automotivos**, com cálculos precisos de custos, impostos, markup, ROI, payback e DRE completo.

---

## ✨ Funcionalidades Principais

### 📊 Módulo ANVI (Análise de Viabilidade)
- ✅ Múltiplas abas para entrada de dados (Matéria Prima, Insumos, Componentes, Processos, MO, etc.)
- ✅ Cálculo automático com ICMS "por dentro" e créditos fiscais (IPI, ICMS, PIS, COFINS)
- ✅ Markup inteligente com IRPJ/CSLL no Lucro Presumido
- ✅ DRE completo (Demonstrativo de Resultados do Exercício)
- ✅ Suporte à Reforma Tributária 2027 (IBS/CBS)
- ✅ Upload de desenhos/imagens técnicas
- ✅ Exportação para PDF profissional
- ✅ Exportação para Excel
- ✅ Validação de rateio de custos (bloqueio >100%)
- ✅ Histórico completo de alterações
- ✅ Vínculo direto com Projetos

### 📁 Módulo Controle de Projetos
- ✅ Gestão visual tipo Kanban
- ✅ Cronograma de tarefas por fase
- ✅ Controle de entregas (Amostras, Desenhos, Ferramental, Produção)
- ✅ Upload de arquivos por projeto
- ✅ Vinculação com ANVIs para orçamento automático
- ✅ Dashboard com visão consolidada
- ✅ Exportação Excel completa

### 👥 Sistema Multiusuário
- ✅ 3 níveis de acesso: Admin, Usuário, Visitante
- ✅ Sessão unificada entre módulos
- ✅ Logs completos de atividade
- ✅ Controle de concorrência (bloqueio de edição)
- ✅ Notificações de sistema

---

## 🖥️ Requisitos do Sistema

### Servidor
- **PHP:** 7.4 ou superior
- **MySQL:** 5.7 ou superior  
- **Apache:** 2.4 ou superior (com `mod_rewrite`)
- **Extensões PHP:** PDO, JSON, GD (para imagens), mbstring

### Cliente (Navegador)
- Chrome 90+, Firefox 88+, Edge 90+, Safari 14+
- JavaScript habilitado
- Cookies habilitados
- Resolução mínima: 1366x768

---

## 📦 Instalação

### 1️⃣ Preparar o Ambiente

```bash
# Windows (XAMPP)
# 1. Instale o XAMPP (https://www.apachefriends.org)
# 2. Inicie Apache e MySQL no painel de controle
# 3. Copie os arquivos para: C:\xampp\htdocs\ANVI\

# Linux
sudo apt update
sudo apt install apache2 php php-mysql php-gd php-mbstring mysql-server
sudo systemctl start apache2 mysql
```

### 2️⃣ Configurar Banco de Dados

**Opção A - Via Script Automático:**
```bash
# Acesse no navegador:
http://localhost/ANVI/renomear_banco_viabix.php
# Clique em "Executar Renomeação"
```

**Opção B - Via Linha de Comando:**
```bash
mysql -u root -p < api/database.sql
```

**Opção C - Via phpMyAdmin:**
1. Acesse `http://localhost/phpmyadmin`
2. Vá em "Importar"
3. Selecione o arquivo `api/database.sql`
4. Clique em "Executar"

### 3️⃣ Configurar Ambiente

Copie `.env.example` para `.env` e preencha as credenciais reais do ambiente:

```bash
cp .env.example .env
```

Variáveis mínimas:

```env
APP_ENV=production
APP_DEBUG=false
DB_HOST=127.0.0.1
DB_NAME=viabix_db
DB_USER=viabix_user
DB_PASS=troque-esta-senha
SESSION_SECURE=true
```

Os arquivos `api/config.php` e `Controle_de_projetos/config.php` já leem esse bootstrap automaticamente.

### 4️⃣ Configurar Permissões (Linux)

```bash
sudo chown -R www-data:www-data /var/www/html/ANVI
sudo chmod -R 755 /var/www/html/ANVI
sudo mkdir -p /var/www/html/ANVI/logs
sudo chmod -R 775 /var/www/html/ANVI/logs
sudo chmod -R 775 /var/www/html/ANVI/uploads  # Se houver pasta de uploads
```

Para deploy em produção, veja também:

- `PREPARACAO_PRODUCAO.md`
- `deploy/apache-vhost.conf`
- `deploy/nginx-site.conf`
- `deploy/php-prod.ini`
- `deploy/systemd-queue-example.service`

### 5️⃣ Testar o Sistema

Acesse no navegador:
```
http://localhost/ANVI/
```

**Credenciais padrão:**
- **Admin:** `admin` / `admin123`
- **Usuário:** `usuario` / `usuario123`
- **Visitante:** `visitante` / `visitante123`

⚠️ **IMPORTANTE:** Altere as senhas após o primeiro login!

---

## 📁 Estrutura de Arquivos

```
ANVI/
├── 📄 index.html                    → Tela inicial (redireciona para dashboard)
├── 📄 login.html                    → Tela de login unificada
├── 📄 dashboard.html                → Dashboard principal com métricas
├── 📄 anvi.html                     → Módulo ANVI (Análise de Viabilidade)
├── 📄 README.md                     → Este arquivo
├── 📄 VIABIX_PRONTO.md             → Guia de conclusão da renomeação
├── 📄 GUIA_RAPIDO.md               → Tutorial rápido (5 minutos)
│
├── 📂 api/                          → Backend PHP
│   ├── config.php                   → Configurações do banco e sessão
│   ├── login.php                    → Autenticação
│   ├── logout.php                   → Encerrar sessão
│   ├── check_session.php            → Verificar sessão ativa
│   ├── anvi.php                     → CRUD de ANVIs
│   ├── usuarios.php                 → Gestão de usuários
│   ├── diagnostico.php              → Diagnóstico do sistema
│   ├── database.sql                 → Script completo do banco
│   ├── criar_db.php                 → Script PHP para criar banco
│   ├── criar_projeto_de_anvi.php    → API de vinculação ANVI→Projeto
│   └── verificar_vinculo.php        → Verificar vínculos bidirecionais
│
├── 📂 Controle_de_projetos/         → Módulo de Projetos
│   ├── index.php                    → Interface principal
│   ├── config.php                   → Configuração do banco
│   ├── auth.php                     → Autenticação unificada
│   ├── login.php                    → Login (redireciona)
│   └── logout.php                   → Logout (redireciona)
│
├── 📂 BD/                           → Backups SQL separados
│   ├── viabix_db_anvis.sql         
│   ├── viabix_db_usuarios.sql
│   ├── viabix_db_projetos.sql
│   └── ...
│
└── 📂 uploads/ (opcional)           → Arquivos enviados
    ├── desenhos/
    └── documentos/
```

---

## 🎯 Primeiros Passos

### 1. Fazer Login
```
http://localhost/ANVI/
→ Entre com: admin / admin123
```

### 2. Criar sua primeira ANVI
1. No dashboard, clique em **"ANVI - Análise de Viabilidade"**
2. Preencha as informações básicas
3. Navegue pelas abas adicionando dados
4. Clique em **"Salvar ANVI"**
5. Visualize os resultados calculados automaticamente

### 3. Criar um Projeto vinculado
1. Após salvar a ANVI, clique em **"Criar Projeto"**
2. Os dados orçamentários serão transferidos automaticamente
3. Gerencie o projeto no módulo **"Controle de Projetos"**

### 4. Explorar o Dashboard
- Visualize estatísticas consolidadas
- Acesse rapidamente ANVIs e Projetos
- Veja vinculos entre ANVI e Projetos

---

## 🔐 Segurança

### Níveis de Acesso
| Nível | Permissões |
|-------|------------|
| **Admin** | Tudo (criar/editar/excluir ANVIs e Projetos, gerenciar usuários) |
| **Usuário** | Criar e editar próprias ANVIs e Projetos |
| **Visitante** | Apenas visualizar (modo leitura) |

### Boas Práticas
- ✅ Altere senhas padrão imediatamente
- ✅ Use HTTPS em produção (`'secure' => true` em `config.php`)
- ✅ Faça backups regulares do banco de dados
- ✅ Mantenha o PHP e MySQL atualizados
- ✅ Configure firewall para proteger portas MySQL (3306)

---

## 📊 Modelo de Negócio (SaaS)

### Planos Sugeridos
| Plano | Preço/mês | ANVIs | Usuários | Suporte |
|-------|-----------|-------|----------|---------|
| **Starter** | R$ 297 | 50 | 3 | Email |
| **Professional** | R$ 697 | 200 | 10 | Email + WhatsApp |
| **Enterprise** | R$ 1.497 | Ilimitado | Ilimitado | Prioritário |
| **White Label** | R$ 3.997 | Ilimitado | Ilimitado | Dedicado + Marca própria |

📄 Veja detalhes completos em: `PLANO_COMERCIALIZACAO.html`

---

## 🛠️ Manutenção

### Backup do Banco de Dados
```bash
# Exportar
mysqldump -u root -p viabix_db > backup_viabix_$(date +%Y%m%d).sql

# Importar
mysql -u root -p viabix_db < backup_viabix_20260317.sql
```

### Limpar Logs Antigos
```sql
USE viabix_db;
DELETE FROM logs_atividade WHERE data_hora < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

### Verificar Integridade
```
http://localhost/ANVI/api/diagnostico.php
```

---

## 📚 Documentação Adicional

- 📘 **GUIA_RAPIDO.md** - Tutorial de 5 minutos
- 📗 **FASE4_CONCLUIDA.md** - Documentação técnica completa
- 📙 **RESUMO_COMPLETO.md** - Visão geral do sistema
- 📕 **PLANO_COMERCIALIZACAO.html** - Estratégia de negócio

---

## ❓ Solução de Problemas

### Erro: "Access denied for user"
**Solução:** Verifique usuário e senha em `api/config.php` e `Controle_de_projetos/config.php`

### Erro: "Unknown database 'viabix_db'"
**Solução:** Execute o script de criação do banco: `http://localhost/ANVI/renomear_banco_viabix.php`

### Sistema volta para tela de login
**Solução:** Limpe cookies (Ctrl+Shift+Delete) ou use navegação anônita

### Cálculos incorretos na ANVI
**Solução:** Verifique se todos os campos obrigatórios estão preenchidos e se o rateio não ultrapassa 100%

### Upload de imagens não funciona
**Solução:** Crie a pasta `uploads/desenhos/` com permissões de escrita (chmod 777 no Linux)

---

## 🎨 Personalização

### Alterar cores do tema
Edite os arquivos HTML e procure por:
```css
background: linear-gradient(135deg, #0a3d2e 0%, #1b5e20 100%);
```
Substitua pelos códigos de cor desejados.

### Adicionar logo personalizada
Substitua o ícone `<i class="fas fa-industry"></i>` por:
```html
<img src="caminho/para/seu/logo.png" alt="Logo" style="height: 40px;">
```

### Alterar nome da empresa
No banco de dados:
```sql
UPDATE configuracoes SET valor = 'Sua Empresa' WHERE chave = 'empresa_nome';
```

---

## 🤝 Suporte

Para suporte técnico ou dúvidas:
- 📧 Email: suporte@viabix.com.br *(configurar)*
- 💬 WhatsApp: +55 11 XXXXX-XXXX *(configurar)*
- 📖 Documentação: Este README e arquivos .md inclusos

---

## 📄 Licença

Sistema desenvolvido para uso comercial.  
**Viabix** - Versão 7.1 © 2025-2026

---

## 🚀 Changelog

### v7.1 (Atual) - 17/03/2026
- ✅ Sistema renomeado de FANAVID para Viabix
- ✅ Integração completa ANVI + Projetos
- ✅ Vinculos bidirecionais com validação
- ✅ Dashboard com métricas consolidadas
- ✅ Session unificada (viabix_session)
- ✅ Banco unificado (viabix_db)

### v7.0 - Fase 4
- ✅ Vinculação ANVI → Projeto
- ✅ Detecção automática de vínculos
- ✅ Badges visuais de vinculação

### v6.0 - Fase 3
- ✅ Login unificado
- ✅ Banco de dados único
- ✅ Navegação integrada

### v5.0 - Fase 2
- ✅ Controle de projetos completo
- ✅ Cronograma de tarefas

### v4.0 - Fase 1
- ✅ Módulo ANVI funcional
- ✅ Multiusuário com MySQL

---

**🎉 Viabix - Transformando Análise em Ação!**

*Sistema desenvolvido com foco em precisão, usabilidade e integração industrial.*
