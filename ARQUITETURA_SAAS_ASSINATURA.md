# Arquitetura SaaS com Assinatura e App Desktop

## Objetivo

Transformar o sistema atual em um produto comercial com estas características:

- venda por assinatura
- ativação centralizada
- controle de planos e módulos
- atualização sob seu controle
- acesso por navegador
- app desktop opcional para Windows com login e sessão persistente

O modelo recomendado para este projeto é:

- produto principal: SaaS web
- cliente opcional: app desktop leve
- licenciamento: validado no servidor
- dados: centralizados em infraestrutura sua

Esse desenho preserva a base atual em PHP/MySQL e evita a complexidade de distribuir banco e servidor local no computador do cliente.

## Arquitetura de Produto

### Camadas

1. Site institucional e comercial
2. Backend SaaS principal
3. Banco de dados central
4. Serviço de autenticação e assinatura
5. Painel administrativo interno
6. App desktop opcional

### Visão Geral

```text
Cliente -> Site oficial -> Cadastro/Checkout -> Assinatura ativa
Cliente -> App desktop ou navegador -> Login -> API de autenticação
API -> Valida assinatura, empresa, plano, módulos e permissões
Sistema -> Libera acesso ao tenant do cliente
Admin interno -> Gerencia clientes, planos, cobranças e bloqueios
```

## Componentes Recomendados

### 1. Site oficial

Responsabilidades:

- página de produto
- tabela de planos
- prova social e materiais comerciais
- cadastro de trial
- checkout
- download do app desktop
- área do cliente

Stack possível:

- landing page simples em HTML/PHP inicial
- ou site separado em Next.js, WordPress ou builder, se quiser foco comercial

### 2. Aplicação SaaS principal

Responsabilidades:

- login
- gestão de usuários
- ANVI
- controle de projetos
- dashboard
- permissões por empresa e plano
- auditoria

Recomendação para a fase 1:

- manter PHP no backend
- consolidar rotas da API
- unificar configuração e autenticação
- remover dependências locais de XAMPP
- preparar a aplicação para ambiente Linux em produção

### 3. Serviço de autenticação e assinatura

Responsabilidades:

- autenticar usuário
- identificar empresa do usuário
- validar status da assinatura
- validar módulos liberados
- validar limites de uso
- emitir sessão segura
- bloquear acesso em caso de inadimplência ou suspensão

Esse serviço pode começar dentro do mesmo backend PHP. Não é obrigatório separar em microserviço no início.

### 4. Banco de dados central

Responsabilidades:

- armazenar dados de todos os clientes
- isolar dados por tenant
- armazenar planos, assinaturas, pagamentos e eventos
- manter trilha de auditoria

Modelo recomendado:

- um banco central com coluna tenant_id em tabelas de negócio
- filtros obrigatórios por tenant em toda operação

Alternativa futura para clientes enterprise:

- banco por tenant para contas muito grandes ou com requisito contratual específico

### 5. Painel administrativo interno

Responsabilidades:

- ver lista de clientes
- ativar, suspender e cancelar contas
- mudar plano
- acompanhar trial
- liberar módulos manualmente
- ver métricas de uso
- abrir suporte interno
- consultar logs de login, cobrança e auditoria

### 6. App desktop opcional

Responsabilidades:

- permitir download pelo site oficial
- oferecer experiência de software instalado
- fazer login no SaaS
- armazenar sessão local com segurança
- abrir o sistema em janela própria
- receber atualização automática do cliente desktop

Tecnologias recomendadas:

- Electron: mais maduro e simples para empacotar
- Tauri: mais leve, mas exige mais cuidado no ecossistema

Para o seu caso, Electron tende a acelerar o time-to-market.

## Modelo Comercial Recomendado

### Produto principal

- assinatura mensal ou anual
- acesso por login
- atualização contínua no servidor
- dados hospedados por você

### App desktop

- opcional
- distribuído pelo site
- não contém a lógica principal de negócio
- não contém banco do cliente
- depende do seu backend para autenticação e autorização

### Resultado

Você mantém o controle de:

- versão liberada
- plano contratado
- usuários ativos
- módulos por cliente
- status financeiro
- bloqueios e reativações

## Modelo de Multiempresa

O sistema precisa sair do modelo local e passar para um modelo multi-tenant.

### Entidades novas

- tenants
- tenant_users
- plans
- subscriptions
- invoices
- payments
- subscription_events
- feature_flags
- devices_sessions

### Tenant

Representa a empresa cliente.

Campos sugeridos:

- id
- nome_fantasia
- razao_social
- cnpj
- slug
- status
- created_at
- updated_at

### Usuário

Todo usuário precisa estar vinculado a um tenant.

