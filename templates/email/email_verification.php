<?php
/**
 * Email Template: Email Verification
 * Sent when user needs to verify their email address
 */

return [
    'subject' => 'Confirme seu endereço de email - {{company_name}}',
    'html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 5px; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; }
        .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .info { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; border-radius: 3px; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 30px; }
        .code { font-size: 2em; letter-spacing: 5px; font-weight: bold; color: #667eea; text-align: center; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Confirme seu Email ✉️</h1>
        </div>
        
        <div class="content">
            <p>Olá {{name}},</p>
            
            <p>Obrigado por se registrar no {{company_name}}! Para ativar sua conta, confirme seu endereço de email clicando no botão abaixo:</p>
            
            <p style="text-align: center;">
                <a href="{{verify_url}}" class="button">Confirmar Email</a>
            </p>
            
            <p style="text-align: center;">Ou use este código:</p>
            <div class="code">{{token}}</div>
            
            <div class="info">
                <strong>✓ O que fazer agora:</strong>
                <ol style="margin: 10px 0;">
                    <li>Clique no botão acima para confirmar seu email</li>
                    <li>Você receberá um email de boas-vindas com instruções</li>
                    <li>Faça seu primeiro login e comece a usar!</li>
                </ol>
            </div>
            
            <p><strong>Precisa de ajuda?</strong> Se o botão não funcionar, copie este link:<br>
            <span style="background: #f0f0f0; padding: 5px; border-radius: 3px; word-break: break-all; font-size: 0.85em;">{{verify_url}}</span></p>
        </div>
        
        <div class="footer">
            <p>© 2024 {{company_name}}. Todos os direitos reservados.</p>
            <p>Este é um email automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
',
    'text' => 'Confirme seu email

Olá {{name}},

Clique neste link para confirmar seu email:
{{verify_url}}

Ou use este código: {{token}}

Se você não se registrou, ignore este email.'
];
?>
