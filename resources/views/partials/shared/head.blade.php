{{-- resources/views/partials/shared/head.blade.php --}}

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy"
        content="script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://static.cloudflareinsights.com; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços" />

    <title>Easy Budget - @yield('title', 'Página Inicial')</title>

    <!-- Script Inline para Inicialização do Tema (Evita FOUC) -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
        })();
    </script>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />

    <!-- CSS (Atributos integrity removidos para corrigir erro de carregamento CDN/CORS) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}?v=1.0.0" />
    <link rel="stylesheet" href="{{ asset('assets/css/components/alerts.css') }}?v=1.0.0">
    <link rel="preload" href="{{ asset('assets/img/logo.png') }}" as="image" type="image/png">

    @stack('styles')
</head>
