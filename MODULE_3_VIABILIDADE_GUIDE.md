# 🎯 MÓDULO 3: Dashboard de Compatibilidade/Viabilidade Integrada

**Status:** ✅ Implementado e Pronto para Usar  
**Data:** May 4, 2026  
**Tipo:** Dashboard de Análise de Viabilidade

---

## 📊 O QUE É ESTE MÓDULO?

Um **Dashboard que mostra incompatibilidades e incoerências** em um projeto ANVI, analisando:

### Áreas Analisadas:
1. **💰 Financeiro**
   - Orçamento vs Despesas Reais
   - Fluxo de Caixa (saldo positivo/negativo)
   - Alertas quando despesas ultrapassam orçamento

2. **📅 Planejamento**
   - Cronograma vs Progresso Real
   - Tarefas vs Prazos
   - Atrasos/Adiantos (em dias)

3. **✅ Qualidade**
   - Taxa de Erros / Issues Abertos
   - Cobertura de Testes
   - Issues Críticas

4. **👥 Recursos**
   - Equipe Alocada
   - Infraestrutura (disponibilidade)
   - Recursos Ativos

### Score de Viabilidade Geral
- **80-100:** 🟢 Viável
- **60-79:** 🟡 Viável com Ressalvas
- **0-59:** 🔴 Não Viável

---

## 🚀 COMO ACESSAR

### Via Interface Web (RECOMENDADO)
```
Abra no navegador:
http://localhost:8000/dashboard_viabilidade.html
```

**Processo:**
1. Digite o número do ANVI (ex: 1, 2, 3...)
2. Clique em "Carregar Análise"
3. Veja o dashboard completo

### Via API (Para Integração)
```bash
curl "http://localhost:8000/api/dashboard_viabilidade.php?anvi_id=1"
```

---

## 📊 EXEMPLO DE RESPOSTA API

```json
{
  "anvi": {
    "id": 1,
    "numero": 1,
    "nome": "ANVI #1",
    "projeto": "Projeto ACME",
    "status": "Em Análise"
  },
  "financeiro": {
    "severidade": "warning",
    "compatibilidades": [
      {
        "item": "Orçamento vs Despesas",
        "status": "ATENÇÃO",
        "orcamento": 100000,
        "gasto": 85000,
        "percentual": 85,
        "mensagem": "Dentro do orçamento",
        "severidade": "warning"
      }
    ]
  },
  "planejamento": {
    "severidade": "success",
    "compatibilidades": [
      {
        "item": "Cronograma vs Progresso",
        "status": "OK",
        "tempo_decorrido_percentual": 50,
        "progresso_percentual": 55,
        "atraso_dias": -3,
        "mensagem": "ADIANTADO (-3 dias)",
        "severidade": "success"
      }
    ]
  },
  "qualidade": {
    "severidade": "success",
    "compatibilidades": [...]
  },
  "recursos": {
    "severidade": "success",
    "compatibilidades": [...]
  },
  "viabilidade": {
    "score_geral": 82.5,
    "scores_por_area": {
      "financeiro": 80,
      "planejamento": 100,
      "qualidade": 70,
      "recursos": 80
    },
    "status": "VIÁVEL",
    "recomendacao": "Projeto pode prosseguir normalmente"
  },
  "recomendacoes": [
    {
      "area": "Qualidade",
      "problema": "Taxa de Erros / Issues",
      "acao": "5 issue(s) crítico(s)",
      "prioridade": "MÉDIA"
    }
  ]
}
```

---

## 🎨 VISUAL DO DASHBOARD

