@php
$bgColor = '#e5eaf0'; // Fundo levemente mais escuro para destacar o card branco
$textColor = '#1e293b';
$whiteColor = '#ffffff';
$borderColor = '#cbd5e1'; // Borda mais definida
$primaryColor = config('theme.colors.primary', '#093172');
$contrastColor = '#ffffff';
$inputBgColor = '#f1f5f9'; // Painéis internos um pouco mais contrastados que o branco puro
$smallTextColor = '#334155'; // Texto secundário mais escuro para melhor leitura
$secondaryColor = '#64748b';
$headerBg = $statusColor ?? $primaryColor;
@endphp
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', $title ?? config('app.name', 'Easy Budget'))</title>
    <style>
        /* Reset de estilos para e-mail */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        table {
            border-collapse: collapse !important;
        }

        body {
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }

        /* Estilos base */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            line-height: 1.6;
        }

        .email-wrap {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid <?php echo $borderColor; ?>;
        }

        .header {
            background-color: <?php echo $headerBg; ?>;
            color: <?php echo $contrastColor; ?>;
            text-align: center;
            padding: 30px 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }

        .content {
            padding: 32px;
            font-size: 16px;
            color: <?php echo $textColor; ?>;
        }

        .content h1 {
            font-size: 22px;
            font-weight: 700;
            color: <?php echo $primaryColor; ?>;
            margin-bottom: 20px;
            margin-top: 0;
        }

        .btn {
            display: inline-block;
            background-color: <?php echo $headerBg; ?>;
            color: <?php echo $contrastColor; ?> !important;
            text-decoration: none;
            padding: 14px 28px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
        }

        .panel {
            background-color: <?php echo $inputBgColor; ?>;
            border-radius: 8px;
            padding: 20px;
            margin-top: 24px;
            border: 1px solid <?php echo $borderColor; ?>;
        }

        .panel p {
            margin: 8px 0;
            font-size: 15px;
            color: <?php echo $smallTextColor; ?>;
        }

        .panel strong {
            color: <?php echo $textColor; ?>;
        }

        .subcopy {
            word-break: break-all;
            font-family: Consolas, "Liberation Mono", "Courier New", monospace;
            background-color: <?php echo $bgColor; ?>;
            padding: 12px;
            border: 1px solid <?php echo $borderColor; ?>;
            border-radius: 6px;
            display: block;
            font-size: 12px;
            color: <?php echo $secondaryColor; ?>;
            margin-top: 12px;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: <?php echo $secondaryColor; ?>;
            padding: 24px;
            background-color: <?php echo $bgColor; ?>;
            border-top: 1px solid <?php echo $borderColor; ?>;
        }

        .notice {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-weight: 500;
            font-size: 15px;
            text-align: center;
        }

        @media (max-width:420px) {
            .content {
                padding: 20px;
            }

            .header h1 {
                font-size: 20px;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: <?php echo $bgColor; ?>;">
    <!-- Wrapper de Tabela para melhor compatibilidade de fundo -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 20px 0; background-color: <?php echo $bgColor; ?>;">
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-wrap" style="max-width: 600px; background-color: #ffffff; border: 1px solid <?php echo $borderColor; ?>; border-radius: 12px;">
                    <!-- Header -->
                    <tr>
                        <td class="header" style="background-color: <?php echo $headerBg; ?>; padding: 30px 20px; text-align: center; color: <?php echo $contrastColor; ?>;">
                            @if(!isset($isSystemEmail) || $isSystemEmail === true)
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700;">{{ config('app.name', 'Easy Budget') }}</h1>
                            @elseif(!empty($company['company_name']))
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700;">{{ $company['company_name'] }}</h1>
                            @else
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700;">{{ config('app.name', 'Easy Budget') }}</h1>
                            @endif
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="content" style="padding: 32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td class="footer" style="text-align: center; font-size: 13px; color: <?php echo $secondaryColor; ?>; padding: 24px; background-color: <?php echo $bgColor; ?>; border-top: 1px solid <?php echo $borderColor; ?>;">
                            @if(!isset($isSystemEmail) || $isSystemEmail === true)
                            © {{ date('Y') }} {{ config('app.name', 'Easy Budget') }}.
                            @hasSection('footerExtra')
                            <div style="margin-top: 10px;">@yield('footerExtra')</div>
                            @endif
                            @if(!empty($supportEmail))<br>Suporte: <a href="mailto:{{ $supportEmail }}" style="color: <?php echo $secondaryColor; ?>;">{{ $supportEmail }}</a>@endif
                            @else
                            @if(!empty($company['company_name']))
                            <strong style="color: <?php echo $textColor; ?>;">{{ $company['company_name'] }}</strong><br>
                            @endif
                            @if(!empty($company['address_line1']))
                            {{ $company['address_line1'] }}<br>
                            @endif
                            @if(!empty($company['address_line2']))
                            @php
                            $address2 = $company['address_line2'];
                            if (str_contains($address2, 'CEP:')) {
                            $parts = explode('CEP:', $address2);
                            $cep = trim($parts[1]);
                            $address2 = $parts[0] . 'CEP: ' . \App\Helpers\MaskHelper::formatCEP($cep);
                            }
                            @endphp
                            {!! $address2 !!}<br>
                            @endif
                            @if(!empty($company['phone']) || !empty($company['email']))
                            {{ !empty($company['phone']) ? \App\Helpers\MaskHelper::formatPhone($company['phone']) : '' }} {{ !empty($company['phone']) && !empty($company['email']) ? '|' : '' }} {{ $company['email'] ?? '' }}
                            @endif
                            <p style="margin-top: 10px; font-size: 10px; color: #9ca3af;">
                                Enviado via {{ config('app.name', 'Easy Budget') }}
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
