{{-- layout.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

@include( 'partials.shared.head' )

<body class="d-flex flex-column h-100">
    @include( 'partials.shared.header' )

    <main class="flex-shrink-0">
        @include( 'partials.components.alerts' )
        @yield( 'content' )
    </main>

    @include( 'partials.shared.footer' )

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/utils.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/main.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/components/alerts.js' ) }}"></script>

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
