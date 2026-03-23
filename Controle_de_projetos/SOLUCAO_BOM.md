# 🔧 Solução de Problemas - BOM e JSON

## ⚠️ Se aparecer erro: "SyntaxError: Unexpected token"

Este erro acontece quando os arquivos PHP têm uma marca invisível chamada **BOM** (Byte Order Mark).

### 📝 **Solução Rápida:**

1. Abra o **PowerShell** na pasta do sistema
2. Execute o comando:
   ```powershell
   .\remover_bom.ps1
   ```
3. Pressione **Ctrl+Shift+R** no navegador para recarregar

Pronto! O sistema deve funcionar normalmente.

---

## 🔍 **O que é BOM?**

BOM é uma marca invisível de 3 bytes (EF BB BF) que alguns editores de texto adicionam no início de arquivos UTF-8. Isso causa problemas em JSON porque o JavaScript tenta fazer parse e encontra caracteres invisíveis antes do `{`.

---

## ✅ **O que o script faz?**

O `remover_bom.ps1` verifica todos os arquivos PHP e:
- Detecta se tem BOM
- Remove os 3 bytes invisíveis
- Salva o arquivo limpo

---

## 🚨 **IMPORTANTE:**

- ❌ **NÃO use** arquivo `.htaccess` - ele pode forçar todos os PHP como JSON
- ✅ **USE** o script `remover_bom.ps1` se der erro
- ✅ **Salve** arquivos PHP sempre como "UTF-8 sem BOM" em editores de texto

---

## 💡 **Editores recomendados:**

- **VS Code** - Configurar: "UTF-8" (não "UTF-8 with BOM")
- **Notepad++** - Codificação → UTF-8 sem BOM
- **Sublime Text** - File → Save with Encoding → UTF-8

---

## 📞 **Ainda com problemas?**

Se o erro persistir após rodar o script:

1. Verifique se o PHP está atualizado (7.4+)
2. Limpe o cache do navegador (Ctrl+Shift+Delete)
3. Teste em modo anônimo/privado
4. Verifique se o Apache está rodando

---

**Data:** 02/03/2026  
**Versão do Sistema:** 1.0
