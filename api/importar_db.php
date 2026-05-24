<?php
/**
 * Script para importar o banco de dados
 */

require_once __DIR__ . '/../bootstrap_env.php';

// Configurações do banco
$host = viabix_env('DB_HOST', '127.0.0.1');
$user = viabix_env('DB_USER', 'root');
$pass = viabix_env('DB_PASS', '');
$dbname = viabix_env('DB_NAME', 'viabix_db');

echo "Iniciando importação do banco de dados...\n\n";

try {
    // Conectar sem especificar banco
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conectado ao MySQL\n";
    
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        die("✗ Arquivo database.sql não encontrado!\n");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ Arquivo SQL lido com sucesso\n";
    
    // Executar comandos SQL
    echo "\nExecutando comandos SQL...\n";
    
    // Dividir por comandos (usando DELIMITER como separador)
    $commands = explode(';', $sql);
    $executed = 0;
    $errors = 0;
    
    foreach ($commands as $command) {
        $command = trim($command);
        
        // Ignorar comentários e comandos vazios
        if (empty($command) || substr($command, 0, 2) == '--' || substr($command, 0, 2) == '/*') {
            continue;
        }
        
        // Ignorar DELIMITER commands
        if (stripos($command, 'DELIMITER') !== false) {
            continue;
        }
        
        try {
            $pdo->exec($command);
            $executed++;
        } catch (PDOException $e) {
            // Ignorar alguns erros comuns
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), "Can't DROP") === false) {
                echo "✗ Erro: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "\n✓ Importação concluída!\n";
    echo "  - Comandos executados: $executed\n";
    if ($errors > 0) {
        echo "  - Erros: $errors\n";
    }
    
    // Verificar se banco foi criado
    $pdo->exec("USE $dbname");
    echo "\n✓ Banco de dados '$dbname' criado e acessível\n";
    
    // Criar usuário admin padrão
    echo "\nCriando usuário administrador padrão...\n";
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO usuarios (id, login, nome, senha, nivel, ativo) VALUES (?, ?, ?, ?, 'admin', 1)");
    $userId = uniqid('user_', true);
    $hashedPassword = password_hash('admin123', PASSWORD_BCRYPT);
    
    $stmt->execute([$userId, 'admin', 'Administrador', $hashedPassword]);
    
    if ($stmt->rowCount() > 0) {
        echo "✓ Usuário admin criado (login: admin, senha: admin123)\n";
    } else {
        echo "i Usuário admin já existe\n";
    }
    
    echo "\n===========================================\n";
    echo "BANCO DE DADOS PRONTO PARA USO!\n";
    echo "===========================================\n";
    echo "Database: $dbname\n";
    echo "Host: $host\n";
    echo "User: $user\n";
    echo "\nAcesse o sistema com:\n";
    echo "  Login: admin\n";
    echo "  Senha: admin123\n";
    echo "===========================================\n";
    
} catch (PDOException $e) {
    die("\n✗ ERRO: " . $e->getMessage() . "\n");
}
