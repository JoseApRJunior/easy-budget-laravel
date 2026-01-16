<x-app-layout title="Detalhes Financeiros">
    <x-layout.page-container>
        <x-layout.page-header
            title="Detalhes Financeiros"
            icon="cash-stack"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Controle Financeiro' => route('admin.financial.index'),
                $providerFinancialDetails['provider_name'] => '#'
            ]">
        </x-layout.page-header>

    <!-- Alertas Financeiros -->
    @if(count($providerFinancialDetails['alerts']) > 0)
    <div class="row mb-4">
        <div class="col-12">
            @foreach($providerFinancialDetails['alerts'] as $alert)
            <div class="alert alert-{{ $alert['severity'] == 'critical' ? 'danger' : 'warning' }} alert-dismissible fade show" role="alert">
                <strong><i class="fas fa-exclamation-triangle"></i> Alerta:</strong> {{ $alert['message'] }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Visão Geral Financeira do Provedor -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Receita Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($providerFinancialDetails['revenue']['total'], 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Custos Totais
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($providerFinancialDetails['costs']['total'], 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-muted">
                                Assinatura: R$ {{ number_format($providerFinancialDetails['costs']['subscription'], 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-minus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Lucro Líquido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($providerFinancialDetails['profitability']['net_profit'], 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-info">
                                {{ number_format($providerFinancialDetails['profitability']['profit_margin'], 1, ',', '.') }}% margem
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Crescimento Mensal
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ number_format($providerFinancialDetails['revenue']['growth_rate'], 1, ',', '.') }}%
                            </div>
                            <div class="text-xs text-muted">
                                vs mês anterior
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percentage fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas de Performance -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ticket Médio</h6>
                </div>
                <div class="card-body text-center">
                    <div class="h4 text-primary">
                        R$ {{ number_format($providerFinancialDetails['metrics']['avg_ticket'], 2, ',', '.') }}
                    </div>
                    <small class="text-muted">Valor médio por transação</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Valor do Cliente</h6>
                </div>
                <div class="card-body text-center">
                    <div class="h4 text-primary">
                        R$ {{ number_format($providerFinancialDetails['metrics']['customer_lifetime_value'], 2, ',', '.') }}
                    </div>
                    <small class="text-muted">Valor médio por cliente</small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Taxa de Pagamento</h6>
                </div>
                <div class="card-body text-center">
                    <div class="h4 text-{{ $providerFinancialDetails['metrics']['invoice_payment_rate'] >= 80 ? 'success' : 'warning' }}">
                        {{ number_format($providerFinancialDetails['metrics']['invoice_payment_rate'], 1, ',', '.') }}%
                    </div>
                    <small class="text-muted">Faturas pagas / total</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Comparação Mensal -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Comparação Mensal</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Período</th>
                                    <th>Receita</th>
                                    <th>Custos</th>
                                    <th>Lucro</th>
                                    <th>Crescimento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Mês Atual</strong></td>
                                    <td class="text-success">
                                        R$ {{ number_format($providerFinancialDetails['revenue']['this_month'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-danger">
                                        R$ {{ number_format($providerFinancialDetails['costs']['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="{{ $providerFinancialDetails['revenue']['this_month'] - $providerFinancialDetails['costs']['total'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        R$ {{ number_format($providerFinancialDetails['revenue']['this_month'] - $providerFinancialDetails['costs']['total'], 2, ',', '.') }}
                                    </td>
                                    <td>-</td>
                                </tr>
                                <tr>
                                    <td><strong>Mês Anterior</strong></td>
                                    <td class="text-success">
                                        R$ {{ number_format($providerFinancialDetails['revenue']['last_month'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-danger">
                                        R$ {{ number_format($providerFinancialDetails['costs']['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="{{ $providerFinancialDetails['revenue']['last_month'] - $providerFinancialDetails['costs']['total'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        R$ {{ number_format($providerFinancialDetails['revenue']['last_month'] - $providerFinancialDetails['costs']['total'], 2, ',', '.') }}
                                    </td>
                                    <td class="{{ $providerFinancialDetails['revenue']['growth_rate'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($providerFinancialDetails['revenue']['growth_rate'], 1, ',', '.') }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-footer bg-white border-top-0">
                    <div class="d-flex flex-wrap gap-2">
                        <x-ui.button type="link" :href="route('admin.financial.reports', ['tenant_id' => $providerFinancialDetails['tenant_id']])" variant="primary" label="Ver Relatórios" icon="chart-bar" class="flex-grow-1" />
                        <x-ui.button variant="success" label="Exportar Dados" icon="download" class="flex-grow-1" onclick="exportProviderReports()" />
                        <x-ui.button type="link" :href="route('admin.enterprises.show', $providerFinancialDetails['tenant_id'])" variant="info" label="Ver Empresa" icon="building" class="flex-grow-1" />
                        <x-ui.back-button index-route="admin.financial.index" class="flex-grow-1" label="Voltar" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function exportProviderReports() {
    const tenantId = {{ $providerFinancialDetails['tenant_id'] }};
    const startDate = prompt('Data inicial (YYYY-MM-DD):', new Date().toISOString().slice(0, 7) + '-01');
    const endDate = prompt('Data final (YYYY-MM-DD):', new Date().toISOString().slice(0, 10));
    
    if (startDate && endDate) {
        window.location.href = `/admin/financial/reports/export?tenant_id=${tenantId}&start_date=${startDate}&end_date=${endDate}`;
    }
}

// Adicionar animação aos números
$(document).ready(function() {
    $('.h5, .h4').each(function() {
        const $this = $(this);
        const finalValue = parseFloat($this.text().replace(/[^\d,]/g, '').replace(',', '.'));
        
        if (!isNaN(finalValue) && finalValue > 0) {
            $({ counter: 0 }).animate({ counter: finalValue }, {
                duration: 2000,
                easing: 'swing',
                step: function() {
                    const formatted = this.counter.toLocaleString('pt-BR', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    $this.text($this.text().replace(/[\d,]+/, formatted));
                }
            });
        }
    });
});
</script>
@endsection