@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<div class="container-fluid py-1">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-tags me-2"></i>
                Categorias
            </h1>
            <p class="text-muted">Lista de categorias do sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Categorias</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form id="filtersFormCategories" method="GET" action="{{ route('categories.index') }}">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="search">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                        value="{{ $filters['search'] ?? '' }}" placeholder="Nome ou slug">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="active">Status</label>
                                    <select class="form-control" id="active" name="active">
                                        <option value="">Todos</option>
                                        <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Ativo</option>
                                        <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-nowrap">
                                    <button type="submit" id="btnFilterCategories" class="btn btn-primary" aria-label="Filtrar">
                                        <i class="bi bi-search me-1" aria-hidden="true"></i>Filtrar
                                    </button>
                                    <a href="{{ route('categories.index') }}" class="btn btn-secondary" aria-label="Limpar filtros">
                                        <i class="bi bi-x me-1" aria-hidden="true"></i>Limpar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-1"></i> Lista de Categorias
                        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        ({{ $categories->total() }} registros)
                        @else
                        ({{ $categories->count() }} registros)
                        @endif
                        @if( ($filters['search'] ?? '') !== '' || ($filters['active'] ?? '') !== '' )
                        <span class="badge badge-info ms-2"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Filtros ativos</span>
                        @endif
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('categories.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus me-1" aria-hidden="true"></i>Nova Categoria
                        </a>
                        <a href="{{ route('categories.export', ['format' => 'xlsx']) }}" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-download me-1" aria-hidden="true"></i>Exportar
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-tag" aria-hidden="true"></i></th>
                                    <th>Nome</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th>Criado em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($categories as $category)
                                <tr>
                                    <td class="text-center">
                                        <i class="bi bi-tag text-muted" aria-hidden="true"></i>
                                    </td>
                                    <td>{{ $category->name }}</td>
                                    <td><span class="text-code">{{ $category->slug }}</span></td>
                                    <td>
                                        @if($category->is_active)
                                        <span class="badge badge-success">Ativo</span>
                                        @else
                                        <span class="badge badge-danger">Inativo</span>
                                        @endif
                                    </td>
                                    <td>{{ $category->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="{{ route('categories.show', $category->slug) }}"
                                                class="btn btn-info" title="Visualizar" aria-label="Visualizar">
                                                <i class="bi bi-eye" aria-hidden="true"></i>
                                            </a>
                                            <a href="{{ route('categories.edit', $category->id) }}"
                                                class="btn btn-warning" title="Editar" aria-label="Editar">
                                                <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal-{{ $category->id }}" title="Excluir" aria-label="Excluir">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        <i class="bi bi-inbox mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
                                        <br>
                                        Nenhuma categoria encontrada.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $categories->appends(request()->query())->links() }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmAllCategoriesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Listar todas as categorias?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary btn-confirm-all-categories">Listar todos</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        var form = document.getElementById('filtersFormCategories');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!e.submitter || e.submitter.id !== 'btnFilterCategories') return;
                var search = (form.querySelector('#search')?.value || '').trim();
                var status = (form.querySelector('#active')?.value || '').trim();
                var hasFilters = !!(search || status);
                if (!hasFilters) {
                    e.preventDefault();
                    var modalEl = document.getElementById('confirmAllCategoriesModal');
                    var confirmBtn = modalEl.querySelector('.btn-confirm-all-categories');
                    var modal = new bootstrap.Modal(modalEl);
                    var handler = function() {
                        confirmBtn.removeEventListener('click', handler);
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = 'all';
                        hidden.value = '1';
                        form.appendChild(hidden);
                        modal.hide();
                        form.submit();
                    };
                    confirmBtn.addEventListener('click', handler);
                    modal.show();
                }
            });
        }

        document.querySelectorAll('#search, #active').forEach(function(element) {
            element.addEventListener('change', function() {
                clearTimeout(window.filterTimeout);
                window.filterTimeout = setTimeout(function() {
                    element.closest('form').submit();
                }, 500);
            });
        });
    })();
</script>
@endpush
