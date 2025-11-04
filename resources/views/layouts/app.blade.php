{{-- layout.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

@include( 'partials.shared.head' )

<body class="d-flex flex-column h-100">
    @include( 'partials.shared.header' )

    <main class="flex-shrink-0">
        @include( 'partials.components.alerts' )
        @if ( session()->has( 'trial_expired_warning' ) )
            @include( 'partials.components.trial-expired-warning' )
        @endif
        @yield( 'content' )
    </main>

    @include( 'partials.shared.footer' )

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script type="module" src="{{ asset( 'assets/js/modules/vanilla-masks.js' ) }}"></script>
    <script src="{{ asset( 'assets/js/modules/utils.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/main.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/components/alerts.js' ) }}"></script>
    <script src="{{ asset( 'assets/js/cep-lookup.js' ) }}"></script>

    @stack( 'scripts' )

    <script>
        // Configuração global para AJAX
        document.addEventListener( 'DOMContentLoaded', function () {
            // Configura o token CSRF para todas as requisições AJAX
            const csrfToken = document.querySelector( 'meta[name="csrf-token"]' ).content;

            // Adiciona o token apenas em requisições internas
            const originalFetch = window.fetch;
            window.fetch = function () {
                let [resource, config] = arguments;

                // Só adiciona CSRF token para requisições internas
                const url = typeof resource === 'string' ? resource : resource.url;
                const isInternal = url.startsWith('/') || url.startsWith(window.location.origin);

                if (isInternal) {
                    if ( config === undefined ) {
                        config = {};
                    }
                    if ( config.headers === undefined ) {
                        config.headers = {};
                    }
                    config.headers['X-CSRF-TOKEN'] = csrfToken;
                }
                return originalFetch( resource, config );
            };
        } );
    </script>
</body>

</html>
