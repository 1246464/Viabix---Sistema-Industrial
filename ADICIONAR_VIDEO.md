# Como Adicionar Vídeo Demonstrativo ao Site

## 📹 Seção de Vídeo Criada!

Uma seção profissional de vídeo demonstrativo foi adicionada ao `index.html`, logo após a seção Hero.

## 🎬 Opções para Adicionar seu Vídeo

### Opção 1: Vídeo do YouTube (Recomendado)

1. Faça upload do seu vídeo no YouTube
2. Copie o ID do vídeo (exemplo: se a URL é `https://www.youtube.com/watch?v=ABC123`, o ID é `ABC123`)
3. No arquivo `index.html`, encontre a linha 1323:
   ```html
   <!-- <iframe src="https://www.youtube.com/embed/SEU_VIDEO_ID?rel=0&modestbranding=1" allowfullscreen></iframe> -->
   ```
4. Descomente e substitua `SEU_VIDEO_ID` pelo ID do seu vídeo:
   ```html
   <iframe src="https://www.youtube.com/embed/ABC123?rel=0&modestbranding=1" allowfullscreen></iframe>
   ```
5. Remova ou comente a div `video-placeholder` (linhas 1334-1341)

**Parâmetros da URL:**
- `rel=0` - Não mostra vídeos relacionados
- `modestbranding=1` - Minimiza o branding do YouTube
- `autoplay=1` - (opcional) Inicia automaticamente

---

### Opção 2: Vídeo do Vimeo

1. Faça upload do seu vídeo no Vimeo
2. Copie o ID do vídeo da URL
3. No arquivo `index.html`, encontre a linha 1326:
   ```html
   <!-- <iframe src="https://player.vimeo.com/video/SEU_VIDEO_ID?title=0&byline=0&portrait=0" allowfullscreen></iframe> -->
   ```
4. Descomente e substitua `SEU_VIDEO_ID`:
   ```html
   <iframe src="https://player.vimeo.com/video/123456789?title=0&byline=0&portrait=0" allowfullscreen></iframe>
   ```
5. Remova ou comente a div `video-placeholder`

---

### Opção 3: Arquivo de Vídeo Local

1. Crie uma pasta `videos` na raiz do projeto:
   ```
   C:\xampp\htdocs\ANVI\videos\
   ```

2. Coloque seu vídeo nessa pasta (formatos recomendados: MP4 e WebM)

3. **Opcional:** Crie uma miniatura (thumbnail) do vídeo:
   ```
   C:\xampp\htdocs\ANVI\img\video-thumbnail.jpg
   ```

4. No arquivo `index.html`, encontre a linha 1329:
   ```html
   <!-- <video controls poster="img/video-thumbnail.jpg">
       <source src="videos/viabix-demo.mp4" type="video/mp4">
       <source src="videos/viabix-demo.webm" type="video/webm">
       Seu navegador não suporta vídeos HTML5.
   </video> -->
   ```

5. Descomente e ajuste os nomes dos arquivos:
   ```html
   <video controls poster="img/video-thumbnail.jpg">
       <source src="videos/viabix-demo.mp4" type="video/mp4">
       <source src="videos/viabix-demo.webm" type="video/webm">
       Seu navegador não suporta vídeos HTML5.
   </video>
   ```

6. Remova ou comente a div `video-placeholder`

---

## 🎨 Alternativa: GIF Animado

Se preferir usar um GIF animado ao invés de vídeo:

1. Coloque seu GIF na pasta `img/`:
   ```
   C:\xampp\htdocs\ANVI\img\viabix-demo.gif
   ```

2. Substitua todo o conteúdo da `video-wrapper` por:
   ```html
   <div class="video-wrapper" style="padding-bottom: 0; height: auto;">
       <img src="img/viabix-demo.gif" alt="Demonstração Viabix" style="position: relative; width: 100%; height: auto; display: block;">
   </div>
   ```

---

## 🎥 Como Criar um Vídeo Demonstrativo

### Dicas para gravar:

1. **Duração:** 30-60 segundos
2. **Resolução:** 1920x1080 (Full HD) mínimo
3. **Formato:** MP4 (H.264) ou WebM

### O que mostrar:

1. **Início (0-10s):** Logo e nome do produto
2. **Demo (10-45s):** Navegue pelas principais funcionalidades:
   - Dashboard principal
   - Criação de ANVI
   - Módulo de projetos
   - Relatórios
3. **CTA (45-60s):** Call-to-action "Comece seu teste grátis"

### Ferramentas para gravar:

- **OBS Studio** (gratuito) - Windows/Mac/Linux
- **Loom** (gratuito para vídeos curtos) - Web/Desktop
- **Screencast-O-Matic** - Web
- **QuickTime** (Mac nativo)
- **Xbox Game Bar** (Windows nativo - Win+G)

### Ferramentas para converter GIF:

- **Ezgif.com** (online)
- **CloudConvert** (online)
- **GIPHY** (online)

---

## 📊 Otimização de Vídeo

### Para vídeos locais, otimize o tamanho:

1. **Comprimir vídeo:**
   - Usar HandBrake (gratuito)
   - Configuração: Web > Gmail Medium 720p30
   - Resultado: boa qualidade, tamanho reduzido

2. **Tamanho recomendado:**
   - Máximo: 10-15 MB para vídeos de 60s
   - Menor possível sem perder qualidade

---

## ✅ Checklist Pós-Adição

Depois de adicionar seu vídeo:

- [ ] Testar no navegador (Chrome, Firefox, Safari, Edge)
- [ ] Verificar em mobile (responsividade)
- [ ] Confirmar que o vídeo carrega rápido
- [ ] Verificar se áudio está ok (se houver)
- [ ] Remover ou comentar o placeholder
- [ ] Fazer commit e push para o GitHub
- [ ] Fazer deploy para o servidor DigitalOcean

---

## 🚀 Deploy Após Adicionar Vídeo

```bash
# Local (Windows)
git add .
git commit -m "feat: adicionar vídeo demonstrativo do sistema"
git push origin main

# Servidor (SSH)
ssh root@146.190.244.133
cd /var/www/viabix
git pull origin main
```

---

## 💡 Dica Extra

Se você não tem um vídeo ainda, considere:

1. **Gravar a tela navegando no sistema** (5 minutos de trabalho)
2. **Adicionar música de fundo** (opcional)
3. **Colocar legendas com pontos-chave** (recomendado)
4. **Fazer upload no YouTube como "não listado"** (mais fácil)

**Benefício:** Vídeos convertem 80% mais que apenas texto!
