@extends('layouts.app')

@section('title', 'Inventário do Produto')

@section('content')
    @php
        $inventory = $inventory ?? $product->inventory;
    @endphp
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Detalhes do Inventário"
            icon="box-seam"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Inventário' => route('provider.inventory.dashboard'),
                $product->name => route('provider.products.show', $product->sku),
                'Detalhes' => '#'
            ]">
            <p class="text-muted mb-0">Visualize o saldo e histórico de movimentações do produto</p>
        </x-layout.page-header>

        <div class="row g-4 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3 flex-shrink-0" style="width: 42px; height: 42px; min-width: 42px;">
                                <i class="bi bi-box text-white"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Produto</h6>
                                <h5 class="mb-0 text-dark fw-bold text-truncate-2" style="line-height: 1.4;">{{ $product->name ?? 'Produto' }}</h5>
                            </div>
                        </div>
                        <div class="mb-4">
                            <small class="text-muted d-block mb-1">SKU</small>
                            <span class="badge bg-light text-dark border">{{ $product->sku }}</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="text-muted small text-uppercase fw-bold d-block mb-1">Preço de Venda</label>
                                <p class="h4 text-success mb-0">{{ \App\Helpers\CurrencyHelper::format($product->price ?? 0) }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small text-uppercase fw-bold d-block mb-1">Preço de Custo</label>
                                <p class="h4 text-muted mb-0">{{ \App\Helpers\CurrencyHelper::format($product->cost_price ?? 0) }}</p>
                            </div>
                            <div class="col-md-4">
                                <label class="text-muted small text-uppercase fw-bold d-block mb-1">Margem</label>
                                <p class="h4 mb-0">
                                    @if($product->cost_price > 0 && $product->price > 0)
                                        @php
                                            $margin = (($product->price - $product->cost_price) / $product->price) * 100;
                                            $marginClass = $margin >= 30 ? 'success' : ($margin >= 15 ? 'warning' : 'danger');
                                        @endphp
                                        <span class="text-{{ $marginClass }}">
                                            {{ \App\Helpers\CurrencyHelper::format($margin, 1, false) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-success bg-gradient me-3"><i
                                        class="bi bi-clipboard-data text-white"></i></div>
                                <div>
                                    <h6 class="text-muted mb-1">Estoque</h6>
                                    <h5 class="mb-0">Total: {{ \App\Helpers\CurrencyHelper::format($inventory?->quantity ?? 0, 0, false) }}</h5>
                                </div>
                            </div>
                            @can('adjustInventory', $product)
                                <button type="button" class="btn btn-sm btn-outline-primary border-0" data-bs-toggle="modal" data-bs-target="#updateLimitsModal" title="Editar Limites">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endcan
                        </div>
                        <div class="row text-center g-2">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Reservado</div>
                                    <div class="h6 mb-0 text-info">{{ \App\Helpers\CurrencyHelper::format($inventory?->reserved_quantity ?? 0, 0, false) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small text-success">Disponível</div>
                                    <div class="h6 mb-0 text-success fw-bold">{{ \App\Helpers\CurrencyHelper::format($inventory?->available_quantity ?? 0, 0, false) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Mín</div>
                                    <div class="h6 mb-0">{{ \App\Helpers\CurrencyHelper::format($inventory?->min_quantity ?? 0, 0, false) }}</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="text-muted small">Máx</div>
                                    <div class="h6 mb-0">{{ $inventory?->max_quantity ? \App\Helpers\CurrencyHelper::format($inventory->max_quantity, 0, false) : '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3"><i
                                    class="bi bi-info-circle text-white"></i></div>
                            <h6 class="text-muted mb-0">Resumo</h6>
                        </div>
                        <ul class="list-unstyled small text-muted mb-0">
                            <li class="mb-2">Baixo estoque:
                                {{ $inventory?->isLowStock() ? 'Sim' : 'Não' }}
                            </li>
                            <li class="mb-2">Alto estoque:
                                {{ $inventory?->isHighStock() ? 'Sim' : 'Não' }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <hr class="my-0 text-muted opacity-25">
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Total Entradas</h6>
                        <h4 class="mb-1 text-success fw-bold">{{ \App\Helpers\CurrencyHelper::format($summary['total_entries'], 0, false) }}</h4>
                        <small class="text-muted">{{ $summary['count_entries'] }} movimentações</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Total Saídas</h6>
                        <h4 class="mb-1 text-danger fw-bold">{{ \App\Helpers\CurrencyHelper::format($summary['total_exits'], 0, false) }}</h4>
                        <small class="text-muted">{{ $summary['count_exits'] }} movimentações</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Saldo no Período</h6>
                        <h4 class="mb-1 fw-bold {{ $summary['balance'] >= 0 ? 'text-primary' : 'text-danger' }}">
                            {{ \App\Helpers\CurrencyHelper::format($summary['balance'], 0, false) }}
                        </h4>
                        <small class="text-muted">Entradas - Saídas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-muted small text-uppercase fw-bold mb-2">Outros Ajustes</h6>
                        <h4 class="mb-1 text-warning fw-bold">{{ \App\Helpers\CurrencyHelper::format($summary['total_adjustments'], 0, false) }}</h4>
                        <small class="text-muted">{{ $summary['count_adjustments'] }} movimentações</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos do Produto -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-pie-chart me-2 text-primary"></i>Distribuição por Tipo</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 220px;"><canvas id="productMovementTypesChart"></canvas></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-3">
                        <h6 class="mb-0 fw-bold"><i class="bi bi-bar-chart me-2 text-primary"></i>Volumes por Tipo</h6>
                    </div>
                    <div class="card-body">
                        <div style="height: 220px;"><canvas id="productMovementTotalsChart"></canvas></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right me-2"></i>Histórico de Movimentações</h6>
                <div class="d-flex gap-2">
                    <x-ui.button
                        href="{{ route('provider.inventory.movements', ['sku' => $product->sku]) }}"
                        variant="primary"
                        size="sm"
                        icon="bi bi-list-ul"
                        label="Ver Tudo"
                        feature="inventory"
                    />
                </div>
            </div>
            <div class="card-body">
                @if (!empty($movements) && $movements->count() > 0)
                    <!-- Conteúdo da tabela -->
                    <div class="card-body p-0">
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table modern-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Tipo</th>
                                        <th>Quantidade</th>
                                        <th>Usuário</th>
                                        <th>Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($movements as $m)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</td>
                                            <td>
                                                @php
                                                    $badgeClass = match($m->type) {
                                                        'entry' => 'bg-success',
                                                        'exit' => 'bg-danger',
                                                        'adjustment' => 'bg-info',
                                                        default => 'bg-secondary'
                                                    };
                                                    $typeName = match($m->type) {
                                                        'entry' => 'Entrada',
                                                        'exit' => 'Saída',
                                                        'adjustment' => 'Ajuste',
                                                        default => $m->type
                                                    };
                                                @endphp
                                                <span class="badge {{ $badgeClass }}">{{ $typeName }}</span>
                                            </td>
                                            <td class="fw-bold">{{ $m->quantity }}</td>
                                            <td>{{ $m->user->name ?? 'Sistema' }}</td>
                                            <td>{{ $m->reason }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view d-md-none px-3">
                            @foreach ($movements as $m)
                                <div class="list-group-item border-start-0 border-end-0 px-0">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div>
                                            @php
                                                $mobileBadgeClass = match($m->type) {
                                                    'entry' => 'text-success',
                                                    'exit' => 'text-danger',
                                                    'adjustment' => 'text-info',
                                                    default => 'text-secondary'
                                                };
                                                $typeName = match($m->type) {
                                                    'entry' => 'Entrada',
                                                    'exit' => 'Saída',
                                                    'adjustment' => 'Ajuste',
                                                    default => $m->type
                                                };
                                            @endphp
                                            <h6 class="mb-0 {{ $mobileBadgeClass }}">{{ $typeName }}</h6>
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($m->created_at)->format('d/m/Y H:i') }}</small>
                                        </div>
                                        <span class="badge bg-light text-dark border fw-bold">{{ $m->quantity }}</span>
                                    </div>
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-person me-1"></i>{{ $m->user->name ?? 'Sistema' }}
                                    </div>
                                    <p class="mb-0 small text-secondary">{{ $m->reason }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="card-body">
                        <div class="text-center py-5">
                            <i class="bi bi-inbox display-4 text-muted mb-3"></i>
                            <h6 class="text-muted">Nenhum movimento encontrado</h6>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="mt-4 pb-2">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto order-2 order-md-1">
                    <x-ui.back-button index-route="provider.inventory.index" class="w-100 w-md-auto px-md-3" />
                </div>

                <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                    <small class="text-muted">
                        Última atualização: {{ $inventory->updated_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </small>
                </div>

                <div class="col-12 col-md-auto order-1 order-md-3">
                    <div class="d-grid d-md-flex gap-2">
                        <x-ui.button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="arrow-down-circle" label="Entrada" style="min-width: 120px;" feature="inventory" />
                        <x-ui.button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="arrow-up-circle" label="Saída" style="min-width: 120px;" feature="inventory" />
                        <x-ui.button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" label="Ajustar" style="min-width: 120px;" feature="inventory" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('adjustInventory', $product)
    <!-- Modal Atualizar Limites -->
    <div class="modal fade" id="updateLimitsModal" tabindex="-1" aria-labelledby="updateLimitsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold" id="updateLimitsModalLabel">
                        <i class="bi bi-sliders me-2"></i>Editar Limites de Estoque
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('provider.inventory.limits.update', $product->sku) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <p class="text-muted small mb-4">
                            Defina os níveis mínimo e máximo para receber alertas automáticos de reposição e excesso de estoque.
                        </p>

                        <div class="mb-3">
                            <label for="min_quantity" class="form-label fw-bold">Estoque Mínimo (Alerta de Baixa)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-arrow-down-circle text-danger"></i></span>
                                <input type="number" class="form-control" id="min_quantity" name="min_quantity"
                                    value="{{ old('min_quantity', $inventory?->min_quantity ?? 0) }}" min="0" required>
                            </div>
                            <div class="form-text">Você será notificado quando o estoque estiver igual ou abaixo deste valor.</div>
                        </div>

                        <div class="mb-0">
                            <label for="max_quantity" class="form-label fw-bold">Estoque Máximo (Opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light"><i class="bi bi-arrow-up-circle text-success"></i></span>
                                <input type="number" class="form-control" id="max_quantity" name="max_quantity"
                                    value="{{ old('max_quantity', $inventory?->max_quantity) }}" min="0">
                            </div>
                            <div class="form-text">Define o limite ideal de armazenamento para este produto. Deixe vazio para não limitar.</div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-link text-muted fw-bold text-decoration-none" data-bs-dismiss="modal">Cancelar</button>
                        <x-ui.button type="submit" variant="primary" label="Salvar Alterações" icon="check-lg" feature="inventory" />
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endcan
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const s = {!! json_encode($summary) !!};
        const typesLabels = ['Entradas', 'Saídas', 'Ajustes', 'Reservas', 'Cancelamentos'];
        const typesCounts = [s.count_entries || 0, s.count_exits || 0, s.count_adjustments || 0, s.count_reservations || 0, s.count_cancellations || 0];
        const totalsValues = [s.total_entries || 0, s.total_exits || 0, s.total_adjustments || 0, s.total_reservations || 0, s.total_cancellations || 0];
        const colors = ['#198754', '#dc3545', '#ffc107', '#0dcaf0', '#212529'];

        const ctx1 = document.getElementById('productMovementTypesChart');
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

        const ctx2 = document.getElementById('productMovementTotalsChart');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: typesLabels,
                    datasets: [{
                        label: 'Volume Total',
                        data: totalsValues,
                        backgroundColor: colors.map(c => c + 'CC'),
                        borderColor: colors,
                        borderWidth: 1,
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
                        y: {
                            beginAtZero: true,
                            grid: { drawBorder: false, color: '#f0f0f0' },
                            ticks: { font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });
        }
    });
</script>
@endpush
