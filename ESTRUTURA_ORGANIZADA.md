# 📁 ESTRUTURA ORGANIZADA - Sistema Viabix

## 🎯 Visão Geral

Este documento organiza todos os arquivos do sistema **Viabix** por categoria e função.

---

## 📂 Estrutura Completa do Projeto

```
ANVI/ (Pasta Principal)
│
├── 🌐 ARQUIVOS PRINCIPAIS (Interface do Usuário)
│   ├── index.html               → Página inicial (redireciona para dashboard)
│   ├── login.html               → Tela de login unificada
│   ├── dashboard.html           → Dashboard com estatísticas e navegação
│   ├── anvi.html                → Módulo ANVI (Análise de Viabilidade)
│   ├── teste_login.html         → Página de teste/debug de login
│   ├── banco_unificado.php      → Status da unificação do banco
│   └── renomear_banco_viabix.php → Script de renomeação automática
│
├── 📚 DOCUMENTAÇÃO
│   ├── README.md                → Documentação principal do sistema
│   ├── VIABIX_PRONTO.md        → Guia de conclusão da renomeação
│   ├── RENOMEACAO_VIABIX.md    → Guia técnico de renomeação
│   ├── GUIA_RAPIDO.md          → Tutorial rápido (5 minutos)
│   ├── RESUMO_COMPLETO.md      → Visão geral completa
│   ├── FASE2_CONCLUIDA.md      → Documentação Fase 2
│   ├── FASE3_CONCLUIDA.md      → Documentação Fase 3
│   ├── FASE4_CONCLUIDA.md      → Documentação Fase 4 (atual)
│   ├── PLANO_COMERCIALIZACAO.html → Plano de negócio visual
│   ├── ESTRUTURA_ORGANIZADA.md  → Este arquivo
│   └── Estrutura Final dos Arquivos.txt → Lista antiga
│
├── 📂 api/ (Backend - APIs PHP)
│   │
│   ├── 🔧 CONFIGURAÇÃO
│   │   └── config.php           → Config banco + sessão (viabix_db, viabix_session)
│   │
│   ├── 🔐 AUTENTICAÇÃO
│   │   ├── login.php            → API de login
│   │   ├── logout.php           → API de logout
│   │   ├── logout_redirect.php  → Redirecionamento de logout
│   │   └── check_session.php    → Verificação de sessão ativa
│   │
│   ├── 📊 CRUD PRINCIPAL
│   │   ├── anvi.php             → CRUD completo de ANVIs
│   │   └── usuarios.php         → Gestão de usuários
│   │
│   ├── 🔗 INTEGRAÇÃO (Fase 4)
│   │   ├── criar_projeto_de_anvi.php → Criar projeto vinculado
│   │   └── verificar_vinculo.php     → Verificar vínculo ANVI↔Projeto
│   │
│   ├── 💾 BANCO DE DADOS
│   │   ├── database.sql         → Script SQL completo
│   │   ├── criar_db.php         → Criar banco via PHP
│   │   ├── importar_db.php      → Importar banco
│   │   ├── unificar_bancos.php  → Unificar ANVI+Projetos
│   │   └── renomear_para_viabix.sql → Script de renomeação
│   │
│   ├── 🛠️ UTILITÁRIOS
│   │   ├── diagnostico.php      → Diagnóstico do sistema
│   │   ├── reset_usuarios.php   → Resetar usuários
│   │   └── test_salvar.php      → Teste de salvamento
│   │
│   └── 🗑️ BACKUPS (não usar)
│       └── check_session_backup.php → Backup antigo
│
├── 📂 Controle_de_projetos/ (Módulo de Projetos)
│   ├── index.php                → Interface principal do módulo
│   ├── config.php               → Configuração do banco
│   ├── auth.php                 → Autenticação unificada
│   ├── login.php                → Redirecionamento de login
│   ├── logout.php               → Redirecionamento de logout
│   └── projetos.json            → Dados dos projetos (se usar JSON)
│
├── 📂 BD/ (Backups SQL Separados)
│   ├── viabix_db_anvis.sql
│   ├── viabix_db_anvis_historico.sql
│   ├── viabix_db_usuarios.sql
│   ├── viabix_db_logs_atividade.sql
│   ├── viabix_db_notificacoes.sql
│   ├── viabix_db_configuracoes.sql
│   ├── viabix_db_conflitos_edicao.sql
│   ├── viabix_db_bancos_dados.sql
│   └── viabix_db_routines.sql
│
├── 📂 uploads/ (Arquivos do Sistema - opcional)
│   ├── desenhos/                → Imagens/desenhos das ANVIs
│   └── documentos/              → Documentos dos projetos
│
└── 🔧 ARQUIVOS DE CONFIGURAÇÃO
    ├── .htaccess                → Configuração Apache (renomear de .htaccess.txt)
    └── .htaccess.txt            → Template de configuração
```

