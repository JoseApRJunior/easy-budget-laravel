@extends('layouts.app')

@section('title', 'Inventário do Produto')

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0"><i class="bi bi-box-seam me-2"></i>Inventário do Produto</h1>
                <p class="text-muted mb-0">Detalhes de estoque e movimentos.</p>
            </div>
            <nav aria-label="breadcrumb">
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
                                <h5 class="mb-0">Qtd: {{ $inventory->quantity ?? 0 }}</h5>
                            </div>
                        </div>
                        <div class="row text-center">
                            <div class="col">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Mín</div>
                                    <div class="h6 mb-0">{{ $inventory->min_quantity ?? 0 }}</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="border rounded p-3">
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
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Movimentos Recentes</h6>
            </div>
            <div class="card-body">
                @if (!empty($movements) && $movements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
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
                @else
                    <div class="text-center py-5"><i class="bi bi-inbox display-4 text-muted mb-3"></i>
                        <h6 class="text-muted">Nenhum movimento encontrado</h6>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .avatar-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .text-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: .85em
        }
    </style>
@endpush
