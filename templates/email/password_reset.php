<?php
/**
 * Email Template: Password Reset
 * Sent when user requests password reset
 */

return [
    'subject' => 'Redefinir sua senha - {{company_name}}',
    'html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 30px; text-align: center; border-radius: 5px; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; }
        .button { display: inline-block; background: #f5576c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 3px; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 30px; }
        .token { background: #f0f0f0; padding: 10px; border-radius: 3px; font-family: monospace; word-break: break-all; font-size: 0.85em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Redefinir Senha 🔐</h1>
        </div>
        
        <div class="content">
            <p>Olá {{name}},</p>
            
            <p>Recebemos uma solicitação para redefinir a senha da sua conta {{company_name}}.</p>
            
            <p>Clique no link abaixo para redefinir sua senha:</p>
            <p style="text-align: center;">
                <a href="{{reset_url}}" class="button">Redefinir Senha</a>
            </p>
            
            <p>Ou copie este código:</p>
            <div class="token">{{token}}</div>
            
            <div class="warning">
                <strong>⚠️ Importante:</strong>
                <ul style="margin: 10px 0;">
                    <li>Este link expira em {{expiry_hours}} horas</li>
                    <li>Se você não solicitou isso, ignore este email</li>
                    <li>Nunca compartilhe este código com ninguém</li>
                </ul>
            </div>
            
            <p>Se você não conseguir clicar no link, copie e cole esta URL no seu navegador:<br>
            <span style="background: #f0f0f0; padding: 5px; border-radius: 3px; word-break: break-all; font-size: 0.85em;">{{reset_url}}</span></p>
        </div>
        
        <div class="footer">
            <p>© 2024 {{company_name}}. Todos os direitos reservados.</p>
            <p>Este é um email automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
',
    'text' => 'Redefinir sua senha

Olá {{name}},

Clique neste link para redefinir sua senha:
{{reset_url}}

Este link expira em {{expiry_hours}} horas.

Se você não solicitou isto, ignore este email.

Código: {{token}}'
];
?>
