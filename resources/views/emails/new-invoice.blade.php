<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Sua Fatura {{ $invoice[ 'code' ] }}</title>
</head>

<body style="font-family: Arial, sans-serif; margin: 20px; color: #333;">

    <h2>Olá, {{ $invoice[ 'customer_name' ] }}!</h2>

    <p>Sua fatura de número <strong>{{ $invoice[ 'code' ] }}</strong>, no valor de <strong>R$
            {{ number_format( $invoice[ 'total' ], 2, ',', '.' ) }}</strong>, foi gerada.</p>

    <p>A data de vencimento é <strong>{{ \Carbon\Carbon::parse( $invoice[ 'due_date' ] )->format( 'd/m/Y' ) }}</strong>.</p>

    <!-- Botão de Pagamento -->
    <p>Para visualizar a fatura e efetuar o pagamento de forma segura, clique no botão abaixo:</p>
    <table width="100%" cellspacing="0" cellpadding="0" style="margin: 20px 0;">
        <tr>
            <td>
                <table cellspacing="0" cellpadding="0" align="center">
                    <tr>
                        <td align="center" height="40" bgcolor="#0d6efd" style="border-radius: 5px;">
                            <a href="{{ $public_link }}" target="_blank"
                                style="font-size: 16px; font-weight: bold; color: #ffffff; text-decoration: none; display: inline-block; line-height: 40px; width: 200px;">
                                Visualizar e Pagar
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <p>A fatura está anexada a este e-mail em formato PDF para sua conveniência.</p>

    <p>Se tiver alguma dúvida, por favor, entre em contato conosco.</p>

    <br>

    <p>Atenciosamente,</p>
    <p>
        <strong>{{ $company[ 'company_name' ] }}</strong><br>
        Email: {{ $company[ 'email_business' ] ?? $company[ 'email' ] }}<br>
        Telefone: {{ $company[ 'phone_business' ] ?? $company[ 'phone' ] }}
    </p>

    <hr>
    <div class='footer'>
        <p>Este é um e-mail automático, por favor não responda.</p>
        <p>&copy; {{ date( "Y" ) }} Easy Budget.<br>Todos os direitos reservados.</p>
    </div>
</body>

</html>
