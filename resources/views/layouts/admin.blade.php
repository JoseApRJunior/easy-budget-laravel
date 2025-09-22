<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Easy Budget - Admin - @yield( 'title', 'Dashboard' )</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="{{ asset( 'css/app.css' ) }}" rel="stylesheet">
</head>

<body class="d-flex h-100 bg-light">
  <div class="d-flex h-100 bg-white shadow">
    <!-- Sidebar Admin -->
    <div class="bg-dark text-white p-3" style="width: 250px; min-height: 100vh;">
      <div class="sidebar-header text-center mb-4">
        <h4 class="text-white">Administração</h4>
      </div>
      <nav class="nav flex-column">
        <a class="nav-link text-white" href="{{ route( 'admin.dashboard' ) }}">Dashboard</a>
        <a class="nav-link text-white" href="{{ route( 'admin.users.index' ) }}">Usuários</a>
        <a class="nav-link text-white" href="{{ route( 'admin.roles.index' ) }}">Roles</a>
        <a class="nav-link text-white" href="{{ route( 'admin.tenants.index' ) }}">Tenants</a>
        <a class="nav-link text-white" href="{{ route( 'admin.settings.index' ) }}">Configurações</a>
        <a class="nav-link text-white" href="{{ route( 'admin.monitoring.index' ) }}">Monitoramento</a>
        <a class="nav-link text-white" href="{{ route( 'admin.alerts.index' ) }}">Alertas</a>
        <a class="nav-link text-white" href="{{ route( 'admin.ai.index' ) }}">IA</a>
        <a class="nav-link text-white" href="{{ route( 'admin.backups.index' ) }}">Backups</a>
      </nav>
    </div>

    <div class="flex-grow-1 p-4">
      @include( 'partials.components.alerts' )

      @yield( 'content' )
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset( 'js/app.js' ) }}"></script>
  @stack( 'scripts' )
</body>

</html>