<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificação de Orçamento - Easy Budget</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 5px 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Easy Budget</h1>
        <p>Notificação de Orçamento</p>
    </div>
    
    <div class="content">
        <h2>Olá, {{ $customerName }}!</h2>
        
        <p>Seu orçamento foi {{ $status }}.</p>
        
        <p><strong>Detalhes do Orçamento:</strong></p>
        <ul>
            <li><strong>Número:</strong> #{{ $budgetNumber }}</li>
            <li><strong>Data:</strong> {{ $budgetDate }}</li>
            <li><strong>Valor Total:</strong> R$ {{ number_format($totalValue, 2, ',', '.') }}</li>
        </ul>
        
        @if($message)
        <p><strong>Mensagem:</strong></p>
        <p>{{ $message }}</p>
        @endif
        
        <p>
            <a href="{{ $budgetUrl }}" class="btn">Ver Orçamento</a>
        </p>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} Easy Budget. Todos os direitos reservados.</p>
        <p>Este é um e-mail automático, não responda.</p>
    </div>
</body>
</html>