<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $appName }} - Nova mensagem de contato</title>
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

    .contact-info {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 5px;
      margin: 20px 0;
    }

    .contact-info h3 {
      margin-top: 0;
      color: #0D6EFD;
    }

    .contact-info ul {
      list-style: none;
      padding: 0;
    }

    .contact-info li {
      margin-bottom: 10px;
    }

    .contact-info strong {
      color: #495057;
    }

    .message-box {
      background-color: #f8f9fa;
      border-left: 4px solid #0D6EFD;
      padding: 15px;
      margin: 20px 0;
      font-style: italic;
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

    .btn:hover {
      background-color: #0b5ed7;
    }
  </style>
</head>

<body>
  <div class='container'>
    <div class='header'>
      <h1>{{ $appName }}</h1>
      <p>Nova mensagem de contato recebida</p>
    </div>
    <div class='content'>
      <p>Olá,</p>
      <p>Uma nova mensagem de contato foi recebida através do formulário de suporte. Aqui estão os detalhes:</p>

      <div class="contact-info">
        <h3>Informações do Contato</h3>
        <ul>
          <li><strong>Nome:</strong> {{ $contactData[ 'first_name' ] ?? '' }} {{ $contactData[ 'last_name' ] ?? '' }}</li>
          <li><strong>E-mail:</strong> {{ $contactData[ 'email' ] }}</li>
          <li><strong>Assunto:</strong> {{ $contactData[ 'subject' ] }}</li>
          <li><strong>Data/Hora:</strong> {{ now()->format( 'd/m/Y H:i:s' ) }}</li>
          @if( $tenant )
            <li><strong>Empresa:</strong> {{ $tenant->name }}</li>
          @endif
        </ul>
      </div>

      <div class="message-box">
        <strong>Mensagem:</strong><br>
        {{ $contactData[ 'message' ] }}
      </div>

      <p>Por favor, responda ao contato o mais breve possível para manter um bom atendimento ao cliente.</p>

      <hr>
      <a href="{{ $supportUrl }}" class="btn">Acessar Sistema de Suporte</a>
    </div>
    <div class='footer'>
      <p>Este é um e-mail automático enviado pelo sistema {{ $appName }}.</p>
      <p>&copy; {{ date( 'Y' ) }} {{ $appName }}.<br>Todos os direitos reservados.</p>
    </div>
  </div>

</body>

</html>
