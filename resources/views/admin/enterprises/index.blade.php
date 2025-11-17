@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h2 mb-0">
                    <i class="bi bi-building text-primary me-2"></i>
                    Gestão de Empresas
                </h1>
                <div class="btn-group" role="group">
                    <a href="{{ route('admin.enterprises.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        Nova Empresa
                    </a>
                    <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                        <i class="bi bi-download me-1"></i>
                        Exportar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cards de Estatísticas --}}
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

    {{-- Filtros e Tabela --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-list me-2"></i>
                        Lista de Empresas
                    </h6>
                    <div class="d-flex gap-2">
                        <select class="form-select form-select-sm" id="statusFilter" style="width: auto;">
                            <option value="">Todos os Status</option>
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                            <option value="suspended">Suspenso</option>
                        </select>
                        <select class="form-select form-select-sm" id="planFilter" style="width: auto;">
                            <option value="">Todos os Planos</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                            @endforeach
                        </select>
                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Buscar..." style="width: 200px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="enterprisesTable" width="100%" cellspacing="0">
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
                                @foreach($enterprises as $enterprise)
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
                                        @if($enterprise->plan)
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
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.enterprises.show', $enterprise->id) }}" class="btn btn-sm btn-primary" title="Ver">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.enterprises.edit', $enterprise->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            @if($enterprise->status === 'active')
                                                <button onclick="suspendEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" class="btn btn-sm btn-danger" title="Suspender">
                                                    <i class="bi bi-pause"></i>
                                                </button>
                                            @else
                                                <button onclick="reactivateEnterprise({{ $enterprise->id }}, '{{ $enterprise->name }}')" class="btn btn-sm btn-success" title="Reativar">
                                                    <i class="bi bi-play"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    {{-- Paginação --}}
                    <div class="d-flex justify-content-center mt-4">
                        {{ $enterprises->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal de Confirmação --}}
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
    #enterprisesTable_wrapper .dataTables_filter {
        display: none;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Inicializar DataTable
    var table = $('#enterprisesTable').DataTable({
        responsive: true,
        pageLength: 25,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
        },
        order: [[0, 'desc']]
    });

    // Filtros
    $('#statusFilter, #planFilter').on('change', function() {
        filterTable();
    });

    $('#searchInput').on('keyup', function() {
        table.search(this.value).draw();
    });

    function filterTable() {
        var status = $('#statusFilter').val();
        var plan = $('#planFilter').val();
        
        // Implementar filtro AJAX se necessário
        console.log('Filtros:', { status: status, plan: plan });
    }
});

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
</script>
@endpush