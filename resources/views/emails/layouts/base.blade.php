<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>@yield('title', $title ?? config('app.name', 'Easy Budget'))</title>
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
            @if(!isset($isSystemEmail) || $isSystemEmail === true)
                <h1>{{ config( 'app.name', 'Easy Budget' ) }}</h1>
            @elseif(!empty($company['company_name']))
                <h1>{{ $company['company_name'] }}</h1>
            @else
                <h1>{{ config( 'app.name', 'Easy Budget' ) }}</h1>
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
                {{ $company['address_line2'] }}<br>
            @endif
            @if(!empty($company['phone']) || !empty($company['email']))
                {{ $company['phone'] ?? '' }} {{ !empty($company['phone']) && !empty($company['email']) ? '|' : '' }} {{ $company['email'] ?? '' }}
            @endif
            <p style="margin-top: 10px; font-size: 10px; color: #9ca3af;">
                Enviado via {{ config('app.name', 'Easy Budget') }}
            </p>
        @endif
</div>
    </div>
</body>

</html>
