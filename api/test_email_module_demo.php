<?php
/**
 * DEMONSTRAÇÃO DO MÓDULO 3 - EMAIL DELIVERY COM SENDGRID
 * 
 * Este arquivo mostra como o módulo de email funciona na prática.
 * Execute: curl http://localhost:8000/api/test_email_module_demo.php?action=demo
 */

require_once 'config.php';
require_once 'email.php';

header('Content-Type: application/json; charset=utf-8');

// ============================================================
// CONFIGURAÇÃO
// ============================================================

// Para este teste, usamos email de teste (não precisa de SENDGRID_API_KEY real)
$DEMO_MODE = true; // Simula envios sem API key real

// ============================================================
// FUNÇÃO AUXILIAR: Simular envio de email
// ============================================================

function simulateEmailSend($email, $subject, $template) {
    global $DEMO_MODE;
    
    if ($DEMO_MODE) {
        // Em modo demo, apenas simular que funcionaria
        return [
            'success' => true,
            'message' => "Email simulado",
            'email' => $email,
            'subject' => $subject,
            'template' => $template,
            'timestamp' => date('Y-m-d H:i:s'),
            'mode' => 'SIMULADO (sem API key real)',
        ];
    }
    
    // Código real usaria SendGrid API
    return sendEmail($email, $subject, $template);
}

// ============================================================
// AÇÕES DISPONÍVEIS
// ============================================================

$action = $_GET['action'] ?? 'menu';
$response = [];

