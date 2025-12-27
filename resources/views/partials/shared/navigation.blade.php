{{-- partials/shared/navigation.blade.php --}}
{{-- Navbar seguindo melhores práticas Laravel --}}
<div class="collapse navbar-collapse" id="navbarNav">
    <ul class="navbar-nav ms-auto">

        {{-- Menu público - sempre visível --}}
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ url('/about') }}">
                <i class="bi bi-info-circle me-2"></i>
                <span>Sobre</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="{{ url('/support') }}">
                <i class="bi bi-headset me-2"></i>
                <span>Suporte</span>
            </a>
        </li>

        {{-- Usuário autenticado - Menu principal --}}
        @auth
            {{-- Dashboard - acesso rápido --}}
            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('provider.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link d-flex align-items-center" href="{{ route('provider.analytics.index') }}">
                    <i class="bi bi-robot me-2"></i>
                    <span>IA Analytics</span>
                </a>
            </li>

            {{-- Menu administrativo - apenas para admins --}}
            @role('admin')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-shield-lock-fill me-2"></i>
                        <span>Administração</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                        {{-- Rotas implementadas --}}
                        <li><a class="dropdown-item" href="{{ route('admin.index') }}">
                                <i class="bi bi-house me-2"></i>Home Admin</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.enterprises.index') }}">
                                <i class="bi bi-building me-2"></i>Gestão de Empresas</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.financial.index') }}">
                                <i class="bi bi-currency-dollar me-2"></i>Controle Financeiro</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.settings') }}">
                                <i class="bi bi-speedometer2 me-2"></i>Dashboard Executivo</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.users.index') }}">
                                <i class="bi bi-people me-2"></i>Usuários</a></li>

                        {{-- Sistema de Monitoramento --}}
                        <li><a class="dropdown-item" href="{{ route('admin.monitoring.dashboard') }}">
                                <i class="bi bi-graph-up me-2"></i>Monitoramento Técnico</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.alerts.index') }}">
                                <i class="bi bi-exclamation-triangle me-2"></i>Alertas</a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Gestão de Recursos --}}
                        <li><a class="dropdown-item" href="{{ route('admin.activities.index') }}">
                                <i class="bi bi-activity me-2"></i>Atividades</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.professions.index') }}">
                                <i class="bi bi-briefcase me-2"></i>Profissões</a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Gestão de Usuários --}}
                        <li><a class="dropdown-item" href="{{ route('admin.customers.index') }}">
                                <i class="bi bi-person me-2"></i>Clientes</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.providers.index') }}">
                                <i class="bi bi-building me-2"></i>Fornecedores</a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Planos e Configurações --}}
                        <li><a class="dropdown-item" href="{{ route('admin.plans.index') }}">
                                <i class="bi bi-card-list me-2"></i>Planos</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.global-settings.index') }}">
                                <i class="bi bi-gear me-2"></i>Configurações Globais</a></li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        {{-- Métricas Avançadas --}}
                        <li><a class="dropdown-item" href="{{ route('admin.metrics.index') }}">
                                <i class="bi bi-graph-up-arrow me-2"></i>Métricas Avançadas</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.ai.metrics') }}">
                                <i class="bi bi-robot me-2"></i>IA Analytics</a></li>

                        {{-- Sistema de Backup (FUTURO) --}}
                        {{-- <li><a class="dropdown-item" href="{{ route('admin.backups.index') }}">
                                                <i class="bi bi-database me-2"></i>Backups</a>
                                </li> --}}

                        {{-- Sistema de Logs (FUTURO) --}}
                        {{-- <li><a class="dropdown-item" href="{{ route('admin.logs.index') }}">
                                                <i class="bi bi-terminal me-2"></i>Logs</a></li> --}}

                        {{-- Gestão de Perfis e Tenants (FUTURO) --}}
                        {{-- <li><a class="dropdown-item" href="{{ route('admin.roles.index') }}">
                                                <i class="bi bi-shield-check me-2"></i>Perfis</a></li> --}}
                        {{-- <li><a class="dropdown-item" href="{{ route('admin.tenants.index') }}">
                                                <i class="bi bi-building me-2"></i>Tenants</a></li> --}}
                    </ul>
                </li>
            @endrole

            {{-- Menu de gerenciamento - para usuários comuns --}}
            @if (request()->path() != '/')
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="managementDropdown"
                        role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-kanban me-2"></i>
                        <span>Gerenciar</span>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="managementDropdown">
                        {{-- Entradas do menu Gerenciar apontando para os dashboards/listas principais de cada
                                        módulo --}}
                        <li><a class="dropdown-item" href="{{ route('provider.categories.dashboard') }}">
                                <i class="bi bi-tags me-2"></i>Categorias</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.products.dashboard') }}">
                                <i class="bi bi-box me-2"></i>Produtos</a></li>
                                  <li><a class="dropdown-item" href="{{ route('provider.inventory.dashboard') }}">
                                <i class="bi bi-archive me-2"></i>Estoque</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.customers.dashboard') }}">
                                <i class="bi bi-people me-2"></i>Clientes</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.budgets.dashboard') }}">
                                <i class="bi bi-file-earmark-text me-2"></i>Orçamentos</a></li>
                                <li><a class="dropdown-item" href="{{ route('provider.services.dashboard') }}">
                                <i class="bi bi-tools me-2"></i>Serviços</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.budgets.shares.dashboard') }}">
                                <i class="bi bi-share me-2"></i>Compartilhamentos</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.schedules.dashboard') }}">
                                <i class="bi bi-calendar-event me-2"></i>Agendamentos</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.invoices.dashboard') }}">
                                <i class="bi bi-receipt me-2"></i>Faturas</a></li>
                        <li><a class="dropdown-item" href="{{ route('reports.dashboard') }}">
                                <i class="bi bi-graph-up me-2"></i>Relatórios</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.qrcode.index') }}">
                                <i class="bi bi-qr-code me-2"></i>Gerador QR Code</a></li>
                    </ul>
            @endif
        @endauth

        {{-- Botão de tema - sempre visível --}}
        <li class="nav-item">
            <button class="nav-link theme-toggle d-flex align-items-center justify-content-center"
                onclick="toggleTheme()" title="Alternar Tema" aria-label="Alternar Tema">
                <i class="bi bi-sun theme-light-icon"></i>
                <i class="bi bi-moon theme-dark-icon"></i>
            </button>
        </li>

        {{-- Menu do usuário - sempre incluído --}}
        @include('partials.shared.user-menu')
    </ul>
</div>
