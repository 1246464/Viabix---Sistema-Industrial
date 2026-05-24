<?php
/**
 * Email Template: Invoice
 * Sent when invoice is generated
 */

return [
    'subject' => 'Sua Fatura - {{company_name}}',
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
        .invoice-details { background: white; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sua Fatura 📄</h1>
        </div>
        
        <div class="content">
            <p>Olá {{name}},</p>
            
            <p>Segue em anexo sua fatura do período. Você pode visualizá-la ou fazer o download usando o link abaixo:</p>
            
            <div class="invoice-details">
                <p><strong>Número da Fatura:</strong> {{invoice_id}}</p>
                <p><strong>Data:</strong> {{date}}</p>
                <p style="text-align: center;">
                    <a href="{{invoice_url}}" class="button">Visualizar Fatura</a>
                </p>
            </div>
            
            <p><strong>Informações Importantes:</strong></p>
            <ul>
                <li>Guarde sua fatura para fins fiscais e de contabilidade</li>
                <li>Se tiver dúvidas, consulte nosso suporte</li>
                <li>Você pode acessar suas faturas anteriores no seu dashboard</li>
            </ul>
            
            <p>Obrigado por ser um cliente {{company_name}}!</p>
        </div>
        
        <div class="footer">
            <p>© 2024 {{company_name}}. Todos os direitos reservados.</p>
            <p>Este é um email automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
',
    'text' => 'Sua Fatura

Olá {{name}},

Segue sua fatura {{invoice_id}}.

Visualize em: {{invoice_url}}

Obrigado!'
];
?>
