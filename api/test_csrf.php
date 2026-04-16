<?php
/**
 * Teste de CSRF Protection
 * 
 * Acesse: http://localhost/ANVI/api/test_csrf.php
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teste de CSRF Protection</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
            .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
            h2 { color: #555; margin-top: 30px; }
            .status { padding: 15px; border-radius: 4px; margin: 15px 0; font-weight: bold; }
            .status.enabled { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
            .status.disabled { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            .test-section { background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 15px 0; border-left: 4px solid #007bff; }
            button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; font-size: 14px; }
            button:hover { background: #0056b3; }
            button.danger { background: #dc3545; }
            button.danger:hover { background: #c82333; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
            pre { background: #f4f4f4; padding: 12px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
            .token-box { background: #e7f3ff; padding: 12px; border-radius: 4px; margin: 10px 0; word-break: break-all; font-family: monospace; font-size: 12px; }
            .success { color: #28a745; }
            .error { color: #dc3545; }
            .info { background: #e7f3ff; padding: 12px; border-left: 4px solid #2196F3; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🛡️ Teste de CSRF Protection</h1>

            <?php
            // Inicializar CSRF
            if (session_status() === PHP_SESSION_NONE) {
                session_name(SESSION_NAME);
                session_start();
            }
            
            viabixInitializeCsrfProtection();
            $csrfToken = viabixGetCsrfToken();
            ?>

            <div class="status enabled">
                ✅ CSRF Protection ATIVADA
            </div>

            <h2>📋 Status Atual</h2>
            <div class="info">
                <strong>Token CSRF Ativo:</strong>
                <div class="token-box"><?php echo htmlspecialchars($csrfToken); ?></div>
            </div>

            <h2>🧪 Testes Disponíveis</h2>

            <div class="test-section">
                <h3>Teste 1: Validação com Token Correto</h3>
                <p>Simula um POST com CSRF token válido.</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="test" value="valid_token">
                    <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <button type="submit" style="background: #28a745;">✅ Token Válido</button>
                </form>
            </div>

            <div class="test-section">
                <h3>Teste 2: Rejeição sem Token</h3>
                <p>Simula um POST sem CSRF token (deve ser rejeitado).</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="test" value="no_token">
                    <button type="submit" class="danger">❌ Sem Token</button>
                </form>
            </div>

            <div class="test-section">
                <h3>Teste 3: Rejeição com Token Inválido</h3>
                <p>Simula um POST com CSRF token inválido (deve ser rejeitado).</p>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="test" value="invalid_token">
                    <input type="hidden" name="_csrf_token" value="token-inválido-propositalmente">
                    <button type="submit" class="danger">❌ Token Inválido</button>
                </form>
            </div>

            <div class="test-section">
                <h3>Teste 4: Validação com Header</h3>
                <p>Simula um PUT/DELETE com token no header HTTP.</p>
                <button onclick="testarComHeader()">🔗 Validar via Header</button>
            </div>

            <div class="test-section">
                <h3>Teste 5: Validação com JSON</h3>
                <p>Simula um POST com token no body JSON.</p>
                <button onclick="testarComJson()">📄 Validar via JSON</button>
            </div>

            <h2>📚 Informações Técnicas</h2>

            <div class="info">
                <strong>Como funciona:</strong>
                <ul>
                    <li>Cada sessão recebe um token CSRF único (64 caracteres)</li>
                    <li>Token é regenerado a cada 1 hora</li>
                    <li>Validação usa <code>hash_equals()</code> (timing-safe)</li>
                    <li>Token pode estar em: POST, JSON body ou header HTTP</li>
                    <li>GET/HEAD/OPTIONS não exigem CSRF</li>
                </ul>
            </div>

            <div class="info">
                <strong>Leia a documentação completa:</strong>
                <a href="../CSRF_PROTECTION.md">CSRF_PROTECTION.md</a>
            </div>
        </div>

        <script>
            async function testarComHeader() {
                try {
                    const response = await fetch('test_csrf.php', {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-Token': document.querySelector('.token-box').textContent.trim()
                        },
                        body: JSON.stringify({ test: 'header' })
                    });
                    
                    const data = await response.json();
                    alert(data.message || 'Teste concluído');
                } catch (err) {
                    alert('Erro: ' + err.message);
                }
            }

            async function testarComJson() {
                try {
                    const response = await fetch('test_csrf.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            test: 'json',
                            _csrf_token: document.querySelector('.token-box').textContent.trim()
                        })
                    });
                    
                    const data = await response.json();
                    alert(data.message || 'Teste concluído');
                } catch (err) {
                    alert('Erro: ' + err.message);
                }
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Processar testes
header('Content-Type: application/json; charset=utf-8');

$test = $_POST['test'] ?? $_GET['test'] ?? '';

switch ($test) {
    case 'valid_token':
        try {
            viabixValidateCsrfToken();
            echo json_encode([
                'success' => true,
                'message' => '✅ Token CSRF válido! Validação bem-sucedida.'
            ]);
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Erro: ' . $e->getMessage()
            ]);
        }
        break;

    case 'no_token':
        try {
            viabixValidateCsrfToken();
            echo json_encode([
                'success' => false,
                'message' => '❌ FALHA: Token foi aceito quando deveria ser rejeitado!'
            ]);
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode([
                'success' => true,
                'message' => '✅ Token corretamente rejeitado: ' . $e->getMessage()
            ]);
        }
        break;

    case 'invalid_token':
        try {
            viabixValidateCsrfToken();
            echo json_encode([
                'success' => false,
                'message' => '❌ FALHA: Token inválido foi aceito!'
            ]);
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode([
                'success' => true,
                'message' => '✅ Token inválido corretamente rejeitado: ' . $e->getMessage()
            ]);
        }
        break;

    case 'header':
    case 'json':
        try {
            viabixValidateCsrfToken();
            echo json_encode([
                'success' => true,
                'message' => '✅ Validação via ' . ucfirst($test) . ' bem-sucedida!'
            ]);
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => '❌ Validação falhou: ' . $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Teste não especificado'
        ]);
}
