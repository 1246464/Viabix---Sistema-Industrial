<?php
/**
 * SCRIPT DE RENOMEAÇÃO: fanavid_db → viabix_db
 * Acesse: http://localhost/ANVI/renomear_banco_viabix.php
 */

require_once __DIR__ . '/bootstrap_env.php';

// Configurações
$host = viabix_env('DB_HOST', '127.0.0.1');
$user = viabix_env('DB_USER', 'root');
$pass = viabix_env('DB_PASS', '');
$old_db = viabix_env('DB_OLD_NAME', 'fanavid_db');
$new_db = viabix_env('DB_NAME', 'viabix_db');

// Estilização
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renomear Banco para Viabix</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0a3d2e 0%, #1b5e20 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .content {
            padding: 40px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #0a3d2e;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #0a3d2e 0%, #1b5e20 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: scale(1.05);
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .badge-success { background: #28a745; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        .badge-info { background: #17a2b8; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <i class="fas fa-sync-alt fa-3x" style="margin-bottom: 20px;"></i>
            <h1>Renomear Banco de Dados</h1>
            <p>FANAVID → VIABIX</p>
        </div>
        
        <div class="content">
<?php

// Verificar se é requisição de execução
if (isset($_GET['executar']) && $_GET['executar'] === 'sim') {
    
    echo "<h2><i class='fas fa-cog fa-spin'></i> Executando renomeação...</h2>";
    
    try {
        // Conectar ao MySQL
        $conn = new mysqli($host, $user, $pass);
        
        if ($conn->connect_error) {
            throw new Exception("Falha na conexão: " . $conn->connect_error);
        }
        
        echo "<div class='success'><i class='fas fa-check-circle'></i> Conectado ao MySQL com sucesso!</div>";
        
        // Verificar se banco antigo existe
        $result = $conn->query("SHOW DATABASES LIKE '$old_db'");
        if ($result->num_rows === 0) {
            throw new Exception("Banco '$old_db' não encontrado! Talvez já tenha sido renomeado?");
        }
        
        echo "<div class='info'><i class='fas fa-database'></i> Banco '$old_db' encontrado.</div>";
        
        // Verificar se novo banco já existe
        $result = $conn->query("SHOW DATABASES LIKE '$new_db'");
        if ($result->num_rows > 0) {
            throw new Exception("Banco '$new_db' já existe! Delete-o primeiro ou use outro nome.");
        }
        
        // Criar novo banco
        if (!$conn->query("CREATE DATABASE `$new_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
            throw new Exception("Erro ao criar banco: " . $conn->error);
        }
        
        echo "<div class='success'><i class='fas fa-plus-circle'></i> Banco '$new_db' criado com sucesso!</div>";
        
        // Listar todas as tabelas do banco antigo
        $conn->select_db($old_db);
        $result = $conn->query("SHOW TABLES");
        
        $tables = [];
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        echo "<div class='info'><i class='fas fa-table'></i> Encontradas " . count($tables) . " tabelas para mover.</div>";
        
        echo "<table class='table'>";
        echo "<thead><tr><th>Tabela</th><th>Status</th></tr></thead>";
        echo "<tbody>";
        
        // Renomear cada tabela
        $success_count = 0;
        foreach ($tables as $table) {
            try {
                $sql = "RENAME TABLE `$old_db`.`$table` TO `$new_db`.`$table`";
                if ($conn->query($sql)) {
                    echo "<tr><td>$table</td><td><span class='badge badge-success'>✓ Movida</span></td></tr>";
                    $success_count++;
                } else {
                    echo "<tr><td>$table</td><td><span class='badge badge-danger'>✗ Erro: " . $conn->error . "</span></td></tr>";
                }
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td><span class='badge badge-danger'>✗ " . $e->getMessage() . "</span></td></tr>";
            }
        }
        
        echo "</tbody></table>";
        
        if ($success_count === count($tables)) {
            // Remover banco antigo (agora vazio)
            if ($conn->query("DROP DATABASE `$old_db`")) {
                echo "<div class='success'><i class='fas fa-trash-alt'></i> Banco antigo '$old_db' removido.</div>";
            }
            
            echo "<div class='success' style='font-size: 1.2rem; text-align: center; padding: 30px;'>";
            echo "<i class='fas fa-check-circle' style='font-size: 3rem; margin-bottom: 20px;'></i><br>";
            echo "<strong>✅ RENOMEAÇÃO CONCLUÍDA COM SUCESSO!</strong><br><br>";
            echo "Banco '$old_db' → '$new_db'<br>";
            echo "$success_count tabelas movidas<br><br>";
            echo "<a href='../ANVI/' class='btn'>Ir para o Sistema Viabix</a>";
            echo "</div>";
            
            echo "<div class='info'>";
            echo "<h3>Próximos passos:</h3>";
            echo "<ol>";
            echo "<li>Limpe os cookies do navegador (Ctrl+Shift+Delete)</li>";
            echo "<li>Acesse: <a href='../ANVI/'>http://localhost/ANVI/</a></li>";
            echo "<li>Faça login: <strong>admin</strong> / <strong>admin123</strong></li>";
            echo "<li>Verifique se 'Viabix' aparece em todos os lugares</li>";
            echo "</ol>";
            echo "</div>";
            
        } else {
            echo "<div class='error'><i class='fas fa-exclamation-triangle'></i> Apenas $success_count de " . count($tables) . " tabelas foram movidas. Verifique os erros acima.</div>";
        }
        
        $conn->close();
        
    } catch (Exception $e) {
        echo "<div class='error'><i class='fas fa-times-circle'></i> <strong>Erro:</strong> " . $e->getMessage() . "</div>";
        echo "<a href='?' class='btn' style='background: #dc3545;'>Voltar</a>";
    }
    
} else {
    // Tela inicial - mostrar informações
    ?>
    
    <div class="step">
        <h2><i class="fas fa-info-circle"></i> O que este script faz?</h2>
        <p>Este script vai automaticamente:</p>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Criar o novo banco de dados: <strong><?php echo $new_db; ?></strong></li>
            <li>Mover todas as tabelas de <strong><?php echo $old_db; ?></strong> para <strong><?php echo $new_db; ?></strong></li>
            <li>Remover o banco antigo <strong><?php echo $old_db; ?></strong></li>
        </ul>
    </div>
    
    <div class="info">
        <h3><i class="fas fa-exclamation-circle"></i> Importante:</h3>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Certifique-se de que o XAMPP/MySQL está rodando</li>
            <li>Faça um backup do banco antes (opcional, mas recomendado)</li>
            <li>Esta operação não pode ser desfeita facilmente</li>
        </ul>
    </div>
    
    <div class="step">
        <h3>Configurações atuais:</h3>
        <table class="table">
            <tr><th>Host</th><td><?php echo $host; ?></td></tr>
            <tr><th>Usuário</th><td><?php echo $user; ?></td></tr>
            <tr><th>Banco Antigo</th><td><span class="badge badge-danger"><?php echo $old_db; ?></span></td></tr>
            <tr><th>Banco Novo</th><td><span class="badge badge-success"><?php echo $new_db; ?></span></td></tr>
        </table>
    </div>
    
    <div style="text-align: center; margin-top: 40px;">
        <a href="?executar=sim" class="btn" onclick="return confirm('Tem certeza que deseja renomear o banco de dados?')">
            <i class="fas fa-rocket"></i> EXECUTAR RENOMEAÇÃO
        </a>
    </div>
    
    <?php
}
?>
        </div>
    </div>
</body>
</html>
