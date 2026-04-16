<?php
/**
 * Email Template: Welcome
 * Sent to new users on account creation
 */

return [
    'subject' => 'Bem-vindo à {{company_name}}! 🎉',
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
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Bem-vindo, {{name}}! 🚀</h1>
        </div>
        
        <div class="content">
            <p>Obrigado por criar sua conta no {{company_name}}!</p>
            
            <p>Você agora tem acesso a:</p>
            <ul>
                <li>💼 Dashboard completo para gerenciar seus dados</li>
                <li>📊 Relatórios e análises em tempo real</li>
                <li>🔐 Segurança de nível empresarial</li>
                <li>⚡ Performance otimizada e confiável</li>
            </ul>
            
            <p>Para começar, acesse sua conta:</p>
            <p style="text-align: center;">
                <a href="{{login_url}}" class="button">Acessar Minha Conta</a>
            </p>
            
            <p>Se você não criou essa conta, ignore este email.</p>
        </div>
        
        <div class="footer">
            <p>© 2024 {{company_name}}. Todos os direitos reservados.</p>
            <p>Este é um email automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
',
    'text' => 'Bem-vindo ao {{company_name}}!

Obrigado por criar sua conta!

Acesse sua conta em: {{login_url}}

Se você não criou essa conta, ignore este email.'
];
?>
