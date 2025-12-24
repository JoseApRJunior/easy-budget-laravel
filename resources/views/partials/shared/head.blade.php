{{-- resources/views/partials/shared/head.blade.php --}}

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy" content="script-src 'self' 'unsafe-inline'; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços" />

    <title>Easy Budget - @yield('title', 'Página Inicial')</title>

    <!-- Script Inline para Inicialização do Tema (Evita FOUC) -->
    <script>
        // Bloqueia a renderização até definir o tema
        (function() {
            const savedTheme = localStorage.getItem('theme') ||
                (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

            // Define o atributo para o Bootstrap 5
            document.documentElement.setAttribute('data-bs-theme', savedTheme);

            // Cria uma tag de estilo temporária para evitar o flash branco no body
            const css = document.createElement('style');
            css.innerText = `body { display: none !important; }`; // Esconde o body até o tema estar pronto
            document.head.appendChild(css);

            document.addEventListener('DOMContentLoaded', () => {
                document.body.classList.add('theme-' + savedTheme);
                css.remove(); // Mostra o body já com a cor certa
            });
        })();
    </script>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/bootstrap-icons.css') }}" rel="stylesheet">
    <link rel="stylesheet"
        href="{{ asset('assets/css/layout.css') }}?v={{ filemtime(public_path('assets/css/layout.css')) }}" />
    <link rel="stylesheet"
        href="{{ asset('assets/css/components/alerts.css') }}?v={{ filemtime(public_path('assets/css/components/alerts.css')) }}">
    <link rel="preload" href="{{ asset('assets/img/logo.png') }}" as="image" type="image/png">

    @stack('styles')
</head>
