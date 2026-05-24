# 🚀 Instruções para Deploy via SSH

## Status: Conectado com sucesso! ✅

Você está logado como `root` no servidor `146.190.244.133`

---

## Próximos passos:

### 1️⃣ Navegar até o diretório do projeto
```bash
cd /var/www/viabix.com.br
```

Se não souber o caminho exato, procure com:
```bash
find / -name "viabix.com.br" -type d 2>/dev/null
# ou
find / -name "index.html" -path "*/viabix*" 2>/dev/null | head -5
```

### 2️⃣ Fazer o pull do GitHub
```bash
git pull origin main
```

Isso vai:
- ✅ Baixar os 2 commits recentes
- ✅ Atualizar index.html com as correções
- ✅ Remover os arquivos deletados

### 3️⃣ Verificar se funcionou
```bash
ls -la index.html
# Deve mostrar arquivo atualizado

git log --oneline -3
# Deve mostrar os 2 últimos commits
```

### 4️⃣ Testar o site
```bash
curl -I https://viabix.com.br
# Deve retornar HTTP 200
```

---

## Caminhos comuns:
- `/var/www/viabix.com.br`
- `/home/user/viabix.com.br`
- `/opt/viabix`
- `/root/viabix.com.br`

Tente `cd /var/www/viabix.com.br` primeiro!

---

**Manda aí o output dos comandos que vou monitorar! 👀**
