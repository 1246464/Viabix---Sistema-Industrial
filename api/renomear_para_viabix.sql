-- =====================================================
-- SCRIPT DE RENOMEAÇÃO: FANAVID → VIABIX
-- =====================================================
-- Este script renomeia o banco de dados de fanavid_db para viabix_db
-- 
-- IMPORTANTE: Execute este script via phpMyAdmin ou linha de comando MySQL
-- 
-- Método 1 (Recomendado): Via phpMyAdmin
--   1. Acesse http://localhost/phpmyadmin
--   2. Selecione o banco fanavid_db
--   3. Clique em "Operações"
--   4. Em "Renomear banco de dados para:", digite: viabix_db
--   5. Clique em "Executar"
--
-- Método 2: Via linha de comando
--   Execute este arquivo: mysql -u root -p < renomear_para_viabix.sql
--
-- =====================================================

-- Criar novo banco de dados
CREATE DATABASE IF NOT EXISTS viabix_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Renomear todas as tabelas de fanavid_db para viabix_db
RENAME TABLE fanavid_db.anvis TO viabix_db.anvis;
RENAME TABLE fanavid_db.anvis_historico TO viabix_db.anvis_historico;
RENAME TABLE fanavid_db.usuarios TO viabix_db.usuarios;
RENAME TABLE fanavid_db.logs_atividade TO viabix_db.logs_atividade;
RENAME TABLE fanavid_db.notificacoes TO viabix_db.notificacoes;
RENAME TABLE fanavid_db.configuracoes TO viabix_db.configuracoes;
RENAME TABLE fanavid_db.conflitos_edicao TO viabix_db.conflitos_edicao;
RENAME TABLE fanavid_db.bancos_dados TO viabix_db.bancos_dados;

-- Verificar se existe a tabela projetos (Fase 4)
-- Se existir, renomear também
RENAME TABLE fanavid_db.projetos TO viabix_db.projetos;

-- Remover banco antigo (opcional - só execute se tiver certeza)
-- DROP DATABASE fanavid_db;

-- =====================================================
-- USUÁRIO DO BANCO DE DADOS (Opcional)
-- =====================================================
-- Se você criou um usuário específico chamado fanavid_user, 
-- pode criar um novo usuário viabix_user:

-- CREATE USER IF NOT EXISTS 'viabix_user'@'localhost' IDENTIFIED BY 'Viabix@2026';
-- GRANT ALL PRIVILEGES ON viabix_db.* TO 'viabix_user'@'localhost';
-- FLUSH PRIVILEGES;

-- Para remover o usuário antigo (opcional):
-- DROP USER IF EXISTS 'fanavid_user'@'localhost';

-- =====================================================
-- VERIFICAÇÃO
-- =====================================================
USE viabix_db;
SHOW TABLES;

SELECT 'Banco de dados renomeado com sucesso! Agora você tem: viabix_db' AS resultado;
