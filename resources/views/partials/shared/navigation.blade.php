<div class="collapse navbar-collapse" id="navbarNav">
  <ul class="navbar-nav ms-auto align-items-center">
    @auth
    <li class="nav-item">
      <a class="nav-link px-3 rounded-pill mx-1 nav-link-hover" href="{{ url( '/provider' ) }}">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
      </a>
    </li>

    @if( auth()->user()->hasRole( 'admin' ) )
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle px-3 rounded-pill mx-1 nav-link-hover" href="#" id="adminDropdown"
        role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-shield-lock-fill me-2"></i>Administração
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2" aria-labelledby="adminDropdown">
        <li>
          <h6 class="dropdown-header text-primary"><i class="bi bi-gear me-2"></i>Sistema</h6>
        </li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin' ) }}"><i class="bi bi-house me-2 text-primary"></i>Home
            Admin</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/dashboard' ) }}"><i
              class="bi bi-speedometer2 me-2 text-success"></i>Dashboard Executivo</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/monitoring' ) }}"><i
              class="bi bi-graph-up me-2 text-info"></i>Monitoramento</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/alerts' ) }}"><i
              class="bi bi-exclamation-triangle me-2 text-warning"></i>Alertas</a></li>

        <li>
          <hr class="dropdown-divider my-2">
        </li>
        <li>
          <h6 class="dropdown-header text-primary"><i class="bi bi-box me-2"></i>Recursos</h6>
        </li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/plans/subscriptions' ) }}"><i
              class="bi bi-card-checklist me-2 text-success"></i>Assinaturas</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/backups' ) }}"><i
              class="bi bi-database me-2 text-info"></i>Backups</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/logs' ) }}"><i
              class="bi bi-terminal me-2 text-secondary"></i>Logs</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/activities' ) }}"><i
              class="bi bi-activity me-2 text-primary"></i>Atividades</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/ai' ) }}"><i
              class="bi bi-robot me-2 text-info"></i>IA</a></li>

        <li>
          <hr class="dropdown-divider my-2">
        </li>
        <li>
          <h6 class="dropdown-header text-primary"><i class="bi bi-people me-2"></i>Gestão</h6>
        </li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/categories' ) }}"><i
              class="bi bi-tags me-2 text-warning"></i>Categorias</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/users' ) }}"><i
              class="bi bi-people me-2 text-primary"></i>Usuários</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/roles' ) }}"><i
              class="bi bi-shield-check me-2 text-success"></i>Perfis</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/tenants' ) }}"><i
              class="bi bi-building me-2 text-info"></i>Tenants</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/admin/settings' ) }}"><i
              class="bi bi-gear me-2 text-secondary"></i>Configurações</a></li>
      </ul>
    </li>
    @endif

    @if( request()->path() != '/' )
    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle px-3 rounded-pill mx-1 nav-link-hover" href="#" id="managementDropdown"
        role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-kanban me-2"></i>Gerenciar
      </a>
      <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2"
        aria-labelledby="managementDropdown">
        <li>
          <h6 class="dropdown-header text-primary"><i class="bi bi-briefcase me-2"></i>Negócios</h6>
        </li>
        <li><a class="dropdown-item py-2" href="{{ url( '/provider/budgets' ) }}"><i
              class="bi bi-file-earmark-text me-2 text-primary"></i>Orçamentos</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/provider/services' ) }}"><i
              class="bi bi-tools me-2 text-warning"></i>Serviços</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/provider/invoices' ) }}"><i
              class="bi bi-receipt me-2 text-success"></i>Faturas</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/provider/customers' ) }}"><i
              class="bi bi-people me-2 text-info"></i>Clientes</a></li>
        <li><a class="dropdown-item py-2" href="{{ url( '/provider/products' ) }}"><i
              class="bi bi-box me-2 text-secondary"></i>Produtos</a></li>
      </ul>
    </li>

    <li class="nav-item">
      <a class="nav-link px-3 rounded-pill mx-1 nav-link-hover" href="{{ url( '/provider/reports' ) }}">
        <i class="bi bi-graph-up me-2"></i>Relatórios
      </a>
    </li>
    @endif

    @include( 'partials.shared.user-menu' )
    @endauth

    @guest
    <!-- Menu para usuários não logados -->
    <li class="nav-item">
      <a class="nav-link px-3 rounded-pill mx-1 nav-link-hover" href="{{ url( '/about' ) }}">
        <i class="bi bi-info-circle me-2"></i>Sobre
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link px-3 rounded-pill mx-1 nav-link-hover" href="{{ url( '/support' ) }}">
        <i class="bi bi-headset me-2"></i>Suporte
      </a>
    </li>

    <li class="nav-item">
      <button class="nav-link btn  btn-sm rounded-pill px-3 mx-2 theme-toggle-btn" onclick="toggleTheme()"
        title="Alternar Tema" aria-label="Alternar Tema">
        <i class="bi bi-sun-fill theme-light-icon me-1"></i>
        <i class="bi bi-moon-stars-fill theme-dark-icon me-1"></i>
        <span class="d-none d-lg-inline">Tema</span>
      </button>
    </li>

    <li class="nav-item">
      <a class="nav-link btn btn-primary btn-sm px-4 rounded-pill mx-1 login-btn" href="{{ url( '/login' ) }}">
        <i class="bi bi-box-arrow-in-right me-2"></i>Entrar
      </a>
    </li>
    @endguest
  </ul>
</div>