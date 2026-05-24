# 📦 Guia de Deploy para viabix.com.br

## Arquivo de Deploy
**Arquivo**: `viabix-deploy-20260508-153305.zip`  
**Tamanho**: ~98 KB  
**Versão**: 1dfcf5e (com correções de links)

---

## 🚀 Opção 1: Deploy via SSH + Git (RECOMENDADO)

### Passo 1: Sincronizar no servidor
```bash
ssh root@146.190.244.133
cd /var/www/viabix.com.br  # Ajuste o caminho conforme necessário
git pull origin main
```

Pronto! Os arquivos serão atualizados automaticamente.

---

## 📤 Opção 2: Upload Manual de Arquivos

### Passo 1: Descompactar o ZIP
O arquivo contém:
- `index.html` (página inicial - ATUALIZADO)
- `login.html` (login)
- `signup.html` (cadastro)
- `anvi.html` (aplicação)
- `dashboard.html` (dashboard)

### Passo 2: Fazer upload via SFTP
```bash
sftp root@146.190.244.133
cd /var/www/viabix.com.br
put index.html
put login.html
put signup.html
put anvi.html
put dashboard.html
```

Ou use um cliente SFTP (WinSCP, FileZilla, etc)

---

## ✅ Verificação Pós-Deploy

### No servidor
```bash
curl -I https://viabix.com.br/index.html
# Deve retornar HTTP/2 200
```

### No navegador
1. Acesse https://viabix.com.br
2. Verifique se a navegação funciona
3. Clique nos links de CTA:
   - "Teste Grátis" → signup.html
   - "Login" → login.html
   - "Acessar App" → anvi.html

---

## 📋 O que foi alterado

### Correções implementadas:
- ✅ Removidos ~100 arquivos desnecessários (PHASE_*, test_*, etc)
- ✅ Corrigidos 4 links quebrados:
  - app-demo.html → anvi.html
  - PLANO_COMERCIALIZACAO.html (3x) → #planos
  - LICENSE.html → removido
- ✅ Atualizado texto em FAQ
- ✅ Todos os links agora apontam para arquivos válidos

### Commits no GitHub:
```
1dfcf5e - fix: remove broken links to deleted files
38b6d88 - chore: remove obsolete test files
```

---

## 🆘 Troubleshooting

### Erro 404 em algum arquivo
- Verifique se o arquivo foi enviado corretamente
- Confirme permissões (chmod 644)

### Links não funcionam
- Limpe cache do navegador (Ctrl+Shift+Del)
- Verifique se os 5 arquivos estão no diretório raiz

### CSS/JS não carrega
- Verifique a URL base no .htaccess
- Confirm que os assets estão no caminho correto

---

## 📞 Contato para dúvidas
Qualquer problema, me avisa!

---

**Preparado em**: 2026-05-08  
**Versão**: 1.0  
**Status**: Pronto para produção ✅
