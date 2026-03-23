# 🚀 GUIA RÁPIDO - Sistema FANAVID Integrado

## ⚡ Início Rápido (5 minutos)

### 1️⃣ Acessar o Sistema
```
URL: http://localhost/ANVI/
Login: admin
Senha: admin123
```

### 2️⃣ Conhecer o Dashboard
- **Card Verde** → Módulo ANVI (Orçamentação)
- **Card Azul** → Módulo Projetos (Gestão)
- **Barra de Estatísticas** → Métricas em tempo real

### 3️⃣ Criar sua Primeira ANVI Vinculada

#### Passo A: Criar ANVI
1. Dashboard → Clicar em "**ANVI - Análise de Viabilidade**"
2. Clicar em "**Nova ANVI**"
3. Preencher:
   - Nº ANVI: `001`
   - Revisão: `01`
   - Cliente: `Empresa Teste`
   - Produto: `Produto de Teste`
4. Preencher dados de custos (qualquer valor)
5. Clicar em "**Salvar ANVI**"

#### Passo B: Criar Projeto Vinculado
1. Após salvar, aparece botão "**Criar Projeto**" 🎯
2. Clicar no botão
3. Modal abre com dados pré-preenchidos:
   - Nome: já vem preenchido
   - Descrição: já vem preenchida
   - Orçamento: automático (valor da ANVI)
4. Escolher um Líder (opcional)
5. Clicar em "**Criar Projeto**"
6. ✅ Projeto criado e vinculado!

#### Passo C: Navegar Entre Módulos
1. Badge "**Projeto #X**" aparece na ANVI
2. Clicar em "**Ver Projeto**" → Abre módulo Projetos
3. No módulo Projetos, clicar "**Ver ANVI Vinculada**" → Volta para ANVI
4. 🔁 Navegação bidirecional funcionando!

---

## 🎯 Principais Recursos

### Dashboard
- ✅ Visão geral do sistema
- ✅ Acesso rápido aos módulos
- ✅ Estatísticas em tempo real
- ✅ Métrica de vínculos ANVI-Projeto

### Módulo ANVI
- ✅ Análise de Viabilidade completa
- ✅ Cálculos automáticos (DRE, custos, impostos)
- ✅ Exportação PDF
- ✅ Bloqueio de edição simultânea
- ✅ **Criar Projeto vinculado** 🆕
- ✅ **Ver Projeto vinculado** 🆕

### Módulo Projetos
- ✅ Gestão de projetos com Gantt
- ✅ Tarefas e prazos
- ✅ Progresso visual
- ✅ Exportação Excel
- ✅ **Ver ANVI vinculada** 🆕

### Integração
- ✅ Login único (um login para tudo)
- ✅ Menu de navegação unificado
- ✅ Vínculo bidirecional ANVI ↔ Projeto
- ✅ Orçamento automático do projeto

---

## 💡 Dicas Úteis

### Navegação Rápida
```
📊 Dashboard (F5 recarrega página atual)
    ↓
🧮 ANVI (criar/editar orçamentos)
    ↓
🔗 Criar Projeto (botão aparece após salvar)
    ↓
📁 Projetos (gestão e Gantt)
    ↓
🔙 Menu Superior (voltar ao Dashboard)
```

### Atalhos
- **Menu Superior**: Sempre visível em todos os módulos
- **Logout**: Disponível em qualquer tela
- **Dashboard**: Métricas atualizadas automaticamente
- **Nova Aba**: Vínculos abrem em nova aba (trabalhar em paralelo)

### Indicadores Visuais
- 🟢 **Badge Verde** = ANVI vinculada a projeto
- 🔵 **Botão "Criar Projeto"** = ANVI não vinculada
- 🔗 **Botão "Ver Projeto"** = ANVI tem projeto vinculado
- 📊 **Estatística "Vinculadas"** = Total de ANVIs com projeto

---

## 🔐 Níveis de Acesso

### Admin
- ✅ Criar/editar ANVIs
- ✅ Criar/editar Projetos
- ✅ Gerenciar Usuários
- ✅ Gerenciar Líderes
- ✅ Criar vínculos
- ✅ Acesso completo

### Usuário/Líder
- ✅ Criar/editar ANVIs
- ✅ Criar/editar Projetos
- ✅ Criar vínculos
- ❌ Gerenciar Usuários

### Visitante
- ✅ Visualizar ANVIs
- ✅ Visualizar Projetos
- ❌ Editar
- ❌ Criar vínculos

---

## 📋 Fluxo Recomendado

