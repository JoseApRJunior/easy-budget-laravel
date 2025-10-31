<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appName }} - Confirmação de recebimento</title>
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
    <div class='header'>
      <h1>{{ $appName }}</h1>
    </div>
    <div class='content'>
      <p>Olá{{ $confirmationData[ 'first_name' ] ? ' ' . $confirmationData[ 'first_name' ] : '' }},</p>

      <p>Recebemos sua mensagem de contato com sucesso!</p>

      <p>Aqui estão os detalhes da sua solicitação:</p>
      <ul>
        <li><strong>Assunto:</strong> {{ $confirmationData[ 'subject' ] }}</li>
        <li><strong>Enviada em:</strong> {{ $confirmationData[ 'submitted_at' ] }}</li>
        <li><strong>Protocolo:</strong> #{{ $confirmationData[ 'support_id' ] }}</li>
      </ul>

      <p>Nossa equipe irá analisar sua solicitação e entrará em contato o mais breve possível.</p>

      <p>Para acompanhar o status da sua solicitação, você pode usar o protocolo #{{ $confirmationData[ 'support_id' ] }}.
      </p>

      <hr>
      <a href="{{ $appUrl }}/support" class="btn">Enviar Nova Mensagem</a>
    </div>
    <div class='footer'>
      <p>Este é um e-mail automático, por favor não responda.</p>
      <p>&copy; {{ date( 'Y' ) }} {{ $appName }}.<br>Todos os direitos reservados.</p>
    </div>
  </div>

</body>

</html>