Campos sugeridos:

- id
- tenant_id
- nome
- email
- senha_hash
- perfil
- status
- ultimo_login_em
- created_at
- updated_at

### Plano

Define limites e recursos liberados.

Campos sugeridos:

- id
- nome
- codigo
- valor_mensal
- valor_anual
- limite_usuarios
- limite_projetos
- permite_modulo_anvi
- permite_modulo_projetos
- permite_exportacao
- permite_api
- status

### Assinatura

Representa o contrato ativo da empresa.

Campos sugeridos:

- id
- tenant_id
- plan_id
- status
- provider
- provider_subscription_id
- ciclo
- trial_ate
- vigente_de
- vigente_ate
- cancelado_em
- bloqueado_em
- created_at
- updated_at

### Eventos de assinatura

Tabela de histórico operacional.

Campos sugeridos:

- id
- subscription_id
- tipo_evento
- payload_json
- origem
- created_at

## Fluxo de Autenticação

### Login web

1. usuário informa email e senha
2. backend valida credenciais
3. backend identifica tenant do usuário
4. backend verifica status do tenant
5. backend verifica status da assinatura
6. backend verifica permissões e plano
7. backend cria sessão
8. frontend carrega módulos liberados

### Login no app desktop

1. usuário instala o app
2. usuário informa email e senha
3. app chama API de autenticação
4. backend retorna token e dados da assinatura
5. app salva sessão local com segurança
6. app abre interface SaaS em janela própria

### Regras de bloqueio

O acesso deve ser negado quando:

- assinatura estiver vencida
- tenant estiver suspenso
- usuário estiver inativo
- limite de usuários for ultrapassado e sua regra comercial exigir bloqueio

## Fluxo de Cobrança

### Gateway recomendado

Opções adequadas para Brasil:

- Asaas
- Pagar.me
- Mercado Pago
- Iugu

Se quiser operação internacional, Stripe é uma opção forte.

### Fluxo ideal

1. cliente escolhe plano no site
2. cliente cria conta da empresa
3. sistema cria tenant em estado pendente
4. checkout gera assinatura no gateway
5. gateway confirma pagamento via webhook
6. backend atualiza assinatura para ativa
7. sistema libera login
8. eventos ficam registrados para auditoria

### Webhooks necessários

- pagamento aprovado
- pagamento recusado
- assinatura renovada
- assinatura cancelada
- chargeback ou estorno
- trial convertido

### Regras comerciais

Defina desde o início:

- dias de trial
- dias de tolerância após vencimento
- bloqueio imediato ou gradual
- downgrade automático ou manual
- cobrança proporcional no upgrade
- política de cancelamento

## Fluxo de Licenciamento

Mesmo usando app desktop, a licença não deve ser local. Ela deve ser validada online.

### O que o backend valida

- usuário autenticado
- assinatura ativa
- tenant ativo
- plano contratado
- módulos permitidos
- quantidade de usuários ou dispositivos, se aplicável

### O que o app desktop armazena

- token de sessão
- refresh token, se houver
- identificador do dispositivo
- versão do app
- preferências locais

### O que não deve ficar no app

- regra principal de licenciamento
- decisão final de ativação
- segredos sensíveis do backend
- banco operacional do cliente

## Atualizações

### Atualização do produto SaaS

Feita no servidor.

Vantagens:

- uma única versão oficial
- correção imediata
- sem depender do cliente instalar patch
- observabilidade centralizada

### Atualização do app desktop

Feita pelo mecanismo do empacotador.

No Electron, o fluxo típico é:

1. app inicia
2. verifica endpoint de release
3. baixa nova versão
4. instala update
5. reinicia app

O app desktop deve ser fino. A atualização mais importante continuará sendo a do servidor.

## Segurança Recomendadada

### Aplicação

- HTTPS obrigatório
- senhas com hash seguro
- rotação de segredos
- proteção contra brute force
- proteção CSRF e XSS
- sessões com expiração e revogação
- auditoria de login e ação crítica

### Infraestrutura

- banco sem acesso público irrestrito
- backups automáticos
- logs centralizados
- monitoramento de disponibilidade
- firewall e WAF quando possível

### Dados

- isolamento por tenant
- exportação por permissão
- trilha de auditoria
- retenção e descarte conforme política

## Ajustes Necessários no Projeto Atual

### 1. Remover credenciais fixas do código

Hoje as credenciais estão no código-fonte. Isso precisa virar configuração por ambiente.

### 2. Unificar a configuração

Hoje há configuração duplicada do banco e diferenças entre módulos. O ideal é centralizar.

### 3. Preparar o sistema para produção Linux

O sistema hoje está organizado para uso local com XAMPP. Em SaaS, o alvo deve ser servidor Linux com Nginx ou Apache e PHP-FPM.

