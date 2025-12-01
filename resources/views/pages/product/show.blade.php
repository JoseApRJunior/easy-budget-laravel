@extends( 'layouts.app' )

@section( 'title', 'Detalhes do Produto: ' . $product->name )

@section( 'content' )
    <div class="container-fluid py-1">
        @push( 'styles' )
            <style>
                .nav-tabs .nav-link {
                    background-color: #f8f9fa;
                    color: #212529;
                }

                .nav-tabs .nav-link.active {
                    background-color: #ffffff;
                    border-color: #dee2e6 #dee2e6 #fff;
                    color: #000;
                }

                .nav-tabs {
                    border-bottom: 1px solid #dee2e6;
                }
            </style>
        @endpush
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-box-seam me-2"></i>
                    {{ $product->name }}
                </h1>
                <p class="text-muted mb-0">SKU: <span class="text-code">{{ $product->sku }}</span></p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route( 'provider.products.edit', $product->sku ) }}" class="btn btn-warning">
                    <i class="bi bi-pencil-square me-1"></i> Editar
                </a>
                <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Voltar
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Coluna Esquerda: Imagem e Status -->

            <div class="col-md-4 mb-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                            class="img-fluid rounded shadow-sm mb-3" style="max-height: 300px; object-fit: cover;">

                        <div class="d-grid gap-2">
                            @if( $product->active )
                                <div class="alert alert-success py-2 mb-0">
                                    <i class="bi bi-check-circle-fill me-1"></i> Produto Ativo
                                </div>
                            @else
                                <div class="alert alert-danger py-2 mb-0">
                                    <i class="bi bi-x-circle-fill me-1"></i> Produto Inativo
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Card de Ações Rápidas -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-lightning-charge me-1"></i> Ações Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <form action="{{ route( 'provider.products.toggle-status', $product->sku ) }}" method="POST"
                                onsubmit="return confirm('{{ $product->active ? 'Desativar' : 'Ativar' }} este produto?')">
                                @csrf
                                @method( 'PATCH' )
                                <button type="submit"
                                    class="btn {{ $product->active ? 'btn-outline-danger' : 'btn-outline-success' }} w-100">
                                    <i class="bi bi-{{ $product->active ? 'slash-circle' : 'check-lg' }} me-1"></i>
                                    {{ $product->active ? 'Desativar Produto' : 'Ativar Produto' }}
                                </button>
                            </form>

                            <form action="{{ route( 'provider.products.destroy', $product->sku ) }}" method="POST"
                                onsubmit="return confirm('Tem certeza que deseja excluir este produto permanentemente?')">
                                @csrf
                                @method( 'DELETE' )
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="bi bi-trash me-1"></i> Excluir Produto
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Detalhes e Abas -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="productTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active text-dark" id="details-tab" data-bs-toggle="tab"
                                    data-bs-target="#details" type="button" role="tab" aria-selected="true">
                                    <i class="bi bi-info-circle me-1"></i> Detalhes
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-dark" id="inventory-tab" data-bs-toggle="tab"
                                    data-bs-target="#inventory" type="button" role="tab" aria-selected="false">
                                    <i class="bi bi-boxes me-1"></i> Inventário
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="productTabsContent">
                            <!-- Aba Detalhes -->
                            <div class="tab-pane fade show active" id="details" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Preço</label>
                                        <p class="h4 text-success">R$ {{ number_format( $product->price, 2, ',', '.' ) }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Categoria</label>
                                        <p class="h5">
                                            @if( $product->category )
                                                <span class="badge bg-primary">{{ $product->category->name }}</span>
                                            @else
                                                <span class="text-muted">Sem categoria</span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Unidade</label>
                                        <p class="h5">{{ $product->unit ?? 'N/A' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted small text-uppercase fw-bold">Criado em</label>
                                        <p>{{ $product->created_at->format( 'd/m/Y H:i' ) }}</p>
                                    </div>
                                    <div class="col-12">
                                        <label class="text-muted small text-uppercase fw-bold">Descrição</label>
                                        <div class="p-3 rounded border">
                                            {{ $product->description ?? 'Nenhuma descrição informada.' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Aba Inventário -->
                            <div class="tab-pane fade" id="inventory" role="tabpanel">
                                @php
                                    $inventory   = $product->productInventory->first();
                                    $quantity    = $inventory ? $inventory->quantity : 0;
                                    $minQuantity = $inventory ? $inventory->min_quantity : 0;
                                    $maxQuantity = $inventory ? $inventory->max_quantity : null;

                                    $statusClass = 'success';
                                    $statusLabel = 'Estoque OK';

                                    if ( $quantity <= 0 ) {
                                        $statusClass = 'danger';
                                        $statusLabel = 'Sem Estoque';
                                    } elseif ( $quantity <= $minQuantity ) {
                                        $statusClass = 'warning';
                                        $statusLabel = 'Estoque Baixo';
                                    }
                                @endphp

                                <div class="row mb-4">
                                    <div class="col-md-4 text-center">
                                        <div class="p-3 border rounded">
                                            <small class="text-muted text-uppercase">Quantidade Atual</small>
                                            <h2 class="display-4 fw-bold text-{{ $statusClass }} mb-0">{{ $quantity }}</h2>
                                            <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="p-3 border rounded">
                                                    <small class="text-muted">Mínimo</small>
                                                    <p class="h4 mb-0">{{ $minQuantity }}</p>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="p-3 border rounded">
                                                    <small class="text-muted">Máximo</small>
                                                    <p class="h4 mb-0">{{ $maxQuantity ?? '∞' }}</p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex gap-2 mt-2">
                                                    <a href="{{ route( 'provider.inventory.entry', $product->sku ) }}"
                                                        class="btn btn-success flex-grow-1">
                                                        <i class="bi bi-arrow-down-circle me-1"></i> Entrada
                                                    </a>
                                                    <a href="{{ route( 'provider.inventory.exit', $product->sku ) }}"
                                                        class="btn btn-warning flex-grow-1">
                                                        <i class="bi bi-arrow-up-circle me-1"></i> Saída
                                                    </a>
                                                    <a href="{{ route( 'provider.inventory.adjust', $product->sku ) }}"
                                                        class="btn btn-secondary flex-grow-1">
                                                        <i class="bi bi-sliders me-1"></i> Ajustar
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Para ver o histórico completo de movimentações, acesse o <a
                                        href="{{ route( 'provider.inventory.show', $product->sku ) }}"
                                        class="alert-link">Painel de Inventário</a> deste produto.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