---

## 🗂️ Organização por Tipo de Arquivo

### 1️⃣ Arquivos HTML (Interface)
| Arquivo | Finalidade | Acesso |
|---------|------------|--------|
| `index.html` | Splash/Redirecionamento | Público |
| `login.html` | Tela de login | Público |
| `dashboard.html` | Dashboard principal | Requer login |
| `anvi.html` | Módulo ANVI | Requer login |
| `teste_login.html` | Debug de login | Debug |
| `PLANO_COMERCIALIZACAO.html` | Apresentação comercial | Interno |

### 2️⃣ Arquivos PHP (Backend)
| Arquivo | Tipo | Função |
|---------|------|--------|
| `api/config.php` | Config | Conexão DB + Sessão |
| `api/login.php` | API | Autenticação |
| `api/anvi.php` | API | CRUD ANVIs |
| `api/usuarios.php` | API | Gestão usuários |
| `api/criar_projeto_de_anvi.php` | API | Integração Fase 4 |
| `Controle_de_projetos/index.php` | Interface | Módulo Projetos |
| `renomear_banco_viabix.php` | Utility | Renomeação automática |

### 3️⃣ Arquivos SQL (Banco de Dados)
| Arquivo | Finalidade | Quando usar |
|---------|------------|-------------|
| `api/database.sql` | Script completo | Instalação inicial |
| `api/renomear_para_viabix.sql` | Renomeação | Migração FANAVID→Viabix |
| `BD/*.sql` | Backups separados | Restauração específica |

### 4️⃣ Arquivos Markdown (Documentação)
| Arquivo | Conteúdo | Público |
|---------|----------|---------|
| `README.md` | Documentação principal | ✅ Sim |
| `GUIA_RAPIDO.md` | Tutorial 5min | ✅ Sim |
| `VIABIX_PRONTO.md` | Guia de conclusão | 🔧 Setup |
| `FASE4_CONCLUIDA.md` | Doc técnica completa | 👨‍💻 Dev |
| `PLANO_COMERCIALIZACAO.html` | Estratégia de negócio | 💼 Negócio |

---

## 🎨 Arquivos por Camada (Arquitetura)

### 🌐 Camada de Apresentação (Frontend)
```
index.html        → Router
login.html        → Auth UI
dashboard.html    → Main UI
anvi.html         → ANVI Module UI
Controle_de_projetos/index.php → Projects Module UI
```

### 🔄 Camada de Lógica de Negócio (Backend)
```
api/login.php     → Authentication Logic
api/anvi.php      → ANVI Business Rules
api/usuarios.php  → User Management
api/criar_projeto_de_anvi.php → Integration Logic
```

### 💾 Camada de Dados (Database)
```
api/config.php    → DB Connection
api/database.sql  → DB Schema
viabix_db         → MySQL Database
```

### 🔐 Camada de Segurança
```
api/check_session.php      → Session Validation
Controle_de_projetos/auth.php → Access Control
SESSION: viabix_session    → Session Management
```

---

## 📋 Checklist de Arquivos Essenciais

