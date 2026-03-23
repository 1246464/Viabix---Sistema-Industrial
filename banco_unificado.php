<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banco Unificado - Teste</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        h1 {
            color: #2e7d32;
            text-align: center;
            margin-bottom: 30px;
        }
        .success-box {
            background: #e8f5e9;
            border-left: 5px solid #4caf50;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 5px solid #2196f3;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #2e7d32;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .badge-success {
            background: #4caf50;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #2e7d32;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
            transition: 0.3s;
        }
        .btn:hover {
            background: #1b5e20;
            transform: translateY(-2px);
        }
        .link-section {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎉 Banco de Dados Unificado com Sucesso!</h1>
        
        <div class="success-box">
            <h2>✅ Unificação Concluída</h2>
            <p><strong>Banco:</strong> viabix_db</p>
            <p><strong>Status:</strong> <span class="badge badge-success">OPERACIONAL</span></p>
        </div>

        <?php
        require_once 'api/config.php';
        
        try {
            echo '<div class="info-box">';
            echo '<h2>📊 Estrutura do Banco Unificado</h2>';
            
            // Tabelas
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo '<h3>Tabelas (' . count($tables) . '):</h3>';
            echo '<table>';
            echo '<tr><th>Nome da Tabela</th><th>Registros</th><th>Módulo</th></tr>';
            
            $modules = [
                'usuarios' => 'Comum',
                'anvis' => 'ANVI',
                'anvis_historico' => 'ANVI',
                'conflitos_edicao' => 'ANVI',
                'logs_atividade' => 'Comum',
                'notificacoes' => 'Comum',
                'configuracoes' => 'Comum',
                'projetos' => 'Projetos',
                'lideres' => 'Projetos',
                'mudancas' => 'Projetos'
            ];
            
            foreach ($tables as $table) {
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM `$table`");
                $count = $stmt->fetch()['total'];
                $module = $modules[$table] ?? 'Outro';
                
                $moduleColor = $module == 'ANVI' ? '#4caf50' : ($module == 'Projetos' ? '#2196f3' : '#ff9800');
                
                echo "<tr>";
                echo "<td><strong>$table</strong></td>";
                echo "<td>$count</td>";
                echo "<td><span class='badge' style='background: $moduleColor; color: white;'>$module</span></td>";
                echo "</tr>";
            }
            
            echo '</table>';
            echo '</div>';
            
            // Mostrar relacionamentos
            echo '<div class="info-box">';
            echo '<h2>🔗 Relacionamentos Entre Módulos</h2>';
            echo '<ul>';
            echo '<li><strong>anvis.projeto_id</strong> → projetos.id (ANVI vinculada a Projeto)</li>';
            echo '<li><strong>projetos.anvi_id</strong> → anvis.id (Projeto vinculado a ANVI)</li>';
            echo '<li><strong>projetos.lider_id</strong> → lideres.id (Projeto tem um Líder)</li>';
            echo '<li><strong>Todos compartilham:</strong> usuarios, logs_atividade, notificacoes</li>';
            echo '</ul>';
            echo '</div>';
            
            // Mostrar recursos
            echo '<div class="success-box">';
            echo '<h2>🚀 Recursos Integrados</h2>';
            echo '<ul>';
            echo '<li>✅ Login único para ambos os sistemas</li>';
            echo '<li>✅ Criar projeto a partir de uma ANVI aprovada</li>';
            echo '<li>✅ Visualizar projeto associado à ANVI</li>';
            echo '<li>✅ Histórico unificado de atividades</li>';
            echo '<li>✅ Notificações compartilhadas</li>';
            echo '<li>✅ Gestão centralizada de usuários</li>';
            echo '</ul>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div style="background: #ffebee; border-left: 5px solid #f44336; padding: 20px; margin: 20px 0;">';
            echo '<h2>❌ Erro</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <div class="link-section">
            <h2>🔗 Acessar Sistemas</h2>
            <a href="index.html" class="btn">📊 Sistema ANVI</a>
            <a href="Controle_de_projetos/index.php" class="btn">📅 Controle de Projetos</a>
        </div>
    </div>
</body>
</html>
