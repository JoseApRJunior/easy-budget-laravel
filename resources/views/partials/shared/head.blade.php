{{-- resources/views/partials/shared/head.blade.php --}}

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://static.cloudflareinsights.com; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços" />

    <title>Easy Budget - {{ $title ?? View::getSection('title', 'Página Inicial') }}</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />

     <!-- Variáveis de Tema Dinâmicas (Sincronizadas com config/theme.php) -->
    <style>
        :root {
            /* Cores Base */
            --primary-color: {{ config('theme.colors.primary') }};
            --text-color: {{ config('theme.colors.text') }};
            --bg-color: {{ config('theme.colors.background') }};
            --surface-color: {{ config('theme.colors.surface') }};
            --secondary-color: {{ config('theme.colors.secondary') }};
            --border-color: {{ config('theme.colors.border') }};

            /* Status e Alertas */
            --text-primary: {{ config('theme.colors.primary') }};
            --text-secondary: {{ config('theme.colors.secondary') }};
            --text-success: {{ config('theme.colors.success') }};
            --text-error: {{ config('theme.colors.danger') }};
            --text-warning: {{ config('theme.colors.warning') }};
            --text-info: {{ config('theme.colors.info') }};

            /* Formulários e UI */
            --form-bg: {{ config('theme.colors.form_bg') }};
            --form-text: {{ config('theme.colors.form_text') }};
            --form-border: {{ config('theme.colors.form_border') }};
            --form-input-bg: {{ config('theme.colors.form_input_bg') }};
            --form-input-border: {{ config('theme.colors.form_input_border') }};
            --small-text-color: {{ config('theme.colors.small_text') }};

            /* Contraste */
            --contrast-text: {{ config('theme.colors.contrast_text') }};
            --contrast-text-secondary: {{ config('theme.colors.contrast_text_secondary') }};
            --contrast-overlay: {{ config('theme.colors.contrast_overlay') }};
        }
    </style>


    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.css') }}" rel="stylesheet">
    <link rel="stylesheet"
        href="{{ asset('assets/css/layout.css') }}?v={{ filemtime(public_path('assets/css/layout.css')) }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/css/components/alerts.css') }}?v={{ filemtime(public_path('assets/css/components/alerts.css')) }}">

    <!-- Tom Select -->
    <link rel="stylesheet" href="{{ asset('assets/css/tom-select/bootstrap5.min.css') }}?v={{ filemtime(public_path('assets/css/tom-select/bootstrap5.min.css')) }}">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="preload" href="{{ asset('assets/img/logo.png') }}" as="image" type="image/png">

    @stack('styles')
</head>