### ✅ Para o Sistema Funcionar
- ✅ `index.html` - Entrada do sistema
- ✅ `login.html` - Autenticação
- ✅ `dashboard.html` - Dashboard
- ✅ `anvi.html` - Módulo ANVI
- ✅ `api/config.php` - Configuração
- ✅ `api/login.php` - API de login
- ✅ `api/check_session.php` - Validação de sessão
- ✅ `api/anvi.php` - CRUD de ANVIs
- ✅ `api/database.sql` - Estrutura do banco
- ✅ `Controle_de_projetos/index.php` - Módulo de projetos
- ✅ `Controle_de_projetos/config.php` - Config de projetos

### 📚 Documentação Recomendada
- ✅ `README.md` - Documentação principal
- ✅ `GUIA_RAPIDO.md` - Tutorial
- ⚠️ `FASE4_CONCLUIDA.md` - Opcional mas recomendado

### 🗑️ Arquivos que Podem ser Removidos
- ❌ `check_session_backup.php` - Backup antigo
- ❌ `Estrutura Final dos Arquivos.txt` - Lista antiga
- ❌ `teste_login.html` - Apenas para debug (remover em produção)
- ❌ Arquivos `BD/*.sql` com `fanavid_db` - Backups antigos

---

## 🔄 Fluxo de Dados

```
┌─────────────┐
│   USUÁRIO   │
└──────┬──────┘
       │
       ▼
┌─────────────┐     ┌──────────────┐
│ login.html  │────▶│ api/login.php│
└─────────────┘     └──────┬───────┘
                           │
                           ▼
                    ┌─────────────┐
                    │ viabix_db   │
                    │ (MySQL)     │
                    └──────┬──────┘
                           │
       ┌───────────────────┼───────────────────┐
       ▼                   ▼                   ▼
┌──────────────┐    ┌─────────────┐    ┌──────────────┐
│dashboard.html│    │  anvi.html  │    │projetos/index│
└──────┬───────┘    └──────┬──────┘    └──────┬───────┘
       │                   │                   │
       ▼                   ▼                   ▼
┌──────────────┐    ┌─────────────┐    ┌──────────────┐
│ Estatísticas │    │ api/anvi.php│    │ Cronogramas  │
└──────────────┘    └─────────────┘    └──────────────┘
```

---

## 🧹 Limpeza Recomendada

### Arquivos para Mover para Pasta "OLD" ou Remover:
```bash
# Criar pasta de arquivos antigos
mkdir OLD

# Mover backups antigos
mv api/check_session_backup.php OLD/
mv BD/*fanavid* OLD/  # Só se já tiver backups novos

# Mover documentação de migração (após concluir)
mv RENOMEACAO_VIABIX.md OLD/
mv VIABIX_PRONTO.md OLD/
mv renomear_banco_viabix.php OLD/
```

### Arquivos para Manter Sempre
- ✅ Todos os HTML principais
- ✅ Toda a pasta `api/` (exceto backups)
- ✅ `README.md`, `GUIA_RAPIDO.md`
- ✅ `api/database.sql` (sempre atualizado)
- ✅ Pasta `Controle_de_projetos/`

---

## 📊 Estatísticas do Projeto

| Categoria | Quantidade | Tamanho Aprox. |
|-----------|------------|----------------|
| Arquivos HTML | 5 | ~2 MB |
| Arquivos PHP | 20+ | ~500 KB |
| Arquivos SQL | 12+ | ~2 MB |
| Documentação | 10+ | ~500 KB |
| **TOTAL** | **~50** | **~5 MB** |

---

## 🎯 Próximos Passos de Organização

1. **Criar pasta de uploads:**
   ```bash
   mkdir uploads/desenhos uploads/documentos
   chmod 777 uploads -R  # Linux
   ```

2. **Ativar .htaccess:**
   ```bash
   cp .htaccess.txt .htaccess
   ```

3. **Limpar arquivos temporários:**
   ```bash
   rm -rf OLD/
   rm teste_login.html (em produção)
   ```

4. **Fazer backup final:**
   ```bash
   mysqldump -u root -p viabix_db > backup_viabix_producao.sql
   ```

---

**📁 Sistema Viabix - Organização Completa**  
*Última atualização: 17/03/2026*
