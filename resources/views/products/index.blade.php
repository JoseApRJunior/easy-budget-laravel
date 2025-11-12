@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Produtos</h3>
                    <div class="card-tools">
                        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Novo Produto
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <form method="GET" action="{{ route('products.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="search">Buscar</label>
                                    <input type="text" class="form-control" id="search" name="search"
                                           value="{{ $filters['search'] ?? '' }}" placeholder="Nome, SKU ou descrição">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="category_id">Categoria</label>
                                    <select class="form-control" id="category_id" name="category_id">
                                        <option value="">Todas</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}"
                                                    {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="active">Status</label>
                                    <select class="form-control" id="active" name="active">
                                        <option value="">Todos</option>
                                        <option value="1" {{ ($filters['active'] ?? '') === '1' ? 'selected' : '' }}>Ativo</option>
                                        <option value="0" {{ ($filters['active'] ?? '') === '0' ? 'selected' : '' }}>Inativo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="min_price">Preço Mínimo</label>
                                    <input type="number" class="form-control" id="min_price" name="min_price"
                                           value="{{ $filters['min_price'] ?? '' }}" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="max_price">Preço Máximo</label>
                                    <input type="number" class="form-control" id="max_price" name="max_price"
                                           value="{{ $filters['max_price'] ?? '' }}" step="0.01" min="0">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <a href="{{ route('products.index') }}" class="btn btn-secondary btn-block">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Tabela de Produtos -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Imagem</th>
                                    <th>Nome</th>
                                    <th>SKU</th>
                                    <th>Categoria</th>
                                    <th>Preço</th>
                                    <th>Status</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products as $product)
                                    <tr>
                                        <td class="text-center">
                                            @if($product->image)
                                                <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            @else
                                                <div class="bg-light d-flex align-items-center justify-content-center"
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>{{ $product->name }}</td>
                                        <td><span class="text-code">{{ $product->sku }}</span></td>
                                        <td>{{ $product->category->name ?? 'N/A' }}</td>
                                        <td>R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                                        <td>
                                            @if($product->active)
                                                <span class="badge badge-success">Ativo</span>
                                            @else
                                                <span class="badge badge-danger">Inativo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('products.show', $product->sku) }}"
                                                   class="btn btn-info" title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('products.edit', $product->sku) }}"
                                                   class="btn btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('products.toggle', $product->sku) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('{{ $product->active ? 'Desativar' : 'Ativar' }} este produto?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn {{ $product->active ? 'btn-secondary' : 'btn-success' }}"
                                                            title="{{ $product->active ? 'Desativar' : 'Ativar' }}">
                                                        <i class="fas fa-{{ $product->active ? 'ban' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('products.destroy', $product->sku) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('Excluir este produto permanentemente?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <br>
                                            Nenhum produto encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    @if($products->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $products->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Auto-submit do formulário de filtros (opcional - para busca em tempo real)
document.querySelectorAll('#search, #category_id, #active, #min_price, #max_price').forEach(function(element) {
    element.addEventListener('change', function() {
        // Opcional: auto-submit após 500ms de inatividade
        clearTimeout(window.filterTimeout);
        window.filterTimeout = setTimeout(function() {
            element.closest('form').submit();
        }, 500);
    });
});
</script>
@endsection
