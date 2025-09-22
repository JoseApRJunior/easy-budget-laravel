<!DOCTYPE html>
<html lang="pt-BR" class="h-100">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Easy Budget - @yield( 'title', 'Dashboard' )</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset( 'css/app.css' ) }}" rel="stylesheet">
</head>

<body class="d-flex flex-column h-100">
  @include( 'partials.shared.header' )

  <main class="flex-shrink-0">
    @include( 'partials.components.alerts' )

    @yield( 'content' )
  </main>

  @include( 'partials.shared.footer' )

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset( 'js/app.js' ) }}"></script>
  @stack( 'scripts' )

  <script>
  // Configuração global para AJAX
  document.addEventListener('DOMContentLoaded', function() {
    // Configura o token CSRF para todas as requisições AJAX
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    // Adiciona o token em todas as requisições fetch
    const originalFetch = window.fetch;
    window.fetch = function() {
      let [resource, config] = arguments;
      if (config === undefined) {
        config = {};
      }
      if (config.headers === undefined) {
        config.headers = {};
      }
      config.headers['X-CSRF-TOKEN'] = csrfToken;
      return originalFetch(resource, config);
    };
  });
  </script>
</body>

</html>