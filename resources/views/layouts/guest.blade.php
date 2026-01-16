{{-- resources/views/layouts/guest.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

@include('partials.shared.head')

<body class="d-flex flex-column h-100 bg-light">
    @include('partials.shared.header')

    <main class="flex-shrink-0">

                            @include('partials.components.alerts')
                            @yield('content')

    </main>

    @include('partials.shared.footer')

    <!-- Scripts Base -->
    <script defer src="{{ asset('assets/js/jquery-3.7.1.min.js') }}?v={{ filemtime(public_path('assets/js/jquery-3.7.1.min.js')) }}"></script>
    <script defer src="{{ asset('assets/js/bootstrap.bundle.min.js') }}?v={{ filemtime(public_path('assets/js/bootstrap.bundle.min.js')) }}"></script>

    <!-- Módulos e Componentes -->
    <script defer src="{{ asset('assets/js/modules/vanilla-masks.js') }}?v={{ filemtime(public_path('assets/js/modules/vanilla-masks.js')) }}"></script>
    <script defer src="{{ asset('assets/js/main.js') }}?v={{ filemtime(public_path('assets/js/main.js')) }}" type="module"></script>

    <script defer src="{{ asset('assets/js/components/alerts.js') }}?v={{ filemtime(public_path('assets/js/components/alerts.js')) }}"></script>

    @stack('scripts')
    @yield('scripts')
    @stack('modals')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.content : '';

            if (!csrfToken) return;

            const originalFetch = window.fetch;
            window.fetch = function() {
                let [resource, config] = arguments;
                const url = typeof resource === 'string' ? resource : resource.url;

                // Só adiciona token para rotas internas (evita vazamento de token para APIs externas)
                const isInternal = url.startsWith('/') || url.startsWith(window.location.origin);

                if (isInternal) {
                    config = config || {};
                    config.headers = config.headers || {};
                    config.headers['X-CSRF-TOKEN'] = csrfToken;
                    config.headers['X-Requested-With'] = 'XMLHttpRequest';
                }
                return originalFetch(resource, config);
            };
        });
    </script>
</body>

</html>
