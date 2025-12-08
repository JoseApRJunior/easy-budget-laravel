@extends('layouts.app')

@section('title', 'Categorias')

@push('styles')
<style>
/* ========================================
   VARIÁVEIS E CORES
======================================== */
:root {
    --category-primary: #0d6efd;
    --category-success: #198754;
    --category-danger: #dc3545;
    --category-warning: #ffc107;
    --category-info: #0dcaf0;
    --category-secondary: #6c757d;
    --category-light: #f8f9fa;
    --category-dark: #212529;
    --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
    --radius: 0.5rem;
    --transition: all 0.3s ease;
}

/* ========================================
   DESKTOP - TABELA OTIMIZADA
======================================== */
@media (min-width: 769px) {
    .mobile-view { display: none !important; }
    
    .category-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .category-table thead th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 1rem 0.75rem;
        border: none;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .category-table tbody tr {
        transition: var(--transition);
        background: white;
    }
    
    .category-table tbody tr:hover {
        background: #f8f9ff;
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
    }
    
    .category-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }
    
    .category-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-size: 1.2rem;
    }
    
    .category-name-cell {
        font-weight: 600;
        color: var(--category-dark);
        font-size: 0.95rem;
    }
    
    .category-badge {
        padding: 0.35rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-system { background: #e3f2fd; color: #1976d2; }
    .badge-personal { background: #f3e5f5; color: #7b1fa2; }
    .badge-active { background: #e8f5e9; color: #2e7d32; }
    .badge-inactive { background: #ffebee; color: #c62828; }
    
    .action-btn-group {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: var(--transition);
        cursor: pointer;
    }
    
    .action-btn:hover {
        transform: scale(1.15);
        box-shadow: var(--shadow-md);
    }
    
    .action-btn-view { background: #e3f2fd; color: #1976d2; }
    .action-btn-edit { background: #fff3e0; color: #f57c00; }
    .action-btn-delete { background: #ffebee; color: #c62828; }
    .action-btn-restore { background: #e8f5e9; color: #2e7d32; }
}

/* ========================================
   MOBILE - CARDS MODERNOS
======================================== */
@media (max-width: 768px) {
    .desktop-view { display: none !important; }
    
    .category-card {
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow-sm);
        margin-bottom: 1rem;
        overflow: hidden;
        transition: var(--transition);
        border-left: 4px solid transparent;
    }
    
    .category-card.system { border-left-color: #1976d2; }
    .category-card.personal { border-left-color: #7b1fa2; }
    
    .category-card:active {
        transform: scale(0.98);
        box-shadow: var(--shadow-md);
    }
    
    .card-header-mobile {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 1rem;
        color: white;
    }
    
    .card-title-mobile {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .card-subtitle-mobile {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-top: 0.25rem;
    }
    
    .card-body-mobile {
        padding: 1rem;
    }
    
    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-row:last-child { border-bottom: none; }
    
    .info-label {
        font-size: 0.8rem;
        color: var(--category-secondary);
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--category-dark);
    }
    
    .card-actions-mobile {
        padding: 1rem;
        background: var(--category-light);
        display: flex;
        gap: 0.5rem;
        justify-content: stretch;
    }
    
    .card-actions-mobile .btn {
        flex: 1;
        padding: 0.75rem;
        border-radius: var(--radius);
        font-weight: 600;
        font-size: 0.85rem;
        border: none;
        transition: var(--transition);
    }
    
    .card-actions-mobile .btn:active {
        transform: scale(0.95);
    }
}

/* ========================================
   FILTROS RESPONSIVOS
======================================== */
.filter-card {
    border: none;
    box-shadow: var(--shadow-sm);
    border-radius: var(--radius);
    margin-bottom: 1.5rem;
}

.filter-card .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 1.25rem;
    border-radius: var(--radius) var(--radius) 0 0;
}

.filter-card .card-header h5 {
    margin: 0;
    font-weight: 600;
    font-size: 1rem;
}

.filter-btn {
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    transition: var(--transition);
}

.filter-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* ========================================
   ANIMAÇÕES
======================================== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.category-card, .category-table tbody tr {
    animation: fadeInUp 0.4s ease;
}

/* ========================================
   ESTADOS VAZIOS
======================================== */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--category-secondary);
}

.empty-state-icon {
    font-size: 4rem;
    opacity: 0.3;
    margin-bottom: 1rem;
}

.empty-state-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.empty-state-text {
    font-size: 0.95rem;
    opacity: 0.8;
}
</style>
@endpush

@section('content')
<div class="container-fluid py-3">
    <!-- Cabeçalho -->
    <div class="mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">
                    <i class="bi bi-tags me-2"></i>
                    Categorias
                </h1>
                <p class="text-muted mb-0">Gerencie as categorias do sistema</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 justify-content-md-end">
                        <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Categorias</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card filter-card">
        <div class="card-header">
            <h5><i class="bi bi-funnel me-2"></i>Filtros de Busca</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('categories.index') }}">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search" 
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Nome, slug...">
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="active">
                            <option value="">Todos</option>
                            <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Ativo</option>
                            <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Por página</label>
                        <select class="form-select" name="per_page">
                            @php($pp = (int)($filters['per_page'] ?? 10))
                            <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                            <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-6">
                        <label class="form-label">Registros</label>
                        <select class="form-select" name="deleted">
                            <option value="">Atuais</option>
                            <option value="only" {{ ($filters['deleted'] ?? '') === 'only' ? 'selected' : '' }}>Deletados</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary filter-btn">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                        <a href="{{ route('categories.index') }}" class="btn btn-secondary filter-btn">
                            <i class="bi bi-x me-2"></i>Limpar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista -->
    <div class="card">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i>
                        Lista de Categorias
                        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <span class="badge bg-primary ms-2">{{ $categories->total() }}</span>
                        @endif
                    </h5>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <a href="{{ route('categories.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Nova Categoria
                    </a>
                </div>
            </div>
        </div>

        <!-- DESKTOP VIEW -->
        <div class="desktop-view">
            <div class="table-responsive">
                <table class="category-table table mb-0">
                    <thead>
                        <tr>
                            <th width="60"></th>
                            <th>Categoria</th>
                            <th>Subcategoria</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th width="150" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php($tenantId = auth()->user()->tenant_id ?? null)
                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    <div class="category-icon">
                                        <i class="bi bi-tag-fill"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="category-name-cell">
                                        {{ $category->parent ? $category->parent->name : $category->name }}
                                    </div>
                                </td>
                                <td>
                                    @if($category->parent)
                                        <span class="text-muted">{{ $category->name }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                                    @if($isCustom)
                                        <span class="category-badge badge-personal">Pessoal</span>
                                    @else
                                        <span class="category-badge badge-system">Sistema</span>
                                    @endif
                                </td>
                                <td>
                                    @if($category->is_active)
                                        <span class="category-badge badge-active">Ativo</span>
                                    @else
                                        <span class="category-badge badge-inactive">Inativo</span>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $category->created_at?->format('d/m/Y H:i') ?? '—' }}
                                    </small>
                                </td>
                                <td>
                                    <div class="action-btn-group">
                                        <a href="{{ route('categories.show', $category->slug) }}" 
                                           class="action-btn action-btn-view" 
                                           title="Visualizar">
                                            <i class="bi bi-eye-fill"></i>
                                        </a>
                                        @if(!$category->isGlobal() || auth()->user()->hasRole('admin'))
                                            <a href="{{ route('categories.edit', $category->id) }}" 
                                               class="action-btn action-btn-edit" 
                                               title="Editar">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <button type="button" 
                                                    class="action-btn action-btn-delete" 
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteModal"
                                                    data-delete-url="{{ route('categories.destroy', $category->id) }}"
                                                    data-category-name="{{ $category->name }}"
                                                    title="Excluir">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="bi bi-inbox"></i>
                                        </div>
                                        <div class="empty-state-title">Nenhuma categoria encontrada</div>
                                        <div class="empty-state-text">
                                            Tente ajustar os filtros ou criar uma nova categoria
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MOBILE VIEW -->
        <div class="mobile-view">
            <div class="p-3">
                @php($tenantId = auth()->user()->tenant_id ?? null)
                @forelse($categories as $category)
                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                    <div class="category-card {{ $isCustom ? 'personal' : 'system' }}">
                        <div class="card-header-mobile">
                            <div class="card-title-mobile">
                                <i class="bi bi-tag-fill"></i>
                                {{ $category->parent ? $category->parent->name : $category->name }}
                            </div>
                            @if($category->parent)
                                <div class="card-subtitle-mobile">
                                    Subcategoria: {{ $category->name }}
                                </div>
                            @endif
                        </div>
                        
                        <div class="card-body-mobile">
                            <div class="info-row">
                                <span class="info-label">Tipo</span>
                                <span class="info-value">
                                    <span class="category-badge {{ $isCustom ? 'badge-personal' : 'badge-system' }}">
                                        {{ $isCustom ? 'Pessoal' : 'Sistema' }}
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="category-badge {{ $category->is_active ? 'badge-active' : 'badge-inactive' }}">
                                        {{ $category->is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </span>
                            </div>
                            
                            <div class="info-row">
                                <span class="info-label">Criado em</span>
                                <span class="info-value">
                                    {{ $category->created_at?->format('d/m/Y') ?? '—' }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="card-actions-mobile">
                            <a href="{{ route('categories.show', $category->slug) }}" 
                               class="btn btn-info">
                                <i class="bi bi-eye-fill me-1"></i>Ver
                            </a>
                            @if(!$category->isGlobal() || auth()->user()->hasRole('admin'))
                                <a href="{{ route('categories.edit', $category->id) }}" 
                                   class="btn btn-warning">
                                    <i class="bi bi-pencil-fill me-1"></i>Editar
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="bi bi-inbox"></i>
                        </div>
                        <div class="empty-state-title">Nenhuma categoria encontrada</div>
                        <div class="empty-state-text">
                            Tente ajustar os filtros ou criar uma nova categoria
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Paginação -->
        @if($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
            <div class="card-footer bg-white">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja excluir a categoria <strong id="deleteCategoryName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const url = button.getAttribute('data-delete-url');
            const name = button.getAttribute('data-category-name');
            
            document.getElementById('deleteCategoryName').textContent = name;
            document.getElementById('deleteForm').action = url;
        });
    }
});
</script>
@endpush
@endsection
