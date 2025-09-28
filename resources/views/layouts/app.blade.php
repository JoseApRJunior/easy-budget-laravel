<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield( 'title', config( 'app.name', 'Easy Budget' ) )</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link href="{{ asset( 'css/app.css' ) }}" rel="stylesheet">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="d-flex flex-column h-100">
    @include( 'layouts.navigation' )

    <main class="flex-shrink-0">
        @include( 'components.alerts' )

        @yield( 'content' )
    </main>

    @include( 'layouts.footer' )

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Core JS -->
    <script src="{{ asset( 'js/modules/utils.js' ) }}" type="module"></script>
    <script src="{{ asset( 'js/main.js' ) }}" type="module"></script>
    <script src="{{ asset( 'js/alert/alert.js' ) }}"></script>

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

    @yield( 'custom_scripts' )
</body>

</html>
