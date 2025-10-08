@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-box me-2"></i>Meus Produtos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Produtos</li>
                </ol>
            </nav>
        </div>

        <!-- Filtros e Ações -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form action="{{ route( 'provider.products.index' ) }}" method="GET">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-4">
                            <label for="search" class="form-label">Pesquisar</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="{{ request( 'search' ) }}" placeholder="Nome ou código...">
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Todos</option>
                                <option value="1" {{ request( 'status' ) == '1' ? 'selected' : '' }}>Ativos</option>
                                <option value="0" {{ request( 'status' ) == '0' ? 'selected' : '' }}>Inativos</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label for="order_by" class="form-label">Ordenar por</label>
                            <select class="form-select" id="order_by" name="order_by">
                                <option value="name_asc" {{ request( 'order_by' ) == 'name_asc' ? 'selected' : '' }}>Nome (A-Z)
                                </option>
                                <option value="name_desc" {{ request( 'order_by' ) == 'name_desc' ? 'selected' : '' }}>Nome
                                    (Z-A)
                                </option>
                                <option value="price_asc" {{ request( 'order_by' ) == 'price_asc' ? 'selected' : '' }}>Preço
                                    (Menor)
                                </option>
                                <option value="price_desc" {{ request( 'order_by' ) == 'price_desc' ? 'selected' : '' }}>Preço
                                    (Maior)
                                </option>
                                <option value="created_at_desc" {{ request( 'order_by', 'created_at_desc' ) == 'created_at_desc' ? 'selected' : '' }}>Mais Recentes</option>
                                <option value="created_at_asc" {{ request( 'order_by' ) == 'created_at_asc' ? 'selected' : '' }}>Mais Antigos
                                </option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search me-2"></i>Filtrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cards de Ação -->
        <div class="row g-4 mb-4">
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-action border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-primary text-white mb-3">
                            <i class="bi bi-plus-lg"></i>
                        </div>
                        <h5 class="card-title">Novo Produto</h5>
                        <p class="card-text">Adicione um novo produto ao seu catálogo.</p>
                        <a href="{{ route( 'provider.products.create' ) }}" class="btn btn-primary stretched-link">
                            <i class="bi bi-plus-circle me-2"></i>Criar Produto
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card card-action border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-success text-white mb-3">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <h5 class="card-title">Importar Produtos</h5>
                        <p class="card-text">Importe uma lista de produtos de um arquivo CSV.</p>
                        <a href="#" class="btn btn-success stretched-link">
                            <i class="bi bi-upload me-2"></i>Importar CSV
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card card-action border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="icon-circle bg-info text-white mb-3">
                            <i class="bi bi-download"></i>
                        </div>
                        <h5 class="card-title">Exportar Produtos</h5>
                        <p class="card-text">Exporte seus produtos para um arquivo CSV ou PDF.</p>
                        <a href="#" class="btn btn-info stretched-link">
                            <i class="bi bi-download me-2"></i>Exportar Dados
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Resultados -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h5 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>Resultados
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped table-borderless mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 5%;">#</th>
                                <th style="width: 40%;">Produto</th>
                                <th class="text-center" style="width: 10%;">Status</th>
                                <th class="text-end" style="width: 15%;">Preço</th>
                                <th class="text-center" style="width: 15%;">Criado em</th>
                                <th class="text-center" style="width: 15%;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ( $products as $product )
                                <tr>
                                    <td class="text-center align-middle">
                                        <img src="{{ $product->image ? asset( 'storage/products/' . $product->image ) : asset('assets/img/img_not_found.png') }}"
                                            alt="{{ $product->name }}" class="img-thumbnail" width="50">
                                    </td>
                                    <td class="align-middle">
                                        <a href="{{ route( 'provider.products.show', $product->id ) }}"
                                            class="fw-bold text-dark">{{ $product->name }}</a>
                                        <p class="text-muted mb-0">{{ $product->code }}</p>
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ( $product->active )
                                            <span class="badge bg-success-soft text-success">Ativo</span>
                                        @else
                                            <span class="badge bg-danger-soft text-danger">Inativo</span>
                                        @endif
                                    </td>
                                    <td class="text-end align-middle">R$ {{ number_format( $product->price, 2, ',', '.' ) }}</td>
                                    <td class="text-center align-middle">{{ $product->created_at->format( 'd/m/Y' ) }}</td>
                                    <td class="text-center align-middle">
                                        <a href="{{ route( 'provider.products.show', $product->id ) }}"
                                            class="btn btn-sm btn-outline-primary me-1" title="Ver"><i
                                                class="bi bi-eye"></i></a>
                                        <a href="{{ route( 'provider.products.edit', $product->id ) }}"
                                            class="btn btn-sm btn-outline-secondary me-1" title="Editar"><i
                                                class="bi bi-pencil"></i></a>
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                            data-product-id="{{ $product->id }}" data-product-name="{{ $product->name }}"
                                            title="Excluir">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-exclamation-circle-fill fs-1 text-muted"></i>
                                        <p class="mt-3 mb-0">Nenhum produto encontrado.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ( $products->hasPages() )
                <div class="card-footer bg-transparent border-0">
                    @include( 'partials.components.table_paginator', [ 'paginator' => $products ] )
                </div>
            @endif
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
                    <p>Tem certeza que deseja excluir o produto <strong id="productName"></strong>?</p>
                    <p class="text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Esta ação não pode ser
                        desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const deleteModal = new bootstrap.Modal( document.getElementById( 'deleteModal' ) );
            const deleteButtons = document.querySelectorAll( '.btn-delete' );
            const deleteForm = document.getElementById( 'deleteForm' );
            const productName = document.getElementById( 'productName' );

            deleteButtons.forEach( button => {
                button.addEventListener( 'click', function () {
                    const productId = this.dataset.productId;
                    const prodName = this.dataset.productName;
                    const url = `{{ route( 'provider.products.destroy', ':id' ) }}`.replace( ':id', productId );

                    productName.textContent = prodName;
                    deleteForm.action = url;
                    deleteModal.show();
                } );
            } );
        } );
    </script>
@endpush
