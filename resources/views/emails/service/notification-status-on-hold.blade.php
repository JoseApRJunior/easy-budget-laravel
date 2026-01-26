<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Easy Budget - Serviço em Espera</title>
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
            background-color: #6c757d;
            /* Cinza para "em espera" */
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
            color: white !important;
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
            <h1>Serviço em Espera</h1>
        </div>
        <div class='content'>
            <p>Olá, {{ $emailData[ 'first_name' ] }}.</p>
            <p>Gostaríamos de informar que o serviço <strong>#{{ $emailData[ 'service_code' ] }}</strong> foi colocado em
                espera
                temporariamente.</p>
            <p>Isso pode ocorrer por diversos motivos, como a necessidade de aguardar uma peça ou uma definição.
                Entraremos em
                contato em breve com mais detalhes.</p>

            <p>Você pode visualizar o status atual do serviço no link abaixo.</p>
            <a href="{{ $emailData[ 'link' ] }}" class="btn">Ver Status do Serviço</a>
            <p style="margin-top: 20px;">Agradecemos a sua compreensão.</p>
            <p style="margin-top: 20px;">Se tiver alguma dúvida, entre em contato conosco.</p>
            <p>
                <strong>{{ $company[ 'company_name' ] }}</strong><br>
                Email: {{ $company[ 'email_business' ] ?? $company[ 'email' ] }}<br>
                Telefone: {{ $company[ 'phone_business' ] ?? $company[ 'phone' ] }}
            </p>
            <hr>
            <a href="{{ $urlSuporte }}" class="btn">Suporte Easy Budget</a>
        </div>
        <div class='footer'>
            <p>Este é um e-mail automático, por favor não responda.</p>
            <p>&copy; {{ date( "Y" ) }} Easy Budget.<br>Todos os direitos reservados.</p>
        </div>
    </div>
</body>

</html>
