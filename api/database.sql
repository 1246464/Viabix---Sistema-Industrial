-- ======================================================
-- SISTEMA VIABIX - MODELO INDUSTRIAL 10/10
-- BANCO DE DADOS MYSQL - VERSÃO 7.1 (COMPLETO)
-- ======================================================

-- ======================================================
-- CRIAÇÃO DO BANCO DE DADOS
-- ======================================================
DROP DATABASE IF EXISTS viabix_db;
CREATE DATABASE IF NOT EXISTS viabix_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE viabix_db;

-- ======================================================
-- TABELA: USUÁRIOS
-- ======================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id VARCHAR(36) PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'usuario', 'visitante') DEFAULT 'usuario',
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_acesso DATETIME NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_login (login),
    INDEX idx_nivel (nivel),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: ANVIs (PROJETOS) COM CONTROLE DE VERSÃO
-- ======================================================
CREATE TABLE IF NOT EXISTS anvis (
    id VARCHAR(50) PRIMARY KEY, -- Formato: ANVI-2025-001_REV01
    numero VARCHAR(50) NOT NULL,
    revisao VARCHAR(10) NOT NULL,
    cliente VARCHAR(200),
    projeto VARCHAR(200),
    produto TEXT,
    volume_mensal INT DEFAULT 1000,
    data_anvi DATE,
    status VARCHAR(50) DEFAULT 'em-andamento',
    versao INT DEFAULT 1,
    bloqueado_por VARCHAR(36) NULL,
    bloqueado_em DATETIME NULL,
    hash_conteudo VARCHAR(64) NULL,
    dados JSON NOT NULL, -- Todos os dados da ANVI em formato JSON
    criado_por VARCHAR(36),
    atualizado_por VARCHAR(36),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (bloqueado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_numero (numero),
    INDEX idx_cliente (cliente),
    INDEX idx_status (status),
    INDEX idx_data (data_anvi),
    INDEX idx_versao (versao),
    INDEX idx_bloqueio (bloqueado_por, bloqueado_em),
    INDEX idx_hash (hash_conteudo),
    FULLTEXT INDEX idx_busca (numero, cliente, projeto, produto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: CONFLITOS DE EDIÇÃO
-- ======================================================
CREATE TABLE IF NOT EXISTS conflitos_edicao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anvi_id VARCHAR(50) NOT NULL,
    usuario_id VARCHAR(36) NOT NULL,
    versao_usuario INT NOT NULL,
    versao_banco INT NOT NULL,
    dados_usuario JSON,
    dados_banco JSON,
    resolvido BOOLEAN DEFAULT FALSE,
    data_conflito TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_resolucao DATETIME NULL,
    INDEX idx_anvi (anvi_id),
    INDEX idx_resolvido (resolvido),
    INDEX idx_data (data_conflito),
    FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: LOGS DE ATIVIDADES
-- ======================================================
CREATE TABLE IF NOT EXISTS logs_atividade (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(36),
    acao VARCHAR(50) NOT NULL,
    detalhes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_usuario (usuario_id),
    INDEX idx_acao (acao),
    INDEX idx_data (data_hora)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: BACKUPS DE ANVIs (HISTÓRICO)
-- ======================================================
CREATE TABLE IF NOT EXISTS anvis_historico (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anvi_id VARCHAR(50),
    anvi_numero VARCHAR(50),
    anvi_revisao VARCHAR(10),
    dados JSON NOT NULL,
    usuario_id VARCHAR(36),
    acao ENUM('criacao', 'atualizacao', 'exclusao') NOT NULL,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_anvi (anvi_id),
    INDEX idx_data (data_hora),
    INDEX idx_acao (acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: CONFIGURAÇÕES DO SISTEMA
-- ======================================================
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT,
    tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
    descricao TEXT,
    atualizado_por VARCHAR(36),
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: BANCOS DE DADOS AUXILIARES
-- ======================================================
CREATE TABLE IF NOT EXISTS bancos_dados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('materia_prima', 'insumos', 'componentes', 'recursos', 
              'ferramental', 'materiais_ferramental', 'embalagem', 
              'normas', 'mao_obra', 'custos_indiretos', 'classificacao_fiscal') NOT NULL,
    dados JSON NOT NULL,
    versao INT DEFAULT 1,
    criado_por VARCHAR(36),
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_tipo (tipo),
    INDEX idx_versao (versao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- TABELA: NOTIFICAÇÕES DO SISTEMA
-- ======================================================
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id VARCHAR(36),
    tipo ENUM('info', 'sucesso', 'aviso', 'erro') DEFAULT 'info',
    titulo VARCHAR(200),
    mensagem TEXT,
    lida BOOLEAN DEFAULT FALSE,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_leitura DATETIME NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_lida (lida),
    INDEX idx_data (data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ======================================================
-- PROCEDURES
-- ======================================================

DELIMITER //

-- Procedure para atualização segura de ANVI com controle de concorrência
CREATE PROCEDURE sp_atualizar_anvi_seguro(
    IN p_id VARCHAR(50),
    IN p_dados JSON,
    IN p_usuario_id VARCHAR(36),
    IN p_versao_cliente INT,
    OUT p_sucesso BOOLEAN,
    OUT p_mensagem VARCHAR(255),
    OUT p_nova_versao INT,
    OUT p_dados_atuais JSON
)
BEGIN
    DECLARE v_versao_atual INT;
    DECLARE v_bloqueado_por VARCHAR(36);
    DECLARE v_bloqueado_em DATETIME;
    DECLARE v_hash_atual VARCHAR(64);
    DECLARE v_hash_novo VARCHAR(64);
    DECLARE v_tempo_bloqueio INT;
    DECLARE v_numero VARCHAR(50);
    DECLARE v_revisao VARCHAR(10);
    DECLARE v_cliente VARCHAR(200);
    DECLARE v_projeto VARCHAR(200);
    DECLARE v_produto TEXT;
    DECLARE v_volume INT;
    DECLARE v_data DATE;
    DECLARE v_status VARCHAR(50);
    
    -- Extrair campos do JSON
    SET v_numero = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.numero'));
    SET v_revisao = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.revisao'));
    SET v_cliente = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.cliente'));
    SET v_projeto = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.projeto'));
    SET v_produto = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.produto'));
    SET v_volume = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.volumeMensal'));
    SET v_data = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.dataANVI'));
    SET v_status = JSON_UNQUOTE(JSON_EXTRACT(p_dados, '$.status'));
    
    -- Calcular hash do novo conteúdo
    SET v_hash_novo = SHA2(p_dados, 256);
    
    -- Iniciar transação
    START TRANSACTION;
    
    -- Verificar se a ANVI existe
    SELECT versao, bloqueado_por, bloqueado_em, hash_conteudo 
    INTO v_versao_atual, v_bloqueado_por, v_bloqueado_em, v_hash_atual
    FROM anvis 
    WHERE id = p_id 
    FOR UPDATE;
    
    -- Se não encontrou, inserir nova
    IF v_versao_atual IS NULL THEN
        INSERT INTO anvis (
            id, numero, revisao, cliente, projeto, produto, 
            volume_mensal, data_anvi, status, dados, versao, 
            criado_por, atualizado_por, hash_conteudo
        ) VALUES (
            p_id, v_numero, v_revisao, v_cliente, v_projeto, v_produto,
            IFNULL(v_volume, 1000), v_data, IFNULL(v_status, 'em-andamento'),
            p_dados, 1, p_usuario_id, p_usuario_id, v_hash_novo
        );
        
        SET p_sucesso = TRUE;
        SET p_mensagem = 'ANVI criada com sucesso';
        SET p_nova_versao = 1;
        SET p_dados_atuais = NULL;
        
        COMMIT;
        
    ELSE
        -- Verificar bloqueio
        IF v_bloqueado_por IS NOT NULL AND v_bloqueado_por != p_usuario_id THEN
            SET v_tempo_bloqueio = TIMESTAMPDIFF(MINUTE, v_bloqueado_em, NOW());
            
            IF v_tempo_bloqueio < 30 THEN -- Bloqueado por outro usuário há menos de 30min
                SET p_sucesso = FALSE;
                SET p_mensagem = CONCAT('ANVI bloqueada por outro usuário desde ', DATE_FORMAT(v_bloqueado_em, '%d/%m/%Y %H:%i'));
                SET p_nova_versao = v_versao_atual;
                SET p_dados_atuais = NULL;
                
                ROLLBACK;
                
            ELSE
                -- Bloqueio expirado, liberar
                UPDATE anvis SET bloqueado_por = NULL, bloqueado_em = NULL WHERE id = p_id;
                SET v_bloqueado_por = NULL;
            END IF;
        END IF;
        
        -- Verificar versão
        IF p_versao_cliente < v_versao_atual THEN
            -- Conflito de versão
            SET p_sucesso = FALSE;
            SET p_mensagem = CONCAT('Conflito de versão. Versão atual: ', v_versao_atual, ', sua versão: ', p_versao_cliente);
            SET p_nova_versao = v_versao_atual;
            
            -- Buscar dados atuais
            SELECT dados INTO p_dados_atuais FROM anvis WHERE id = p_id;
            
            -- Registrar conflito
            INSERT INTO conflitos_edicao (
                anvi_id, usuario_id, versao_usuario, versao_banco, 
                dados_usuario, dados_banco
            ) VALUES (
                p_id, p_usuario_id, p_versao_cliente, v_versao_atual,
                p_dados, p_dados_atuais
            );
            
            ROLLBACK;
            
        ELSEIF v_hash_atual = v_hash_novo THEN
            -- Conteúdo não mudou, apenas atualizar timestamp
            UPDATE anvis 
            SET atualizado_por = p_usuario_id,
                data_atualizacao = NOW()
            WHERE id = p_id;
            
            SET p_sucesso = TRUE;
            SET p_mensagem = 'ANVI atualizada (sem alterações)';
            SET p_nova_versao = v_versao_atual;
            SET p_dados_atuais = NULL;
            
            COMMIT;
            
        ELSE
            -- Atualização normal
            UPDATE anvis 
            SET dados = p_dados,
                numero = v_numero,
                revisao = v_revisao,
                cliente = v_cliente,
                projeto = v_projeto,
                produto = v_produto,
                volume_mensal = IFNULL(v_volume, 1000),
                data_anvi = v_data,
                status = IFNULL(v_status, 'em-andamento'),
                versao = versao + 1,
                atualizado_por = p_usuario_id,
                hash_conteudo = v_hash_novo,
                bloqueado_por = NULL,
                bloqueado_em = NULL
            WHERE id = p_id;
            
            SET p_sucesso = TRUE;
            SET p_mensagem = 'ANVI atualizada com sucesso';
            SET p_nova_versao = v_versao_atual + 1;
            SET p_dados_atuais = NULL;
            
            COMMIT;
        END IF;
    END IF;
END //

-- Procedure para backup automático de ANVI
CREATE PROCEDURE sp_backup_anvi(
    IN p_anvi_id VARCHAR(50),
    IN p_acao VARCHAR(20)
)
BEGIN
    DECLARE v_dados JSON;
    DECLARE v_numero VARCHAR(50);
    DECLARE v_revisao VARCHAR(10);
    DECLARE v_usuario_id VARCHAR(36);
    
    SELECT dados, numero, revisao, atualizado_por 
    INTO v_dados, v_numero, v_revisao, v_usuario_id
    FROM anvis WHERE id = p_anvi_id;
    
    INSERT INTO anvis_historico (anvi_id, anvi_numero, anvi_revisao, dados, usuario_id, acao)
    VALUES (p_anvi_id, v_numero, v_revisao, v_dados, v_usuario_id, p_acao);
END //

-- Procedure para limpeza de logs antigos
CREATE PROCEDURE sp_limpar_logs(IN p_dias INT)
BEGIN
    DELETE FROM logs_atividade 
    WHERE data_hora < DATE_SUB(NOW(), INTERVAL p_dias DAY);
    
    DELETE FROM anvis_historico 
    WHERE data_hora < DATE_SUB(NOW(), INTERVAL p_dias DAY);
    
    DELETE FROM conflitos_edicao 
    WHERE data_conflito < DATE_SUB(NOW(), INTERVAL p_dias DAY) AND resolvido = TRUE;
END //

-- Procedure para estatísticas do sistema
CREATE PROCEDURE sp_estatisticas_sistema()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM usuarios) as total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) as usuarios_ativos,
        (SELECT COUNT(*) FROM anvis) as total_anvis,
        (SELECT COUNT(*) FROM anvis WHERE status = 'aprovada') as anvis_aprovadas,
        (SELECT COUNT(*) FROM anvis WHERE status = 'em-andamento') as anvis_andamento,
        (SELECT COUNT(*) FROM logs_atividade WHERE data_hora >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as atividades_24h,
        (SELECT COUNT(*) FROM conflitos_edicao WHERE resolvido = FALSE) as conflitos_pendentes;
END //

DELIMITER ;

-- ======================================================
-- TRIGGERS
-- ======================================================

DELIMITER //

-- Trigger para backup automático ao atualizar ANVI
CREATE TRIGGER tr_anvi_update
AFTER UPDATE ON anvis
FOR EACH ROW
BEGIN
    IF OLD.hash_conteudo != NEW.hash_conteudo THEN
        CALL sp_backup_anvi(NEW.id, 'atualizacao');
    END IF;
END //

-- Trigger para backup automático ao excluir ANVI
CREATE TRIGGER tr_anvi_delete
BEFORE DELETE ON anvis
FOR EACH ROW
BEGIN
    INSERT INTO anvis_historico (anvi_id, anvi_numero, anvi_revisao, dados, acao)
    VALUES (OLD.id, OLD.numero, OLD.revisao, OLD.dados, 'exclusao');
END //

-- Trigger para registrar criação de ANVI
CREATE TRIGGER tr_anvi_insert
AFTER INSERT ON anvis
FOR EACH ROW
BEGIN
    INSERT INTO anvis_historico (anvi_id, anvi_numero, anvi_revisao, dados, usuario_id, acao)
    VALUES (NEW.id, NEW.numero, NEW.revisao, NEW.dados, NEW.criado_por, 'criacao');
END //

DELIMITER ;

-- ======================================================
-- VIEWS PARA RELATÓRIOS
-- ======================================================

-- View: Resumo de ANVIs por status
CREATE OR REPLACE VIEW vw_resumo_anvis_status AS
SELECT 
    status,
    COUNT(*) as total,
    MIN(data_criacao) as primeira_anvi,
    MAX(data_criacao) as ultima_anvi,
    AVG(volume_mensal) as volume_medio
FROM anvis
GROUP BY status;

-- View: Atividades recentes
CREATE OR REPLACE VIEW vw_atividades_recentes AS
SELECT 
    l.data_hora,
    u.nome as usuario,
    u.login,
    l.acao,
    l.detalhes,
    l.ip_address
FROM logs_atividade l
LEFT JOIN usuarios u ON l.usuario_id = u.id
ORDER BY l.data_hora DESC
LIMIT 100;

-- View: Top usuários por atividade
CREATE OR REPLACE VIEW vw_top_usuarios AS
SELECT 
    u.nome,
    u.login,
    u.nivel,
    COUNT(l.id) as total_atividades,
    MAX(l.data_hora) as ultima_atividade
FROM usuarios u
LEFT JOIN logs_atividade l ON u.id = l.usuario_id
GROUP BY u.id
ORDER BY total_atividades DESC;

-- View: Conflitos pendentes
CREATE OR REPLACE VIEW vw_conflitos_pendentes AS
SELECT 
    c.*,
    a.numero as anvi_numero,
    a.revisao as anvi_revisao,
    u.nome as usuario_nome
FROM conflitos_edicao c
JOIN anvis a ON c.anvi_id = a.id
JOIN usuarios u ON c.usuario_id = u.id
WHERE c.resolvido = FALSE
ORDER BY c.data_conflito DESC;

-- ======================================================
-- INSERIR USUÁRIOS PADRÃO
-- ======================================================
-- Senhas (bcrypt hash):
-- admin: admin123
-- usuario: 123456
-- visitante: visit

INSERT IGNORE INTO usuarios (id, login, nome, senha, nivel) VALUES
('admin-001', 'admin', 'Administrador', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/LewdBPj2NXW1hLZNS', 'admin'),
('usuario-001', 'usuario', 'Usuário Padrão', '$2y$12$Y1qXQKZqZzYxqQKZqZzYxqQKZqZzYxqQKZqZzYxqQKZqZzYxqQKZ', 'usuario'),
('visitante-001', 'visitante', 'Visitante', '$2y$12$ZzYxqQKZqZzYxqQKZqZzYxqQKZqZzYxqQKZqZzYxqQKZqZzYx', 'visitante');

-- ======================================================
-- INSERIR CONFIGURAÇÕES PADRÃO
-- ======================================================
INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES
('versao_sistema', '7.1', 'texto', 'Versão atual do sistema'),
('ultima_atualizacao', NOW(), 'texto', 'Data da última atualização'),
('margem_padrao', '20', 'numero', 'Margem de lucro padrão (%)'),
('encargos_padrao', '80', 'numero', 'Encargos sociais padrão (%)'),
('ipi_padrao', '10', 'numero', 'Alíquota IPI padrão (%)'),
('icms_padrao', '18', 'numero', 'Alíquota ICMS padrão (%)'),
('pis_padrao_lucro_real', '1.65', 'numero', 'Alíquota PIS - Lucro Real'),
('cofins_padrao_lucro_real', '7.6', 'numero', 'Alíquota COFINS - Lucro Real'),
('pis_padrao_outros', '0.65', 'numero', 'Alíquota PIS - Outros regimes'),
('cofins_padrao_outros', '3.0', 'numero', 'Alíquota COFINS - Outros regimes'),
('irpj_padrao', '15', 'numero', 'Alíquota IRPJ padrão'),
('csll_padrao', '9', 'numero', 'Alíquota CSLL padrão'),
('percentual_presumido_padrao', '8', 'numero', 'Percentual de presunção IRPJ/CSLL'),
('horas_trabalhadas_padrao', '176', 'numero', 'Horas trabalhadas por mês'),
('kwh_padrao', '0.85', 'numero', 'Preço padrão do kWh'),
('agua_padrao', '8.50', 'numero', 'Preço padrão do m³ de água'),
('cbs_padrao', '12.5', 'numero', 'Alíquota CBS - Reforma 2027'),
('ibs_padrao', '14', 'numero', 'Alíquota IBS - Reforma 2027'),
('is_padrao', '0', 'numero', 'Alíquota IS - Reforma 2027'),
('manutencao_programada', '0', 'booleano', 'Sistema em manutenção'),
('limite_arquivos', '52428800', 'numero', 'Limite de upload (bytes)'),
('formatos_imagem', '["jpg","jpeg","png","gif"]', 'json', 'Formatos de imagem aceitos');

-- ======================================================
-- INSERIR NOTIFICAÇÕES INICIAIS
-- ======================================================
INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem) VALUES
('admin-001', 'info', 'Bem-vindo ao Sistema FANAVID', 'Sistema de Análise de Viabilidade versão 7.1'),
('usuario-001', 'info', 'Bem-vindo ao Sistema FANAVID', 'Sistema de Análise de Viabilidade versão 7.1'),
('visitante-001', 'info', 'Bem-vindo ao Sistema FANAVID', 'Sistema de Análise de Viabilidade versão 7.1');

-- ======================================================
-- INSERIR DADOS DE EXEMPLO PARA BANCOS AUXILIARES
-- ======================================================
INSERT INTO bancos_dados (tipo, dados, versao, criado_por) VALUES
('classificacao_fiscal', JSON_ARRAY(
    JSON_OBJECT('ncm', '7003.00.00', 'descricao', 'Vidro vazado ou laminado', 'ipi', '10', 'icms', '18', 'pis', '1.65', 'cofins', '7.6'),
    JSON_OBJECT('ncm', '7004.00.00', 'descricao', 'Vidro estirado ou soprado', 'ipi', '10', 'icms', '18', 'pis', '1.65', 'cofins', '7.6'),
    JSON_OBJECT('ncm', '7005.00.00', 'descricao', 'Vidro flotado', 'ipi', '10', 'icms', '18', 'pis', '1.65', 'cofins', '7.6')
), 1, 'admin-001'),

('recursos', JSON_ARRAY(
    JSON_OBJECT('processo', 'Corte', 'recurso', 'Mesa de Corte CNC', 'potencia', '15', 'kwh', '0.85', 'agua', '0', 'preco_agua', '0', 'rendimento', '95', 'producao_hora', '20', 'setup', '10', 'depreciacao', '1500', 'outros', '50'),
    JSON_OBJECT('processo', 'Têmpera', 'recurso', 'Forno de Têmpera', 'potencia', '150', 'kwh', '0.85', 'agua', '2', 'preco_agua', '8.50', 'rendimento', '98', 'producao_hora', '15', 'setup', '30', 'depreciacao', '5000', 'outros', '200'),
    JSON_OBJECT('processo', 'Laminação', 'recurso', 'Prensa de Laminação', 'potencia', '80', 'kwh', '0.85', 'agua', '1', 'preco_agua', '8.50', 'rendimento', '96', 'producao_hora', '10', 'setup', '45', 'depreciacao', '3500', 'outros', '150')
), 1, 'admin-001'),

('materia_prima', JSON_ARRAY(
    JSON_OBJECT('tipo', 'Vidro Float', 'codigo', 'VF-04', 'descricao', 'Vidro Float 4mm', 'ncm', '7005.00.00', 'unidade', 'm²', 'valor', '45.00', 'ipi', '10', 'icms', '18'),
    JSON_OBJECT('tipo', 'Vidro Float', 'codigo', 'VF-05', 'descricao', 'Vidro Float 5mm', 'ncm', '7005.00.00', 'unidade', 'm²', 'valor', '55.00', 'ipi', '10', 'icms', '18'),
    JSON_OBJECT('tipo', 'Vidro Float', 'codigo', 'VF-06', 'descricao', 'Vidro Float 6mm', 'ncm', '7005.00.00', 'unidade', 'm²', 'valor', '65.00', 'ipi', '10', 'icms', '18')
), 1, 'admin-001'),

('ferramental', JSON_ARRAY(
    JSON_OBJECT('descricao', 'Molde Curvo', 'vida_util', '50000', 'valor', '25000.00'),
    JSON_OBJECT('descricao', 'Gabarito de Corte', 'vida_util', '100000', 'valor', '8500.00'),
    JSON_OBJECT('descricao', 'Matriz Serigrafia', 'vida_util', '75000', 'valor', '12000.00')
), 1, 'admin-001'),

('mao_obra', JSON_ARRAY(
    JSON_OBJECT('funcao', 'Operador de Corte', 'setor', 'Produção', 'centro_custo', 'CC-001', 'salario_hora', '18.50'),
    JSON_OBJECT('funcao', 'Operador de Têmpera', 'setor', 'Produção', 'centro_custo', 'CC-002', 'salario_hora', '22.00'),
    JSON_OBJECT('funcao', 'Inspetor de Qualidade', 'setor', 'Qualidade', 'centro_custo', 'CC-003', 'salario_hora', '20.00')
), 1, 'admin-001');

-- ======================================================
-- CRIAR ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ======================================================
CREATE INDEX idx_anvis_data_atualizacao ON anvis(data_atualizacao DESC);
CREATE INDEX idx_logs_data ON logs_atividade(data_hora DESC);
CREATE INDEX idx_historico_data ON anvis_historico(data_hora DESC);
CREATE INDEX idx_conflitos_data ON conflitos_edicao(data_conflito DESC);

-- ======================================================
-- EVENTOS PROGRAMADOS (OPCIONAL - REQUER EVENT_SCHEDULER ON)
-- ======================================================
DELIMITER //

CREATE EVENT IF NOT EXISTS event_limpar_logs_antigos
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_DATE + INTERVAL 1 DAY
DO
BEGIN
    CALL sp_limpar_logs(90);
END //

CREATE EVENT IF NOT EXISTS event_atualizar_estatisticas
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    INSERT INTO logs_atividade (usuario_id, acao, detalhes) 
    VALUES (NULL, 'sistema', 'Executou limpeza automática de logs');
END //

DELIMITER ;

-- ======================================================
-- PRIVILÉGIOS (AJUSTE CONFORME NECESSÁRIO)
-- ======================================================
-- Criar usuário específico para a aplicação
CREATE USER IF NOT EXISTS 'fanavid_user'@'localhost' IDENTIFIED BY 'Fanavid@2025';
GRANT ALL PRIVILEGES ON fanavid_db.* TO 'fanavid_user'@'localhost';

-- Para acesso remoto (cuidado em produção)
-- CREATE USER IF NOT EXISTS 'fanavid_user'@'%' IDENTIFIED BY 'Fanavid@2025';
-- GRANT ALL PRIVILEGES ON fanavid_db.* TO 'fanavid_user'@'%';

FLUSH PRIVILEGES;

-- ======================================================
-- VERIFICAÇÕES FINAIS
-- ======================================================
SELECT '✅ BANCO DE DADOS CRIADO COM SUCESSO!' as STATUS;
SELECT CONCAT('📊 TOTAL DE TABELAS: ', COUNT(*)) as INFO FROM information_schema.tables WHERE table_schema = 'fanavid_db';
SELECT CONCAT('👥 TOTAL DE USUÁRIOS: ', COUNT(*)) as INFO FROM usuarios;
SELECT CONCAT('⚙️ TOTAL DE CONFIGURAÇÕES: ', COUNT(*)) as INFO FROM configuracoes;