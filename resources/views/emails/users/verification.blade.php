<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Confirme sua conta - {{ config( 'app.name', 'Easy Budget' ) }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f5;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .email-wrap {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: #0d6efd;
            color: #fff;
            text-align: center;
            padding: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
        }

        .content {
            padding: 24px;
            font-size: 15px;
            line-height: 1.5;
            color: #1f2937;
        }

        .btn {
            display: inline-block;
            background: #0d6efd;
            color: #fff;
            text-decoration: none;
            padding: 12px 18px;
            border-radius: 6px;
            font-weight: 600;
        }

        .panel {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
            margin-top: 18px;
            font-size: 13px;
            color: #6b7280;
        }

        .subcopy {
            word-break: break-all;
            font-family: monospace;
            background: #ffffff;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            display: inline-block;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #6b7280;
            padding: 16px;
            background: #ffffff;
        }

        .notice {
            background: #ecfdf5;
            border: 1px solid #34d399;
            color: #065f46;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .notice .icon {
            width: 20px;
            height: 20px;
            display: inline-block;
            border-radius: 50%;
            background: #10b981;
            color: #fff;
            text-align: center;
            line-height: 20px;
            font-size: 14px;
        }

        @media (max-width:420px) {
            .content {
                padding: 16px;
            }

            .header h1 {
                font-size: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrap">
        <div class="header">
            <h1>{{ config( 'app.name', 'Easy Budget' ) }}</h1>
        </div>

        <div class="content">
            @if( !empty( $resent ) )
                <div class="notice" role="status">
                    <span class="icon">✓</span>
                    <div>
                        E-mail de verificação reenviado com sucesso. Verifique sua caixa de entrada.
                        @if( !empty( $resentAt ) )
                            <div style="font-weight:400; font-size:13px; margin-top:6px;">Enviado em: {{ $resentAt }}</div>
                        @endif
                    </div>
                </div>
            @endif

            <p>Olá <strong>{{ $first_name ?? 'usuário' }}</strong>,</p>

            <p>Obrigado por se cadastrar. Para ativar sua conta, clique no botão abaixo.</p>

            <p style="text-align:center; margin:20px 0;">
                <a href="{{ $confirmationLink ?? '#' }}" class="btn" target="_blank" rel="noopener noreferrer">
                    Confirmar minha conta
                </a>
            </p>

            <p>Se o botão acima não funcionar, copie e cole o seguinte link no seu navegador:</p>
            <p class="subcopy">{{ $confirmationLink ?? config( 'app.url' ) . '/login' }}</p>

            <p style="margin-top:18px;">Este link expira em 30 minutos.</p>

            <p>Se você não se cadastrou no {{ config( 'app.name', 'Easy Budget' ) }}, por favor ignore este e‑mail.</p>

            <div class="panel">
                Este é um e‑mail automático, por favor não responda.
            </div>
        </div>

        <div class="footer">
            © {{ date( 'Y' ) }} {{ config( 'app.name', 'Easy Budget' ) }}. Todos os direitos reservados.
            @if( !empty( $supportEmail ) )<br>Suporte: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif
        </div>
    </div>
</body>

</html>
