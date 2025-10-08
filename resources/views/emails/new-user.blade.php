<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Budget - Confirme sua conta</title>
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
        <div class='header' style="background-color: #4a69bd; color: white; text-align: center; padding: 25px;">
            <h1 style="margin: 0; font-size: 28px;">Bem-vindo ao Easy Budget!</h1>
        </div>
        <div class='content'>
            <p>Olá, {{ $first_name }}.</p>
            <p>Bem-vindo ao Easy Budget! Obrigado por se cadastrar. Para ativar sua conta, por favor clique no botão
                abaixo:
            </p>
            <p style="text-align: center;">
                <a href="{{ $confirmationLink }}" class="btn">Confirmar minha conta</a>
            </p>
            <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
            <p><a href="{{ $confirmationLink }}">{{ $confirmationLink }}</a></p>
            <p>Este link expirará em 30 minutos.</p>
            <p>Se você não se cadastrou no Easy Budget, por favor ignore este e-mail.</p>
        </div>
        <div class='footer'>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ date( "Y" ) }} Easy Budget.<br>Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>