```
┌─────────────────────────────────────────────────┐
│  🎯 Dashboard de Compatibilidade/Viabilidade   │
│  Digite o ANVI: [___] [Carregar Análise]       │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   📊 Score de Viabilidade Geral: 82.5          │
│   Status: VIÁVEL ✅                             │
│                                                  │
│   Financeiro: 80   Planejamento: 100            │
│   Qualidade: 70    Recursos: 80                 │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   💰 FINANCEIRO                                  │
│   ├─ Orçamento vs Despesas: OK (85%)            │
│   └─ Fluxo de Caixa: OK (positivo)              │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   📅 PLANEJAMENTO                               │
│   ├─ Cronograma: ADIANTADO (-3 dias)            │
│   └─ Tarefas: OK (2 de 10 vencidas)             │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   ✅ QUALIDADE                                  │
│   ├─ Issues: ATENÇÃO (5 críticas)               │
│   └─ Testes: OK (95% passaram)                  │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   👥 RECURSOS                                   │
│   ├─ Equipe: OK (4 membros)                     │
│   └─ Infraestrutura: OK (95% disponível)        │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│   ⚠️  RECOMENDAÇÕES DE AÇÃO                     │
│   • [ALTA] Qualidade: Resolver 5 issues críticas│
└─────────────────────────────────────────────────┘
```

---

## 📈 MÉTRICAS ANALISADAS

### 1. Financeiro
```
Orçamento            R$ 100.000,00
Gasto Real           R$ 85.000,00
Percentual Gasto     85%
Status               ✅ Dentro do orçamento

Meses com Deficit    0
Status Fluxo Caixa   ✅ Positivo
```

### 2. Planejamento
```
Tempo Decorrido      50%
Progresso Real       55%
Status Cronograma    ADIANTADO (-3 dias)

Tarefas Total        10
Tarefas Concluídas   8
Tarefas Vencidas     0
Status Tarefas       ✅ No prazo
```

### 3. Qualidade
```
Issues Total         10
Issues Abertos       5
Issues Críticos      2
Taxa de Testes       95% de sucesso
Status Qualidade     ⚠️ Atenção (2 críticos)
```

### 4. Recursos
```
Membros da Equipe    4
Recursos Ativos      19/20
Disponibilidade      95%
Status Recursos      ✅ Adequados
```

---

## 🎯 CASOS DE USO

### 1️⃣ Verificar Viabilidade do Projeto
```
Gerente de Projeto: "Este projeto é viável para prosseguir?"
→ Abre dashboard, digita ANVI, vê score 82 (VIÁVEL)
```

### 2️⃣ Identificar Gargalos
```
Stakeholder: "Onde estão os problemas?"
→ Dashboard mostra Qualidade com score 70 (ATENÇÃO)
→ 5 issues críticas precisam ser resolvidas
```

### 3️⃣ Monitorar Progresso
```
PMO: "Estamos atrasados?"
→ Cronograma vs Progresso: ADIANTADO (-3 dias)
→ Projeto está 3 dias adiantado
```

### 4️⃣ Analisar Saúde Financeira
```
CFO: "Estamos dentro do orçamento?"
→ Orçamento vs Despesas: 85% (OK)
→ Ainda há R$ 15.000 disponíveis
```

---

## 💡 EXEMPLO DE INTEGRAÇÃO

### Integração em Página Existente
```html
<!-- Em um dashboard executivo -->
<iframe 
    src="http://localhost:8000/dashboard_viabilidade.html" 
    width="100%" 
    height="1200"
    frameborder="0"
></iframe>
```

### Integração via JavaScript
```javascript
// Carregar dados do dashboard para um projeto específico
const anviId = 42;
fetch(`/api/dashboard_viabilidade.php?anvi_id=${anviId}`)
    .then(r => r.json())
    .then(data => {
        console.log('Score:', data.viabilidade.score_geral);
        console.log('Status:', data.viabilidade.status);
        console.log('Recomendações:', data.recomendacoes);
    });
```

---

## 🔧 CONFIGURAÇÃO

### Banco de Dados Necessário
O módulo usa as seguintes tabelas (se existirem):
- `anvis` - Projetos/ANVIs
- `projetos` - Informações do projeto
- `despesas` - Despesas realizadas
- `fluxo_caixa` - Fluxo de caixa mensal
- `tarefas` - Tarefas do projeto
- `anvis_etapas` - Etapas de análise
- `issues` - Issues/bugs encontrados
- `testes` - Testes realizados
- `anvis_equipe` - Membros da equipe
- `recursos_infraestrutura` - Recursos disponíveis

