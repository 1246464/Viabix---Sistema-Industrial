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
-- Gere hashes bcrypt antes de importar. Nunca use senhas em texto puro.
INSERT INTO usuarios (username, senha, nome, nivel) VALUES
('admin', '$2y$12$replaceWithGeneratedHashBeforeImport', 'Administrador', 'admin'),
('lider', '$2y$12$replaceWithGeneratedHashBeforeImport', 'Líder de Projetos', 'lider'),
('visualizador', '$2y$12$replaceWithGeneratedHashBeforeImport', 'Visualizador', 'visualizador')
ON DUPLICATE KEY UPDATE username = username;

-- ==============================================
-- CONSULTAS ÚTEIS
-- ==============================================

-- Listar todos os usuários
-- SELECT * FROM usuarios;

-- Adicionar novo usuário
-- INSERT INTO usuarios (username, senha, nome, nivel) 
-- VALUES ('novo_usuario', '$2y$12$hash_bcrypt_gerado', 'Nome Completo', 'lider');

-- Alterar senha
-- UPDATE usuarios SET senha = '$2y$12$hash_bcrypt_gerado' WHERE username = 'admin';

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
