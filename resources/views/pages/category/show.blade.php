@extends( 'layouts.app' )

@section( 'breadcrumb' )
<li class="breadcrumb-item"><a href="{{ route( 'categories.index' ) }}">Categorias</a></li>
<li class="breadcrumb-item active">Detalhes</li>
@endsection

@section( 'content' )
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-tag me-2"></i>Detalhes da Categoria
        </h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4">

                @php($tenantId = auth()->user()->tenant_id ?? null)
                @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                @php($isGlobal = $category->isGlobal())
                @php($isAdmin = false)
                @role('admin')
                @php($isAdmin = true)
                @endrole

                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Nome</label>
                        <h5 class="mb-0">{{ $category->name }} @if($isCustom)<span class="badge bg-primary ms-2">Pessoal</span>@else<span class="badge bg-secondary ms-2">Sistema</span>@endif</h5>
                    </div>
                </div>

                @if($category->parent)
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Categoria Pai</label>
                        <h5 class="mb-0">
                            <a href="{{ route('categories.show', $category->parent->slug) }}" class="text-decoration-none">
                                {{ $category->parent->name }}
                            </a>
                            @php($parentIsCustom = $tenantId ? $category->parent->isCustomFor($tenantId) : false)
                            @if($parentIsCustom)
                            <span class="badge bg-primary ms-2">Pessoal</span>
                            @else
                            <span class="badge bg-secondary ms-2">Sistema</span>
                            @endif
                        </h5>
                    </div>
                </div>
                @endif

                @if($isAdmin)
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Slug</label>
                        <h5 class="mb-0"><span class="text-code">{{ $category->slug }}</span></h5>
                    </div>
                </div>
                @endif

                @if($isAdmin || !$isGlobal)
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Criado em</label>
                        <h5 class="mb-0">{{ $category->created_at?->format( 'd/m/Y H:i' ) }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Atualizado em</label>
                        <h5 class="mb-0">{{ $category->updated_at?->format( 'd/m/Y H:i' ) }}</h5>
                    </div>
                </div>
                @endif
            </div>

            <div class="mt-4 d-flex justify-content-between">
                <a href="{{ route( 'categories.index' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
                <div>
                    @php($tenantId = auth()->user()->tenant_id ?? null)
                    @php($isCustom = $tenantId ? $category->isCustomFor($tenantId) : false)
                    @php($isGlobal = $category->isGlobal())
                    @php($isAdmin = false)
                    @role('admin')
                    @php($isAdmin = true)
                    @endrole
                    @php($hasChildren = $category->hasChildren())
                    @php($hasServices = $category->services()->exists())
                    @php($hasProducts = \App\Models\Product::query()->where('category_id', $category->id)->whereNull('deleted_at')->exists())
                    @php($canDelete = !$hasChildren && !$hasServices && !$hasProducts)
                    @if(($isAdmin && $isGlobal) || (!$isAdmin && $isCustom))
                    <a href="{{ route( 'categories.edit', $category->id ) }}" class="btn btn-secondary">
                        <i class="bi bi-pencil me-2"></i>Editar
                    </a>
                    @if($canDelete)
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteModal-{{ $category->id }}">
                        <i class="bi bi-trash me-2"></i>Excluir
                    </button>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="deleteModal-{{ $category->id }}" tabindex="-1"
    aria-labelledby="deleteModalLabel-{{ $category->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $category->id }}">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir a categoria <strong>"{{ $category->name }}"</strong>?
                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route( 'categories.destroy', $category->id ) }}" method="POST" class="d-inline">
                    @csrf
                    @method( 'DELETE' )
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