### Se as Tabelas Não Existirem
O módulo **funciona mesmo sem algumas tabelas**:
- Pula a análise daquela seção
- Não afeta o score geral
- Mostra o que conseguir obter

---

## 📊 INTERPRETANDO OS RESULTADOS

### Score de Viabilidade

| Score | Status | Significado |
|-------|--------|-------------|
| 80-100 | 🟢 VIÁVEL | Projeto pode prosseguir normalmente |
| 60-79 | 🟡 VIÁVEL COM RESSALVAS | Prosseguir, mas atentar para áreas em amarelo |
| 0-59 | 🔴 NÃO VIÁVEL | Requer correções antes de prosseguir |

### Severidade por Área

| Cor | Significado |
|-----|------------|
| 🟢 Verde | Tudo bem, sem problemas |
| 🟡 Amarelo | Atenção, acompanhar de perto |
| 🔴 Vermelho | Problema crítico, ação necessária |

---

## 🎯 RECOMENDAÇÕES

### Se Score = 🟢 Verde (80+)
✅ Projeto viável  
✅ Prosseguir com confiança  
✅ Continuar monitoramento regular  

### Se Score = 🟡 Amarelo (60-79)
⚠️ Viável, mas com ressalvas  
✓ Resolver itens em amarelo/vermelho  
✓ Aumentar frequência de monitoramento  
✓ Comunicar riscos ao stakeholder  

### Se Score = 🔴 Vermelho (<60)
❌ Não recomendado prosseguir  
✗ Identificar e resolver problemas críticos  
✗ Fazer novo diagnóstico antes de prosseguir  
✗ Considerar replanejar ou cancelar  

---

## 🚀 PRÓXIMOS PASSOS

1. **Agora:** Testar o dashboard
   - Abra: http://localhost:8000/dashboard_viabilidade.html
   - Digite um ANVI (1, 2, 3, etc)
   - Veja a análise completa

2. **Integração:** Adicionar ao seu painel executivo
   - Incorpore em página existente
   - Use API para dados em tempo real
   - Configure alertas automáticos

3. **Monitoramento:** Acompanhar regularmente
   - Semanal para projetos críticos
   - Mensal para projetos normais
   - Diário durante crises

---

## ❓ PERGUNTAS FREQUENTES

**P: Qual é a diferença entre os scores das áreas?**  
R: Cada área tem seu próprio score (0-100) baseado em análises específicas. O score geral é a média.

**P: Por que meu ANVI mostra "Viável com Ressalvas"?**  
R: Significa que há problemas em algumas áreas (amarelo/vermelho) que precisam de atenção, mas nada impede o prosseguimento.

**P: Como posso melhorar o score?**  
R: Siga as recomendações do dashboard. Resolva os itens em vermelho (críticos) primeiro, depois amarelo (atenção).

**P: O dashboard é em tempo real?**  
R: Sim, mostra dados conforme estão no banco de dados. Atualiza a cada carregamento.

**P: Posso exportar o relatório?**  
R: Faça um screenshot ou use a API para integrar em ferramentas de BI.

---

## 📚 DOCUMENTAÇÃO RELACIONADA

- [PHASE_1_DOCUMENTATION_INDEX.md](PHASE_1_DOCUMENTATION_INDEX.md) - Índice geral
- [DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md) - Deploy para produção

---

## 🎉 CONCLUSÃO

**Módulo 3 está pronto para:**
- ✅ Analisar viabilidade de projetos
- ✅ Identificar incompatibilidades
- ✅ Monitorar saúde do projeto
- ✅ Gerar recomendações de ação
- ✅ Tomar decisões informadas

**Acesse agora:** http://localhost:8000/dashboard_viabilidade.html

---

*Criado em: May 4, 2026*  
*Status: ✅ Pronto para Uso*
