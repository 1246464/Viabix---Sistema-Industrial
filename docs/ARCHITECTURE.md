# Arquitetura Viabix

Este documento registra a organização desejada do projeto para reduzir arquivos monolíticos e evitar regras duplicadas.

## Estrutura principal

- `api/`: endpoints PHP e bootstrap da aplicação web.
- `api/lib/`: helpers compartilhados por domínio. Novas regras reutilizáveis devem entrar aqui antes de crescer dentro de `api/config.php`.
- `assets/css/`: estilos compartilhados entre páginas.
- `assets/js/`: scripts de página ou módulos front-end extraídos de HTML inline.
- `BD/`: migrações e seeds SQL.
- `Controle_de_projetos/`: módulo legado/operacional de projetos.
- `viabix-android/`: aplicação Android.
- `deploy/`: scripts e arquivos de operação para servidor.

## Regras de organização

- `api/config.php` deve ficar focado em bootstrap, sessão, ambiente, conexão e helpers de baixo nível.
- Regras comerciais de planos, billing e limites ficam em `api/lib/billing.php`.
- Novos scripts grandes não devem ser escritos inline em HTML. Use `assets/js/<pagina>.js`.
- Novos estilos reutilizáveis devem ir para `assets/css/viabix-theme.css` ou outro arquivo em `assets/css/`.
- Endpoints novos devem reaproveitar autenticação, tenant, CSRF/JWT e quota existentes.
- Arquivos gerados, APKs, JARs, builds e distribuições baixadas não devem ser versionados.

## Próximos recortes recomendados

1. Evoluir os testes mínimos para testes funcionais com banco limpo de homologação.
2. Consolidar endpoints restantes do dashboard de viabilidade quando o fluxo de tela estiver estabilizado.

## Recortes já realizados

- Regras de planos, assinatura, quotas e faturas foram movidas para `api/lib/billing.php`.
- Script do painel admin foi movido para `assets/js/admin-saas.js`.
- Script do cadastro foi movido para `assets/js/signup.js`.
- Script da assinatura foi movido para `assets/js/billing.js`.
- Script do dashboard de viabilidade foi movido para `assets/js/dashboard-viabilidade.js`.
- CSS da tela ANVI foi movido para `assets/css/anvi.css`.
- Script principal da tela ANVI foi movido para `assets/js/anvi-page.js`.
- Sessão e gestão de usuários da ANVI foram movidas para `assets/js/anvi-auth-users.js`.
- Galeria/desenhos da ANVI foram movidos para `assets/js/anvi-drawings.js`.
- Utilitários de formatação da ANVI foram movidos para `assets/js/anvi-utils.js`.
- Notificações, exportação da DRE e controles visuais fiscais da ANVI foram movidos para `assets/js/anvi-ui-helpers.js`.
- Constantes, cálculos e indicadores financeiros da ANVI foram movidos para `assets/js/anvi-calculations.js`.
- Captura e restauração de dados da ANVI foram movidas para `assets/js/anvi-data-io.js`.
- Relatórios e PDF da ANVI foram movidos para `assets/js/anvi-report.js`.
- Classificação fiscal da ANVI foi movida para `assets/js/anvi-tax-classification.js`.
- Edição, importação, exportação e armazenamento de tabelas da ANVI foram movidos para `assets/js/anvi-table-ui.js`.
- Vínculo da ANVI com projetos foi movido para `assets/js/anvi-project-link.js`.
- Fluxo de criação de nova ANVI foi movido para `assets/js/anvi-new-form.js`.
- Fluxo por etapas e demo comercial da ANVI foram movidos para `assets/js/anvi-workflow.js`.
- CSS do Controle de Projetos foi movido para `assets/css/projects-control.css`.
- JavaScript principal do Controle de Projetos foi movido para `assets/js/projects-control.js`.
- JavaScript do Controle de Projetos foi separado por domínio:
  `projects-state.js`, `projects-api.js`, `projects-core.js`, `projects-forms.js`,
  `projects-gantt.js`, `projects-charts.js`, `projects-apqp.js`,
  `projects-capability.js`, `projects-reports.js` e `projects-control.js`.
- `api/config.php` foi reduzido para bootstrap e seus domínios foram movidos para:
  `api/lib/runtime.php`, `api/lib/support.php`, `api/lib/schema.php`,
  `api/lib/auth_tenant.php`, `api/lib/billing_gateway.php` e `api/lib/billing.php`.
- `api/list_users.php` foi consolidado como alias administrativo seguro, com autenticação admin, tenant e limite de retorno.
- Testes mínimos estáticos foram criados em `tests/smoke_static.php` para signup, login, ANVI, projetos, billing e usuários.
