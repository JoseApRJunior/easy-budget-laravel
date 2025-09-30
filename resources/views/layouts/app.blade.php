{{--
Layout principal da aplicação Easy Budget
Convertido de layout.twig para Blade
--}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

@include( 'layouts.partials.head' )

<body class="d-flex flex-column h-100">
    @include( 'layouts.partials.header' )

    <main class="flex-shrink-0">
        @include( 'layouts.partials.alerts' )

        @yield( 'content' )
    </main> @include( 'layouts.partials.footer' ) <!-- Scripts -->
    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Vite Assets - Abordagem Oficial Laravel/Vite --}}
    @vite( 'resources/js/app.js' )

    {{-- Scripts específicos que ainda usam asset() --}}

    @yield( 'scripts' )

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
