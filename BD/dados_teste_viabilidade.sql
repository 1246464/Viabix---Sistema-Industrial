-- ======================================================
-- DADOS DE TESTE: Extensões de Viabilidade
-- Propósito: Popular tabelas com dados de exemplo
-- Data: May 4, 2026
-- ======================================================

-- ======================================================
-- INSERIR DADOS DE TESTE EM ANVIS (com dados financeiros)
-- ======================================================

-- Nota: Substituir 'admin' pelo seu tenant_id real
-- Nota: Substituir projeto_id se necessário

UPDATE anvis 
SET dados_financeiros = JSON_OBJECT(
    'investimento_total', 100000,
    'roi_esperado_pct', 25,
    'payback_meses', 12,
    'duracao_meses', 36,
    'recursos_necessarios', JSON_OBJECT(
        'pessoas', 5,
        'horas_semana', 40
    ),
    'riscos_identificados', JSON_ARRAY(
        JSON_OBJECT(
            'descricao', 'Mudanças de requisitos',
            'severidade', 'alta',
            'probabilidade', 0.4
        ),
        JSON_OBJECT(
            'descricao', 'Indisponibilidade de recursos',
            'severidade', 'media',
            'probabilidade', 0.2
        )
    )
)
WHERE id IN (SELECT id FROM anvis LIMIT 1);

-- ======================================================
-- INSERIR DADOS DE TESTE EM PROJETOS (com dados realizados)
-- ======================================================

UPDATE projetos
SET dados_financeiros_reais = JSON_OBJECT(
    'custo_real', 85000,
    'gasto_ate_agora', 85000,
    'variancia_orcamentaria_pct', -15,
    'data_fim_estimada', DATE_ADD(NOW(), INTERVAL 2 MONTH),
    'recursos_alocados', JSON_OBJECT(
        'pessoas', 4,
        'horas_reais_por_semana', 38
    )
)
WHERE id = (SELECT projeto_id FROM anvis WHERE id = (SELECT id FROM anvis LIMIT 1) LIMIT 1);

-- ======================================================
-- INSERIR RISCOS DO PROJETO
-- ======================================================

INSERT IGNORE INTO projeto_riscos (
    tenant_id, projeto_id, descricao, severidade, 
    probabilidade, impacto_financeiro, mitigacoes, status
)
SELECT 
    a.tenant_id, 
    p.id,
    'Atraso de fornecedor crítico',
    'alta',
    0.3,
    25000,
    'Estabelecer contratos com penalidades e ter fornecedor alternativo',
    'monitorado'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_riscos (
    tenant_id, projeto_id, descricao, severidade, 
    probabilidade, impacto_financeiro, mitigacoes, status
)
SELECT 
    a.tenant_id, 
    p.id,
    'Indisponibilidade de especialista-chave',
    'media',
    0.2,
    15000,
    'Documentar conhecimento e ter backup de recursos',
    'novo'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_riscos (
    tenant_id, projeto_id, descricao, severidade, 
    probabilidade, impacto_financeiro, mitigacoes, status
)
SELECT 
    a.tenant_id, 
    p.id,
    'Mudanças de requisitos',
    'critica',
    0.4,
    40000,
    'Implementar processo rigoroso de change control',
    'novo'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

-- ======================================================
-- INSERIR ETAPAS DO PROJETO
-- ======================================================

INSERT IGNORE INTO projeto_etapas (
    tenant_id, projeto_id, numero, descricao, 
    data_inicio_planejada, data_fim_planejada, 
    percentual_completo, status
)
SELECT 
    a.tenant_id,
    p.id,
    1,
    'Levantamento de Requisitos',
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    DATE_SUB(CURDATE(), INTERVAL 25 DAY),
    100,
    'concluida'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_etapas (
    tenant_id, projeto_id, numero, descricao, 
    data_inicio_planejada, data_fim_planejada,
    data_inicio_real,
    percentual_completo, status
)
SELECT 
    a.tenant_id,
    p.id,
    2,
    'Design da Arquitetura',
    DATE_SUB(CURDATE(), INTERVAL 20 DAY),
    DATE_SUB(CURDATE(), INTERVAL 10 DAY),
    DATE_SUB(CURDATE(), INTERVAL 20 DAY),
    75,
    'em_andamento'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_etapas (
    tenant_id, projeto_id, numero, descricao, 
    data_inicio_planejada, data_fim_planejada,
    percentual_completo, status
)
SELECT 
    a.tenant_id,
    p.id,
    3,
    'Desenvolvimento',
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    0,
    'planejada'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_etapas (
    tenant_id, projeto_id, numero, descricao, 
    data_inicio_planejada, data_fim_planejada,
    percentual_completo, status
)
SELECT 
    a.tenant_id,
    p.id,
    4,
    'Testes',
    DATE_ADD(CURDATE(), INTERVAL 30 DAY),
    DATE_ADD(CURDATE(), INTERVAL 45 DAY),
    0,
    'planejada'
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

-- ======================================================
-- INSERIR ALOCAÇÕES DE RECURSOS
-- ======================================================

INSERT IGNORE INTO projeto_alocacoes (
    tenant_id, projeto_id, usuario_id, papel,
    horas_planejadas, horas_reais,
    custo_hora_planejado, custo_hora_real,
    data_inicio_prevista, data_inicio_real,
    data_fim_prevista, percentual_utilizacao
)
SELECT 
    a.tenant_id,
    p.id,
    u.id,
    'Desenvolvedor Sênior',
    40,
    38,
    150,
    155,
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    DATE_SUB(CURDATE(), INTERVAL 30 DAY),
    DATE_ADD(CURDATE(), INTERVAL 60 DAY),
    95
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
LEFT JOIN usuarios u ON u.ativo = 1
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

INSERT IGNORE INTO projeto_alocacoes (
    tenant_id, projeto_id, usuario_id, papel,
    horas_planejadas, horas_reais,
    custo_hora_planejado, custo_hora_real,
    data_inicio_prevista, data_inicio_real,
    data_fim_prevista, percentual_utilizacao
)
SELECT 
    a.tenant_id,
    p.id,
    u.id,
    'QA Engineer',
    20,
    15,
    80,
    85,
    CURDATE(),
    CURDATE(),
    DATE_ADD(CURDATE(), INTERVAL 45 DAY),
    75
FROM anvis a
LEFT JOIN projetos p ON a.projeto_id = p.id
LEFT JOIN usuarios u ON u.ativo = 1 AND u.id != (
    SELECT usuario_id FROM projeto_alocacoes 
    WHERE projeto_id = p.id 
    LIMIT 1
)
WHERE a.id = (SELECT id FROM anvis LIMIT 1)
LIMIT 1;

-- ======================================================
-- FIM DOS DADOS DE TESTE
-- ======================================================

-- Verificação dos dados inseridos
SELECT 'Dados de Teste Inseridos com Sucesso!' as status;
SELECT COUNT(*) as total_riscos FROM projeto_riscos;
SELECT COUNT(*) as total_etapas FROM projeto_etapas;
SELECT COUNT(*) as total_alocacoes FROM projeto_alocacoes;
