@extends('layouts.app')

@section('title', 'Inventário do Produto')

@section('content')
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="mb-4">
            <h3 class="mb-2">
                <i class="bi bi-box-seam me-2"></i>
                Inventário do Produto
            </h3>
            <p class="text-muted mb-3">Detalhes de estoque e movimentos</p>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.dashboard') }}">Produtos</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.inventory.index') }}">Estoque</a></li>
                    <li class="breadcrumb-item active">Detalhes</li>
                </ol>
            </nav>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-box text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Produto</h6>
                                <h5 class="mb-0">{{ $product->name ?? 'Produto' }}</h5>
                            </div>
                        </div>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2">SKU: {{ $product->sku ?? 'N/A' }}</li>
                            <li class="mb-2">Preço: R$ {{ number_format($product->price ?? 0, 2, ',', '.') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3"><i
                                    class="bi bi-clipboard-data text-white"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Estoque</h6>
                                <h5 class="mb-0">Total: {{ $inventory->quantity ?? 0 }}</h5>
                            </div>
                        </div>
                        <div class="row text-center g-2">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Reservado</div>
                                    <div class="h6 mb-0 text-info">{{ $inventory->reserved_quantity ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Disponível</div>
                                    <div class="h6 mb-0 fw-bold">{{ $inventory->available_quantity ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Mín</div>
                                    <div class="h6 mb-0">{{ $inventory->min_quantity ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Máx</div>
                                    <div class="h6 mb-0">{{ $inventory->max_quantity ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid mt-3"><a href="{{ route('provider.inventory.adjust', $product) }}"
                                class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-2"></i>Ajustar
                                Estoque</a></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3"><i
                                    class="bi bi-info-circle text-white"></i></div>
                            <h6 class="text-muted mb-0">Resumo</h6>
                        </div>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2">Baixo estoque:
                                {{ isset($inventory) && method_exists($inventory, 'isLowStock') ? ($inventory->isLowStock() ? 'Sim' : 'Não') : 'N/A' }}
                            </li>
                            <li class="mb-2">Alto estoque:
                                {{ isset($inventory) && method_exists($inventory, 'isHighStock') ? ($inventory->isHighStock() ? 'Sim' : 'Não') : 'N/A' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Movimentos Recentes</h6>
            </div>
            <div class="card-body">
                @if (!empty($movements) && $movements->count() > 0)
                    <!-- Desktop View -->
                    <div class="table-responsive d-none d-md-block">
                        <table class="table modern-table mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Tipo</th>
                                    <th>Quantidade</th>
                                    <th>Motivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($movements as $m)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                                        <td>{{ $m->type }}</td>
                                        <td>{{ $m->quantity }}</td>
                                        <td>{{ $m->reason }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile View -->
                    <div class="mobile-view d-md-none">
                        @foreach ($movements as $m)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">{{ $m->type }}</h6>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <span class="badge bg-primary">{{ $m->quantity }}</span>
                                </div>
                                <p class="mb-0 small">{{ $m->reason }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                        <h6 class="text-muted">Nenhum movimento encontrado</h6>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <a href="{{ url()->previous() }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Voltar
            </a>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $inventory->updated_at->format('d/m/Y H:i') }}
            </small>
            <a href="{{ route('provider.inventory.adjust', $product) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square me-2"></i>Ajustar Estoque
            </a>
        </div>
    </div>
@endsection
