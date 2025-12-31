@extends('layouts.admin')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho Administrativo -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Gestão de Empresas</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <i class="bi bi-building text-primary me-2"></i>Gestão de Empresas
                </h1>
                <p class="text-muted">Monitoramento e controle das empresas cadastradas no sistema</p>
            </div>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                    <i class="bi bi-download me-1"></i>Exportar
                </button>
                <a href="{{ route('admin.enterprises.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Nova Empresa
                </a>
            </div>
        </div>

        <!-- Cards de Estatísticas -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Empresas
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEnterprises">
                                    {{ $statistics['total_enterprises'] }}
                                </div>
                                <div class="text-xs text-muted">
                                    <span id="newThisMonth">{{ $statistics['new_this_month'] }}</span> novas este mês
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-building fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Empresas Ativas
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="activeEnterprises">
                                    {{ $statistics['active_enterprises'] }}
                                </div>
                                <div class="text-xs text-muted">
                                    Taxa de ativação: {{ number_format($statistics['activation_rate'], 1) }}%
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Suspensas
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="suspendedEnterprises">
                                    {{ $statistics['suspended_enterprises'] }}
                                </div>
                                <div class="text-xs text-muted">
                                    Requerem atenção
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-pause-circle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Receita Mensal
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800" id="monthlyRevenue">
                                    R$ {{ number_format($statistics['revenue_this_month'], 2, ',', '.') }}
                                </div>
                                <div class="text-xs text-muted">
                                    Média: R$ {{ number_format($statistics['avg_revenue_per_enterprise'], 2, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-currency-dollar fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card de Filtros (SEPARADO) -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="">Todos os Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Ativo
                                    </option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                                        Inativo</option>
                                    <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>
                                        Suspenso</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="plan">Plano</label>
                                <select class="form-control" name="plan" id="plan">
                                    <option value="">Todos os Planos</option>
                                    @foreach ($plans as $plan)
                                        <option value="{{ $plan->id }}"
                                            {{ request('plan') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_from">Data Início</label>
                                <input type="date" class="form-control" name="date_from" id="date_from"
                                    value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="date_to">Data Fim</label>
                                <input type="date" class="form-control" name="date_to" id="date_to"
                                    value="{{ request('date_to') }}">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search">Buscar</label>
                                <input type="text" class="form-control" name="search" id="search"
                                    placeholder="Buscar por nome ou email..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="min_revenue">Receita Mínima</label>
                                <input type="number" class="form-control" name="min_revenue" id="min_revenue"
                                    placeholder="0.00" step="0.01" value="{{ request('min_revenue') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Filtrar
                                    </button>
                                    <a href="{{ route('admin.enterprises.index') }}" class="btn btn-secondary">
                                        <i class="bi bi-x-circle"></i> Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card Principal -->
        <div class="card">
            <div class="card-body">
                @if ($enterprises->count() > 0)
                    <!-- Tabela responsiva -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>Plano</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($enterprises as $enterprise)
                                    <tr>
                                        <td>{{ $enterprise->id }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $enterprise->name }}</div>
                                                    <small class="text-muted">{{ $enterprise->document }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $enterprise->email }}</td>
                                        <td>
                                            @if ($enterprise->plan)
                                                <span class="badge bg-info">{{ $enterprise->plan->name }}</span>
                                            @else
                                                <span class="badge bg-secondary">Sem Plano</span>
                                            @endif
                                        </td>
                                        <td>
                                            @switch($enterprise->status)
                                                @case('active')
                                                    <span class="badge bg-success">Ativo</span>
                                                @break

                                                @case('inactive')
                                                    <span class="badge bg-warning">Inativo</span>
                                                @break

                                                @case('suspended')
                                                    <span class="badge bg-danger">Suspenso</span>
                                                @break

                                                @default
                                                    <span class="badge bg-secondary">{{ $enterprise->status }}</span>
                                            @endswitch
                                        </td>
                                        <td>{{ $enterprise->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <x-button type="link" :href="route('admin.enterprises.show', $enterprise->id)" variant="info" size="sm" icon="eye" title="Ver" />
                                                <x-button type="link" :href="route('admin.enterprises.edit', $enterprise->id)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                @if ($enterprise->status === 'active')
                                                    <x-button variant="danger" size="sm" icon="pause" title="Suspender"
                                                        onclick="suspendEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                                @else
                                                    <x-button variant="success" size="sm" icon="play" title="Reativar"
                                                        onclick="reactivateEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile view -->
                    <div class="d-md-none">
                        @foreach ($enterprises as $enterprise)
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $enterprise->name }}</h5>
                                    <p class="card-text">
                                        <strong>ID:</strong> {{ $enterprise->id }}<br>
                                        <strong>Email:</strong> {{ $enterprise->email }}<br>
                                        <strong>Plano:</strong>
                                        @if ($enterprise->plan)
                                            <span class="badge bg-info">{{ $enterprise->plan->name }}</span>
                                        @else
                                            <span class="badge bg-secondary">Sem Plano</span>
                                        @endif
                                        <br>
                                        <strong>Status:</strong>
                                        @switch($enterprise->status)
                                            @case('active')
                                                <span class="badge bg-success">Ativo</span>
                                            @break

                                            @case('inactive')
                                                <span class="badge bg-warning">Inativo</span>
                                            @break

                                            @case('suspended')
                                                <span class="badge bg-danger">Suspenso</span>
                                            @break

                                            @default
                                                <span class="badge bg-secondary">{{ $enterprise->status }}</span>
                                        @endswitch
                                        <br>
                                        <strong>Criado em:</strong> {{ $enterprise->created_at->format('d/m/Y') }}
                                    </p>
                                    <div class="d-flex gap-2 mt-3">
                                        <x-button type="link" :href="route('admin.enterprises.show', $enterprise->id)" variant="info" size="sm" icon="eye" label="Ver" class="flex-grow-1" />
                                        <x-button type="link" :href="route('admin.enterprises.edit', $enterprise->id)" variant="primary" size="sm" icon="pencil-square" label="Editar" class="flex-grow-1" />
                                        @if ($enterprise->status === 'active')
                                            <x-button variant="danger" size="sm" icon="pause" label="Suspender" class="flex-grow-1"
                                                onclick="suspendEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                        @else
                                            <x-button variant="success" size="sm" icon="play" label="Reativar" class="flex-grow-1"
                                                onclick="reactivateEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" />
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginação -->
                    @if ($enterprises->hasPages())
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div>
                                <small class="text-muted">
                                    Mostrando {{ $enterprises->firstItem() }} a {{ $enterprises->lastItem() }} de
                                    {{ $enterprises->total() }} resultados
                                </small>
                            </div>
                            <div>
                                {{ $enterprises->links() }}
                            </div>
                            <div>
                                <select class="form-control form-control-sm" onchange="changePerPage(this.value)">
                                    <option value="10" {{ $enterprises->perPage() == 10 ? 'selected' : '' }}>10 por
                                        página</option>
                                    <option value="20" {{ $enterprises->perPage() == 20 ? 'selected' : '' }}>20 por
                                        página</option>
                                    <option value="50" {{ $enterprises->perPage() == 50 ? 'selected' : '' }}>50 por
                                        página</option>
                                </select>
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Empty State -->
                    <div class="text-center py-5">
                        <i class="bi bi-building fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhuma empresa encontrada</h5>
                        <p class="text-muted">Não há empresas para exibir com os filtros aplicados.</p>
                        <a href="{{ route('admin.enterprises.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Criar Primeira Empresa
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmModalTitle">Confirmar Ação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="confirmModalBody">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmModalAction">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }

        .card {
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-2px);
        }
    </style>
@endpush

@push('scripts')
    <script>
        function suspendEnterprise(id, name) {
            $('#confirmModalTitle').text('Suspender Empresa');
            $('#confirmModalBody').html(`
        <p>Tem certeza que deseja suspender a empresa <strong>${name}</strong>?</p>
        <p class="text-warning">
            <i class="bi bi-exclamation-triangle"></i>
            Esta ação suspenderá todos os usuários e serviços da empresa.
        </p>
    `);

            $('#confirmModalAction').removeClass().addClass('btn btn-warning').text('Suspender');
            $('#confirmModalAction').off('click').on('click', function() {
                executeAction(`/admin/enterprises/${id}/suspend`, 'POST', 'Empresa suspensa com sucesso!');
            });

            $('#confirmModal').modal('show');
        }

        function reactivateEnterprise(id, name) {
            $('#confirmModalTitle').text('Reativar Empresa');
            $('#confirmModalBody').html(`
        <p>Tem certeza que deseja reativar a empresa <strong>${name}</strong>?</p>
        <p class="text-info">
            <i class="bi bi-info-circle"></i>
            Esta ação reativará todos os usuários e serviços da empresa.
        </p>
    `);

            $('#confirmModalAction').removeClass().addClass('btn btn-success').text('Reativar');
            $('#confirmModalAction').off('click').on('click', function() {
                executeAction(`/admin/enterprises/${id}/reactivate`, 'POST', 'Empresa reativada com sucesso!');
            });

            $('#confirmModal').modal('show');
        }

        function executeAction(url, method, successMessage) {
            $.ajax({
                url: url,
                method: method,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#confirmModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    alert('Erro ao executar ação: ' + (xhr.responseJSON?.message || 'Erro desconhecido'));
                }
            });
        }

        function exportData() {
            window.location.href = '/admin/enterprises/export';
        }

        function changePerPage(perPage) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', perPage);
            window.location = url.toString();
        }

        // DataTable initialization (opcional, pode ser removido se não necessário)
        $(document).ready(function() {
            // Inicializar DataTable apenas se necessário
            var table = $('#enterprisesTable');
            if (table.length) {
                table.DataTable({
                    responsive: true,
                    pageLength: 25,
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
                    },
                    order: [
                        [0, 'desc']
                    ]
                });
            }
        });
    </script>
@endpush
