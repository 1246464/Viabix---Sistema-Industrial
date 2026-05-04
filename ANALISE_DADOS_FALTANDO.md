# 📊 ANÁLISE: Dados Faltantes no Dashboard de Viabilidade

**Data:** May 4, 2026  
**Análise:** Levantamento de dados solicitados vs disponíveis no schema

---

## 🔍 RESULTADO DA ANÁLISE

### ✅ Dados QUE EXISTEM no Schema

| Campo | Tabela | Coluna | Tipo |
|-------|--------|--------|------|
| Status | projetos | status | VARCHAR(50) |
| Orçamento | projetos | orcamento | DECIMAL(14,2) |
| Progresso | projetos | progresso | DECIMAL(5,2) |
| Fase | projetos | fase | VARCHAR(50) |
| Observações | projetos | observacoes | TEXT |
| Dados Genéricos | projetos | dados | JSON |
| Dados Genéricos | anvis | dados | JSON |
| Criação | anvis | data_criacao | TIMESTAMP |
| Atualização | anvis | data_atualizacao | TIMESTAMP |

**Subtotal:** ~9 campos disponíveis

---

### ❌ Dados QUE FALTAM no Schema

| # | Campo | Categoria | Prioridade | Local Lógico |
|---|-------|-----------|-----------|--------------|
| 1 | Investimento total | ANVI | ALTA | anvis.dados |
| 2 | ROI esperado (%) | ANVI | ALTA | anvis.dados |
| 3 | Payback (meses) | ANVI | ALTA | anvis.dados |
| 4 | Duração planejada (meses) | ANVI | ALTA | anvis.dados |
| 5 | Recursos necessários (pessoas) | ANVI | MÉDIA | anvis.dados |
| 6 | Horas/semana necessárias | ANVI | MÉDIA | anvis.dados |
| 7 | Riscos identificados | ANVI | ALTA | Nova tabela |
| 8 | Custo real (atualizado) | Projeto | ALTA | projetos.dados |
| 9 | Timeline real (vs planejada) | Projeto | ALTA | projetos.dados |
| 10 | Recursos alocados (pessoas) | Projeto | MÉDIA | projetos.dados |
| 11 | Horas reais alocadas | Projeto | MÉDIA | projetos.dados |
| 12 | Riscos encontrados | Projeto | ALTA | Nova tabela |
| 13 | Etapas planejadas | Projeto | ALTA | Nova tabela |
| 14 | Etapas concluídas | Projeto | ALTA | Nova tabela |

**Subtotal:** 14 campos faltando

---

## 📈 CÁLCULOS FALTANDO

### Derivados (precisam apenas dos dados acima)
1. **ROI Real** = (Receita - Custo Real) / Custo Real × 100
2. **Variância Orçamentária** = (Custo Real - Orçado) / Orçado × 100
3. **Variância Timeline** = (Data Real - Data Planejada) em dias
4. **Taxa Progresso** = Etapas Concluídas / Etapas Planejadas × 100
5. **Score Viabilidade** = Média ponderada (Financeiro 25%, Planejamento 25%, Qualidade 25%, Recursos 25%)
6. **Payback Real** = Investimento / Lucro Mensal (em meses)

---

## 💡 SOLUÇÕES PROPOSTAS

### Opção 1: ⚡ RÁPIDA (Usar JSON)
- Armazenar dados faltando em colunas JSON
- **Vantagem:** Sem ALTER TABLE, implementação rápida
- **Desvantagem:** Menos estruturado, queries mais complexas
- **Tempo:** ~2 horas

```sql
-- Adicionar em anvis.dados
{
  "financeiro": {
    "investimento_total": 100000,
    "roi_esperado": 25,
    "payback_meses": 12,
    "duracao_meses": 36,
    "recursos": {
      "pessoas_necessarias": 5,
      "horas_semana": 40
    }
  },
  "riscos": [
    {"descricao": "Risco 1", "severidade": "alta", "probabilidade": 0.3},
    {"descricao": "Risco 2", "severidade": "média", "probabilidade": 0.1}
  ]
}
```

### Opção 2: 📊 ESTRUTURADA (Novas Tabelas)
- Criar tabelas específicas para riscos, etapas, alocações
- **Vantagem:** Bem estruturado, queries otimizadas, relatórios avançados
- **Desvantagem:** Requer ALTER TABLE, mais complexo
- **Tempo:** ~4 horas

```sql
-- Nova tabela: projeto_riscos
CREATE TABLE projeto_riscos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    projeto_id BIGINT NOT NULL,
    descricao TEXT,
    severidade ENUM('baixa', 'media', 'alta', 'critica'),
    probabilidade DECIMAL(3,2),  -- 0-1
    impacto DECIMAL(14,2),
    mitigacoes TEXT,
    status ENUM('novo', 'monitorado', 'resolvido'),
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
);

-- Nova tabela: projeto_etapas
CREATE TABLE projeto_etapas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    projeto_id BIGINT NOT NULL,
    numero INT,
    descricao TEXT,
    data_inicio_planejada DATE,
    data_fim_planejada DATE,
    data_inicio_real DATE,
    data_fim_real DATE,
    percentual_completo INT,
    responsavel_id BIGINT,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
);

-- Nova tabela: projeto_alocacoes
CREATE TABLE projeto_alocacoes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    projeto_id BIGINT NOT NULL,
    usuario_id BIGINT NOT NULL,
    papel VARCHAR(100),
    horas_planejadas DECIMAL(8,2),
    horas_reais DECIMAL(8,2),
    inicio_previsto DATE,
    inicio_real DATE,
    fim_previsto DATE,
    fim_real DATE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE
);
```

