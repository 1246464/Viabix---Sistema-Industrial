# Viabix

Plataforma web para operacao industrial com dois modulos principais:

- ANVI: analise de viabilidade tecnica e economica
- Controle de Projetos: acompanhamento operacional e execucao

O projeto foi preparado para operacao SaaS, com sessao compartilhada, tenancy, onboarding, billing, painel administrativo interno e estrutura de deploy para producao.

## Estado atual

- Aplicacao web em PHP + MySQL
- Ambiente local compativel com XAMPP
- Direcao de produto: SaaS centralizado
- App desktop: opcional no futuro, sem mudar o backend principal

## Modulos

### ANVI

- calculo de custos, impostos, markup, ROI, payback e DRE
- exportacao e historico
- vinculacao com projetos

### Controle de Projetos

- acompanhamento de projetos industriais
- fluxo operacional por etapas
- integracao com ANVI para abrir projeto a partir de uma analise

### SaaS

- cadastro publico com trial
- plano selecionado na landing
- autenticacao com tenant, assinatura e recursos do plano na sessao
- billing com modo manual e integracao inicial com Asaas
- painel administrativo interno para operacao da plataforma

## Estrutura principal

```text
ANVI/
|-- index.html
|-- login.html
|-- signup.html
|-- dashboard.html
|-- anvi.html
|-- billing.html
|-- admin_saas.html
|-- api/
|-- BD/
|-- Controle_de_projetos/
`-- deploy/
```

Arquivos mais importantes:

- `index.html`: landing publica
- `login.html`: login unificado
- `signup.html`: onboarding com trial e plano selecionado
- `dashboard.html`: painel principal apos login
- `anvi.html`: modulo ANVI
- `billing.html`: visao de assinatura e cobrancas
- `admin_saas.html`: operacao interna do SaaS
- `api/config.php`: bootstrap de ambiente e banco
- `api/check_session.php`: contrato principal de sessao
- `api/signup.php`: cria tenant, usuario inicial e assinatura trial
- `api/checkout_create.php`: cria cobranca
- `api/webhook_billing.php`: recebe webhook de billing
- `api/admin_saas.php`: API do painel administrativo
- `Controle_de_projetos/index.php`: modulo de projetos
- `deploy/`: arquivos prontos para publicacao em servidor Linux

## Requisitos

### Desenvolvimento local

- PHP 8.0 ou superior
- MySQL ou MariaDB
- Apache ou XAMPP no Windows
- extensoes PHP comuns para PDO, JSON, mbstring, curl, gd e xml

### Producao recomendada

- Ubuntu 24.04 LTS
- Nginx ou Apache
- PHP 8.2 FPM
- MariaDB 10.11+
- Certbot para TLS

## Instalacao local

### 1. Publicar a pasta no servidor local

No XAMPP, use este caminho:

```text
C:\xampp\htdocs\ANVI
```

### 2. Configurar ambiente

Crie `.env` a partir de `.env.example` e ajuste os valores reais:

```env
APP_ENV=development
APP_DEBUG=true
DB_HOST=127.0.0.1
DB_NAME=viabix_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
SESSION_NAME=viabix_session
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_SAMESITE=Lax
VIABIX_BILLING_PROVIDER=manual
VIABIX_ASAAS_ENV=sandbox
VIABIX_ASAAS_API_KEY=
VIABIX_ASAAS_WEBHOOK_TOKEN=
```

### 3. Criar banco de dados

Opcoes mais comuns:

```bash
mysql -u root -p < api/database.sql
```

Ou use os scripts PHP existentes em `api/` conforme o seu fluxo atual.

### 4. Acessar o sistema

```text
http://localhost/ANVI/
```

Credenciais padrao atuais do ambiente local:

- admin / admin123
- usuario / usuario123
- visitante / visitante123

Troque essas senhas fora do ambiente de teste.

## Fluxo de uso

### Fluxo operacional

1. o usuario acessa a landing em `index.html`
2. faz login em `login.html` ou cria conta em `signup.html`
3. entra no `dashboard.html`
4. acessa `anvi.html` ou `Controle_de_projetos/index.php`
5. cria analises, projetos e acompanha a operacao

### Fluxo comercial atual

1. a landing oferece planos
2. o plano escolhido vai para `signup.html?plan=...`
3. `api/signup.php` cria tenant, usuario admin inicial e assinatura em trial
4. o cliente entra no sistema e pode seguir para billing

Observacao: o fluxo publico atual ainda e trial-first. O checkout pago direto pode ser adicionado depois, sem reestruturar a base.

## Arquitetura SaaS

O projeto foi adaptado para um modelo multi-tenant com controle centralizado.

### Componentes

- tenant para separar empresas
- plano para definir modulos e limites
- assinatura para vigencia e status comercial
- invoice e payment para cobranca
- webhook_events para rastrear eventos de billing
- tenant_settings e device_sessions para suporte operacional

### Diretrizes aplicadas

- login continua centralizado no backend PHP atual
- dados operacionais passam a depender de `tenant_id`
- sessao carrega tenant, plano, status da assinatura e recursos liberados
- modulos podem ser habilitados ou bloqueados pelo plano

## Billing e operacao interna

### Billing

- `billing.html` mostra assinatura atual e cobrancas
- `api/subscription_current.php` consulta status da assinatura
- `api/billing_invoices.php` lista faturas
- `api/checkout_create.php` cria cobrancas
- `api/webhook_billing.php` normaliza eventos do provedor

### Asaas

Integracao inicial pronta quando estas variaveis forem preenchidas:

```env
VIABIX_BILLING_PROVIDER=asaas
VIABIX_ASAAS_ENV=production
VIABIX_ASAAS_API_KEY=sua-chave
VIABIX_ASAAS_WEBHOOK_TOKEN=seu-token
```

### Painel Admin SaaS

`admin_saas.html` e `api/admin_saas.php` permitem:

- listar tenants
- visualizar status operacional e comercial
- trocar plano
- ativar, suspender, cancelar ou marcar inadimplencia
- inspecionar webhooks e pagamentos recentes
- reprocessar webhook com erro

## Deploy em producao

Arquivos preparados para publicacao:

- `deploy/nginx-site.conf`
- `deploy/apache-vhost.conf`
- `deploy/php-prod.ini`
- `deploy/backup-viabix.sh`
- `deploy/backup-viabix.cron`
- `deploy/logrotate-viabix.conf`
- `api/healthcheck.php`

### Stack recomendada

- Ubuntu 24.04 LTS
- Nginx ou Apache
- PHP 8.2 FPM
- MariaDB 10.11+
- Certbot

### Publicacao resumida

1. criar a pasta da aplicacao no servidor, por exemplo `/var/www/viabix`
2. copiar o codigo
3. criar `.env` de producao com `APP_ENV=production` e `APP_DEBUG=false`
4. ajustar usuario dedicado do banco
5. ativar HTTPS
6. configurar backup, logrotate e healthcheck
7. configurar webhook do Asaas para `/api/webhook_billing.php`

## Checklist de go-live

Antes de colocar em producao:

1. confirmar `.env` de producao com credenciais reais
2. usar `SESSION_SECURE=true` com HTTPS ativo
3. validar login, dashboard, signup, billing e admin SaaS
4. validar `api/healthcheck.php`
5. configurar backup automatico e testar restauracao
6. validar logs da aplicacao e do servidor web
7. executar um pagamento real de baixo valor se o billing com Asaas estiver ativo
8. restringir SSH por chave e revisar permissoes do servidor

## Banco e migracao

O repositorio contem:

- `api/database.sql`: schema operacional principal usado no ambiente atual
- `BD/viabix_saas_multitenant.sql`: desenho da estrutura SaaS multi-tenant
- `BD/migracao_para_saas_fase1.sql`: migracao inicial para tenancy e billing

Resumo da estrategia:

1. preservar a base atual
2. adicionar `tenant_id` nas tabelas operacionais
3. criar tenancy, assinatura e cobranca
4. mover a aplicacao para filtros obrigatorios por tenant

## Solucao de problemas

### Erro de JSON ou BOM em arquivos PHP

Se aparecer erro de parse de JSON causado por BOM, execute no PowerShell dentro de `Controle_de_projetos/`:

```powershell
.\remover_bom.ps1
```

Depois recarregue o navegador sem cache.

### Sessao entre modulos

O projeto usa sessao compartilhada. Os pontos mais sensiveis para diagnostico sao:

- `api/check_session.php`
- `login.html`
- `anvi.html`
- `Controle_de_projetos/auth.php`

### Diagnostico rapido

Arquivos uteis para verificar ambiente e integracao:

- `api/diagnostico.php`
- `api/testar_conexao.php`
- `api/test_salvar.php`
- `api/healthcheck.php`

## Observacao sobre a documentacao

Os arquivos Markdown antigos de fase, resumo e preparacao foram consolidados neste README para reduzir poluicao visual no repositorio. Os detalhes tecnicos permanentes agora devem ficar no codigo, nos arquivos de deploy e neste documento.