### 4. Internalizar dependências de front-end

Bibliotecas carregadas via CDN devem ser revisadas. Em produção até podem continuar, mas convém ter controle de versão e estratégia clara.

### 5. Criar camada de autorização por tenant e plano

Toda leitura e gravação precisa respeitar tenant_id, perfil e permissões do plano.

### 6. Separar área administrativa interna da área do cliente

Seu painel operacional não pode ficar misturado com a interface do cliente final.

## Estrutura de Módulos Recomendada

### Módulos públicos

- home
- planos
- checkout
- login
- recuperação de senha
- download do app

### Módulos do cliente

- dashboard
- ANVI
- controle de projetos
- usuários da empresa
- assinatura
- faturamento
- perfil

### Módulos internos da sua empresa

- admin tenants
- admin billing
- admin suporte
- admin feature flags
- admin auditoria
- admin métricas

## APIs Essenciais

### Autenticação

- POST /api/auth/login
- POST /api/auth/logout
- POST /api/auth/refresh
- GET /api/auth/me

### Assinatura

- GET /api/subscription/current
- POST /api/subscription/change-plan
- POST /api/subscription/cancel
- GET /api/billing/invoices

### Checkout e webhooks

- POST /api/checkout/create
- POST /api/webhooks/billing

### Desktop app

- POST /api/desktop/session
- POST /api/desktop/register-device
- GET /api/desktop/version

### Administração interna

- GET /api/admin/tenants
- POST /api/admin/tenants/{id}/suspend
- POST /api/admin/tenants/{id}/activate
- POST /api/admin/tenants/{id}/change-plan

## Roadmap de Implementação

### Fase 1: preparação do core

- centralizar configuração
- revisar autenticação
- remover dependência local de XAMPP como premissa de produto
- revisar esquema atual do banco

### Fase 2: multi-tenant

- criar tabela tenants
- vincular usuários a tenant
- adicionar tenant_id nas tabelas de negócio
- aplicar filtros obrigatórios

### Fase 3: assinatura e cobrança

- criar planos
- criar assinaturas
- integrar gateway
- implementar webhooks
- bloquear e liberar acesso conforme status

### Fase 4: painel interno

- criar administração dos clientes
- criar visão financeira
- criar logs de eventos comerciais

### Fase 5: desktop app

- criar cliente Electron
- implementar login
- abrir app em shell desktop
- configurar atualização automática

### Fase 6: operação e escala

- observabilidade
- backups
- política de deploy
- política de suporte
- indicadores de churn e conversão

## Stack Sugerida

### Versão pragmática

- backend: PHP 8.x
- banco: MySQL ou MariaDB
- frontend atual: HTML, CSS e JavaScript progressivamente organizados
- autenticação: sessão segura ou JWT com refresh token, conforme arquitetura final
- cobrança: Asaas, Pagar.me, Iugu ou Stripe
- app desktop: Electron
- deploy: VPS ou cloud Linux

### Evolução futura

- separar frontend do backend se o produto crescer
- adicionar fila para eventos e notificações
- adicionar cache
- adicionar storage externo para uploads

## Decisões Recomendadas

### Decisão 1

Não distribuir banco e backend no PC do cliente.

### Decisão 2

Usar o navegador como acesso principal e desktop app como canal opcional.

### Decisão 3

Controlar licença exclusivamente pelo servidor.

### Decisão 4

Transformar o sistema atual em multi-tenant antes de criar o desktop app.

### Decisão 5

Priorizar billing, autenticação e isolamento de dados antes de investir em visual de instalador.

## MVP Comercial Recomendado

Para começar a vender mais cedo, o MVP ideal é:

1. site com plano e checkout
2. login centralizado
3. sistema hospedado em produção
4. tenant por empresa
5. assinatura ativa ou trial
6. painel seu para bloquear e liberar contas
7. app desktop apenas na fase seguinte

## Conclusão

O modelo mais sólido para o seu objetivo é:

- SaaS central como produto principal
- assinatura e licenciamento validados no servidor
- app desktop opcional como canal de distribuição e conveniência

Esse modelo reproduz a percepção de produto comercial com download e ativação, sem abrir mão do controle de atualização, cobrança e acesso.

## Arquivos Relacionados

- [MODELO_BANCO_SAAS.md](MODELO_BANCO_SAAS.md)
- [BD/viabix_saas_multitenant.sql](BD/viabix_saas_multitenant.sql)
- [MIGRACAO_BANCO_SAAS.md](MIGRACAO_BANCO_SAAS.md)
- [BD/migracao_para_saas_fase1.sql](BD/migracao_para_saas_fase1.sql)