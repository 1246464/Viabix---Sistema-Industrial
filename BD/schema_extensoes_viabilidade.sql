-- ======================================================
-- Extensões de Schema para Dashboard de Viabilidade
-- Objetivo: Adicionar dados financeiros, riscos, etapas, alocações
-- Data: May 4, 2026
-- ======================================================

-- ======================================================
-- TABELA 1: Riscos do Projeto
-- ======================================================
CREATE TABLE IF NOT EXISTS projeto_riscos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    projeto_id BIGINT NOT NULL,
    descricao TEXT NOT NULL,
    severidade ENUM('baixa', 'media', 'alta', 'critica') NOT NULL DEFAULT 'media',
    probabilidade DECIMAL(3, 2) NOT NULL DEFAULT 0.5 COMMENT '0-1 scale',
    impacto_financeiro DECIMAL(14, 2) COMMENT 'Impacto em R$',
    exposicao DECIMAL(14, 2) GENERATED ALWAYS AS (probabilidade * COALESCE(impacto_financeiro, 0)) STORED,
    mitigacoes TEXT,
    status ENUM('novo', 'monitorado', 'resolvido', 'materializado') NOT NULL DEFAULT 'novo',
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_projeto_risco (projeto_id),
    INDEX idx_tenant_risco (tenant_id),
    INDEX idx_severidade (severidade),
    INDEX idx_status_risco (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA 2: Etapas do Projeto
-- ======================================================
CREATE TABLE IF NOT EXISTS projeto_etapas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    projeto_id BIGINT NOT NULL,
    numero INT NOT NULL,
    descricao TEXT NOT NULL,
    data_inicio_planejada DATE NOT NULL,
    data_fim_planejada DATE NOT NULL,
    data_inicio_real DATE,
    data_fim_real DATE,
    percentual_completo INT NOT NULL DEFAULT 0 COMMENT 'Percentual de conclusão (0-100)',
    responsavel_id BIGINT,
    status ENUM('planejada', 'em_andamento', 'concluida', 'cancelada') NOT NULL DEFAULT 'planejada',
    observacoes TEXT,
    
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    FOREIGN KEY (responsavel_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_projeto_etapa (projeto_id),
    INDEX idx_tenant_etapa (tenant_id),
    INDEX idx_status_etapa (status),
    UNIQUE KEY uk_projeto_numero (projeto_id, numero)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA 3: Alocação de Recursos (Pessoas)
-- ======================================================
CREATE TABLE IF NOT EXISTS projeto_alocacoes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    projeto_id BIGINT NOT NULL,
    usuario_id CHAR(36) NOT NULL,
    papel VARCHAR(100) NOT NULL COMMENT 'Ex: Developer, PM, QA',
    horas_planejadas DECIMAL(8, 2) NOT NULL COMMENT 'Horas/semana planejadas',
    horas_reais DECIMAL(8, 2) COMMENT 'Horas/semana reais',
    custo_hora_planejado DECIMAL(10, 2) COMMENT 'Custo hora planejado (R$)',
    custo_hora_real DECIMAL(10, 2) COMMENT 'Custo hora real (R$)',
    data_inicio_prevista DATE NOT NULL,
    data_inicio_real DATE,
    data_fim_prevista DATE NOT NULL,
    data_fim_real DATE,
    percentual_utilizacao INT COMMENT 'Percentual utilizado vs planejado',
    
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_projeto_alocacao (projeto_id),
    INDEX idx_tenant_alocacao (tenant_id),
    INDEX idx_usuario_alocacao (usuario_id),
    UNIQUE KEY uk_projeto_usuario (projeto_id, usuario_id, data_inicio_prevista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA 4: Histórico de Alterações em Custos (Auditoria)
-- ======================================================
CREATE TABLE IF NOT EXISTS projeto_historico_custos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    projeto_id BIGINT NOT NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_alteracao ENUM('atualizacao_orcamento', 'atualizacao_custo_real', 'atualizacao_alocacao') NOT NULL,
    orcamento_anterior DECIMAL(14, 2),
    orcamento_novo DECIMAL(14, 2),
    custo_real_anterior DECIMAL(14, 2),
    custo_real_novo DECIMAL(14, 2),
    usuario_id CHAR(36) NOT NULL,
    motivo TEXT,
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    
    INDEX idx_projeto_historico (projeto_id),
    INDEX idx_tenant_historico (tenant_id),
    INDEX idx_data_historico (data_atualizacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- ESTENDER TABELA: anvis
-- Adicionar coluna para dados financeiros (JSON)
-- ======================================================
ALTER TABLE anvis 
ADD COLUMN IF NOT EXISTS dados_financeiros JSON DEFAULT NULL 
COMMENT 'Dados financeiros: investimento, ROI, payback, riscos esperados'
AFTER dados;

-- ======================================================
-- ESTENDER TABELA: projetos
-- Adicionar coluna para dados financeiros realizados (JSON)
-- ======================================================
ALTER TABLE projetos
ADD COLUMN IF NOT EXISTS dados_financeiros_reais JSON DEFAULT NULL 
COMMENT 'Dados realizados: custo real, recursos alocados, timeline real'
AFTER dados;

-- ======================================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ======================================================
ALTER TABLE projeto_riscos ADD INDEX IF NOT EXISTS idx_exposicao (exposicao DESC);
ALTER TABLE projeto_etapas ADD INDEX IF NOT EXISTS idx_datas (data_inicio_planejada, data_fim_planejada);
ALTER TABLE projeto_alocacoes ADD INDEX IF NOT EXISTS idx_datas_alocacao (data_inicio_prevista, data_fim_prevista);

-- ======================================================
-- VIEWS ÚTEIS PARA DASHBOARD
-- ======================================================

-- View: Resumo de Riscos por Projeto
CREATE OR REPLACE VIEW v_projeto_resumo_riscos AS
SELECT 
    tenant_id,
    projeto_id,
    COUNT(*) as total_riscos,
    SUM(CASE WHEN severidade = 'critica' THEN 1 ELSE 0 END) as riscos_criticos,
    SUM(CASE WHEN severidade = 'alta' THEN 1 ELSE 0 END) as riscos_altos,
    SUM(CASE WHEN severidade = 'media' THEN 1 ELSE 0 END) as riscos_medios,
    SUM(CASE WHEN severidade = 'baixa' THEN 1 ELSE 0 END) as riscos_baixos,
    SUM(exposicao) as exposicao_total,
    AVG(probabilidade) as probabilidade_media
FROM projeto_riscos
WHERE status != 'resolvido'
GROUP BY tenant_id, projeto_id;

-- View: Progresso de Etapas
CREATE OR REPLACE VIEW v_projeto_progresso_etapas AS
SELECT 
    tenant_id,
    projeto_id,
    COUNT(*) as total_etapas,
    SUM(CASE WHEN status = 'concluida' THEN 1 ELSE 0 END) as etapas_concluidas,
    SUM(CASE WHEN status = 'em_andamento' THEN 1 ELSE 0 END) as etapas_em_andamento,
    SUM(CASE WHEN status = 'planejada' THEN 1 ELSE 0 END) as etapas_planejadas,
    ROUND(AVG(percentual_completo), 2) as progresso_medio,
    MIN(data_fim_planejada) as data_fim_mais_proxima
FROM projeto_etapas
GROUP BY tenant_id, projeto_id;

-- View: Alocação de Recursos
CREATE OR REPLACE VIEW v_projeto_alocacao_resumo AS
SELECT 
    tenant_id,
    projeto_id,
    COUNT(DISTINCT usuario_id) as total_pessoas,
    SUM(horas_planejadas) as horas_planejadas_total,
    SUM(COALESCE(horas_reais, 0)) as horas_reais_total,
    SUM(horas_planejadas * custo_hora_planejado) as custo_recurso_planejado,
    SUM(COALESCE(horas_reais, 0) * custo_hora_real) as custo_recurso_real
FROM projeto_alocacoes
WHERE data_fim_prevista >= CURDATE()
GROUP BY tenant_id, projeto_id;

-- ======================================================
-- FIM DO SCHEMA
-- ======================================================
