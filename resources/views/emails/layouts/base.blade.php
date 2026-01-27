@php
$bgColor = '#f1f5f9';
$textColor = config('theme.colors.text', '#1e293b');
$whiteColor = '#ffffff';
$borderColor = config('theme.colors.border', '#e2e8f0');
$primaryColor = config('theme.colors.primary', '#093172');
$contrastColor = config('theme.colors.contrast_text', '#ffffff');
$inputBgColor = '#f8fafc';
$smallTextColor = config('theme.colors.small_text', '#475569');
$secondaryColor = config('theme.colors.secondary', '#94a3b8');
$headerBg = $statusColor ?? $primaryColor;
@endphp
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>@yield('title', $title ?? config('app.name', 'Easy Budget'))</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: <?php echo $bgColor; ?>;
            color: <?php echo $textColor; ?>;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .email-wrap {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            letter-spacing: -0.025em;
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
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
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
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
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
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .notice .icon {
            font-size: 20px;
            opacity: 0.8;
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

<body>
    <div class="email-wrap">
        <div class="header">
            @if(!isset($isSystemEmail) || $isSystemEmail === true)
            <h1>{{ config('app.name', 'Easy Budget') }}</h1>
            @elseif(!empty($company['company_name']))
            <h1>{{ $company['company_name'] }}</h1>
            @else
            <h1>{{ config('app.name', 'Easy Budget') }}</h1>
            @endif
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            @if(!isset($isSystemEmail) || $isSystemEmail === true)
            © {{ date('Y') }} {{ config('app.name', 'Easy Budget') }}.
            @hasSection('footerExtra')
            <div>@yield('footerExtra')</div>
            @endif
            @if(!empty($supportEmail))<br>Suporte: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>@endif
            @else
            @if(!empty($company['company_name']))
            <strong>{{ $company['company_name'] }}</strong><br>
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
        </div>
    </div>
</body>

</html>
