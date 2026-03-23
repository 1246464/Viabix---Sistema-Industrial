# ✅ SISTEMA RENOMEADO: VIABIX

## 🎉 Parabéns! Todos os arquivos foram atualizados!

O sistema foi completamente renomeado de **FANAVID** para **VIABIX**.

---

## 📝 O QUE FOI ALTERADO AUTOMATICAMENTE

### ✅ Arquivos PHP da API (13 arquivos)
- `api/config.php` → DB_NAME = 'viabix_db' | SESSION_NAME = 'viabix_session'
- `api/check_session.php` → session_name('viabix_session')
- `api/login.php` → session_name('viabix_session')
- `api/logout.php` → Comentários atualizados
- `api/anvi.php` → Comentários atualizados
- `api/usuarios.php` → Comentários atualizados
- `api/diagnostico.php` → Interface atualizada
- `api/criar_projeto_de_anvi.php` → session_name('viabix_session')
- `api/verificar_vinculo.php` → session_name('viabix_session')
- `api/criar_db.php` → dbname = 'viabix_db'
- `api/importar_db.php` → dbname = 'viabix_db'
- `api/unificar_bancos.php` → dbname = 'viabix_db'

### ✅ Arquivos HTML da Interface (4 arquivos)
- `login.html` → Título e logo: "Viabix"
- `dashboard.html` → Título e barra: "Viabix - Sistema Industrial Completo"
- `index.html` → Título: "Viabix"
- `anvi.html` → Todos os textos visíveis: "Viabix"
- `PLANO_COMERCIALIZACAO.html` → Título: "Viabix"

### ✅ Outras Configurações
- `Controle_de_projetos/config.php` → DB_NAME = 'viabix_db'
- `banco_unificado.php` → Texto: "viabix_db"

### ✅ Arquivos PDFs Gerados
- Cabeçalhos de PDF agora mostram: "Viabix"
- Rodapés de PDF: "Sistema Viabix - Modelo Industrial 10/10"

---

## 🔧 O QUE VOCÊ PRECISA FAZER AGORA

### PASSO 1: Renomear o Banco de Dados (OBRIGATÓRIO)

Escolha um dos 3 métodos abaixo:

#### Método 1: Via phpMyAdmin (Mais Fácil)
1. Abra: http://localhost/phpmyadmin
2. No menu lateral, clique em **fanavid_db**
3. Clique na aba **"Operações"**
4. Na seção "Renomear banco de dados para:", digite: **viabix_db**
5. Clique em **"Executar"**
6. Pronto! ✅

#### Método 2: Via Script SQL Automático
Execute no PowerShell (dentro da pasta ANVI):
```powershell
mysql -u root -p < api\renomear_para_viabix.sql
```
Digite sua senha do MySQL quando solicitado.

#### Método 3: Via Terminal MySQL (Manual)
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

DROP DATABASE fanavid_db; -- Opcional
```

### PASSO 2: Limpar Sessões Antigas

Após renomear o banco, faça UMA dessas opções:

**Opção A:** Limpar cookies do navegador
- Pressione `Ctrl + Shift + Delete`
- Selecione "Cookies"
- Confirme

**Opção B:** Usar navegação anônima
- Chrome: `Ctrl + Shift + N`
- Firefox: `Ctrl + Shift + P`

**Opção C:** Fechar e reabrir o navegador

### PASSO 3: Testar o Sistema

1. Acesse: http://localhost/ANVI/
2. Faça login: **admin** / **admin123**
3. Você deve ver "Viabix" em todos os lugares!

---

## 🧪 CHECKLIST DE VERIFICAÇÃO

Após concluir os passos acima, verifique:

- [ ] Consigo fazer login normalmente
- [ ] O título da página mostra "Viabix"
- [ ] O logo/nome no cabeçalho mostra "Viabix"
- [ ] Posso criar uma ANVI e salvar
- [ ] Posso criar um projeto vinculado
- [ ] O dashboard mostra estatísticas
- [ ] Os PDFs gerados mostram "Viabix" no cabeçalho

---

## 📊 COMPARATIVO: ANTES vs DEPOIS

| Item | Antes | Depois |
|------|-------|--------|
| **Nome do Sistema** | FANAVID | **Viabix** |
| **Banco de Dados** | fanavid_db | **viabix_db** |
| **Sessão** | fanavid_session | **viabix_session** |
| **Interface** | "FANAVID - Sistema..." | **"Viabix - Sistema..."** |
| **PDFs** | FANAVID no cabeçalho | **Viabix no cabeçalho** |

---

## ❗ TROUBLESHOOTING

### Erro: "Unknown database 'viabix_db'"
**Causa:** O banco ainda não foi renomeado  
**Solução:** Execute o PASSO 1 (renomear banco)

### Erro: "Access denied for user"
**Causa:** Credenciais do banco incorretas  
**Solução:** Verifique usuário/senha em `api/config.php`

### Sistema volta para login após fazer login
**Causa:** Cookies antigos com nome diferente de sessão  
**Solução:** Execute o PASSO 2 (limpar cookies)

### Ainda aparece "FANAVID" em algum lugar
**Causa:** Cache do navegador  
**Solução:** Pressione `Ctrl + F5` para forçar recarga

---

## 📁 ARQUIVOS CRIADOS PARA VOCÊ

1. **`api/renomear_para_viabix.sql`**  
   Script SQL completo para renomear o banco automaticamente

2. **`RENOMEACAO_VIABIX.md`**  
   Documentação técnica detalhada da renomeação

3. **`VIABIX_PRONTO.md`** (este arquivo)  
   Guia rápido de conclusão

---

## 🚀 PRÓXIMOS PASSOS (Opcional)

Agora que o sistema está renomeado, você pode:

1. **Registrar o domínio:** viabix.com.br
2. **Criar identidade visual:** Logo profissional
3. **Configurar hospedagem:** Preparar para produção
4. **Marketing:** Implementar estratégias do plano de comercialização
5. **Validação:** Testar com clientes piloto

---

## 💰 LEMBRE-SE: PLANO DE COMERCIALIZAÇÃO

Seu plano de preços Viabix:
- **Starter:** R$ 297/mês (até 50 ANVIs)
- **Professional:** R$ 697/mês (até 200 ANVIs)
- **Enterprise:** R$ 1.497/mês (ANVIs ilimitadas)
- **White Label:** R$ 3.997/mês (marca própria)

Veja detalhes em: `PLANO_COMERCIALIZACAO.html`

---

## ✨ RESUMO DE 30 SEGUNDOS

```
1. Renomear banco: phpMyAdmin → Operações → "viabix_db"
2. Limpar cookies: Ctrl+Shift+Delete
3. Testar: http://localhost/ANVI/ → login → funciona!
```

**Pronto! Seu sistema Viabix está operacional! 🎉**

---

*Última atualização: 17 de março de 2026*  
*Versão do Sistema: 7.1 - Modelo Industrial 10/10*  
*Renomeação: FANAVID → Viabix*
