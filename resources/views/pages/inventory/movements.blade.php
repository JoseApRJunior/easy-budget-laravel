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
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-muted small text-uppercase">
                <i class="bi bi-filter me-2"></i>Filtros
            </h5>
        </div>
        <div class="card-body">
            <form id="filtersFormMovements" method="GET" action="{{ route('provider.inventory.movements') }}">
                @if(request('product_id'))
                    <input type="hidden" name="product_id" value="{{ request('product_id') }}">
                @endif
                @if(request('sku'))
                    <input type="hidden" name="sku" value="{{ request('sku') }}">
                @endif
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="search" class="form-label small fw-bold text-muted text-uppercase">Buscar Produto</label>
                            <input type="text" name="search" id="search" class="form-control"
                                placeholder="Nome ou SKU" value="{{ request('search') ?? request('sku') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="type" class="form-label small fw-bold text-muted text-uppercase">Tipo</label>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="start_date" class="form-label small fw-bold text-muted text-uppercase">Data Inicial <span class="text-danger">*</span></label>
                            <input type="text" name="start_date" id="start_date" class="form-control"
                                placeholder="DD/MM/AAAA" value="{{ $filters['start_date'] ?? request('start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="end_date" class="form-label small fw-bold text-muted text-uppercase">Data Final <span class="text-danger">*</span></label>
                            <input type="text" name="end_date" id="end_date" class="form-control"
                                placeholder="DD/MM/AAAA" value="{{ $filters['end_date'] ?? request('end_date') }}">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" />
                            <x-button type="link" :href="route('provider.inventory.movements')" variant="outline-secondary" icon="x" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                    <h5 class="mb-0 fw-bold text-muted small text-uppercase">
                        <i class="bi bi-arrow-left-right me-2"></i>Registros de Movimentação
                        <span class="ms-1">({{ $movements->total() }})</span>
                    </h5>
                </div>
                <div class="col-12 col-lg-4 text-start text-lg-end">
                    <div class="dropdown d-inline-block">
                        <x-button variant="success" size="sm" icon="download" label="Exportar" class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
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
        <div class="card-body p-0">
            @if($movements->count() > 0)
            <!-- Desktop View -->
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle mb-0 modern-table">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-3">Data/Hora</th>
                            <th class="px-3">Produto / SKU</th>
                            <th class="px-3 text-center">Tipo</th>
                            <th class="px-3 text-center">Quantidade</th>
                            <th class="px-3 text-center">Saldo Atual</th>
                            <th class="px-3">Motivo</th>
                            <th class="px-3 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($movements as $movement)
                        <tr>
                            <td class="px-3 small text-muted">
                                <div class="fw-bold text-dark">{{ $movement->created_at->format('d/m/Y') }}</div>
                                <div>{{ $movement->created_at->format('H:i') }}</div>
                            </td>
                            <td class="px-3">
                                <div class="fw-bold text-dark">{{ $movement->product->name }}</div>
                                <div class="small text-muted">{{ $movement->product->sku }}</div>
                            </td>
                            <td class="px-3 text-center">
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
                                <span class="badge bg-{{ $badgeClass }} bg-opacity-10 text-{{ $badgeClass }} border border-{{ $badgeClass }} border-opacity-25 px-2 py-1">
                                    <i class="bi bi-{{ $icon }} me-1"></i> {{ $typeLabel }}
                                </span>
                            </td>
                            <td class="px-3 text-center fw-bold">
                                @if($movement->type === 'entry')
                                <span class="text-success">+{{ number_format($movement->quantity, 0, ',', '.') }}</span>
                                @elseif($movement->type === 'exit' || $movement->type === 'subtraction')
                                <span class="text-danger">-{{ number_format($movement->quantity, 0, ',', '.') }}</span>
                                @else
                                <span class="text-dark">{{ number_format($movement->quantity, 0, ',', '.') }}</span>
                                @endif
                            </td>
                            <td class="px-3 text-center fw-bold text-dark">
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
                            <td class="px-3">
                                <small class="text-muted" title="{{ $movement->reason }}">
                                    {{ Str::limit($movement->reason, 30) }}
                                </small>
                            </td>
                            <td class="px-3 text-center">
                                <div class="btn-group">
                                    <x-button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="outline-primary" size="sm" icon="chevron-right" title="Ver Detalhes" />
                                    <x-button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="outline-info" size="sm" icon="eye" title="Ver Inventário" />
                                    <x-button type="link" :href="route('provider.inventory.adjust', $movement->product->sku)" variant="outline-success" size="sm" icon="sliders" title="Ajustar" />
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Mobile View -->
            <div class="d-md-none">
                <div class="list-group list-group-flush">
                    @foreach($movements as $movement)
                    <div class="list-group-item px-3 py-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <div class="small text-muted">{{ $movement->created_at->format('d/m/Y H:i') }}</div>
                                <div class="fw-bold text-dark">{{ $movement->product->name }}</div>
                                <div class="small text-muted">{{ $movement->product->sku }}</div>
                            </div>
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
                            <span class="badge bg-{{ $badgeClass }} bg-opacity-10 text-{{ $badgeClass }} border border-{{ $badgeClass }} border-opacity-25 px-2 py-1">
                                {{ $typeLabel }}
                            </span>
                        </div>
                        
                        @if($movement->reason)
                            <div class="small text-muted mb-2">
                                <i class="bi bi-chat-left-text me-1"></i> {{ Str::limit($movement->reason, 50) }}
                            </div>
                        @endif
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex gap-3">
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Qtd</div>
                                    <div class="fw-bold {{ $movement->type === 'entry' ? 'text-success' : ($movement->type === 'exit' || $movement->type === 'subtraction' ? 'text-danger' : '') }}">
                                        @if($movement->type === 'entry')
                                            +{{ number_format($movement->quantity, 0, ',', '.') }}
                                        @elseif($movement->type === 'exit' || $movement->type === 'subtraction')
                                            -{{ number_format($movement->quantity, 0, ',', '.') }}
                                        @else
                                            {{ number_format($movement->quantity, 0, ',', '.') }}
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.65rem;">Saldo</div>
                                    <div class="fw-bold text-dark">
                                        @php
                                        $currentQuantity = $movement->previous_quantity;
                                        if ($movement->type === 'entry') {
                                            $currentQuantity += $movement->quantity;
                                        } elseif ($movement->type === 'exit' || $movement->type === 'subtraction') {
                                            $currentQuantity -= $movement->quantity;
                                        }
                                        @endphp
                                        {{ number_format($currentQuantity, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <x-button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="outline-primary" size="sm" icon="chevron-right" label="Detalhes" class="flex-grow-1" />
                            <x-button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="outline-info" size="sm" icon="eye" label="Ver" class="flex-grow-1" />
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            @else
            <div class="text-center py-5">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-search text-muted fs-1"></i>
                </div>
                @if(empty(request()->query()))
                    <p class="text-muted fw-bold">Utilize os filtros acima para pesquisar as movimentações.</p>
                @else
                    <p class="text-muted fw-bold">Nenhuma movimentação encontrada.</p>
                    @if(request()->anyFilled(['search', 'type', 'start_date', 'end_date', 'product_id', 'sku']))
                        <a href="{{ route('provider.inventory.movements') }}" class="btn btn-primary btn-sm mt-2">
                            <i class="bi bi-x-circle me-1"></i>Limpar Filtros
                        </a>
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
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = startDate ? startDate.closest('form') : null;

    if (form) {
        if (typeof VanillaMask !== 'undefined') {
            new VanillaMask('start_date', 'date');
            new VanillaMask('end_date', 'date');
        }

        form.addEventListener('submit', function(e) {
            if (startDate.value && endDate.value) {
                const parseDate = (str) => {
                    const parts = str.split('/');
                    if (parts.length === 3) {
                        return new Date(parts[2], parts[1] - 1, parts[0]);
                    }
                    return new Date(str);
                };

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start > end) {
                    e.preventDefault();
                    if (window.easyAlert) {
                        window.easyAlert.error('A data inicial não pode ser maior que a data final.');
                    } else {
                        alert('A data inicial não pode ser maior que a data final.');
                    }
                    startDate.focus();
                    return;
                }
            }

            if ((startDate.value && !endDate.value) || (!startDate.value && endDate.value)) {
                e.preventDefault();
                const message = 'Para filtrar por período, informe as datas inicial e final.';
                if (window.easyAlert) {
                    window.easyAlert.error(message);
                } else {
                    alert(message);
                }
                if (!startDate.value) startDate.focus();
                else endDate.focus();
            }
        });

        startDate.addEventListener('change', function() {
            if (this.value && endDate.value) {
                const parseDate = (str) => {
                    const parts = str.split('/');
                    if (parts.length === 3) {
                        return new Date(parts[2], parts[1] - 1, parts[0]);
                    }
                    return new Date(str);
                };
                const start = parseDate(this.value);
                const end = parseDate(endDate.value);

                if (start > end) {
                    if (window.easyAlert) {
                        window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                    } else {
                        alert('A data inicial não pode ser maior que a data final.');
                    }
                    this.value = '';
                }
            }
        });

        endDate.addEventListener('change', function() {
            if (this.value && startDate.value) {
                const parseDate = (str) => {
                    const parts = str.split('/');
                    if (parts.length === 3) {
                        return new Date(parts[2], parts[1] - 1, parts[0]);
                    }
                    return new Date(str);
                };
                const end = parseDate(this.value);
                const start = parseDate(startDate.value);

                if (end < start) {
                    if (window.easyAlert) {
                        window.easyAlert.warning('A data final não pode ser menor que a data inicial.');
                    } else {
                        alert('A data final não pode ser menor que a data inicial.');
                    }
                    this.value = '';
                }
            }
        });
    }
});
</script>
@endsection

@push('scripts')
@endpush