### 1. Orçamentação (ANVI)
```
Recebeu solicitação de orçamento
    ↓
Criar ANVI no sistema
    ↓
Preencher custos, impostos, margens
    ↓
Validar consistência
    ↓
Salvar ANVI
    ↓
Exportar PDF para aprovação
```

### 2. Aprovação e Vínculo
```
ANVI aprovada pelo cliente
    ↓
Clicar em "Criar Projeto"
    ↓
Preencher dados do projeto
    ↓
Projeto criado automaticamente
    ↓
Orçamento = Valor da ANVI
```

### 3. Execução (Projeto)
```
Abrir módulo Projetos
    ↓
Criar tarefas no Gantt
    ↓
Atribuir responsáveis
    ↓
Acompanhar progresso
    ↓
Atualizar status
```

### 4. Acompanhamento
```
Dashboard mostra:
- Total de ANVIs
- Total de Projetos
- Quantas ANVIs viraram projetos
- Taxa de conversão
```

---

## 🆘 Resolução de Problemas

### Não consigo fazer login
```
✅ Verificar:
- URL: http://localhost/ANVI/
- Login: admin
- Senha: admin123
- XAMPP rodando
- MySQL ativo
```

### Botão "Criar Projeto" não aparece
```
✅ Verificar:
- ANVI foi salva?
- ANVI já tem projeto vinculado? (ver badge)
- Recarregar página (F5)
```

### Erro ao criar projeto
```
✅ Verificar:
- ANVI existe no banco
- ANVI não está duplicada
- Campos obrigatórios preenchidos
- Console do navegador (F12)
```

### Vínculo não funciona
```
✅ Verificar:
- Banco de dados: fanavid_db
- Foreign keys criadas
- api/verificar_vinculo.php existe
- Console do navegador (F12)
```

---

## 📞 Onde Buscar Ajuda

### Documentação Detalhada
- **[FASE1_CONCLUIDA.md](FASE1_CONCLUIDA.md)** → Banco de dados
- **[FASE3_CONCLUIDA.md](FASE3_CONCLUIDA.md)** → Login único
- **[FASE4_CONCLUIDA.md](FASE4_CONCLUIDA.md)** → Funcionalidades cruzadas
- **[RESUMO_COMPLETO.md](RESUMO_COMPLETO.md)** → Visão geral completa

### Logs do Sistema
```sql
-- Ver últimas ações
SELECT * FROM logs_atividade 
ORDER BY criado_em DESC 
LIMIT 20;

-- Ver vínculos ativos
SELECT a.id, a.nome_anvi, p.id, p.nome 
FROM anvis a 
JOIN projetos p ON a.projeto_id = p.id;
```

### Console do Navegador
```
Pressionar F12 → Aba Console
Ver erros de JavaScript/API
```

---

## ✅ Checklist de Verificação

### Primeira Vez
- [ ] XAMPP instalado e rodando
- [ ] MySQL ativo
- [ ] Banco `fanavid_db` criado
- [ ] Tabelas criadas
- [ ] Usuário admin existe
- [ ] Consegue acessar http://localhost/ANVI/

### Antes de Usar Vínculos
- [ ] ANVI salva com sucesso
- [ ] Botão "Criar Projeto" visível
- [ ] Modal de criação abre
- [ ] Líderes carregam no dropdown

### Após Criar Vínculo
- [ ] Badge "Projeto #X" aparece
- [ ] Botão muda para "Ver Projeto"
- [ ] Dashboard mostra vínculo na estatística
- [ ] Projeto tem anvi_id preenchido
- [ ] ANVI tem projeto_id preenchido

---

## 🎓 Próximos Passos

### Após Dominar o Básico
1. **Criar múltiplas ANVIs** e projetos vinculados
2. **Explorar relatórios** PDF e Excel
3. **Testar permissões** (admin vs usuário vs visitante)
4. **Acompanhar métricas** no dashboard

### Personalização
1. Adicionar mais líderes
2. Criar usuários com diferentes níveis
3. Personalizar status de projetos
4. Ajustar campos conforme necessidade

---

## 🎉 Parabéns!

Você agora tem um **sistema industrial completo** funcionando!

**Recursos Principais:**
- ✅ Orçamentação profissional
- ✅ Gestão de projetos
- ✅ Integração total
- ✅ Dashboard inteligente

**Comece criando sua primeira ANVI vinculada!** 🚀

---

**Sistema FANAVID - Versão Integrada 2.0**
**Pronto para Produção** ✅
