# Plano de Limpeza do Projeto ANVI

## Arquivos para REMOVER (Seguros - não usados em produção)

### 1. Documentação de Fases Anteriores (30 arquivos .md)
```
PHASE_1_CURRENT_STATUS.md
PHASE_1_DOCUMENTATION_INDEX.md
PHASE_1_EXECUTIVE_SUMMARY.md
PHASE_1_IMMEDIATE_ACTIONS.md
PHASE_1_INDEXES.md
PHASE_1_PROGRESS.md
PHASE_1_SESSION_HANDOFF.md
PHASE_1_SESSION_SUMMARY.md
PHASE_1_TENANT_ISOLATION_AUDIT.md
PHASE_1_TENANT_ISOLATION_FIXES.md
PHASE_5_FIXES_PROGRESS.md
CONCLUSAO_BD_MODULE3_REAL.md
CONCLUSAO_DASHBOARD_EXPANDIDO.md
ADAPTER_BD_MODULE3_GUIDE.md
MODULE_3_DEMO_GUIDE.md
MODULE_3_VIABILIDADE_GUIDE.md
ANALYSIS_DOCUMENTATION_INDEX.md
ANALYSIS_EXECUTIVE_SUMMARY.md
ANALYSIS_FILES_README.md
ANALYSIS_TEXT_SUMMARY.txt
ANALYSIS_SUMMARY.json
ANALISE_CONTROLE_ACESSO.md
ANALISE_DADOS_FALTANDO.md
README_ANALYSIS.md
SESSION_2_FINAL_SUMMARY.md
PROGRESSO_REALIZADO.md
RESUMO_IMPLEMENTACAO_COMPLETA.md
SUMARIO_ENTREGA_AUTH.md
GUIA_TESTE_AUTH.md
IMPLEMENTACAO_AUTH_V2.md
```

### 2. Arquivos HTML de Teste/Demo (7 arquivos)
```
test_module_3_demo.html
admin_saas.html
billing.html
dashboard_viabilidade.html
deploy.html
PLANO_COMERCIALIZACAO.html
viabilidade.php
```

### 3. Arquivos de Deployment Antigos (5 arquivos)
```
deploy_indexes.ps1
deploy_indexes.sh
deploy-to-digitalocean.ps1
_deploy_index.php
DEPLOYMENT_CHECKLIST.md
QUICK_DEPLOY_INDEXES.md
PHASE_1_INDEXES.md
QUICK_REFERENCE.md
```

### 4. Arquivos de Análise/Auditoria (7 arquivos)
```
PROJECT_ANALYSIS.json
CHECKLIST_FINAL.txt
TENANT_ISOLATION_VULNERABILITY_AUDIT.md
DELIVERY_COMPLETE.md
00_START_HERE.md
PRODUCTION_ROADMAP.md
PRODUCTION_DEPLOYMENT.md
```

### 5. Arquivos de Setup/Guides (9 arquivos)
```
DEPLOY_QUICK_START.md
SWAGGER_OPENAPI.md
WEBHOOK_VALIDATION_SETUP.md
RATE_LIMITING_REDIS_SETUP.md
EMAIL_DELIVERY_SETUP.md
EMAIL_SYSTEM.md
INPUT_VALIDATION.md
AUDIT_LOGGING.md
CORS_PROTECTION.md
CSRF_PROTECTION.md
TWO_FACTOR_AUTH.md
RATE_LIMITING.md
MONITORING.md
```

## Arquivos para MANTER

### Essenciais
- index.html (página principal)
- login.html (autenticação)
- signup.html (registro)
- anvi.html (aplicação)
- dashboard.html (dashboard principal)
- bootstrap_env.php (ambiente)
- viabilidade.php (funcionalidade)

### Configuração e Ambiente
- .env, .env.example, .env.production
- .gitignore, .github/, .git/
- composer.json
- .htaccess

### Dados e Logs
- BD/ (banco de dados)
- api/ (APIs)
- Controle_de_projetos/ (dados)
- templates/ (templates)
- logs/ (logs)

### 6. Arquivos de Teste em api/ (40+ arquivos)
```
# Arquivos de TESTE/DEBUG (remover)
test_*.php (múltiplos):
  - test_2fa.php
  - test_audit.php
  - test_cors.php
  - test_csrf.php
  - test_email.php (e variações)
  - test_login_*.php
  - test_master*.php
  - test_rate_limit*.php
  - test_sentry.php
  - test_swagger.php
  - test_validation.php
  - test_webhook_signature.php

teste_*.php (múltiplos):
  - teste_funcoes.php
  - teste_incremental.php

diagnostico*.php (remover):
  - diagnostico.php
  - diagnostico_csrf.php
  - diagnostico_producao.php
  - diagnostics.php

debug_*.php:
  - debug_access.php

Outros desnecessários:
  - check_session_backup.php
  - reset_usuarios.php
  - simples.php
  - testar_conexao.php
  - validate_production_config.php
  - verificar_vinculo.php
  - renomear_para_viabix.sql
  - deploy.php
```

### 7. Pasta tests/ em api/
```
api/tests/ (toda a pasta se existir)
```

## Resumo
- **Total para remover**: ~100+ arquivos
- **Tamanho estimado**: ~4-5 MB
- **Impacto**: ZERO em produção (são apenas teste, debug e documentação obsoleta)

## Próximos Passos
1. ✓ Análise concluída
2. Executar limpeza local
3. Fazer commit + push para GitHub
4. Sincronizar com DigitalOcean via git pull
