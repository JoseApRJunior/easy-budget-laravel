<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold text-dark">
            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Alertas de Estoque
        </h5>
        @if($count > 0)
            <span class="badge rounded-pill bg-danger text-white">{{ $count }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($items->isEmpty())
            <div class="text-center py-4">
                <div class="avatar-circle mx-auto mb-3 bg-success bg-opacity-10 d-flex align-items-center justify-content-center rounded-circle" style="width: 48px; height: 48px;">
                    <i class="bi bi-check2-circle fs-4 text-success"></i>
                </div>
                <p class="mb-0 text-muted">Tudo em ordem! Nenhum item com estoque baixo.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase text-muted">
                            <th>Produto</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-center">Mín</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $item->product->name }}</div>
                                    <div class="small text-muted">{{ $item->product->sku ?? 'Sem SKU' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3 bg-warning bg-opacity-10 text-warning">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $item->min_quantity }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-center">
                <a href="{{ route('provider.inventory.index', ['filter' => 'low_stock']) }}" class="btn btn-link btn-sm text-decoration-none text-primary">
                    Ver todo o inventário <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</div>
