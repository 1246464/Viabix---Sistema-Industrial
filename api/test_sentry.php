<?php
/**
 * Teste de Integração Sentry
 * 
 * Acesse: http://localhost/ANVI/api/test_sentry.php
 * 
 * Este arquivo testa a integração com Sentry e mostra exemplos de uso.
 */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Teste de Sentry</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
            .container { border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
            button { background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; margin: 5px; font-size: 16px; }
            button:hover { background: #0056b3; }
            .info { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 12px; margin: 15px 0; }
            .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px; margin: 15px 0; }
            .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 15px 0; }
            .success { background: #d4edda; border-left: 4px solid #28a745; padding: 12px; margin: 15px 0; }
            code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
            pre { background: #f4f4f4; padding: 12px; border-radius: 4px; overflow-x: auto; }
            h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
            .status { padding: 10px; border-radius: 4px; margin: 10px 0; }
            .status.enabled { background: #d4edda; color: #155724; }
            .status.disabled { background: #f8d7da; color: #721c24; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 Teste de Integração Sentry</h1>

            <?php
            $sentry = ViabixSentry::getInstance();
            $sentryEnabled = $sentry->isEnabled();
            $dsnStatus = getenv('SENTRY_DSN') ?: 'NÃO CONFIGURADA';
            ?>

            <div class="status <?php echo $sentryEnabled ? 'enabled' : 'disabled'; ?>">
                <strong>Status Sentry:</strong>
                <?php echo $sentryEnabled ? '✅ ATIVADA' : '❌ DESATIVADA'; ?>
                <br>
                <small>DSN: <?php echo $dsnStatus; ?></small>
            </div>

            <h2>📋 Testes Disponíveis</h2>

            <form method="POST">
                <button type="submit" name="test" value="message_info" style="background: #17a2b8;">
                    ℹ️ Enviar Mensagem Info
                </button>

                <button type="submit" name="test" value="message_warning" style="background: #ffc107; color: #333;">
                    ⚠️ Enviar Mensagem Warning
                </button>

                <button type="submit" name="test" value="message_error" style="background: #dc3545;">
                    ❌ Enviar Mensagem Error
                </button>

                <button type="submit" name="test" value="exception" style="background: #721c24;">
                    💥 Lançar Exceção
                </button>

                <button type="submit" name="test" value="breadcrumbs" style="background: #6c757d;">
                    🔗 Enviar com Breadcrumbs
                </button>

                <button type="submit" name="test" value="context" style="background: #17a2b8;">
                    👤 Enviar com Contexto de Usuário
                </button>

                <button type="submit" name="test" value="full" style="background: #28a745;">
                    ✨ Teste Completo
                </button>
            </form>

            <h2>📚 Documentação Rápida</h2>

            <div class="info">
                <strong>Verificar se Sentry está habilitada:</strong>
                <pre><?php echo htmlspecialchars('$sentry = ViabixSentry::getInstance();
if ($sentry->isEnabled()) {
    // Sentry está configurada
}'); ?></pre>
            </div>

            <div class="info">
                <strong>Enviar mensagem:</strong>
                <pre><?php echo htmlspecialchars('viabix_sentry_message("Operação realizada", "info", "user.action");'); ?></pre>
            </div>

            <div class="info">
                <strong>Capturar exceção:</strong>
                <pre><?php echo htmlspecialchars('try {
    // código
} catch (Exception $e) {
    viabix_sentry_exception($e, "error");
}'); ?></pre>
            </div>

            <div class="info">
                <strong>Adicionar contexto do usuário:</strong>
                <pre><?php echo htmlspecialchars('viabix_sentry_set_user(
    $userId,
    $email,
    $username
);'); ?></pre>
            </div>

            <div class="warning">
                <strong>⚠️ Para produção:</strong>
                Configure <code>SENTRY_DSN</code> no arquivo <code>.env</code> com sua chave real do Sentry.io
            </div>

            <div class="info">
                <strong>📖 Leia a documentação completa em <code>MONITORING.md</code></strong>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Processar testes
$test = $_POST['test'] ?? '';
$result = null;

switch ($test) {
    case 'message_info':
        $eventId = viabix_sentry_message('Teste de mensagem info - Sistema OK', 'info', 'test');
        $result = ['type' => 'success', 'message' => "Mensagem info enviada (ID: $eventId)"];
        break;

    case 'message_warning':
        $eventId = viabix_sentry_message('Teste de mensagem warning - Atenção necessária', 'warning', 'test');
        $result = ['type' => 'warning', 'message' => "Mensagem warning enviada (ID: $eventId)"];
        break;

    case 'message_error':
        $eventId = viabix_sentry_message('Teste de mensagem error - Algo deu errado', 'error', 'test');
        $result = ['type' => 'error', 'message' => "Mensagem error enviada (ID: $eventId)"];
        break;

    case 'exception':
        try {
            throw new RuntimeException('Esta é uma exceção de teste do Sentry', 1001);
        } catch (Exception $e) {
            $eventId = viabix_sentry_exception($e, 'error', ['test' => true]);
            $result = ['type' => 'error', 'message' => "Exceção capturada e enviada (ID: $eventId)"];
        }
        break;

    case 'breadcrumbs':
        viabix_sentry_breadcrumb('Ação 1 - Usuário abriu página', 'user.action', 'info');
        viabix_sentry_breadcrumb('Ação 2 - Clicou em botão', 'user.action', 'info');
        viabix_sentry_breadcrumb('Ação 3 - Submeteu formulário', 'system.api', 'info');
        
        $eventId = viabix_sentry_message('Teste com breadcrumbs', 'info', 'test', [
            'actions' => 3,
        ]);
        $result = ['type' => 'success', 'message' => "Evento com 3 breadcrumbs enviado (ID: $eventId)"];
        break;

    case 'context':
        viabix_sentry_set_user(12345, 'teste@example.com', 'usuario_teste');
        viabix_sentry_set_tenant('tenant-001', 'Empresa Teste');
        viabix_sentry_tag('campaign', 'test');
        
        $eventId = viabix_sentry_message('Teste com contexto de usuário e tenant', 'info', 'test');
        $result = ['type' => 'success', 'message' => "Evento com contexto enviado (ID: $eventId)"];
        break;

    case 'full':
        // Simulação completa
        viabix_sentry_set_user(99999, 'admin@viabix.com', 'administrador');
        viabix_sentry_set_tenant('tenant-full-test', 'Empresa Full Test');
        viabix_sentry_tag('environment', 'test');
        viabix_sentry_tag('version', '1.0.0');
        
        viabix_sentry_breadcrumb('Teste iniciado', 'test', 'info');
        viabix_sentry_breadcrumb('Validação passada', 'test', 'info');
        viabix_sentry_breadcrumb('Operação completada', 'test', 'info');
        
        $eventId = viabix_sentry_message(
            'Teste completo de Sentry com todos os contextos',
            'info',
            'test.complete',
            [
                'test_time' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
            ]
        );
        $result = ['type' => 'success', 'message' => "Teste completo enviado (ID: $eventId)"];
        break;

    default:
        $result = ['type' => 'error', 'message' => 'Teste inválido'];
}

// Retornar resultado
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'success' => $result['type'] !== 'error',
    'result' => $result,
    'sentry_enabled' => $sentry->isEnabled(),
    'timestamp' => date('Y-m-d H:i:s'),
]);
