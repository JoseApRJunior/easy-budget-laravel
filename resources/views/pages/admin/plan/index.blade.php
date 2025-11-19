@extends('layouts.app')

@section('content')
<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-box-seam me-2"></i>Gerenciamento de Planos</h1>
        <div>
            <a href="{{ route('admin.plans.create') }}" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle me-1"></i>Novo Plano
            </a>
            <a href="{{ route('admin.plans.export', ['format' => 'csv']) }}" class="btn btn-outline-secondary">
                <i class="bi bi-download me-1"></i>Exportar
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Planos</h5>
                    <h3>{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Planos Ativos</h5>
                    <h3>{{ $stats['active'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas Ativas</h5>
                    <h3>{{ $stats['active_subscriptions'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Receita Mensal</h5>
                    <h3>R$ {{ number_format($stats['monthly_revenue'], 2, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.plans.index') }}">
                <div class="row">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Pesquisar</label>
                        <input type="text" class="form-control" id="search" name="search" value="{{ $search }}" placeholder="Nome ou descrição...">
                    </div>
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">Todos</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inativo</option>
                            <option value="draft" {{ $status == 'draft' ? 'selected' : '' }}>Rascunho</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="sort" class="form-label">Ordenar por</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="name" {{ $sort == 'name' ? 'selected' : '' }}>Nome</option>
                            <option value="price" {{ $sort == 'price' ? 'selected' : '' }}>Preço</option>
                            <option value="status" {{ $sort == 'status' ? 'selected' : '' }}>Status</option>
                            <option value="created_at" {{ $sort == 'created_at' ? 'selected' : '' }}>Data de Criação</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="direction" class="form-label">Direção</label>
                        <select class="form-select" id="direction" name="direction">
                            <option value="asc" {{ $direction == 'asc' ? 'selected' : '' }}>Ascendente</option>
                            <option value="desc" {{ $direction == 'desc' ? 'selected' : '' }}>Descendente</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>Filtrar
                        </button>
                        <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Plans Table -->
    <div class="card">
        <div class="card-body">
            @if($plans->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Descrição</th>
                                <th>Preço</th>
                                <th>Status</th>
                                <th>Assinaturas</th>
                                <th>Criado em</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($plans as $plan)
                                <tr>
                                    <td>{{ $plan->id }}</td>
                                    <td>
                                        <strong>{{ $plan->name }}</strong>
                                        @if($plan->slug)
                                            <br><small class="text-muted">{{ $plan->slug }}</small>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($plan->description, 50) }}</td>
                                    <td>R$ {{ number_format($plan->price, 2, ',', '.') }}</td>
                                    <td>
                                        @php
                                            $statusClass = match($plan->status) {
                                                'active' => 'bg-success',
                                                'inactive' => 'bg-danger',
                                                'draft' => 'bg-secondary',
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($plan->status) }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $plan->planSubscriptions()->count() }}</span>
                                    </td>
                                    <td>{{ $plan->created_at ? $plan->created_at->format('d/m/Y') : 'N/A' }}</td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.plans.show', $plan) }}" class="btn btn-sm btn-primary" title="Ver detalhes">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-sm btn-warning" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="{{ route('admin.plans.subscribers', $plan) }}" class="btn btn-sm btn-info" title="Assinantes">
                                                <i class="bi bi-people"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="confirmDelete('{{ route('admin.plans.destroy', $plan) }}')" 
                                                    title="Excluir"
                                                    {{ $plan->planSubscriptions()->exists() ? 'disabled' : '' }}>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center">
                    {{ $plans->links() }}
                </div>
            @else
                <div class="alert alert-info" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhum plano encontrado. 
                    <a href="{{ route('admin.plans.create') }}" class="alert-link">Criar seu primeiro plano</a>.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este plano?</p>
                <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete(deleteUrl) {
    document.getElementById('deleteForm').action = deleteUrl;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>
@endpush