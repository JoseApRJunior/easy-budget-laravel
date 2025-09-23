<div class="collapse navbar-collapse" id="navbarNav">
  @section( 'navigation' )
  <ul class="navbar-nav ms-auto">
    @auth
    <li class="nav-item">
      <a class="nav-link" href="{{ url( '/provider' ) }}">
        <i class="bi bi-speedometer2 me-1"></i>Dashboard
      </a>
    </li>
    @if( auth()->user()->hasRole( 'admin' ) )
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="bi bi-shield-lock-fill me-1"></i> Administração
      </a>
      <ul class="dropdown-menu" aria-labelledby="adminDropdown">
        <li><a class="dropdown-item" href="{{ url( '/admin' ) }}"><i class="bi bi-house me-2"></i>Home Admin</a>
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/dashboard' ) }}"><i
              class="bi bi-speedometer2 me-2"></i>Dashboard Executivo</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/monitoring' ) }}"><i
              class="bi bi-graph-up me-2"></i>Monitoramento Técnico</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/alerts' ) }}"><i
              class="bi bi-exclamation-triangle me-2"></i>Alertas</a></li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/plans/subscriptions' ) }}"><i
              class="bi bi-card-checklist me-2"></i>Assinaturas</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/backups' ) }}"><i class="bi bi-database me-2"></i>Backups</a>
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/logs' ) }}"><i class="bi bi-terminal me-2"></i>Logs</a>
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/activities' ) }}"><i
              class="bi bi-activity me-2"></i>Atividades</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/ai' ) }}"><i class="bi bi-robot me-2"></i>Inteligência
            Artificial</a></li>
        <li>
          <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/categories' ) }}"><i
              class="bi bi-tags me-2"></i>Categorias</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/users' ) }}"><i class="bi bi-people me-2"></i>Usuários</a>
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/roles' ) }}"><i
              class="bi bi-shield-check me-2"></i>Perfis</a></li>
        <li><a class="dropdown-item" href="{{ url( '/admin/tenants' ) }}"><i class="bi bi-building me-2"></i>Tenants</a>
        </li>
        <li><a class="dropdown-item" href="{{ url( '/admin/settings' ) }}"><i
              class="bi bi-gear me-2"></i>Configurações</a></li>
      </ul>
    </li>
    @endif
    @endauth

    @if( request()->path() == '/' || !auth()->check() )
    <li class="nav-item">
      <a class="nav-link" href="{{ url( '/about' ) }}">
        <i class="bi bi-info-circle me-1"></i>Sobre
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="{{ url( '/support' ) }}">
        <i class="bi bi-headset me-1"></i>Suporte
      </a>
    </li>
    @endif

    @if( request()->path() != '/' && auth()->check() )
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown"
        aria-expanded="false">
        <i class="bi bi-kanban me-1"></i> Gerenciar
      </a>
      <ul class="dropdown-menu" aria-labelledby="managementDropdown">
        <li><a class="dropdown-item" href="{{ url( '/provider/budgets' ) }}"><i
              class="bi bi-file-earmark-text me-2"></i>Orçamentos</a></li>
        <li><a class="dropdown-item" href="{{ url( '/provider/services' ) }}"><i
              class="bi bi-tools me-2"></i>Serviços</a></li>
        <li><a class="dropdown-item" href="{{ url( '/provider/invoices' ) }}"><i
              class="bi bi-receipt me-2"></i>Faturas</a></li>
        <li><a class="dropdown-item" href="{{ url( '/provider/customers' ) }}"><i
              class="bi bi-people me-2"></i>Clientes</a></li>
        <li><a class="dropdown-item" href="{{ url( '/provider/products' ) }}"><i class="bi bi-box me-2"></i>Produtos</a>
        </li>
      </ul>
    </li>
    <li class="nav-item">
      <a class="nav-link" href="{{ url( '/provider/reports' ) }}">
        <i class="bi bi-graph-up me-1" aria-hidden="true"></i>
        Relatórios
      </a>
    </li>
    @endif

    <li class="nav-item">
      <button class="nav-link theme-toggle" onclick="toggleTheme()" title="Alternar Tema" aria-label="Alternar Tema">
        <i class="bi bi-sun-fill theme-light-icon" aria-hidden="true"></i>
        <i class="bi bi-moon-stars-fill theme-dark-icon" aria-hidden="true"></i>
      </button>
    </li>
    @include( 'partials.shared.user-menu' )
  </ul>
  @endsection
</div>