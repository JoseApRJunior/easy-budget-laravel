@extends('layouts.app')

@section('title', 'Movimentações de Estoque')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Movimentações de Estoque"
        icon="arrow-left-right"
        :breadcrumb-items="[
            'Inventário' => route('provider.inventory.dashboard'),
            'Movimentações' => '#'
        ]">
        <p class="text-muted mb-0">Histórico completo de movimentações de estoque</p>
    </x-page-header>

    <!-- Filtros de Busca -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
        </div>
        <div class="card-body">
            <form id="filtersFormMovements" method="GET" action="{{ route('provider.inventory.movements') }}">
                @if(request('product_id'))
                    <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                @endif
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search">Buscar Produto</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Nome ou SKU" value="{{ request('search') ?? request('sku') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="type">Tipo</label>
                            <select name="type" id="type" class="form-select tom-select">
                                <option value="">Todos os Tipos</option>
                                <option value="entry" {{ request('type') == 'entry' ? 'selected' : '' }}>Entrada</option>
                                <option value="exit" {{ request('type') == 'exit' ? 'selected' : '' }}>Saída</option>
                                <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Ajuste</option>
                                <option value="reservation" {{ request('type') == 'reservation' ? 'selected' : '' }}>Reserva</option>
                                <option value="cancellation" {{ request('type') == 'cancellation' ? 'selected' : '' }}>Cancelamento</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="start_date">Data Inicial</label>
                            <input type="date" name="start_date" id="start_date" class="form-control"
                                value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="end_date">Data Final</label>
                            <input type="date" name="end_date" id="end_date" class="form-control"
                                value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-button type="submit" icon="search" label="Filtrar" />
                            <x-button type="link" :href="route('provider.inventory.movements')" variant="secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 d-flex align-items-center flex-wrap">
                        <span class="me-2">
                            <i class="bi bi-list-ul me-1"></i>
                            <span class="d-none d-sm-inline">Registros de Movimentação</span>
                            <span class="d-sm-none">Movimentações</span>
                        </span>
                        <span class="text-muted" style="font-size: 0.875rem;">
                            ({{ $movements->total() }})
                        </span>
                    </h5>
                </div>
                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                    <div class="d-flex justify-content-start justify-content-lg-end gap-2">
                        <div class="dropdown">
                            <x-button variant="outline-secondary" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('provider.inventory.export-movements', array_merge(request()->all(), ['format' => 'xlsx'])) }}">
                                        <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item"
                                        href="{{ route('provider.inventory.export-movements', array_merge(request()->all(), ['format' => 'pdf'])) }}">
                                        <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @if($movements->count() > 0)
            <!-- Desktop View -->
            <div class="table-responsive d-none d-md-block">
                <table class="table modern-table mb-0">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>SKU</th>
                            <th>Tipo</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-center">Saldo Atual</th>
                            <th>Motivo</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                        <tr>
                            <td class="small">{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <a href="{{ route('provider.products.show', $movement->product->sku) }}" class="text-decoration-none fw-bold">
                                    {{ $movement->product->name }}
                                </a>
                            </td>
                            <td><span class="text-code">{{ $movement->product->sku }}</span></td>
                            <td>
                                @php
                                    $badgeClass = match($movement->type) {
                                        'entry' => 'success',
                                        'exit' => 'danger',
                                        'adjustment' => 'warning',
                                        'reservation' => 'info',
                                        'cancellation' => 'dark',
                                        default => 'secondary'
                                    };
                                    $typeLabel = match($movement->type) {
                                        'entry' => 'Entrada',
                                        'exit' => 'Saída',
                                        'adjustment' => 'Ajuste',
                                        'reservation' => 'Reserva',
                                        'cancellation' => 'Cancel.',
                                        default => ucfirst($movement->type)
                                    };
                                    $icon = match($movement->type) {
                                        'entry' => 'plus-circle',
                                        'exit' => 'dash-circle',
                                        'adjustment' => 'sliders',
                                        'reservation' => 'lock',
                                        'cancellation' => 'arrow-counterclockwise',
                                        default => 'dot'
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    <i class="bi bi-{{ $icon }} me-1"></i> {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="text-center fw-bold">
                                @if($movement->type === 'entry')
                                <span class="text-success">+{{ number_format($movement->quantity, 0, ',', '.') }}</span>
                                @elseif($movement->type === 'exit' || $movement->type === 'subtraction')
                                <span class="text-danger">-{{ number_format($movement->quantity, 0, ',', '.') }}</span>
                                @else
                                {{ number_format($movement->quantity, 0, ',', '.') }}
                                @endif
                            </td>
                            <td class="text-center fw-bold">
                                @php
                                $currentQuantity = $movement->previous_quantity;
                                if ($movement->type === 'entry') {
                                    $currentQuantity += $movement->quantity;
                                } elseif ($movement->type === 'exit' || $movement->type === 'subtraction') {
                                    $currentQuantity -= $movement->quantity;
                                }
                                @endphp
                                {{ number_format($currentQuantity, 0, ',', '.') }}
                            </td>
                            <td>
                                <small class="text-muted" title="{{ $movement->reason }}">
                                    {{ Str::limit($movement->reason, 20) }}
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <x-button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="primary" icon="chevron-right" title="Ver Detalhes da Movimentação" size="sm" />
                                    <x-button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="info" icon="eye" title="Ver Inventário" size="sm" />
                                    <x-button type="link" :href="route('provider.inventory.entry', $movement->product->sku)" variant="success" icon="arrow-down-circle" title="Entrada" size="sm" />
                                    <x-button type="link" :href="route('provider.inventory.exit', $movement->product->sku)" variant="warning" icon="arrow-up-circle" title="Saída" size="sm" />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $movement->product->sku)" variant="secondary" icon="sliders" title="Ajustar" size="sm" />
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="d-md-none">
                @foreach($movements as $movement)
                <div class="p-3 border-bottom">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="small text-muted">{{ $movement->created_at->format('d/m/Y H:i') }}</span>
                        @php
                            $badgeClass = match($movement->type) {
                                'entry' => 'success',
                                'exit' => 'danger',
                                'adjustment' => 'warning',
                                'reservation' => 'info',
                                'cancellation' => 'secondary',
                                default => 'secondary'
                            };
                            $typeLabel = match($movement->type) {
                                'entry' => 'Entrada',
                                'exit' => 'Saída',
                                'adjustment' => 'Ajuste',
                                'reservation' => 'Reserva',
                                'cancellation' => 'Cancel.',
                                default => ucfirst($movement->type)
                            };
                            $icon = match($movement->type) {
                                'entry' => 'plus-circle',
                                'exit' => 'dash-circle',
                                'adjustment' => 'sliders',
                                'reservation' => 'lock',
                                'cancellation' => 'arrow-counterclockwise',
                                default => 'dot'
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">
                            <i class="bi bi-{{ $icon }} me-1"></i> {{ $typeLabel }}
                        </span>
                    </div>
                    <h6 class="mb-1 fw-bold">{{ $movement->product->name }}</h6>
                    @if($movement->reason)
                        <div class="small text-muted mb-2">
                            <i class="bi bi-chat-left-text me-1"></i> {{ $movement->reason }}
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="fw-bold {{ $movement->type === 'entry' ? 'text-success' : ($movement->type === 'exit' || $movement->type === 'subtraction' ? 'text-danger' : '') }}">
                            @if($movement->type === 'entry')
                                +{{ number_format($movement->quantity, 0, ',', '.') }}
                            @elseif($movement->type === 'exit' || $movement->type === 'subtraction')
                                -{{ number_format($movement->quantity, 0, ',', '.') }}
                            @else
                                {{ number_format($movement->quantity, 0, ',', '.') }}
                            @endif
                        </span>
                        <div class="d-flex gap-1">
                            <x-button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="primary" icon="chevron-right" title="Ver Detalhes da Movimentação" size="sm" />
                            <x-button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="info" icon="eye" title="Ver Inventário" size="sm" />
                            <x-button type="link" :href="route('provider.inventory.entry', $movement->product->sku)" variant="success" icon="arrow-down-circle" title="Entrada" size="sm" />
                            <x-button type="link" :href="route('provider.inventory.exit', $movement->product->sku)" variant="warning" icon="arrow-up-circle" title="Saída" size="sm" />
                            <x-button type="link" :href="route('provider.inventory.adjust', $movement->product->sku)" variant="secondary" icon="sliders" title="Ajustar" size="sm" />
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @else
            <div class="text-center py-5">
                <i class="bi bi-search text-muted mb-3" style="font-size: 3rem;"></i>
                @if(empty(request()->query()))
                    <p class="text-muted">Utilize os filtros acima para pesquisar as movimentações de estoque.</p>
                @else
                    <p class="text-muted">Nenhuma movimentação encontrada para os filtros selecionados.</p>
                    @if(request()->anyFilled(['search', 'type', 'start_date', 'end_date', 'product_id', 'sku']))
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-outline-secondary btn-sm">Limpar Filtros</a>
                    @endif
                @endif
            </div>
            @endif
        </div>
        @if ($movements instanceof \Illuminate\Pagination\LengthAwarePaginator && $movements->hasPages())
                @include('partials.components.paginator', [
                    'p' => $movements->appends(
                        collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()
                    ),
                    'show_info' => true,
                ])
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('filtersFormMovements');
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');

    form.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value) {
                if (startDate.value > endDate.value) {
                    e.preventDefault();
                    if (window.easyAlert) {
                        window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                    } else {
                        alert('A data inicial não pode ser maior que a data final.');
                    }
                    startDate.focus();
                }
            }
        });

        startDate.addEventListener('change', function() {
            if (this.value && endDate.value && this.value > endDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                } else {
                    alert('A data inicial não pode ser maior que a data final.');
                }
                this.value = '';
            }
        });

        endDate.addEventListener('change', function() {
            if (this.value && startDate.value && this.value < startDate.value) {
                if (window.easyAlert) {
                    window.easyAlert.warning('A data final não pode ser menor que a data inicial.');
                } else {
                    alert('A data final não pode ser menor que a data inicial.');
                }
                this.value = '';
            }
        });
});
</script>
@endsection

@push('scripts')
@endpush
