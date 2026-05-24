-- ======================================================
-- VIABIX SAAS - MIGRACAO INCREMENTAL FASE 1
-- Objetivo: preparar o banco atual para multi-tenant e billing
-- sem quebrar o fluxo legado de autenticacao de imediato.
--
-- Premissas:
-- 1. O banco principal e viabix_db
-- 2. A base operacional existente pode ou nao conter projetos/lideres
-- 3. O login legado continua funcional nesta fase
-- 4. O tenant inicial sera um tenant legado unico para backfill
-- ======================================================

USE viabix_db;

-- ======================================================
-- HELPERS DE MIGRACAO
-- ======================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DELIMITER //
CREATE PROCEDURE sp_add_column_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_column_name VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND column_name = p_column_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table_name, '` ADD COLUMN `', p_column_name, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_add_index_if_missing;
DELIMITER //
CREATE PROCEDURE sp_add_index_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_index_name VARCHAR(64),
    IN p_index_sql TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.statistics
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND index_name = p_index_name
    ) THEN
        SET @sql = p_index_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS sp_add_fk_if_missing;
DELIMITER //
CREATE PROCEDURE sp_add_fk_if_missing(
    IN p_table_name VARCHAR(64),
    IN p_constraint_name VARCHAR(64),
    IN p_fk_sql TEXT
)
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
    ) AND NOT EXISTS (
        SELECT 1
        FROM information_schema.table_constraints
        WHERE table_schema = DATABASE()
          AND table_name = p_table_name
          AND constraint_name = p_constraint_name
    ) THEN
        SET @sql = p_fk_sql;
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

-- ======================================================
-- TABELAS NOVAS DE TENANCY E BILLING
-- ======================================================

