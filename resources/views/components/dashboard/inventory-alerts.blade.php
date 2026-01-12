@php
    $colors = config('theme.colors');
    $textPrimary = $colors['text'] ?? '#1e293b';
    $textSecondary = $colors['secondary'] ?? '#94a3b8';
@endphp

<div class="card border-0 shadow-sm h-100" style="--text-primary: {{ $textPrimary }}; --text-secondary: {{ $textSecondary }};">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--text-primary);">
            <i class="bi bi-exclamation-triangle me-2" style="color: {{ $colors['warning'] }};"></i>Alertas de Estoque
        </h5>
        @if($count > 0)
            <span class="badge rounded-pill" style="background-color: {{ $colors['danger'] }}; color: #fff;">{{ $count }}</span>
        @endif
    </div>
    <div class="card-body">
        @if($items->isEmpty())
            <div class="text-center py-4">
                <div class="avatar-circle mx-auto mb-3" style="background-color: {{ $colors['success'] }}1a; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                    <i class="bi bi-check2-circle fs-4" style="color: {{ $colors['success'] }};"></i>
                </div>
                <p class="mb-0" style="color: var(--text-secondary);">Tudo em ordem! Nenhum item com estoque baixo.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-uppercase" style="color: var(--text-secondary);">
                            <th>Produto</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-center">Mín</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                            <tr>
                                <td>
                                    <div class="fw-semibold" style="color: var(--text-primary);">{{ $item->product->name }}</div>
                                    <div class="small" style="color: var(--text-secondary);">{{ $item->product->sku ?? 'Sem SKU' }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill px-3" style="background-color: {{ $colors['warning'] }}1a; color: {{ $colors['warning'] }};">
                                        {{ $item->quantity }}
                                    </span>
                                </td>
                                <td class="text-center small" style="color: var(--text-secondary);">
                                    {{ $item->min_quantity }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-center">
                <a href="{{ route('provider.inventory.index', ['filter' => 'low_stock']) }}" class="btn btn-link btn-sm text-decoration-none" style="color: {{ $colors['primary'] }};">
                    Ver todo o inventário <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        @endif
    </div>
</div>
