@extends('layouts.app')

@section('title', 'Dashboard de Faturas')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Dashboard de Faturas"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => '#'
            ]">
            <p class="text-muted mb-0 small">Acompanhe suas faturas, recebimentos e pendências.</p>
        </x-layout.page-header>

        @php
            $total = $stats['total_invoices'] ?? 0;
            $paid = $stats['paid_invoices'] ?? 0;
            $pending = $stats['pending_invoices'] ?? 0;
            $overdue = $stats['overdue_invoices'] ?? 0;
            $cancelled = $stats['cancelled_invoices'] ?? 0;
            $billed = $stats['total_billed'] ?? 0;
            $received = $stats['total_received'] ?? 0;
            $toReceive = $stats['total_pending'] ?? 0;
            $recent = $stats['recent_invoices'] ?? collect();
            $breakdown = $stats['status_breakdown'] ?? [];
            $paidRate = $total > 0 ? \App\Helpers\CurrencyHelper::format(($paid / $total) * 100) : 0;
        @endphp

        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3"><i class="bi bi-receipt text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total de Faturas</h6>
                                <h3 class="mb-0">{{ $total }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Quantidade total emitida.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3"><i
                                    class="bi bi-check-circle-fill text-white"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Pagas</h6>
                                <h3 class="mb-0">{{ $paid }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Faturas liquidadas.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3"><i
                                    class="bi bi-hourglass-split text-white"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Pendentes</h6>
                                <h3 class="mb-0">{{ $pending }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Aguardando pagamento.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-purple bg-gradient me-3" style="background:#6f42c1"><i
                                    class="bi bi-calendar-x-fill text-white"></i></div>
                            <div>
                                <h6 class="text-muted mb-1">Vencidas</h6>
                                <h3 class="mb-0">{{ $overdue }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">Passaram do vencimento.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3"><i
                                    class="bi bi-currency-dollar text-white"></i></div>
                            <h6 class="text-muted mb-0">Totais Financeiros</h6>
                        </div>
                        <div class="row text-center">
                            <div class="col">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Faturado</div>
                                    <div class="h5 mb-0">{{ \App\Helpers\CurrencyHelper::format($billed) }}</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">Recebido</div>
                                    <div class="h5 mb-0">{{ \App\Helpers\CurrencyHelper::format($received) }}</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="border rounded p-3">
                                    <div class="text-muted small">A Receber</div>
                                    <div class="h5 mb-0">{{ \App\Helpers\CurrencyHelper::format($toReceive) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3"><i
                                    class="bi bi-bar-chart text-white"></i></div>
                            <h6 class="text-muted mb-0">Distribuição por Status</h6>
                        </div>
                        <div class="chart-container"><canvas id="statusChart" style="max-height: 120px;"></canvas></div>
                        <div class="text-muted small mt-3">Taxa de faturas pagas: {{ $paidRate }}%</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            <span class="d-none d-sm-inline">Faturas Recentes</span>
                            <span class="d-sm-none">Recentes</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        {{-- Desktop View --}}
                        <div class="desktop-view">
                            @if ($recent->isNotEmpty())
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Cliente</th>
                                            <th>Status</th>
                                            <th>Total</th>
                                            <th>Vencimento</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent as $inv)
                                            <tr>
                                                <td><code class="text-code">{{ $inv->code }}</code></td>
                                                <td>{{ $inv->customer?->commonData?->first_name ?? 'N/A' }}</td>
                                                <td><x-ui.status-badge :item="$inv" /></td>
                                                <td>{{ \App\Helpers\CurrencyHelper::format($inv->total) }}</td>
                                                <td>{{ optional($inv->due_date)->format('d/m/Y') }}</td>
                                                <td class="text-end">
                                                    <x-ui.button type="link" :href="route('provider.invoices.show', $inv->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>Nenhuma fatura encontrada.
                                </div>
                            @endif
                        </div>

                        {{-- Mobile View --}}
                        <div class="mobile-view">
                            @if ($recent->isNotEmpty())
                                <div class="list-group">
                                    @foreach ($recent as $inv)
                                        <a href="{{ route('provider.invoices.show', $inv->code) }}" class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-receipt text-muted me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">{{ $inv->code }}</div>
                                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                                        <span class="badge" style="background: {{ $inv->invoiceStatus?->getColor() }}">
                                                            {{ $inv->invoiceStatus?->getDescription() }}
                                                        </span>
                                                    </div>
                                                    <div class="small text-muted">
                                                        <div>Cliente: {{ $inv->customer?->commonData?->first_name ?? 'N/A' }}</div>
                                                        <div>Total: {{ \App\Helpers\CurrencyHelper::format($inv->total) }}</div>
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>Nenhuma fatura encontrada.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Insights</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li class="mb-2"><i class="bi bi-calendar-x text-purple me-2"
                                    style="color:#6f42c1"></i>Priorize faturas vencidas.</li>
                            <li class="mb-2"><i class="bi bi-hourglass-split text-warning me-2"></i>Evite pendências
                                próximas ao vencimento.</li>
                        </ul>
                    </div>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i>Atalhos</h6>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <x-ui.button type="link" :href="route('provider.invoices.create')" variant="success" size="sm" icon="plus-circle" label="Nova Fatura" />
                        <x-ui.button type="link" :href="route('provider.invoices.index')" variant="primary" outline size="sm" icon="receipt" label="Listar Faturas" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .text-code {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: .85em
        }

        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 120px;
            width: 100%
        }

        .chart-container canvas {
            max-width: 100% !important;
            height: auto !important
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusData = @json($breakdown);
            const labels = [];
            const values = [];
            const colors = [];
            Object.keys(statusData).forEach(k => {
                const s = statusData[k];
                if (s && s.count > 0) {
                    labels.push(k);
                    values.push(s.count);
                    colors.push(s.color || '#6c757d');
                }
            });
            if (values.length === 0) {
                const c = document.querySelector('.chart-container');
                if (c) {
                    c.innerHTML = '<p class="text-muted text-center mb-0 small">Nenhuma fatura cadastrada</p>';
                }
                return;
            }
            const ctx = document.getElementById('statusChart');
            if (!ctx) {
                return;
            }
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const pct = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + pct + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