CREATE TABLE IF NOT EXISTS plans (
    id CHAR(36) PRIMARY KEY,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    preco_mensal DECIMAL(10,2) NOT NULL DEFAULT 0,
    preco_anual DECIMAL(10,2) NOT NULL DEFAULT 0,
    limite_usuarios INT NULL,
    limite_anvis_mensal INT NULL,
    limite_projetos_ativos INT NULL,
    permite_modulo_anvi BOOLEAN NOT NULL DEFAULT TRUE,
    permite_modulo_projetos BOOLEAN NOT NULL DEFAULT TRUE,
    permite_exportacao BOOLEAN NOT NULL DEFAULT TRUE,
    permite_api BOOLEAN NOT NULL DEFAULT FALSE,
    permite_sso BOOLEAN NOT NULL DEFAULT FALSE,
    metadata JSON NULL,
    status ENUM('rascunho', 'ativo', 'arquivado') NOT NULL DEFAULT 'ativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plan_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenants (
    id CHAR(36) PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    nome_fantasia VARCHAR(150) NOT NULL,
    razao_social VARCHAR(180) NULL,
    cnpj VARCHAR(18) NULL,
    email_financeiro VARCHAR(160) NULL,
    telefone VARCHAR(30) NULL,
    status ENUM('trial', 'ativo', 'suspenso', 'inadimplente', 'cancelado') NOT NULL DEFAULT 'trial',
    timezone VARCHAR(50) NOT NULL DEFAULT 'America/Sao_Paulo',
    moeda CHAR(3) NOT NULL DEFAULT 'BRL',
    trial_ate DATETIME NULL,
    ativado_em DATETIME NULL,
    cancelado_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tenant_status (status),
    INDEX idx_tenant_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    id CHAR(36) PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    plan_id CHAR(36) NOT NULL,
    status ENUM('trial', 'ativa', 'inadimplente', 'suspensa', 'cancelada', 'expirada') NOT NULL DEFAULT 'trial',
    gateway VARCHAR(50) NOT NULL,
    gateway_customer_id VARCHAR(120) NULL,
    gateway_subscription_id VARCHAR(120) NULL,
    ciclo ENUM('mensal', 'anual') NOT NULL DEFAULT 'mensal',
    quantidade_usuarios_contratados INT NULL,
    valor_contratado DECIMAL(10,2) NOT NULL DEFAULT 0,
    trial_iniciado_em DATETIME NULL,
    trial_ate DATETIME NULL,
    inicio_vigencia DATETIME NULL,
    fim_vigencia DATETIME NULL,
    cancelar_ao_final BOOLEAN NOT NULL DEFAULT FALSE,
    cancelada_em DATETIME NULL,
    motivo_cancelamento VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_subscription_tenant (tenant_id),
    INDEX idx_subscription_plan (plan_id),
    INDEX idx_subscription_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    subscription_id CHAR(36) NOT NULL,
    tenant_id CHAR(36) NOT NULL,
    tipo_evento VARCHAR(80) NOT NULL,
    origem ENUM('sistema', 'gateway', 'admin', 'webhook') NOT NULL DEFAULT 'sistema',
    payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscription_event_subscription (subscription_id),
    INDEX idx_subscription_event_tenant (tenant_id),
    INDEX idx_subscription_event_tipo (tipo_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS invoices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    subscription_id CHAR(36) NOT NULL,
    gateway_invoice_id VARCHAR(120) NULL,
    numero VARCHAR(60) NULL,
    status ENUM('pendente', 'paga', 'vencida', 'cancelada', 'estornada') NOT NULL DEFAULT 'pendente',
    valor_total DECIMAL(10,2) NOT NULL,
    valor_pago DECIMAL(10,2) NOT NULL DEFAULT 0,
    moeda CHAR(3) NOT NULL DEFAULT 'BRL',
    url_cobranca VARCHAR(500) NULL,
    vencimento_em DATETIME NOT NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_invoice_tenant (tenant_id),
    INDEX idx_invoice_subscription (subscription_id),
    INDEX idx_invoice_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    invoice_id BIGINT NOT NULL,
    gateway_payment_id VARCHAR(120) NULL,
    metodo VARCHAR(40) NULL,
    status ENUM('pendente', 'confirmado', 'falhou', 'estornado') NOT NULL DEFAULT 'pendente',
    valor DECIMAL(10,2) NOT NULL,
    payload JSON NULL,
    pago_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_tenant (tenant_id),
    INDEX idx_payment_invoice (invoice_id),
    INDEX idx_payment_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS webhook_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    provider VARCHAR(50) NOT NULL,
    event_id VARCHAR(120) NOT NULL,
    event_type VARCHAR(120) NOT NULL,
    tenant_id CHAR(36) NULL,
    payload JSON NOT NULL,
    processado BOOLEAN NOT NULL DEFAULT FALSE,
    processado_em DATETIME NULL,
    erro_processamento TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_webhook_provider_event (provider, event_id),
    INDEX idx_webhook_tenant (tenant_id),
    INDEX idx_webhook_processado (processado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    tipo ENUM('texto', 'numero', 'booleano', 'json') NOT NULL DEFAULT 'texto',
    updated_by VARCHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_setting (tenant_id, chave),
    INDEX idx_tenant_setting_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS device_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    usuario_id VARCHAR(36) NOT NULL,
    device_fingerprint VARCHAR(255) NOT NULL,
    device_name VARCHAR(120) NULL,
    plataforma VARCHAR(50) NULL,
    versao_app VARCHAR(40) NULL,
    refresh_token_hash VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    ultimo_ping_em DATETIME NULL,
    expira_em DATETIME NULL,
    revogada_em DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_device_session_tenant (tenant_id),
    INDEX idx_device_session_usuario (usuario_id),
    INDEX idx_device_session_fingerprint (device_fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- GARANTIR TABELAS OPERACIONAIS DE PROJETOS
-- ======================================================

CREATE TABLE IF NOT EXISTS lideres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    departamento VARCHAR(50) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lider_nome (nome),
    INDEX idx_lider_email (email),
    INDEX idx_lider_departamento (departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anvi_id VARCHAR(50) NULL,
    cliente VARCHAR(200) NULL,
    nome VARCHAR(200) NULL,
    segmento VARCHAR(100) NULL,
    lider_id INT NULL,
    codigo VARCHAR(100) NULL,
    modelo VARCHAR(100) NULL,
    processo VARCHAR(100) NULL,
    fase VARCHAR(50) NULL,
    status VARCHAR(50) DEFAULT 'Em Andamento',
    progresso DECIMAL(5,2) NOT NULL DEFAULT 0,
    orcamento DECIMAL(14,2) NOT NULL DEFAULT 0,
    observacoes TEXT NULL,
    dados JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projeto_anvi (anvi_id),
    INDEX idx_projeto_cliente (cliente),
    INDEX idx_projeto_lider (lider_id),
    INDEX idx_projeto_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mudancas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    item_id INT NOT NULL,
    usuario_id VARCHAR(36) NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_data (data_hora),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- COLUNAS DE TENANT E METADADOS
-- ======================================================

CALL sp_add_column_if_missing('usuarios', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('usuarios', 'email', 'VARCHAR(160) NULL AFTER login');
CALL sp_add_column_if_missing('usuarios', 'email_verificado_em', 'DATETIME NULL AFTER ativo');
CALL sp_add_column_if_missing('usuarios', 'ultima_troca_senha_em', 'DATETIME NULL AFTER ultimo_acesso');

CALL sp_add_column_if_missing('anvis', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('anvis', 'projeto_id', 'INT NULL AFTER tenant_id');

CALL sp_add_column_if_missing('conflitos_edicao', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('logs_atividade', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('logs_atividade', 'entidade', 'VARCHAR(60) NULL AFTER acao');
CALL sp_add_column_if_missing('logs_atividade', 'entidade_id', 'VARCHAR(80) NULL AFTER entidade');
CALL sp_add_column_if_missing('anvis_historico', 'tenant_id', 'CHAR(36) NULL AFTER id');

CALL sp_add_column_if_missing('configuracoes', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('bancos_dados', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('bancos_dados', 'escopo', 'ENUM(\'global\', \'tenant\') NOT NULL DEFAULT \'global\' AFTER tenant_id');

CALL sp_add_column_if_missing('notificacoes', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('mudancas', 'tenant_id', 'CHAR(36) NULL AFTER id');

CALL sp_add_column_if_missing('lideres', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('projetos', 'tenant_id', 'CHAR(36) NULL AFTER id');
CALL sp_add_column_if_missing('projetos', 'criado_por', 'VARCHAR(36) NULL AFTER dados');
CALL sp_add_column_if_missing('projetos', 'atualizado_por', 'VARCHAR(36) NULL AFTER criado_por');

-- ======================================================
-- DADOS INICIAIS DE PLANO E TENANT LEGADO
-- ======================================================

INSERT IGNORE INTO plans (
    id, codigo, nome, descricao, preco_mensal, preco_anual,
    limite_usuarios, limite_anvis_mensal, limite_projetos_ativos,
    permite_modulo_anvi, permite_modulo_projetos, permite_exportacao, permite_api, permite_sso, status
) VALUES
(
    '00000000-0000-0000-0000-000000000101',
    'starter',
    'Starter',
    'Plano inicial para operacao reduzida',
    297.00,
    2970.00,
    3,
    30,
    20,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    FALSE,
    'ativo'
),
(
    '00000000-0000-0000-0000-000000000102',
    'pro',
    'Pro',
    'Plano comercial padrao do produto',
    697.00,
    6970.00,
    15,
    NULL,
    NULL,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    FALSE,
    'ativo'
);

INSERT IGNORE INTO tenants (
    id, slug, nome_fantasia, razao_social, status, timezone, moeda, trial_ate, ativado_em
) VALUES (
    '00000000-0000-0000-0000-000000000001',
    'legacy',
    'Tenant Legado Viabix',
    'Tenant Legado Viabix',
    'ativo',
    'America/Sao_Paulo',
    'BRL',
    DATE_ADD(NOW(), INTERVAL 14 DAY),
    NOW()
);

INSERT IGNORE INTO subscriptions (
    id, tenant_id, plan_id, status, gateway, ciclo, quantidade_usuarios_contratados,
    valor_contratado, trial_iniciado_em, trial_ate, inicio_vigencia, fim_vigencia
) VALUES (
    '00000000-0000-0000-0000-000000000201',
    '00000000-0000-0000-0000-000000000001',
    '00000000-0000-0000-0000-000000000102',
    'ativa',
    'manual',
    'mensal',
    15,
    697.00,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 14 DAY),
    NOW(),
    DATE_ADD(NOW(), INTERVAL 30 DAY)
);

-- ======================================================
-- BACKFILL DO TENANT LEGADO
-- ======================================================

UPDATE usuarios
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE usuarios
SET email = CONCAT(login, '@legacy.local')
WHERE (email IS NULL OR email = '')
  AND login IS NOT NULL
  AND login <> '';

UPDATE anvis
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE conflitos_edicao
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE logs_atividade
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE anvis_historico
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE notificacoes
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE mudancas
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE lideres
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE projetos
SET tenant_id = '00000000-0000-0000-0000-000000000001'
WHERE tenant_id IS NULL;

UPDATE bancos_dados
SET escopo = 'global'
WHERE escopo IS NULL OR escopo = '';

-- ======================================================
-- NORMALIZACAO MINIMA DOS DADOS DE PROJETOS
-- ======================================================

UPDATE projetos
SET nome = COALESCE(nome, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.projectName')), CONCAT('Projeto #', id))
WHERE nome IS NULL OR nome = '';

UPDATE projetos
SET cliente = COALESCE(cliente, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.cliente')))
WHERE cliente IS NULL OR cliente = '';

UPDATE projetos
SET codigo = COALESCE(codigo, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.codigo')))
WHERE codigo IS NULL OR codigo = '';

UPDATE projetos
SET segmento = COALESCE(segmento, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.segmento')))
WHERE segmento IS NULL OR segmento = '';

UPDATE projetos
SET status = COALESCE(status, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.status')), 'Em Andamento')
WHERE status IS NULL OR status = '';

UPDATE projetos
SET anvi_id = COALESCE(anvi_id, JSON_UNQUOTE(JSON_EXTRACT(dados, '$.anviId')))
WHERE anvi_id IS NULL OR anvi_id = '';

-- ======================================================
-- INDICES E RESTRICOES
-- ======================================================

CALL sp_add_index_if_missing(
    'usuarios',
    'idx_usuario_tenant',
    'CREATE INDEX idx_usuario_tenant ON usuarios (tenant_id)'
);

CALL sp_add_index_if_missing(
    'usuarios',
    'uk_usuario_tenant_email',
    'CREATE UNIQUE INDEX uk_usuario_tenant_email ON usuarios (tenant_id, email)'
);

CALL sp_add_index_if_missing(
    'anvis',
    'idx_anvi_tenant',
    'CREATE INDEX idx_anvi_tenant ON anvis (tenant_id)'
);

CALL sp_add_index_if_missing(
    'anvis',
    'uk_anvi_tenant_numero_revisao',
    'CREATE UNIQUE INDEX uk_anvi_tenant_numero_revisao ON anvis (tenant_id, numero, revisao)'
);

CALL sp_add_index_if_missing(
    'projetos',
    'idx_projeto_tenant',
    'CREATE INDEX idx_projeto_tenant ON projetos (tenant_id)'
);

CALL sp_add_index_if_missing(
    'lideres',
    'idx_lider_tenant',
    'CREATE INDEX idx_lider_tenant ON lideres (tenant_id)'
);

CALL sp_add_index_if_missing(
    'logs_atividade',
    'idx_log_tenant',
    'CREATE INDEX idx_log_tenant ON logs_atividade (tenant_id)'
);

CALL sp_add_index_if_missing(
    'notificacoes',
    'idx_notificacao_tenant',
    'CREATE INDEX idx_notificacao_tenant ON notificacoes (tenant_id)'
);

CALL sp_add_index_if_missing(
    'mudancas',
    'idx_mudanca_tenant',
    'CREATE INDEX idx_mudanca_tenant ON mudancas (tenant_id)'
);

CALL sp_add_index_if_missing(
    'conflitos_edicao',
    'idx_conflito_tenant',
    'CREATE INDEX idx_conflito_tenant ON conflitos_edicao (tenant_id)'
);

CALL sp_add_index_if_missing(
    'anvis_historico',
    'idx_historico_tenant',
    'CREATE INDEX idx_historico_tenant ON anvis_historico (tenant_id)'
);

-- ======================================================
-- FOREIGN KEYS
-- ======================================================

CALL sp_add_fk_if_missing(
    'subscriptions',
    'fk_subscriptions_tenant',
    'ALTER TABLE subscriptions ADD CONSTRAINT fk_subscriptions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'subscriptions',
    'fk_subscriptions_plan',
    'ALTER TABLE subscriptions ADD CONSTRAINT fk_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT'
);

CALL sp_add_fk_if_missing(
    'subscription_events',
    'fk_subscription_events_subscription',
    'ALTER TABLE subscription_events ADD CONSTRAINT fk_subscription_events_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'subscription_events',
    'fk_subscription_events_tenant',
    'ALTER TABLE subscription_events ADD CONSTRAINT fk_subscription_events_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'invoices',
    'fk_invoices_tenant',
    'ALTER TABLE invoices ADD CONSTRAINT fk_invoices_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'invoices',
    'fk_invoices_subscription',
    'ALTER TABLE invoices ADD CONSTRAINT fk_invoices_subscription FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'payments',
    'fk_payments_tenant',
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'payments',
    'fk_payments_invoice',
    'ALTER TABLE payments ADD CONSTRAINT fk_payments_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'webhook_events',
    'fk_webhook_events_tenant',
    'ALTER TABLE webhook_events ADD CONSTRAINT fk_webhook_events_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'tenant_settings',
    'fk_tenant_settings_tenant',
    'ALTER TABLE tenant_settings ADD CONSTRAINT fk_tenant_settings_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'tenant_settings',
    'fk_tenant_settings_updated_by',
    'ALTER TABLE tenant_settings ADD CONSTRAINT fk_tenant_settings_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'device_sessions',
    'fk_device_sessions_tenant',
    'ALTER TABLE device_sessions ADD CONSTRAINT fk_device_sessions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'device_sessions',
    'fk_device_sessions_usuario',
    'ALTER TABLE device_sessions ADD CONSTRAINT fk_device_sessions_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'usuarios',
    'fk_usuarios_tenant',
    'ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'anvis',
    'fk_anvis_tenant',
    'ALTER TABLE anvis ADD CONSTRAINT fk_anvis_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'conflitos_edicao',
    'fk_conflitos_tenant',
    'ALTER TABLE conflitos_edicao ADD CONSTRAINT fk_conflitos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'logs_atividade',
    'fk_logs_tenant',
    'ALTER TABLE logs_atividade ADD CONSTRAINT fk_logs_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'anvis_historico',
    'fk_anvis_historico_tenant',
    'ALTER TABLE anvis_historico ADD CONSTRAINT fk_anvis_historico_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'notificacoes',
    'fk_notificacoes_tenant',
    'ALTER TABLE notificacoes ADD CONSTRAINT fk_notificacoes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'mudancas',
    'fk_mudancas_tenant',
    'ALTER TABLE mudancas ADD CONSTRAINT fk_mudancas_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'lideres',
    'fk_lideres_tenant',
    'ALTER TABLE lideres ADD CONSTRAINT fk_lideres_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'projetos',
    'fk_projetos_tenant',
    'ALTER TABLE projetos ADD CONSTRAINT fk_projetos_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE'
);

CALL sp_add_fk_if_missing(
    'projetos',
    'fk_projetos_lider',
    'ALTER TABLE projetos ADD CONSTRAINT fk_projetos_lider FOREIGN KEY (lider_id) REFERENCES lideres(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'projetos',
    'fk_projetos_criado_por',
    'ALTER TABLE projetos ADD CONSTRAINT fk_projetos_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'projetos',
    'fk_projetos_atualizado_por',
    'ALTER TABLE projetos ADD CONSTRAINT fk_projetos_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'projetos',
    'fk_projetos_anvi',
    'ALTER TABLE projetos ADD CONSTRAINT fk_projetos_anvi FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE SET NULL'
);

CALL sp_add_fk_if_missing(
    'anvis',
    'fk_anvis_projeto_id',
    'ALTER TABLE anvis ADD CONSTRAINT fk_anvis_projeto_id FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL'
);

-- ======================================================
-- TORNAR TENANT OBRIGATORIO NAS TABELAS OPERACIONAIS
-- ======================================================

ALTER TABLE usuarios MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE anvis MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE conflitos_edicao MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE logs_atividade MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE anvis_historico MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE notificacoes MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE mudancas MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE lideres MODIFY COLUMN tenant_id CHAR(36) NOT NULL;
ALTER TABLE projetos MODIFY COLUMN tenant_id CHAR(36) NOT NULL;

-- configuracoes e bancos_dados continuam podendo ser globais

-- ======================================================
-- REGISTRO DA MIGRACAO EM CONFIGURACOES
-- ======================================================

INSERT INTO configuracoes (tenant_id, chave, valor, tipo, descricao)
SELECT NULL, 'saas_migracao_fase1', 'aplicada', 'texto', 'Marca a migracao inicial para tenancy e billing'
WHERE NOT EXISTS (
    SELECT 1 FROM configuracoes WHERE chave = 'saas_migracao_fase1' AND tenant_id IS NULL
);

-- ======================================================
-- LIMPEZA DOS HELPERS
-- ======================================================

DROP PROCEDURE IF EXISTS sp_add_column_if_missing;
DROP PROCEDURE IF EXISTS sp_add_index_if_missing;
DROP PROCEDURE IF EXISTS sp_add_fk_if_missing;