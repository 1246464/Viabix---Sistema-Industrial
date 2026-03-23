<?php
/**
 * Diagnóstico do Sistema Viabix
 * Acesse: http://localhost/ANVI/api/diagnostico.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico Viabix</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; font-weight: bold; }
        pre { background: #f0f0f0; padding: 10px; border-radius: 4px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico do Sistema Viabix</h1>
    
    <div class="card">
        <h2>📁 Verificação de Arquivos</h2>
        <table>
            <tr>
                <th>Arquivo</th>
                <th>Status</th>
                <th>Permissões</th>
            </tr>
            <?php
            $arquivos = [
                '../index.html',
                'config.php',
                'login.php',
                'check_session.php',
                'logout.php',
                'usuarios.php',
                'anvi.php'
            ];
            
            foreach ($arquivos as $arquivo) {
                $caminho = __DIR__ . '/' . $arquivo;
                if (file_exists($caminho)) {
                    $perms = substr(sprintf('%o', fileperms($caminho)), -4);
                    echo "<tr>
                        <td>{$arquivo}</td>
                        <td class='success'>✅ OK</td>
                        <td>{$perms}</td>
                    </tr>";
                } else {
                    echo "<tr>
                        <td>{$arquivo}</td>
                        <td class='error'>❌ Não encontrado</td>
                        <td>-</td>
                    </tr>";
                }
            }
            ?>
        </table>
    </div>

    <div class="card">
        <h2>🔌 Conexão com MySQL</h2>
        <?php
        require_once 'config.php';
        
        if (isset($pdo) && $pdo) {
            echo "<p class='success'>✅ Conexão com MySQL estabelecida!</p>";
            
            // Verificar se as tabelas existem
            $tabelas = ['usuarios', 'anvis', 'logs_atividade'];
            echo "<h3>Tabelas:</h3><ul>";
            foreach ($tabelas as $tabela) {
                try {
                    $result = $pdo->query("SELECT 1 FROM {$tabela} LIMIT 1");
                    echo "<li class='success'>✅ {$tabela} - OK</li>";
                } catch (PDOException $e) {
                    echo "<li class='error'>❌ {$tabela} - Não encontrada</li>";
                }
            }
            echo "</ul>";
            
            // Verificar usuários
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
            $total = $stmt->fetch()['total'];
            echo "<p>Total de usuários no banco: <strong>{$total}</strong></p>";
            
        } else {
            echo "<p class='error'>❌ Falha na conexão com MySQL</p>";
        }
        ?>
    </div>

    <div class="card">
        <h2>🔐 Teste de Login Direto</h2>
        <form onsubmit="testarLogin(event)">
            <input type="text" id="testLogin" value="admin" placeholder="Usuário">
            <input type="password" id="testSenha" value="admin123" placeholder="Senha">
            <button type="submit">Testar Login</button>
        </form>
        <div id="resultadoLogin" style="margin-top: 10px;"></div>
        
        <script>
        async function testarLogin(event) {
            event.preventDefault();
            const login = document.getElementById('testLogin').value;
            const senha = document.getElementById('testSenha').value;
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'include',
                    body: JSON.stringify({ login, senha })
                });
                
                const data = await response.json();
                const resultado = document.getElementById('resultadoLogin');
                
                if (data.success) {
                    resultado.innerHTML = `<p class='success'>✅ Login bem-sucedido! Usuário: ${data.user.nome} (${data.user.nivel})</p>`;
                } else {
                    resultado.innerHTML = `<p class='error'>❌ Erro: ${data.message || 'Falha no login'}</p>`;
                }
            } catch (error) {
                document.getElementById('resultadoLogin').innerHTML = `<p class='error'>❌ Erro de conexão: ${error.message}</p>`;
            }
        }
        </script>
    </div>

    <div class="card">
        <h2>🌐 Configurações do PHP</h2>
        <table>
            <tr><th>session.save_path</th><td><?php echo session_save_path() ?: 'Padrão do sistema'; ?></td></tr>
            <tr><th>session.name</th><td><?php echo session_name(); ?></td></tr>
            <tr><th>display_errors</th><td><?php echo ini_get('display_errors'); ?></td></tr>
            <tr><th>error_log</th><td><?php echo ini_get('error_log'); ?></td></tr>
        </table>
    </div>

    <div class="card">
        <h2>📝 Logs de Erro (últimas 10 linhas)</h2>
        <pre><?php
        $logFile = ini_get('error_log');
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $lastLines = array_slice($lines, -10);
            echo htmlspecialchars(implode('', $lastLines));
        } else {
            echo "Arquivo de log não encontrado: {$logFile}";
        }
        ?></pre>
    </div>

    <div class="card">
        <h2>🔧 Soluções rápidas</h2>
        <ol>
            <li><strong>Verifique o caminho:</strong> O erro mostra "localhost//fanavid/api/anvi.php" (duas barras). Certifique-se de acessar o sistema pelo endereço correto.</li>
            <li><strong>Execute o SQL:</strong> Importe o arquivo <code>database.sql</code> no MySQL</li>
            <li><strong>Configure o config.php:</strong> Verifique se os dados de conexão estão corretos</li>
            <li><strong>Permissões:</strong> Dê permissão 755 para a pasta api/</li>
            <li><strong>Session path:</strong> Verifique se o diretório de sessão tem permissão de escrita</li>
        </ol>
    </div>

    <div class="card">
        <h2>📊 Status da Sessão Atual</h2>
        <?php
        session_start();
        echo "<pre>";
        if (isset($_SESSION) && !empty($_SESSION)) {
            echo "Sessão ativa:\n";
            print_r($_SESSION);
        } else {
            echo "Nenhuma sessão ativa";
        }
        echo "</pre>";
        ?>
    </div>
</body>
</html>