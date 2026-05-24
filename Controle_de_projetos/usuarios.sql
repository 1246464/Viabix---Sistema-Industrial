-- ==============================================
-- SCRIPT DE INSTALAÇÃO - SISTEMA DE LOGIN
-- ==============================================

USE gestao_projetos;

-- Criar tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(100) NOT NULL,
    nivel ENUM('admin', 'lider', 'visualizador') NOT NULL,
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_nivel (nivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir usuários padrão
INSERT INTO usuarios (username, senha, nome, nivel) VALUES
('admin', '123', 'Administrador', 'admin'),
('lider', '123', 'Líder de Projetos', 'lider'),
('visualizador', '123', 'Visualizador', 'visualizador')
ON DUPLICATE KEY UPDATE username = username;

-- ==============================================
-- CONSULTAS ÚTEIS
-- ==============================================

-- Listar todos os usuários
-- SELECT * FROM usuarios;

-- Adicionar novo usuário
-- INSERT INTO usuarios (username, senha, nome, nivel) 
-- VALUES ('novo_usuario', '123', 'Nome Completo', 'lider');

-- Alterar senha
-- UPDATE usuarios SET senha = 'nova_senha' WHERE username = 'admin';

-- Desativar usuário
-- UPDATE usuarios SET ativo = 0 WHERE username = 'usuario';

-- Ativar usuário
-- UPDATE usuarios SET ativo = 1 WHERE username = 'usuario';

-- Excluir usuário
-- DELETE FROM usuarios WHERE username = 'usuario';

-- Alterar nível de acesso
-- UPDATE usuarios SET nivel = 'admin' WHERE username = 'usuario';

SELECT 'Tabela de usuários criada com sucesso!' as status;
SELECT * FROM usuarios;
