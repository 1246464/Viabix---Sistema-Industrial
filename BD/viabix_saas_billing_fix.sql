-- ======================================================
-- VIABIX SAAS - TABELAS DE BILLING (FIX)
-- ======================================================

USE viabix_db;

-- Garantir que as tabelas básicas existem primeiro
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
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id) ON DELETE RESTRICT,
    INDEX idx_subscription_tenant (tenant_id),
    INDEX idx_subscription_plan (plan_id),
    INDEX idx_subscription_status (status),
    INDEX idx_subscription_periodo (inicio_vigencia, fim_vigencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscription_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    subscription_id CHAR(36) NOT NULL,
    tenant_id CHAR(36) NOT NULL,
    tipo_evento VARCHAR(80) NOT NULL,
    origem ENUM('sistema', 'gateway', 'admin', 'webhook') NOT NULL DEFAULT 'sistema',
    payload JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
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
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    INDEX idx_invoice_tenant (tenant_id),
    INDEX idx_invoice_subscription (subscription_id),
    INDEX idx_invoice_status (status),
    INDEX idx_invoice_due_date (vencimento_em)
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
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
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
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE SET NULL,
    INDEX idx_webhook_tenant (tenant_id),
    INDEX idx_webhook_processado (processado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_settings (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    tipo ENUM('texto', 'numero', 'booleano', 'json') NOT NULL DEFAULT 'texto',
    updated_by CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tenant_setting (tenant_id, chave),
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_setting_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS device_sessions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    usuario_id CHAR(36) NOT NULL,
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

-- Inserir plano padrão se não existir
INSERT IGNORE INTO plans (id, codigo, nome, preco_mensal, permite_modulo_anvi, permite_modulo_projetos, permite_exportacao, status)
VALUES (
    'plan-00000000-0000-0000-0000-000000000001',
    'starter',
    'Plano Starter',
    99.00,
    TRUE,
    TRUE,
    TRUE,
    'ativo'
);

-- Inserir tenant padrão se não existir
INSERT IGNORE INTO tenants (id, slug, nome_fantasia, status, timezone)
VALUES (
    'tenant-00000000-0000-0000-0000-000000000001',
    'admin',
    'Administrador',
    'ativo',
    'America/Sao_Paulo'
);

-- Verificar criação
SELECT 'Tables created successfully' as status;