### Opção 3: 🎯 HÍBRIDA (Recomendada)
- JSON para dados financeiros/ROI do ANVI (menos mutável)
- Tabelas para riscos/etapas/alocações (mais dinâmico)
- **Vantagem:** Melhor de ambos os mundos
- **Desvantagem:** Implementação média
- **Tempo:** ~3 horas

---

## 🎯 RECOMENDAÇÃO

**Implemente OPÇÃO 3 (Híbrida):**

### Fase 1 (Hoje): Estrutura
```sql
-- Executar no BD
-- 1. Estender anvis.dados com financeiro (JSON)
-- 2. Estender projetos.dados com custos reais (JSON)
-- 3. Criar tabelas: projeto_riscos, projeto_etapas, projeto_alocacoes
```

### Fase 2 (Hoje): Dashboard
```php
// Atualizar api/dashboard_viabilidade.php
// 1. Extrair dados financeiros do ANVI (JSON)
// 2. Extrair custos reais do Projeto (JSON)
// 3. Consultar riscos (tabela)
// 4. Consultar etapas (tabela)
// 5. Calcular variâncias
// 6. Renderizar novo dashboard com ROI, Payback, etc
```

---

## 📋 EXEMPLO DO NOVO JSON (anvis.dados)

```json
{
  "financeiro": {
    "investimento_total": 100000,
    "roi_esperado_pct": 25,
    "payback_meses": 12,
    "duracao_meses": 36,
    "receita_anual_esperada": 50000,
    "economias_esperadas": 30000,
    "recursos_necessarios": {
      "pessoas": 5,
      "horas_semana": 40,
      "custo_pessoa_mes": 8000
    }
  },
  "riscos_identificados": [
    {
      "id": 1,
      "descricao": "Atraso de fornecedor",
      "severidade": "alta",
      "probabilidade": 0.4,
      "impacto": 50000,
      "mitigacao": "Diversificar fornecedores"
    }
  ]
}
```

---

## 📊 EXEMPLO DO NOVO JSON (projetos.dados)

```json
{
  "financeiro_realizado": {
    "custo_real": 95000,
    "gasto_ate_agora": 85000,
    "variancia_orcamentaria_pct": -5,
    "recursos_alocados": {
      "pessoas": 4,
      "horas_reais_por_semana": 38,
      "pessoas_info": [
        {
          "nome": "João Silva",
          "horas_reais": 38,
          "custo": 32000
        }
      ]
    }
  },
  "timeline_realizada": {
    "data_inicio_planejada": "2026-01-01",
    "data_fim_planejada": "2026-12-31",
    "data_inicio_real": "2026-01-05",
    "data_fim_real_estimada": "2027-01-15",
    "variancia_dias": 15,
    "percentual_tempo_decorrido_planejado": 30,
    "percentual_tempo_decorrido_real": 25
  },
  "riscos_encontrados": [
    {
      "id": 1,
      "descricao": "Equipe reduzida",
      "severidade": "media",
      "status": "em_andamento",
      "impacto_dias": 7
    }
  ]
}
```

---

## 🗂️ ARQUIVOS A CRIAR/MODIFICAR

### Novos Arquivos
1. `BD/schema_extensoes.sql` - Criar tabelas (riscos, etapas, alocações)
2. `api/projeto_riscos.php` - CRUD para riscos
3. `api/projeto_etapas.php` - CRUD para etapas
4. `api/projeto_alocacoes.php` - CRUD para alocações

### Modificar
1. `api/dashboard_viabilidade.php` - Adicionar ROI, Payback, Variâncias
2. `dashboard_viabilidade.html` - Nova seção com cálculos
3. `BD/viabix_saas_multitenant.sql` - Adicionar novas tabelas ao seed

---

## ⏱️ TIMELINE DE IMPLEMENTAÇÃO

| Tarefa | Tempo | Dependências |
|--------|-------|--------------|
| Criar schema de extensões | 20 min | Nenhuma |
| Atualizar dashboard API | 1 hora | Schema criado |
| Atualizar dashboard HTML | 45 min | API pronta |
| Testes | 30 min | Tudo acima |
| **TOTAL** | **2h 35min** | - |

---

## ✅ CHECKLIST

- [ ] Criar tabelas: projeto_riscos, projeto_etapas, projeto_alocacoes
- [ ] Estender anvis.dados com financeiro (investimento, ROI, payback)
- [ ] Estender projetos.dados com financeiro_realizado + timeline_realizada
- [ ] Criar API CRUD para gerenciar riscos/etapas/alocações
- [ ] Atualizar dashboard_viabilidade.php com cálculos
- [ ] Atualizar dashboard_viabilidade.html com novas seções
- [ ] Testar com dados reais
- [ ] Documentar nova estrutura

---

## 🤔 PRÓXIMO PASSO?

Quer que eu implemente **AGORA**?

Se sim, qual opção:
1. ⚡ **RÁPIDA** (JSON) - 2 horas
2. 📊 **ESTRUTURADA** (Tabelas) - 4 horas
3. 🎯 **HÍBRIDA** (Recomendada) - 3 horas

