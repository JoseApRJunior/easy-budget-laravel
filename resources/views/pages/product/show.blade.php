@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-box me-2"></i>Detalhes do Produto
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.products.index' ) }}">Produtos</a></li>
                    <li class="breadcrumb-item active">{{ $product->code }}</li>
                </ol>
            </nav>
        </div>

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-end align-items-center mb-4 gap-2">
            <a href="{{ route( 'provider.products.edit', $product->id ) }}" class="btn btn-secondary">
                <i class="bi bi-pencil-square me-2"></i>Editar
            </a>
            @if ( $product->active )
                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                    <i class="bi bi-toggle-off me-2"></i>Desativar
                </button>
            @else
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#activateModal">
                    <i class="bi bi-toggle-on me-2"></i>Ativar
                </button>
            @endif
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                <i class="bi bi-trash me-2"></i>Excluir
            </button>
        </div>

        <div class="row g-4">
            <!-- Imagem e Status -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="image-container mb-3">
                            <img src="{{ $product->image ? asset( 'storage/products/' . $product->image ) : '/assets/img/img_not_found.png' }}"
                                alt="{{ $product->name }}" class="img-fluid rounded" style="max-height: 200px;">
                        </div>
                        <h5 class="card-title">{{ $product->name }}</h5>
                        <p class="text-muted mb-2">Código: {{ $product->code }}</p>
                        @if ( $product->active )
                            <span class="badge bg-success-soft text-success fs-6">Ativo</span>
                        @else
                            <span class="badge bg-danger-soft text-danger fs-6">Inativo</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informações Detalhadas -->
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Informações Gerais
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Preço</span>
                                <strong class="text-dark">R$ {{ number_format( $product->price, 2, ',', '.' ) }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Estoque</span>
                                <strong class="text-dark">{{ $product->stock_quantity ?? 'N/A' }} unidades</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Data de Criação</span>
                                <span class="text-muted">{{ $product->created_at->format( 'd/m/Y H:i' ) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Última Atualização</span>
                                <span class="text-muted">{{ $product->updated_at->format( 'd/m/Y H:i' ) }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Categoria</span>
                                <span
                                    class="badge bg-primary-soft text-primary">{{ $product->category->name ?? 'N/A' }}</span>
                            </li>
                            <li class="list-group-item">
                                <span>Tags</span>
                                <div class="mt-2">
                                    @forelse ( $product->tags as $tag )
                                        <span class="badge bg-secondary-soft text-secondary me-1">{{ $tag->name }}</span>
                                    @empty
                                        <span class="text-muted">Nenhuma tag associada.</span>
                                    @endforelse
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-file-text me-2"></i>Descrição
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">{{ $product->description ?? 'Nenhuma descrição fornecida.' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Ativação -->
    <div class="modal fade" id="activateModal" tabindex="-1" aria-labelledby="activateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="activateModalLabel">Confirmar Ativação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja ativar o produto <strong>{{ $product->name }}</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route( 'provider.products.activate', $product->id ) }}" method="POST">
                        @csrf
                        @method( 'PATCH' )
                        <button type="submit" class="btn btn-success">Ativar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Desativação -->
    <div class="modal fade" id="deactivateModal" tabindex="-1" aria-labelledby="deactivateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deactivateModalLabel">Confirmar Desativação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja desativar o produto <strong>{{ $product->name }}</strong>?</p>
                    <p class="text-warning"><i class="bi bi-exclamation-triangle-fill me-2"></i>O produto não ficará visível
                        para novos orçamentos.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route( 'provider.products.deactivate', $product->id ) }}" method="POST">
                        @csrf
                        @method( 'PATCH' )
                        <button type="submit" class="btn btn-warning">Desativar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o produto <strong>{{ $product->name }}</strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Esta ação não pode ser
                        desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route( 'provider.products.destroy', $product->id ) }}" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
