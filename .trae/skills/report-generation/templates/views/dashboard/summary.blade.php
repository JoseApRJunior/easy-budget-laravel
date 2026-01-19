@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active">Resumo Executivo</li>
                    </ol>
                </div>
                <h4 class="page-title">Resumo Executivo</h4>
            </div>
        </div>
    </div>

    <!-- KPIs Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-currency-usd widget-icon"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Receita Total">Receita Total</h5>
                    <h3 class="mt-2 mb-1">{{ formatCurrency($summary['total_revenue'] ?? 0) }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-{{ $summary['revenue_growth'] >= 0 ? 'success' : 'danger' }}">
                            <i class="mdi mdi-arrow-{{ $summary['revenue_growth'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ $summary['revenue_growth'] >= 0 ? '+' : '' }}{{ $summary['revenue_growth'] ?? 0 }}%
                        </span>
                        <span class="ms-2">vs mês anterior</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-account-group widget-icon"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Clientes Ativos">Clientes Ativos</h5>
                    <h3 class="mt-2 mb-1">{{ $summary['active_customers'] ?? 0 }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-success">
                            <i class="mdi mdi-arrow-up"></i>
                            {{ $summary['customer_growth'] ?? 0 }}%
                        </span>
                        <span class="ms-2">vs mês anterior</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-package-variant widget-icon"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Produtos em Estoque">Estoque Total</h5>
                    <h3 class="mt-2 mb-1">{{ $summary['total_stock_value'] ?? 0 }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-warning">
                            <i class="mdi mdi-alert"></i>
                            {{ $summary['low_stock_products'] ?? 0 }} baixo
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card widget-flat">
                <div class="card-body">
                    <div class="float-end">
                        <i class="mdi mdi-file-document widget-icon"></i>
                    </div>
                    <h5 class="text-muted fw-normal mt-0" title="Faturas Pendentes">Faturas Pendentes</h5>
                    <h3 class="mt-2 mb-1">{{ $summary['pending_invoices'] ?? 0 }}</h3>
                    <p class="mb-0 text-muted">
                        <span class="text-danger">
                            <i class="mdi mdi-currency-usd"></i>
                            {{ formatCurrency($summary['pending_amount'] ?? 0) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Receita por Mês</h4>
                </div>
                <div class="card-body">
                    <div id="revenue-chart" class="apex-charts" data-colors="#007bff"></div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Status das Faturas</h4>
                </div>
                <div class="card-body">
                    <div id="invoice-status-chart" class="apex-charts" data-colors="#28a745,#ffc107,#dc3545"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Tables Row -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Produtos Mais Vendidos</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Produto</th>
                                    <th class="text-end">Quantidade</th>
                                    <th class="text-end">Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_products as $product)
                                <tr>
                                    <td>{{ $product['name'] }}</td>
                                    <td class="text-end">{{ $product['quantity'] }}</td>
                                    <td class="text-end">{{ formatCurrency($product['total_value']) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Clientes com Maior Compra</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Cliente</th>
                                    <th class="text-end">Total Comprado</th>
                                    <th class="text-end">Última Compra</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($top_customers as $customer)
                                <tr>
                                    <td>{{ $customer['name'] }}</td>
                                    <td class="text-end">{{ formatCurrency($customer['total_spent']) }}</td>
                                    <td class="text-end">{{ $customer['last_purchase'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts Row -->
    @if(!empty($alerts))
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Alertas e Notificações</h4>
                </div>
                <div class="card-body">
                    @foreach($alerts as $alert)
                    <div class="alert alert-{{ $alert['type'] }} alert-dismissible fade show" role="alert">
                        <i class="mdi mdi-alert-circle me-2"></i>
                        {{ $alert['message'] }}
                        @if(isset($alert['action_url']))
                        <a href="{{ $alert['action_url'] }}" class="alert-link">Ver detalhes</a>
                        @endif
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
    // Revenue Chart
    var revenueChart = new ApexCharts(document.querySelector("#revenue-chart"), {
        chart: {
            type: 'line',
            height: 350
        },
        series: [{
            name: 'Receita',
            data: @json($charts['revenue_by_month']['data'])
        }],
        xaxis: {
            categories: @json($charts['revenue_by_month']['labels'])
        },
        colors: ['#007bff']
    });
    revenueChart.render();

    // Invoice Status Chart
    var invoiceStatusChart = new ApexCharts(document.querySelector("#invoice-status-chart"), {
        chart: {
            type: 'donut',
            height: 350
        },
        series: @json($charts['invoice_status']['data']),
        labels: ['Pagas', 'Pendentes', 'Canceladas'],
        colors: ['#28a745', '#ffc107', '#dc3545']
    });
    invoiceStatusChart.render();
</script>
@endpush
@endsection
