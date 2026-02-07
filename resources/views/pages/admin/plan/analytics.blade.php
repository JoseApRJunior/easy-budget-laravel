<x-app-layout :title="'Análises do Plano: ' . $plan->name">
<div class="container-fluid py-4">
    <x-layout.page-header
        :title="'Análises do Plano: ' . $plan->name"
        icon="graph-up"
        :breadcrumb-items="[
            'Dashboard' => route('admin.dashboard'),
            'Planos' => route('admin.plans.index'),
            $plan->name => route('admin.plans.show', $plan),
            'Análises' => '#'
        ]">
        <div class="d-flex gap-2">
            <a href="{{ route('admin.plans.show', $plan) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao Plano
            </a>
            <a href="{{ route('admin.plans.export', ['format' => 'json']) }}" class="btn btn-outline-primary">
                <i class="bi bi-download me-1"></i>Exportar Dados
            </a>
        </div>
    </x-layout.page-header>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Taxa de Crescimento</h5>
                    <h3>{{ \App\Helpers\CurrencyHelper::format($analytics['growth_rate'], 2, false) }}%</h3>
                    <small>Comparado ao mês anterior</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Taxa de Retenção</h5>
                    <h3>{{ \App\Helpers\CurrencyHelper::format($analytics['retention_rate'], 2, false) }}%</h3>
                    <small>Assinaturas ativas vs total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas Novas</h5>
                    <h3>{{ collect($analytics['monthly_data'])->sum('new_subscriptions') }}</h3>
                    <small>No último ano</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Assinaturas Canceladas</h5>
                    <h3>{{ collect($analytics['monthly_data'])->sum('cancelled_subscriptions') }}</h3>
                    <small>No último ano</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Chart -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-bar-chart me-2"></i>Evolução Mensal</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Dados Mensais Detalhados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Mês</th>
                                    <th>Novas Assinaturas</th>
                                    <th>Assinaturas Canceladas</th>
                                    <th>Crescimento Líquido</th>
                                    <th>Receita</th>
                                    <th>Ticket Médio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($analytics['monthly_data'] as $data)
                                    @php
                                        $netGrowth = $data['new_subscriptions'] - $data['cancelled_subscriptions'];
                                        $avgTicket = $data['new_subscriptions'] > 0 ? $data['revenue'] / $data['new_subscriptions'] : 0;
                                    @endphp
                                    <tr>
                                        <td><strong>{{ $data['month'] }}</strong></td>
                                        <td><span class="badge bg-success">{{ $data['new_subscriptions'] }}</span></td>
                                        <td><span class="badge bg-danger">{{ $data['cancelled_subscriptions'] }}</span></td>
                                        <td>
                                            @if($netGrowth > 0)
                                                <span class="badge bg-success">+{{ $netGrowth }}</span>
                                            @elseif($netGrowth < 0)
                                                <span class="badge bg-danger">{{ $netGrowth }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $netGrowth }}</span>
                                            @endif
                                        </td>
                                        <td>{{ \App\Helpers\CurrencyHelper::format($data['revenue']) }}</td>
                                        <td>{{ \App\Helpers\CurrencyHelper::format($avgTicket) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="table-active">
                                    <th>Total</th>
                                    <th>{{ collect($analytics['monthly_data'])->sum('new_subscriptions') }}</th>
                                    <th>{{ collect($analytics['monthly_data'])->sum('cancelled_subscriptions') }}</th>
                                    <th>
                                        @php
                                            $totalNetGrowth = collect($analytics['monthly_data'])->sum('new_subscriptions') - 
                                                             collect($analytics['monthly_data'])->sum('cancelled_subscriptions');
                                        @endphp
                                        @if($totalNetGrowth > 0)
                                            <span class="badge bg-success">+{{ $totalNetGrowth }}</span>
                                        @elseif($totalNetGrowth < 0)
                                            <span class="badge bg-danger">{{ $totalNetGrowth }}</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $totalNetGrowth }}</span>
                                        @endif
                                    </th>
                                    <th>{{ \App\Helpers\CurrencyHelper::format(collect($analytics['monthly_data'])->sum('revenue')) }}</th>
                                    <th>
                                        @php
                                            $totalNewSubscriptions = collect($analytics['monthly_data'])->sum('new_subscriptions');
                                            $totalRevenue = collect($analytics['monthly_data'])->sum('revenue');
                                            $totalAvgTicket = $totalNewSubscriptions > 0 ? $totalRevenue / $totalNewSubscriptions : 0;
                                        @endphp
                                        {{ \App\Helpers\CurrencyHelper::format($totalAvgTicket) }}
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for Chart.js
const monthlyData = @json($analytics['monthly_data']);
const labels = monthlyData.map(item => item.month);
const newSubscriptions = monthlyData.map(item => item.new_subscriptions);
const cancelledSubscriptions = monthlyData.map(item => item.cancelled_subscriptions);
const revenue = monthlyData.map(item => item.revenue);

// Create the chart
const ctx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [
            {
                label: 'Novas Assinaturas',
                data: newSubscriptions,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            },
            {
                label: 'Assinaturas Canceladas',
                data: cancelledSubscriptions,
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            },
            {
                label: 'Receita (R$)',
                data: revenue,
                borderColor: 'rgb(255, 205, 86)',
                backgroundColor: 'rgba(255, 205, 86, 0.2)',
                tension: 0.1,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            title: {
                display: true,
                text: 'Evolução Mensal do Plano {{ $plan->name }}'
            },
            legend: {
                position: 'top',
            }
        },
        scales: {
            x: {
                display: true,
                title: {
                    display: true,
                    text: 'Mês'
                }
            },
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Quantidade de Assinaturas'
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Receita (R$)'
                },
                grid: {
                    drawOnChartArea: false,
                },
            }
        }
    }
});
</script>
@endpush
</x-app-layout>