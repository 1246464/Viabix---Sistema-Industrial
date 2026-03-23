# 🚀 Guia de Renomeação: FANAVID → VIABIX

## ✅ Arquivos PHP já atualizados

Os seguintes arquivos foram automaticamente atualizados:

### API (todos os arquivos em `/api/`)
- ✅ `config.php` - DB_NAME e SESSION_NAME alterados
- ✅ `check_session.php` - session_name atualizado
- ✅ `login.php` - session_name atualizado
- ✅ `logout.php` - comentários atualizados
- ✅ `anvi.php` - comentários atualizados
- ✅ `usuarios.php` - comentários atualizados
- ✅ `diagnostico.php` - título atualizado
- ✅ `criar_projeto_de_anvi.php` - session_name atualizado
- ✅ `verificar_vinculo.php` - session_name atualizado

## 🗄️ BANCO DE DADOS - AÇÃO NECESSÁRIA

Você precisa renomear o banco de dados manualmente. Escolha um dos métodos:

### Método 1: Via phpMyAdmin (Recomendado)
1. Acesse http://localhost/phpmyadmin
2. Selecione o banco `fanavid_db` no menu lateral
3. Clique na aba **"Operações"**
4. Em "Renomear banco de dados para:", digite: `viabix_db`
5. Clique em **"Executar"**

### Método 2: Via Script SQL
Execute o arquivo que foi criado para você:
```bash
# No terminal, dentro da pasta ANVI:
mysql -u root -p < api/renomear_para_viabix.sql
```

### Método 3: Via Terminal MySQL
```sql
CREATE DATABASE viabix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

RENAME TABLE fanavid_db.anvis TO viabix_db.anvis;
RENAME TABLE fanavid_db.anvis_historico TO viabix_db.anvis_historico;
RENAME TABLE fanavid_db.usuarios TO viabix_db.usuarios;
RENAME TABLE fanavid_db.logs_atividade TO viabix_db.logs_atividade;
RENAME TABLE fanavid_db.notificacoes TO viabix_db.notificacoes;
RENAME TABLE fanavid_db.configuracoes TO viabix_db.configuracoes;
RENAME TABLE fanavid_db.conflitos_edicao TO viabix_db.conflitos_edicao;
RENAME TABLE fanavid_db.bancos_dados TO viabix_db.bancos_dados;
RENAME TABLE fanavid_db.projetos TO viabix_db.projetos;

-- Opcional: remover banco antigo
-- DROP DATABASE fanavid_db;
```

## 🔄 Após renomear o banco

1. **Limpe as sessões antigas:**
   - Feche todos os navegadores
   - OU limpe os cookies manualmente
   - OU use navegação anônima para testar

2. **Teste o login:**
   - Acesse http://localhost/ANVI/
   - Faça login com admin/admin123
   - Deve funcionar normalmente

## 📝 Opcional: Atualizar interface

Os arquivos HTML ainda contêm "FANAVID" na interface (títulos, cabeçalhos, etc). Você pode:

1. **Manter como está** - O sistema funcionará perfeitamente
2. **Atualizar depois** - Apenas solicite: "atualizar interface para Viabix"

Arquivos que teriam alterações visuais:
- `anvi.html` - Título da página e cabeçalhos
- `dashboard.html` - Nome do sistema
- `login.html` - Tela de login
- Arquivos no `/Controle_de_projetos/`

## 🧪 Testar após a mudança

1. Login: http://localhost/ANVI/ → admin/admin123
2. Criar uma ANVI e salvar
3. Criar um projeto vinculado
4. Verificar vinculos no dashboard

## ❓ Em caso de erro

**"Access denied for user"** ou **"Unknown database"**:
- Verifique se o banco `viabix_db` foi criado
- Confirme que `api/config.php` tem `DB_NAME = 'viabix_db'`

**"Session não funciona"**:
- Limpe os cookies do navegador
- Use Ctrl+Shift+Delete → Cookies → localhost

**Volta para tela de login**:
- Verifique se todos os arquivos PHP foram salvos
- Certifique-se que não há arquivos em cache

---

## ✨ Resumo do que mudou

| Antes | Depois |
|-------|--------|
| fanavid_db | viabix_db |
| fanavid_session | viabix_session |
| Sistema FANAVID | Sistema Viabix |

**Status:** Sistema pronto para usar assim que o banco for renomeado! 🎉
