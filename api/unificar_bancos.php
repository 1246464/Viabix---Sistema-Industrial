<?php
/**
 * Script para unificar os bancos de dados
 * ANVI + Controle de Projetos = Sistema Integrado
 */

require_once __DIR__ . '/../bootstrap_env.php';

$host = viabix_env('DB_HOST', '127.0.0.1');
$user = viabix_env('DB_USER', 'root');
$pass = viabix_env('DB_PASS', '');
$dbname = viabix_env('DB_NAME', 'viabix_db');

echo "===========================================\n";
echo "UNIFICAÇÃO DE BANCOS DE DADOS\n";
echo "ANVI + Controle de Projetos\n";
echo "===========================================\n\n";

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conectado ao MySQL\n";
    
    // Usar banco ANVI (viabix_db) como base
    $pdo->exec("USE $dbname");
    echo "✓ Usando banco de dados: $dbname\n\n";
    
    // ========================================
    // 1. ADICIONAR TABELAS DO CONTROLE DE PROJETOS
    // ========================================
    
    echo "1. Criando tabelas do Controle de Projetos...\n";
    
    // Tabela de líderes
    echo "   - Criando tabela 'lideres'...\n";
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela de projetos
    echo "   - Criando tabela 'projetos'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS projetos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            anvi_id VARCHAR(50) NULL,
            cliente VARCHAR(200),
            nome VARCHAR(200) NOT NULL,
            segmento VARCHAR(100),
            lider_id INT,
            codigo VARCHAR(100),
            modelo VARCHAR(100),
            processo VARCHAR(100),
            fase VARCHAR(50),
            status VARCHAR(50) DEFAULT 'Em Andamento',
            observacoes TEXT,
            dados JSON NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE SET NULL,
            FOREIGN KEY (lider_id) REFERENCES lideres(id) ON DELETE SET NULL,
            INDEX idx_projeto_anvi (anvi_id),
            INDEX idx_projeto_cliente (cliente),
            INDEX idx_projeto_lider (lider_id),
            INDEX idx_projeto_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Tabela de mudanças (para SSE - Server Sent Events)
    echo "   - Criando tabela 'mudancas'...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS mudancas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(50) NOT NULL,
            item_id INT NOT NULL,
            usuario_id VARCHAR(36),
            data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_data (data_hora),
            INDEX idx_tipo (tipo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    echo "✓ Tabelas do Controle de Projetos criadas\n\n";
    
    // ========================================
    // 2. ADICIONAR CAMPO DE PROJETO NA TABELA ANVIS
    // ========================================
    
    echo "2. Adicionando campo 'projeto_id' na tabela ANVIs...\n";
    
    // Verificar se já existe
    $result = $pdo->query("SHOW COLUMNS FROM anvis LIKE 'projeto_id'");
    if ($result->rowCount() == 0) {
        $pdo->exec("
            ALTER TABLE anvis 
            ADD COLUMN projeto_id INT NULL AFTER id,
            ADD FOREIGN KEY (projeto_id) REFERENCES projetos(id) ON DELETE SET NULL,
            ADD INDEX idx_anvi_projeto (projeto_id)
        ");
        echo "✓ Campo 'projeto_id' adicionado\n";
    } else {
        echo "✓ Campo 'projeto_id' já existe\n";
    }
    
    echo "\n";
    
    // ========================================
    // 3. INSERIR LÍDERES PADRÃO
    // ========================================
    
    echo "3. Inserindo líderes padrão...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM lideres");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        $pdo->exec("
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
            ('Marcos T.', 'marcos.t@empresa.com', 'Produção')
        ");
        echo "✓ 10 líderes inseridos\n";
    } else {
        echo "✓ Líderes já cadastrados ($count registros)\n";
    }
    
    echo "\n";
    
    // ========================================
    // 4. ADICIONAR CONFIGURAÇÕES
    // ========================================
    
    echo "4. Adicionando configurações do sistema...\n";
    
    $pdo->exec("
        INSERT IGNORE INTO configuracoes (chave, valor, tipo, descricao) VALUES
        ('modulo_projetos_ativo', 'true', 'booleano', 'Ativar módulo de Controle de Projetos'),
        ('integracao_anvi_projeto', 'true', 'booleano', 'Permitir vincular ANVIs a Projetos'),
        ('criar_projeto_automatico', 'false', 'booleano', 'Criar projeto automaticamente ao aprovar ANVI')
    ");
    
    echo "✓ Configurações adicionadas\n\n";
    
    // ========================================
    // 5. RESUMO FINAL
    // ========================================
    
    echo "===========================================\n";
    echo "UNIFICAÇÃO CONCLUÍDA COM SUCESSO!\n";
    echo "===========================================\n\n";
    
    echo "📊 Estrutura do Banco Unificado:\n\n";
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $usuarios = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM anvis");
    $anvis = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM projetos");
    $projetos = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM lideres");
    $lideres = $stmt->fetch()['total'];
    
    echo "Tabelas ANVI:\n";
    echo "  ✓ usuarios ............... $usuarios registros\n";
    echo "  ✓ anvis .................. $anvis registros\n";
    echo "  ✓ anvis_historico\n";
    echo "  ✓ conflitos_edicao\n";
    echo "  ✓ logs_atividade\n";
    echo "  ✓ notificacoes\n";
    echo "  ✓ configuracoes\n\n";
    
    echo "Tabelas Controle de Projetos:\n";
    echo "  ✓ projetos ............... $projetos registros\n";
    echo "  ✓ lideres ................ $lideres registros\n";
    echo "  ✓ mudancas\n\n";
    
    echo "Relacionamentos:\n";
    echo "  ✓ anvis.projeto_id → projetos.id\n";
    echo "  ✓ projetos.anvi_id → anvis.id\n";
    echo "  ✓ projetos.lider_id → lideres.id\n\n";
    
    echo "===========================================\n";
    echo "Banco: $dbname\n";
    echo "Status: ✅ PRONTO PARA USO\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
