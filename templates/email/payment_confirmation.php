<?php
/**
 * Email Template: Payment Confirmation
 * Sent when payment is received
 */

return [
    'subject' => 'Pagamento Confirmado ✓ - {{company_name}}',
    'html' => '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 30px; text-align: center; border-radius: 5px; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 5px; }
        .receipt { background: white; border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .receipt-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .receipt-row.total { border-bottom: 2px solid #333; font-weight: bold; font-size: 1.2em; }
        .status-badge { display: inline-block; background: #28a745; color: white; padding: 5px 15px; border-radius: 20px; font-weight: bold; }
        .footer { text-align: center; color: #888; font-size: 0.9em; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Pagamento Confirmado</h1>
        </div>
        
        <div class="content">
            <p>Olá {{name}},</p>
            
            <p>Recebemos seu pagamento com sucesso! Aqui está o comprovante:</p>
            
            <div class="receipt">
                <div style="text-align: center; margin-bottom: 20px;">
                    <span class="status-badge">PAGO</span>
                </div>
                
                <div class="receipt-row">
                    <span>Número da Fatura:</span>
                    <strong>{{invoice_id}}</strong>
                </div>
                
                <div class="receipt-row">
                    <span>Data:</span>
                    <strong>{{date}}</strong>
                </div>
                
                <div class="receipt-row">
                    <span>Moeda:</span>
                    <strong>{{currency}}</strong>
                </div>
                
                <div class="receipt-row total">
                    <span>Valor:</span>
                    <strong>{{amount}}</strong>
                </div>
            </div>
            
            <p><strong>✓ Próximas Etapas:</strong></p>
            <ul>
                <li>Sua assinatura foi ativada</li>
                <li>Você pode começar a usar todas as funcionalidades</li>
                <li>Você receberá uma nota fiscal em breve</li>
            </ul>
            
            <p>Obrigado por sua confiança!</p>
        </div>
        
        <div class="footer">
            <p>© 2024 {{company_name}}. Todos os direitos reservados.</p>
            <p>Este é um email automático. Por favor, não responda.</p>
        </div>
    </div>
</body>
</html>
',
    'text' => 'Pagamento Confirmado

Olá {{name}},

Seu pagamento foi confirmado!

Fatura: {{invoice_id}}
Data: {{date}}
Valor: {{amount}} {{currency}}

Sua assinatura agora está ativa.

Obrigado!'
];
?>
