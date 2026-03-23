-- ======================================================
-- VIABIX SAAS - MODELO MULTI-TENANT COM ASSINATURAS
-- Alvo: evolução do banco unificado viabix_db
-- Banco: MySQL 8+ / MariaDB 10.6+
-- ======================================================

CREATE DATABASE IF NOT EXISTS viabix_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE viabix_db;

-- ======================================================
-- TABELAS DE ASSINATURA E TENANCY
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

-- ======================================================
-- TABELAS DO DOMÍNIO VIABIX EM MODO MULTI-TENANT
-- ======================================================

CREATE TABLE IF NOT EXISTS usuarios (
    id CHAR(36) PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    login VARCHAR(100) NOT NULL,
    email VARCHAR(160) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('owner', 'admin', 'usuario', 'visitante', 'financeiro', 'suporte') NOT NULL DEFAULT 'usuario',
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    email_verificado_em DATETIME NULL,
    ultimo_acesso DATETIME NULL,
    ultima_troca_senha_em DATETIME NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_usuario_tenant_login (tenant_id, login),
    UNIQUE KEY uk_usuario_tenant_email (tenant_id, email),
    INDEX idx_usuario_nivel (nivel),
    INDEX idx_usuario_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tenant_settings
ADD CONSTRAINT fk_tenant_settings_updated_by
FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL;

ALTER TABLE device_sessions
ADD CONSTRAINT fk_device_session_tenant
FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_device_session_usuario
FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS lideres (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    departamento VARCHAR(50) NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY uk_lider_tenant_email (tenant_id, email),
    INDEX idx_lider_nome (nome),
    INDEX idx_lider_departamento (departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS projetos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    anvi_id VARCHAR(50) NULL,
    cliente VARCHAR(200) NULL,
    nome VARCHAR(200) NOT NULL,
    segmento VARCHAR(100) NULL,
    lider_id BIGINT NULL,
    codigo VARCHAR(100) NULL,
    modelo VARCHAR(100) NULL,
    processo VARCHAR(100) NULL,
    fase VARCHAR(50) NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'planejamento',
    progresso DECIMAL(5,2) NOT NULL DEFAULT 0,
    orcamento DECIMAL(14,2) NOT NULL DEFAULT 0,
    observacoes TEXT NULL,
    dados JSON NOT NULL,
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (lider_id) REFERENCES lideres(id) ON DELETE SET NULL,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uk_projeto_tenant_codigo (tenant_id, codigo),
    INDEX idx_projeto_anvi (anvi_id),
    INDEX idx_projeto_cliente (cliente),
    INDEX idx_projeto_status (status),
    INDEX idx_projeto_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    criado_por CHAR(36) NULL,
    atualizado_por CHAR(36) NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (bloqueado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL,
    UNIQUE KEY uk_anvi_tenant_numero_revisao (tenant_id, numero, revisao),
    INDEX idx_anvi_projeto_id (projeto_id),
    INDEX idx_anvi_cliente (cliente),
    INDEX idx_anvi_status (status),
    INDEX idx_anvi_data (data_anvi),
    INDEX idx_anvi_tenant (tenant_id),
    FULLTEXT INDEX idx_anvi_busca (numero, cliente, projeto, produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE projetos
ADD CONSTRAINT fk_projeto_anvi
FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS conflitos_edicao (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    anvi_id VARCHAR(50) NOT NULL,
    usuario_id CHAR(36) NOT NULL,
    versao_usuario INT NOT NULL,
    versao_banco INT NOT NULL,
    dados_usuario JSON NULL,
    dados_banco JSON NULL,
    resolvido BOOLEAN NOT NULL DEFAULT FALSE,
    data_conflito TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_conflito_tenant (tenant_id),
    INDEX idx_conflito_anvi (anvi_id),
    INDEX idx_conflito_resolvido (resolvido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_atividade (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    usuario_id CHAR(36) NULL,
    acao VARCHAR(80) NOT NULL,
    entidade VARCHAR(60) NULL,
    entidade_id VARCHAR(80) NULL,
    detalhes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_log_tenant (tenant_id),
    INDEX idx_log_usuario (usuario_id),
    INDEX idx_log_acao (acao),
    INDEX idx_log_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_historico_tenant (tenant_id),
    INDEX idx_historico_anvi (anvi_id),
    INDEX idx_historico_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS configuracoes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    tipo ENUM('texto', 'numero', 'booleano', 'json') NOT NULL DEFAULT 'texto',
    descricao TEXT NULL,
    atualizado_por CHAR(36) NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    UNIQUE KEY uk_configuracao_escopo (tenant_id, chave),
    INDEX idx_configuracao_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bancos_dados (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NULL,
    escopo ENUM('global', 'tenant') NOT NULL DEFAULT 'global',
    tipo ENUM(
        'materia_prima',
        'insumos',
        'componentes',
        'recursos',
        'ferramental',
        'materiais_ferramental',
        'embalagem',
        'normas',
        'mao_obra',
        'custos_indiretos',
        'classificacao_fiscal'
    ) NOT NULL,
    dados JSON NOT NULL,
    versao INT NOT NULL DEFAULT 1,
    criado_por CHAR(36) NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_banco_dados_escopo (escopo),
    INDEX idx_banco_dados_tipo (tipo),
    INDEX idx_banco_dados_tenant (tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notificacoes (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    usuario_id CHAR(36) NOT NULL,
    tipo ENUM('info', 'sucesso', 'aviso', 'erro') NOT NULL DEFAULT 'info',
    titulo VARCHAR(200) NULL,
    mensagem TEXT NULL,
    lida BOOLEAN NOT NULL DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_leitura DATETIME NULL,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_notificacao_tenant (tenant_id),
    INDEX idx_notificacao_usuario (usuario_id),
    INDEX idx_notificacao_lida (lida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mudancas (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    item_id BIGINT NOT NULL,
    usuario_id CHAR(36) NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_mudanca_tenant (tenant_id),
    INDEX idx_mudanca_data (data_hora),
    INDEX idx_mudanca_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- DADOS INICIAIS DE EXEMPLO
-- ======================================================

INSERT IGNORE INTO plans (
    id, codigo, nome, descricao, preco_mensal, preco_anual,
    limite_usuarios, limite_anvis_mensal, limite_projetos_ativos,
    permite_modulo_anvi, permite_modulo_projetos, permite_exportacao, permite_api, permite_sso, status
) VALUES
(
    'plan-starter-0000-0000-000000000001',
    'starter',
    'Starter',
    'Plano inicial para pequenas operações',
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
    'plan-pro-0000-0000-000000000002',
    'pro',
    'Pro',
    'Plano principal para operação comercial',
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
),
(
    'plan-enterprise-0000-000000000003',
    'enterprise',
    'Enterprise',
    'Plano para contas com operação ampliada e integrações',
    1497.00,
    14970.00,
    NULL,
    NULL,
    NULL,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    'ativo'
);

-- ======================================================
-- OBSERVAÇÕES DE MODELAGEM
-- ======================================================

-- 1. usuarios, anvis, projetos, lideres, logs_atividade, notificacoes e mudancas
--    agora são todos escopados por tenant_id.
-- 2. configuracoes e bancos_dados permitem escopo global (tenant_id NULL)
--    ou específico por empresa.
-- 3. O módulo desktop deve usar device_sessions para registrar sessões renováveis.
-- 4. A decisão de licença deve ser feita via subscriptions + invoices + payments.
-- 5. projetos mantém dados JSON para compatibilidade com o módulo atual,
--    mas já expõe colunas indexáveis para filtros operacionais.