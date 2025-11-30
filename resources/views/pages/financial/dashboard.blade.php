@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard Financeiro</h1>
            <p class="text-muted mb-0">Visão geral das finanças do seu negócio</p>
        </div>
        <div>
            <select class="form-select" id="periodSelect" onchange="changePeriod()">
                @foreach($periods as $key => $label)
                    <option value="{{ $key }}" {{ $period === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <!-- Receita -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Receita</h6>
                            <h4 class="mb-0">R$ {{ number_format($revenue['current'], 2, ',', '.') }}</h4>
                            <small class="text-{{ $revenue['growth_positive'] ? 'success' : 'danger' }}">
                                <i class="bi bi-arrow-{{ $revenue['growth_positive'] ? 'up' : 'down' }}"></i>
                                {{ abs($revenue['growth']) }}% vs período anterior
                            </small>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-currency-dollar" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Faturas Pagas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Faturas Pagas</h6>
                            <h4 class="mb-0">{{ $invoices['paid'] }}</h4>
                            <small class="text-muted">
                                de {{ $invoices['total'] }} faturas ({{ $invoices['conversion_rate'] }}%)
                            </small>
                        </div>
                        <div class="text-success">
                            <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Faturas Pendentes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Faturas Pendentes</h6>
                            <h4 class="mb-0">{{ $invoices['pending'] + $invoices['overdue'] }}</h4>
                            <small class="text-warning">
                                R$ {{ number_format($invoices['pending_amount'], 2, ',', '.') }} em aberto
                            </small>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-clock" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ticket Médio -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Ticket Médio</h6>
                            <h4 class="mb-0">R$ {{ number_format($payments['average_ticket'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Por pagamento recebido</small>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-graph-up" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Receita Timeline -->
        <div class="col-lg-8 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Receita dos Últimos 30 Dias</h5>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Status das Faturas -->
        <div class="col-lg-4 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Status das Faturas</h5>
                </div>
                <div class="card-body">
                    <canvas id="invoiceStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Métodos de Pagamento e Orçamentos -->
    <div class="row">
        <!-- Métodos de Pagamento -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Métodos de Pagamento</h5>
                </div>
                <div class="card-body">
                    @if(!empty($payments['by_method']))
                        @foreach($payments['by_method'] as $method => $data)
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <strong>{{ \App\Models\Payment::getPaymentMethods()[$method] ?? $method }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $data['count'] }} pagamentos</small>
                                </div>
                                <div class="text-end">
                                    <strong>R$ {{ number_format($data['total'], 2, ',', '.') }}</strong>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted text-center py-4">Nenhum pagamento no período</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Orçamentos -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0">Orçamentos</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3 class="text-primary">{{ $budgets['total'] }}</h3>
                            <p class="text-muted mb-0">Total</p>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success">{{ $budgets['approved'] }}</h3>
                            <p class="text-muted mb-0">Aprovados</p>
                        </div>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h4>{{ $budgets['approval_rate'] }}%</h4>
                        <p class="text-muted mb-0">Taxa de Aprovação</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const revenueData = @json($charts['revenue_timeline']);
const invoiceStatusData = @json($charts['invoice_status']);

// Gráfico de Receita
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: Object.keys(revenueData),
        datasets: [{
            label: 'Receita (R$)',
            data: Object.values(revenueData),
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Gráfico de Status das Faturas
const invoiceStatusCtx = document.getElementById('invoiceStatusChart').getContext('2d');
new Chart(invoiceStatusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(invoiceStatusData),
        datasets: [{
            data: Object.values(invoiceStatusData),
            backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#6c757d']
        }]
    }
});

function changePeriod() {
    const period = document.getElementById('periodSelect').value;
    window.location.href = `?period=${period}`;
}
</script>
@endpush