switch ($action) {
    
    case 'demo':
        // DEMONSTRAÇÃO COMPLETA
        $response = [
            'titulo' => '🎉 DEMONSTRAÇÃO DO MÓDULO 3 - EMAIL DELIVERY',
            'status' => 'DEMONSTRAÇÃO INTERATIVA',
            'opcoes' => [
                '1' => [
                    'nome' => 'Email de Boas-vindas',
                    'descricao' => 'Simula envio de email de bem-vindo para novo usuário',
                    'url' => '?action=demo_welcome',
                    'exemplo' => 'Enviado para: novo_usuario@example.com',
                ],
                '2' => [
                    'nome' => 'Email de Reset de Senha',
                    'descricao' => 'Simula envio de link de reset de senha',
                    'url' => '?action=demo_password_reset',
                    'exemplo' => 'Enviado para: usuario@example.com',
                ],
                '3' => [
                    'nome' => 'Email de Confirmação 2FA',
                    'descricao' => 'Simula envio de código OTP para 2FA',
                    'url' => '?action=demo_2fa',
                    'exemplo' => 'Código: 123456',
                ],
                '4' => [
                    'nome' => 'Email de Convite de Colaborador',
                    'descricao' => 'Simula convite para colaborador do projeto',
                    'url' => '?action=demo_invite',
                    'exemplo' => 'Enviado para: colaborador@example.com',
                ],
                '5' => [
                    'nome' => 'Ver Configuração do Módulo',
                    'descricao' => 'Mostra como o módulo está configurado',
                    'url' => '?action=show_config',
                    'exemplo' => 'Variáveis de configuração',
                ],
            ],
            'modo' => $DEMO_MODE ? '🟡 MODO SIMULADO (sem API real)' : '🟢 MODO PRODUÇÃO (com API real)',
            'instrucoes' => [
                'Escolha uma opção acima clicando na URL',
                'Veja como o email seria enviado',
                'Na produção, o SendGrid envia de verdade',
            ],
        ];
        break;
    
    case 'demo_welcome':
        // EMAIL DE BOAS-VINDAS
        $usuario_email = $_GET['email'] ?? 'novo_usuario@example.com';
        $usuario_nome = $_GET['nome'] ?? 'Novo Usuário';
        
        $response = [
            'tipo' => 'Email de Boas-vindas',
            'destinatario' => $usuario_email,
            'nome_usuario' => $usuario_nome,
            'assunto' => 'Bem-vindo ao VIABIX!',
            'template' => 'welcome',
            'dados_email' => [
                'user_name' => $usuario_nome,
                'login_url' => 'https://viabix.app/login',
                'docs_url' => 'https://docs.viabix.app',
            ],
            'conteudo_html' => <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px;">
    <h1 style="color: #4CAF50;">Bem-vindo ao VIABIX, {$usuario_nome}! 🎉</h1>
    
    <p>Sua conta foi criada com sucesso.</p>
    
    <p>Agora você pode:</p>
    <ul>
        <li>Criar e gerenciar projetos</li>
        <li>Analisar dados de produção</li>
        <li>Colaborar com sua equipe</li>
    </ul>
    
    <a href="https://viabix.app/login" style="background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">
        Acessar VIABIX
    </a>
    
    <p style="margin-top: 30px; color: #666; font-size: 12px;">
        Equipe VIABIX<br>
        suporte@viabix.app
    </p>
</div>
HTML,
            'resultado_simulacao' => simulateEmailSend($usuario_email, 'Bem-vindo ao VIABIX!', 'welcome'),
        ];
        break;
    
    case 'demo_password_reset':
        // EMAIL DE RESET DE SENHA
        $usuario_email = $_GET['email'] ?? 'usuario@example.com';
        $reset_token = bin2hex(random_bytes(32));
        $reset_link = "https://viabix.app/reset?token={$reset_token}&email=" . urlencode($usuario_email);
        
        $response = [
            'tipo' => 'Email de Reset de Senha',
            'destinatario' => $usuario_email,
            'assunto' => 'Redefinir sua senha VIABIX',
            'template' => 'password_reset',
            'dados_email' => [
                'reset_token' => substr($reset_token, 0, 16) . '...',
                'reset_link' => $reset_link,
                'expiracao' => '1 hora',
                'validade' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ],
            'conteudo_html' => <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px;">
    <h2 style="color: #FF9800;">Redefinir sua senha</h2>
    
    <p>Você solicitou para redefinir sua senha do VIABIX.</p>
    
    <p style="background: #FFF3E0; padding: 15px; border-left: 4px solid #FF9800; border-radius: 3px;">
        ⚠️ Este link expira em <strong>1 hora</strong>.<br>
        Se você não solicitou esta redefinição, ignore este email.
    </p>
    
    <a href="{$reset_link}" style="background: #FF9800; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">
        Redefinir Senha
    </a>
    
    <p style="margin-top: 20px; color: #999; font-size: 12px;">
        Ou copie este link: {$reset_link}
    </p>
    
    <p style="color: #666; font-size: 12px;">
        Equipe VIABIX<br>
        suporte@viabix.app
    </p>
</div>
HTML,
            'resultado_simulacao' => simulateEmailSend($usuario_email, 'Redefinir sua senha VIABIX', 'password_reset'),
        ];
        break;
    
    case 'demo_2fa':
        // EMAIL DE 2FA
        $usuario_email = $_GET['email'] ?? 'usuario@example.com';
        $otp_code = mt_rand(100000, 999999);
        
        $response = [
            'tipo' => 'Email de Verificação 2FA',
            'destinatario' => $usuario_email,
            'assunto' => 'Seu código de verificação VIABIX',
            'template' => '2fa_otp',
            'dados_email' => [
                'otp_code' => $otp_code,
                'validade_minutos' => 10,
                'expiracao' => date('Y-m-d H:i:s', strtotime('+10 minutes')),
            ],
            'conteudo_html' => <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px;">
    <h2 style="color: #2196F3;">Seu código de verificação</h2>
    
    <p>Você está tentando fazer login no VIABIX.</p>
    
    <div style="background: #E3F2FD; padding: 25px; border-radius: 8px; text-align: center; margin: 20px 0;">
        <p style="font-size: 12px; color: #666; margin: 0 0 10px 0;">Código de verificação:</p>
        <p style="font-size: 32px; font-weight: bold; color: #2196F3; letter-spacing: 5px; margin: 10px 0;">
            {$otp_code}
        </p>
        <p style="font-size: 12px; color: #999; margin: 10px 0 0 0;">Válido por 10 minutos</p>
    </div>
    
    <p style="background: #FFEBEE; padding: 15px; border-left: 4px solid #F44336; border-radius: 3px; color: #C62828;">
        ⚠️ Nunca compartilhe este código com ninguém.<br>
        VIABIX nunca pedirá este código por mensagem ou telefone.
    </p>
    
    <p style="margin-top: 20px; color: #666; font-size: 12px;">
        Equipe VIABIX<br>
        suporte@viabix.app
    </p>
</div>
HTML,
            'resultado_simulacao' => simulateEmailSend($usuario_email, 'Seu código de verificação VIABIX', '2fa_otp'),
        ];
        break;
    
    case 'demo_invite':
        // EMAIL DE CONVITE
        $colaborador_email = $_GET['email'] ?? 'colaborador@example.com';
        $projeto_nome = $_GET['projeto'] ?? 'Projeto ACME';
        $convite_token = bin2hex(random_bytes(16));
        $convite_link = "https://viabix.app/invite?token={$convite_token}";
        
        $response = [
            'tipo' => 'Email de Convite de Colaborador',
            'destinatario' => $colaborador_email,
            'projeto' => $projeto_nome,
            'assunto' => "Você foi convidado para {$projeto_nome}",
            'template' => 'team_invite',
            'dados_email' => [
                'projeto_nome' => $projeto_nome,
                'convite_link' => $convite_link,
                'expiracao' => '7 dias',
            ],
            'conteudo_html' => <<<HTML
<div style="font-family: Arial, sans-serif; max-width: 600px;">
    <h2 style="color: #9C27B0;">Você foi convidado!</h2>
    
    <p>Você foi convidado para colaborar no projeto <strong>{$projeto_nome}</strong> no VIABIX.</p>
    
    <p>Como colaborador, você poderá:</p>
    <ul>
        <li>📊 Visualizar dados e análises</li>
        <li>✏️ Editar projetos</li>
        <li>👥 Convidar mais colaboradores</li>
        <li>📝 Adicionar comentários e anotações</li>
    </ul>
    
    <a href="{$convite_link}" style="background: #9C27B0; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0;">
        Aceitar Convite
    </a>
    
    <p style="margin-top: 30px; color: #666; font-size: 12px;">
        Este convite expira em <strong>7 dias</strong>.<br>
        Equipe VIABIX<br>
        suporte@viabix.app
    </p>
</div>
HTML,
            'resultado_simulacao' => simulateEmailSend($colaborador_email, "Você foi convidado para {$projeto_nome}", 'team_invite'),
        ];
        break;
    
    case 'show_config':
        // MOSTRAR CONFIGURAÇÃO
        $has_api_key = !empty(getenv('SENDGRID_API_KEY'));
        
        $response = [
            'tipo' => 'Configuração do Módulo 3',
            'configuracoes' => [
                'sendgrid_api_key' => [
                    'status' => $has_api_key ? '✅ Configurado' : '❌ Não configurado',
                    'valor' => $has_api_key ? 'SG.' . substr(getenv('SENDGRID_API_KEY'), 3, 10) . '...' : 'Não definido',
                    'como_definir' => 'Adicionar em .env.production ou via deploy',
                ],
                'email_from' => [
                    'valor' => 'noreply@viabix.app',
                    'tipo' => 'Email de saída',
                ],
                'email_support' => [
                    'valor' => 'suporte@viabix.app',
                    'tipo' => 'Email de suporte',
                ],
                'templates_disponiveis' => [
                    'welcome' => 'Email de boas-vindas',
                    'password_reset' => 'Reset de senha',
                    '2fa_otp' => 'Código OTP para 2FA',
                    'team_invite' => 'Convite de colaborador',
                    'billing_invoice' => 'Fatura de cobrança',
                ],
                'rate_limiting' => [
                    'emails_por_usuario_por_hora' => 5,
                    'emails_por_ip_por_hora' => 10,
                    'status' => '✅ Ativo',
                ],
                'modo_atual' => $DEMO_MODE ? '🟡 Simulado (modo teste)' : '🟢 Produção (SendGrid real)',
            ],
            'arquivo_de_configuracao' => 'api/email.php',
            'como_usar' => [
                'Importar' => 'require_once "api/email.php";',
                'Enviar Email' => 'sendEmail("usuario@example.com", "Assunto", "welcome", $dados);',
                'Verificar Limite' => 'viabixCheckEmailRateLimit($_SESSION["user_id"]);',
            ],
        ];
        break;
    
    default:
        $response = [
            'erro' => 'Ação não reconhecida',
            'acao_fornecida' => $action,
            'acoes_validas' => ['demo', 'demo_welcome', 'demo_password_reset', 'demo_2fa', 'demo_invite', 'show_config'],
            'comece_com' => '?action=demo',
        ];
}

// ============================================================
// RETORNAR RESPOSTA
// ============================================================

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
