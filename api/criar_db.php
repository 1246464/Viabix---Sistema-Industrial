<?php
/**
 * Script simplificado para criar o banco de dados essencial
 */

require_once __DIR__ . '/../bootstrap_env.php';

$host = viabix_env('DB_HOST', '127.0.0.1');
$user = viabix_env('DB_USER', 'root');
$pass = viabix_env('DB_PASS', '');
$dbname = viabix_env('DB_NAME', 'viabix_db');

echo "Criando banco de dados Viabix...\n\n";

try {
    // Conectar ao MySQL
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conectado ao MySQL\n";
    
    // Criar banco de dados
    $pdo->exec("DROP DATABASE IF EXISTS $dbname");
    $pdo->exec("CREATE DATABASE $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE $dbname");
    
    echo "✓ Banco de dados '$dbname' criado\n\n";
    
    // Criar tabela de usuários
    echo "Criando tabela usuarios...\n";
    $pdo->exec("
        CREATE TABLE usuarios (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela usuarios criada\n";
    
    // Criar tabela de ANVIs
    echo "Criando tabela anvis...\n";
    $pdo->exec("
        CREATE TABLE anvis (
            id VARCHAR(50) PRIMARY KEY,
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
            dados JSON NOT NULL,
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
            FULLTEXT INDEX idx_busca (numero, cliente, projeto, produto)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela anvis criada\n";
    
    // Criar tabela de conflitos
    echo "Criando tabela conflitos_edicao...\n";
    $pdo->exec("
        CREATE TABLE conflitos_edicao (
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
            FOREIGN KEY (anvi_id) REFERENCES anvis(id) ON DELETE CASCADE,
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela conflitos_edicao criada\n";
    
    // Criar tabela de logs
    echo "Criando tabela logs_atividade...\n";
    $pdo->exec("
        CREATE TABLE logs_atividade (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela logs_atividade criada\n";
    
    // Criar tabela de histórico
    echo "Criando tabela anvis_historico...\n";
    $pdo->exec("
        CREATE TABLE anvis_historico (
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
            INDEX idx_data (data_hora)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela anvis_historico criada\n";
    
    // Criar tabela de configurações
    echo "Criando tabela configuracoes...\n";
    $pdo->exec("
        CREATE TABLE configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chave VARCHAR(100) UNIQUE NOT NULL,
            valor TEXT,
            tipo ENUM('texto', 'numero', 'booleano', 'json') DEFAULT 'texto',
            descricao TEXT,
            atualizado_por VARCHAR(36),
            data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (atualizado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
            INDEX idx_chave (chave)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela configuracoes criada\n";
    
    // Criar tabela de notificações
    echo "Criando tabela notificacoes...\n";
    $pdo->exec("
        CREATE TABLE notificacoes (
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
            INDEX idx_lida (lida)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Tabela notificacoes criada\n\n";
    
    // Inserir usuário admin
    echo "Criando usuário administrador...\n";
    $userId = 'admin-' . uniqid();
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
    
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (id, login, nome, senha, nivel, ativo) 
        VALUES (?, ?, ?, ?, 'admin', 1)
    ");
    $stmt->execute([$userId, 'admin', 'Administrador do Sistema', $hashedPassword]);
    
    echo "✓ Usuário admin criado com sucesso\n\n";
    
    // Inserir configurações padrão
    echo "Inserindo configurações iniciais...\n";
    $pdo->exec("
        INSERT INTO configuracoes (chave, valor, tipo, descricao) VALUES
        ('versao_sistema', '7.1', 'texto', 'Versão do sistema Viabix'),
        ('empresa_nome', 'Viabix', 'texto', 'Nome da empresa'),
        ('tempo_bloqueio_anvi', '30', 'numero', 'Tempo de bloqueio de edição em minutos'),
        ('backup_automatico', 'true', 'booleano', 'Ativar backup automático'),
        ('dias_manter_logs', '90', 'numero', 'Dias para manter logs de atividade')
    ");
    echo "✓ Configurações iniciais inseridas\n\n";
    
    echo "===========================================\n";
    echo "BANCO DE DADOS CRIADO COM SUCESSO!\n";
    echo "===========================================\n";
    echo "Database: $dbname\n";
    echo "Host: $host\n";
    echo "User: $user\n";
    echo "\n";
    echo "Tabelas criadas:\n";
    echo "  ✓ usuarios\n";
    echo "  ✓ anvis\n";
    echo "  ✓ conflitos_edicao\n";
    echo "  ✓ logs_atividade\n";
    echo "  ✓ anvis_historico\n";
    echo "  ✓ configuracoes\n";
    echo "  ✓ notificacoes\n";
    echo "\n";
    echo "Acesse o sistema com:\n";
    echo "  Login: admin\n";
    echo "  Senha: admin123\n";
    echo "===========================================\n";
    
} catch (PDOException $e) {
    die("\n✗ ERRO: " . $e->getMessage() . "\n");
}
