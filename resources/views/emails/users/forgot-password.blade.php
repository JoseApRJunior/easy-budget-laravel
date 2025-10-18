<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Budget - Nova Senha</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            background-color: #0D6EFD;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .content {
            padding: 30px;
        }

        .password-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            font-size: 0.9em;
            color: #6c757d;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #0D6EFD;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <div class='container'>
        <div class='header'>
            <h1>Easy Budget - Nova Senha</h1>
        </div>
        <div class='content'>
            <p>Olá {{ $first_name }},</p>
            <p>Você solicitou uma redefinição de senha para sua conta no Easy Budget.</p>
            <p>Clique no botão abaixo para redefinir sua senha. Este link é válido por 1 hora:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{{ $reset_link }}" class="btn" style="background-color: #0D6EFD;">Redefinir Senha</a>
            </p>
            <p><strong>Link de redefinição:</strong> <a href="{{ $reset_link }}">{{ $reset_link }}</a></p>
            <p><strong>Válido até:</strong> {{ $expires_at }}</p>
            <p>Se você não solicitou esta redefinição, por favor, ignore este e-mail.</p>
        </div>
        <div class='footer'>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ date( "Y" ) }} Easy Budget.<br>Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>
