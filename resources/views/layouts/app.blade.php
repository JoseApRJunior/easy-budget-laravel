<!DOCTYPE html>
<html lang="{{ str_replace( '_', '-', app()->getLocale() ) }}">

<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Security-Policy"
        content="script-src 'self' 'unsafe-inline' 'unsafe-eval' https://code.jquery.com https://cdn.jsdelivr.net http://localhost:5173 http://localhost:5174; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços">

    <title>@yield( 'title', 'Easy Budget' )</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset( 'assets/img/favicon.ico' ) }}">
    <link rel="preload" href="{{ asset( 'assets/img/logo.png' ) }}" as="image" type="image/png">

    <!-- Bootstrap CSS (temporário durante transição) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- CSS Legado durante transição -->
    <link rel="stylesheet" href="{{ asset( 'assets/css/layout.css' ) }}">
    <link rel="stylesheet" href="{{ asset( 'assets/css/alert/alert.css' ) }}">

    <!-- TailwindCSS + CSS personalizado -->
    @vite( [ 'resources/css/app.css', 'resources/js/app.js' ] )

    @stack( 'styles' )
</head>

<body class="min-h-screen bg-gray-50 text-gray-900 font-sans antialiased">
    @include( 'layouts.partials.header' )

    <main class="flex-shrink-0">
        @include( 'components.alerts' )
        @yield( 'content' )
    </main>

    @include( 'layouts.partials.footer' )

    <!-- Scripts externos (temporários durante transição) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts internos -->
    <script src="{{ asset( 'assets/js/modules/utils.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/main.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/alert/alert.js' ) }}"></script>

    @stack( 'scripts' )

    <script>
        // Configuração global para AJAX
        document.addEventListener( 'DOMContentLoaded', function () {
            // Configura o token CSRF para todas as requisições AJAX
            const csrfToken = document.querySelector( 'meta[name="csrf-token"]' ).content;

            // Adiciona o token em todas as requisições fetch
            const originalFetch = window.fetch;
            window.fetch = function () {
                let [resource, config] = arguments;
                if ( config === undefined ) {
                    config = {};
                }
                if ( config.headers === undefined ) {
                    config.headers = {};
                }
                config.headers['X-CSRF-TOKEN'] = csrfToken;
                return originalFetch( resource, config );
            };
        } );
    </script>
</body>

</html>
