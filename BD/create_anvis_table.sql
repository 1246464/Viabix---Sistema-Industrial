-- Create anvis table if it doesn't exist
CREATE TABLE IF NOT EXISTS anvis (
    id VARCHAR(50) PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    projeto_id BIGINT NULL,
    numero VARCHAR(50) NOT NULL,
    revisao VARCHAR(10) NOT NULL,
    cliente VARCHAR(200) NULL,
    projeto VARCHAR(200) NULL,
    produto TEXT NULL,
    volume_mensal INT NOT NULL DEFAULT 1000,
    data_anvi DATE NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'em-andamento',
    versao INT NOT NULL DEFAULT 1,
    bloqueado_por CHAR(36) NULL,
    bloqueado_em DATETIME NULL,
    hash_conteudo VARCHAR(64) NULL,
    dados JSON NOT NULL,
    dados_financeiros JSON NULL,
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_anvi_projeto_id (projeto_id),
    INDEX idx_anvi_cliente (cliente),
    INDEX idx_anvi_status (status),
    INDEX idx_anvi_data (data_anvi),
    INDEX idx_anvi_tenant (tenant_id),
    FULLTEXT INDEX idx_anvi_busca (numero, cliente, projeto, produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create anvis_historico table if it doesn't exist
CREATE TABLE IF NOT EXISTS anvis_historico (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    anvi_id VARCHAR(50) NOT NULL,
    anvi_numero VARCHAR(50) NOT NULL,
    anvi_revisao VARCHAR(10) NOT NULL,
    dados JSON NOT NULL,
    usuario_id CHAR(36) NULL,
    acao ENUM('criacao', 'atualizacao', 'exclusao') NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_historico_tenant (tenant_id),
    INDEX idx_historico_anvi (anvi_id),
    INDEX idx_historico_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
