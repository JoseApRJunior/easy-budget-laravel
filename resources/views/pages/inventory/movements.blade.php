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

    <!-- Filtros -->
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('provider.inventory.movements') }}">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="product_id">Produto</label>
                                    <select name="product_id" id="product_id" class="form-select tom-select">
                                        <option value="">Todos os Produtos</option>
                                        @foreach($products as $product)
                                        <option value="{{ $product->id }}" {{ request('product_id') == $product->id ? 'selected' : '' }}>
                                            {{ $product->sku }} - {{ $product->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="type">Tipo</label>
                                    <select name="type" id="type" class="form-select tom-select">
                                        <option value="">Todos</option>
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
                                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date">Data Final</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <div class="d-flex gap-2 flex-nowrap w-100">
                                    <x-button type="submit" icon="search" label="Filtrar" class="w-100" />
                                    <x-button type="link" :href="route('provider.inventory.movements')" variant="secondary" icon="x" label="Limpar" class="w-100" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-plus-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL ENTRADAS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($summary['total_entries'], 0, ',', '.') }}</h3>
                    <small class="text-muted">{{ $summary['count_entries'] }} movimentações</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-dash-circle text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TOTAL SAÍDAS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($summary['total_exits'], 0, ',', '.') }}</h3>
                    <small class="text-muted">{{ $summary['count_exits'] }} movimentações</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-arrow-left-right text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">MOVIMENTAÇÕES</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $movements->total() }}</h3>
                    <small class="text-muted">No período selecionado</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-warning bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-calculator text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">SALDO PERÍODO</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ number_format($summary['balance'], 0, ',', '.') }}</h3>
                    <small class="text-muted">Entradas - Saídas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0 fw-bold">Distribuição por Tipo (Qtd. Movimentos)</h6>
                </div>
                <div class="card-body">
                    <div style="height: 200px;"><canvas id="movementTypesChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0 fw-bold">Volumes por Tipo (Somatório de Qtd.)</h6>
                </div>
                <div class="card-body">
                    <div style="height: 200px;"><canvas id="movementTotalsChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de Movimentações -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent">
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-list-ul me-2"></i>Registros de Movimentação
                        <span class="text-muted small">({{ $movements->total() }} registros)</span>
                    </h5>
                </div>
                <div class="col-12 col-md-4 text-md-end mt-2 mt-md-0">
                    <x-button type="link" :href="route('provider.inventory.export-movements', request()->all())" variant="success" icon="file-earmark-excel" label="Exportar Excel" size="sm" />
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
                            <th class="text-center">Saldo Ant.</th>
                            <th class="text-center">Saldo Atual</th>
                            <th>Motivo</th>
                            <th>Usuário</th>
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
                            <td class="text-center text-muted">{{ number_format($movement->previous_quantity, 0, ',', '.') }}</td>
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
                                    {{ Str::limit($movement->reason, 30) }}
                                </small>
                            </td>
                            <td>
                                <small>{{ $movement->user->name ?? 'Sistema' }}</small>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('provider.products.show', $movement->product->sku) }}"><i class="bi bi-eye me-2"></i>Ver Produto</a></li>
                                        <li><a class="dropdown-item" href="{{ route('provider.inventory.adjust', $movement->product->sku) }}"><i class="bi bi-sliders me-2"></i>Ajustar Estoque</a></li>
                                        <li><a class="dropdown-item" href="{{ route('provider.inventory.entry', $movement->product->sku) }}"><i class="bi bi-plus me-2"></i>Entrada</a></li>
                                        <li><a class="dropdown-item" href="{{ route('provider.inventory.exit', $movement->product->sku) }}"><i class="bi bi-dash me-2"></i>Saída</a></li>
                                    </ul>
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
                                default => 'secondary'
                            };
                        @endphp
                        <span class="badge bg-{{ $badgeClass }}">{{ ucfirst($movement->type) }}</span>
                    </div>
                    <h6 class="mb-1 fw-bold">{{ $movement->product->name }}</h6>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">SKU: {{ $movement->product->sku }}</span>
                        <span class="fw-bold {{ $movement->type === 'entry' ? 'text-success' : ($movement->type === 'exit' ? 'text-danger' : '') }}">
                            {{ $movement->type === 'entry' ? '+' : ($movement->type === 'exit' ? '-' : '') }}{{ number_format($movement->quantity, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="p-3">
                {{ $movements->appends(request()->except('page'))->links() }}
            </div>
            @else
            <div class="text-center py-5">
                <i class="bi bi-info-circle text-muted mb-3" style="font-size: 3rem;"></i>
                <p class="text-muted">Nenhuma movimentação encontrada com os filtros aplicados.</p>
                <a href="{{ route('provider.inventory.movements') }}" class="btn btn-primary">Limpar Filtros</a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var s = @json($summary);
        var typesLabels = ['Entradas', 'Saídas', 'Ajustes', 'Reservas', 'Cancelamentos'];
        var typesCounts = [s.count_entries || 0, s.count_exits || 0, s.count_adjustments || 0, s.count_reservations || 0, s.count_cancellations || 0];
        var totalsValues = [s.total_entries || 0, s.total_exits || 0, s.total_adjustments || 0, s.total_reservations || 0, s.total_cancellations || 0];
        var colors = ['#198754', '#dc3545', '#ffc107', '#0dcaf0', '#212529'];
        
        var ctx1 = document.getElementById('movementTypesChart');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: typesLabels,
                    datasets: [{
                        data: typesCounts,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 20,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });
        }
        
        var ctx2 = document.getElementById('movementTotalsChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: typesLabels,
                    datasets: [{
                        label: 'Quantidade',
                        data: totalsValues,
                        backgroundColor: colors,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { display: false } },
                        x: { grid: { display: false } }
                    }
                }
            });
        }
    });
</script>
@endsection

