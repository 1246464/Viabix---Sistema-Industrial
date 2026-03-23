-- ==============================================
-- ARQUIVO: database.sql
-- DESCRIÇÃO: Estrutura completa do banco de dados
-- VERSÃO: 1.1
-- ==============================================

-- Criar banco de dados
DROP DATABASE IF EXISTS gestao_projetos;
CREATE DATABASE gestao_projetos
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE gestao_projetos;

-- ==============================================
-- TABELA: lideres
-- ==============================================
CREATE TABLE lideres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    departamento VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_lider_nome (nome),
    INDEX idx_lider_email (email),
    INDEX idx_lider_departamento (departamento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- TABELA: projetos
-- ==============================================
CREATE TABLE projetos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dados JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_projeto_id (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================
-- TABELA: usuarios
-- ==============================================
CREATE TABLE usuarios (
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

-- ==============================================
-- DADOS INICIAIS - LÍDERES
-- ==============================================
INSERT INTO lideres (nome, email, departamento) VALUES
('Henrique B.', 'henrique.b@empresa.com', 'Engenharia'),
('Carlos S.', 'carlos.s@empresa.com', 'Projetos'),
('Ana P.', 'ana.p@empresa.com', 'Qualidade'),
('Roberto M.', 'roberto.m@empresa.com', 'Produção'),
('Mariana L.', 'mariana.l@empresa.com', 'Suprimentos'),
('Fernando C.', 'fernando.c@empresa.com', 'Comercial'),
('Patrícia R.', 'patricia.r@empresa.com', 'Engenharia'),
('Ricardo A.', 'ricardo.a@empresa.com', 'Qualidade'),
('Juliana S.', 'juliana.s@empresa.com', 'Projetos'),
('Marcos T.', 'marcos.t@empresa.com', 'Produção');

-- ==============================================
-- DADOS INICIAIS - USUÁRIOS
-- ==============================================
INSERT INTO usuarios (username, senha, nome, nivel) VALUES
('admin', '123', 'Administrador', 'admin'),
('lider', '123', 'Líder de Projetos', 'lider'),
('visualizador', '123', 'Visualizador', 'visualizador');

-- ==============================================
-- DADOS INICIAIS - PROJETOS (exemplos)
-- ==============================================
INSERT INTO projetos (dados) VALUES
('{
    "id": 1,
    "cliente": "Montadora ABC",
    "projectName": "Vidro Dianteiro",
    "segmento": "Autos",
    "leaderId": 1,
    "projectLeader": "Henrique B.",
    "codigo": "VD-2024-001",
    "anviNumber": "ANVI-001/2024",
    "modelo": "PBS",
    "processo": "Laminado",
    "fase": "Série",
    "status": "Em Andamento",
    "observacoes": "Projeto prioritário",
    "tasks": {
        "kom": {
            "planned": "2024-01-15",
            "start": "2024-01-15",
            "executed": "2024-01-16",
            "duration": 1,
            "history": []
        },
        "ferramental": {
            "planned": "2024-02-01",
            "start": "2024-02-05",
            "executed": null,
            "duration": 5,
            "history": [],
            "resources": {}
        },
        "cadBomFt": {
            "planned": "2024-02-15",
            "start": null,
            "executed": null,
            "duration": 3,
            "history": []
        },
        "tryout": {
            "planned": "2024-03-01",
            "start": null,
            "executed": null,
            "duration": 3,
            "history": [],
            "resources": {}
        },
        "entrega": {
            "planned": "2024-03-15",
            "start": null,
            "executed": null,
            "duration": 1,
            "history": []
        },
        "psw": {
            "planned": "2024-03-20",
            "start": null,
            "executed": null,
            "duration": 1,
            "history": []
        },
        "handover": {
            "planned": "2024-03-30",
            "start": null,
            "executed": null,
            "duration": 1,
            "history": []
        }
    },
    "apqp": {},
    "capability": {
        "characteristics": []
    }
}');

-- Mostrar tabelas criadas
SHOW TABLES;
SELECT 'Banco de dados criado com sucesso!' as status;