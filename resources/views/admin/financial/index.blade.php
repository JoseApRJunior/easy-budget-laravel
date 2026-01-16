<x-app-layout title="Controle Financeiro">
    <x-layout.page-container>
        <x-layout.page-header
            title="Controle Financeiro"
            icon="cash-stack"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Controle Financeiro' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <x-ui.button type="button" variant="secondary" outline icon="download" label="Exportar" onclick="exportReports()" />
                    <x-ui.button type="button" variant="secondary" outline icon="bell" label="Alertas" onclick="refreshBudgetAlerts()" />
                    <x-ui.button :href="route('admin.enterprises.index')" variant="primary" icon="building" label="Ver Empresas" />
                </div>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Alertas de Orçamento -->
        @if (isset($budgetAlerts) && count($budgetAlerts) > 0)
            <div class="row mb-4">
                <div class="col-12">
                    <x-ui.card class="border-left-warning shadow">
                        <x-slot:header>
                            <h5 class="mb-0 text-warning">
                                <i class="fas fa-exclamation-triangle"></i> Alertas de Orçamento
                            </h5>
                        </x-slot:header>
                        <div class="list-group list-group-flush">
                            @foreach ($budgetAlerts as $alert)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $alert['provider_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $alert['message'] }}</small>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="badge badge-{{ $alert['severity'] == 'critical' ? 'danger' : 'warning' }}">
                                            {{ round($alert['percentage_used']) }}%
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            R$ {{ number_format($alert['current_spending'], 2, ',', '.') }} /
                                            R$ {{ number_format($alert['budget_limit'], 2, ',', '.') }}
                                        </small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                </div>
            </div>
        @endif

        <!-- Visão Geral Financeira -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-primary shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Receita Total
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($financialOverview['total_revenue'] ?? 0, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-danger shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Custos Totais
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($financialOverview['total_costs'] ?? 0, 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-minus-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-success shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Lucro Líquido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                R$ {{ number_format($financialOverview['net_profit'] ?? 0, 2, ',', '.') }}
                            </div>
                            <div class="text-xs text-success">
                                {{ number_format($financialOverview['profit_margin'] ?? 0, 1, ',', '.') }}% margem
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <x-ui.card class="border-left-info shadow h-100 py-2">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Provedores Ativos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $financialOverview['active_providers'] ?? 0 }}
                            </div>
                            <div class="text-xs text-info">
                                R$
                                {{ number_format($financialOverview['avg_revenue_per_provider'] ?? 0, 2, ',', '.') }}
                                média
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Card de Filtros (SEPARADO) -->
        <x-ui.card class="mb-4">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-ui.form.select 
                            name="status" 
                            label="Status" 
                            :options="[
                                'active' => 'Ativo',
                                'inactive' => 'Inativo',
                                'suspended' => 'Suspenso'
                            ]"
                            placeholder="Todos os Status"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.select 
                            name="plan" 
                            label="Plano" 
                            :options="[
                                'trial' => 'Trial',
                                'basic' => 'Básico',
                                'premium' => 'Premium'
                            ]"
                            placeholder="Todos os Planos"
                        />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="date" name="date_from" label="Data Início" :value="request('date_from')" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="date" name="date_to" label="Data Fim" :value="request('date_to')" />
                    </div>
                </div>
                <div class="row mt-3 g-3">
                    <div class="col-md-6">
                        <x-ui.form.input name="search" label="Buscar" placeholder="Buscar por empresa..." :value="request('search')" />
                    </div>
                    <div class="col-md-3">
                        <x-ui.form.input type="number" name="min_revenue" label="Receita Mínima" placeholder="0.00" step="0.01" :value="request('min_revenue')" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <x-ui.button type="submit" variant="primary" icon="search" label="Filtrar" />
                            <x-ui.button type="link" :href="route('admin.financial.index')" variant="secondary" icon="x-lg" label="Limpar" />
                        </div>
                    </div>
                </div>
            </form>
        </x-ui.card>

        <!-- Card Principal -->
        <x-ui.card>
            <x-slot:header>
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-chart-bar me-2"></i>Desempenho dos Provedores
                    </h6>
                    <div class="d-flex gap-2">
                        <x-ui.button type="link" :href="route('admin.financial.reports')" variant="primary" size="sm" icon="chart-bar" label="Relatórios Detalhados" />
                    </div>
                </div>
            </x-slot:header>

            @if (isset($providersData) && count($providersData) > 0)
                <!-- Tabela responsiva -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Receita Mensal</th>
                                <th>Custos</th>
                                <th>Lucro</th>
                                <th>Margem</th>
                                <th>Clientes</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($providersData as $provider)
                                <tr>
                                    <td>
                                        <strong>{{ $provider['name'] }}</strong>
                                        <br><small class="text-muted">{{ $provider['document'] ?? 'N/A' }}</small>
                                    </td>
                                    <td class="text-success">
                                        <strong>R$
                                            {{ number_format($provider['monthly_revenue'] ?? 0, 2, ',', '.') }}</strong>
                                    </td>
                                    <td class="text-danger">
                                        R$ {{ number_format($provider['monthly_costs'] ?? 0, 2, ',', '.') }}
                                    </td>
                                    <td
                                        class="{{ ($provider['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        <strong>R$ {{ number_format($provider['profit'] ?? 0, 2, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            $margin = $provider['profit_margin'] ?? 0;
                                            $marginClass = match (true) {
                                                $margin >= 20 => 'bg-success',
                                                $margin >= 10 => 'bg-warning',
                                                default => 'bg-danger',
                                            };
                                        @endphp
                                        <span class="badge {{ $marginClass }}">
                                            {{ number_format($margin, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $provider['customer_count'] ?? 0 }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match ($provider['status'] ?? 'inactive') {
                                                'active' => 'bg-success',
                                                'suspended' => 'bg-danger',
                                                default => 'bg-warning',
                                            };
                                            $statusText = match ($provider['status'] ?? 'inactive') {
                                                'active' => 'Ativo',
                                                'suspended' => 'Suspenso',
                                                default => 'Inativo',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <x-ui.button type="link" :href="'/admin/financial/providers/' . $provider['id'] . '/details'" variant="primary" size="sm" icon="chart-line" title="Ver Detalhes" />
                                            <x-ui.button type="link" :href="route('admin.enterprises.show', $provider['id'])" variant="info" size="sm" icon="building" title="Ver Empresa" />
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Mobile view -->
                <div class="d-md-none">
                    @foreach ($providersData as $provider)
                        <x-ui.card class="mb-3">
                            <h5 class="card-title">{{ $provider['name'] }}</h5>
                            <p class="card-text">
                                <strong>Receita:</strong>
                                <span class="text-success">R$
                                    {{ number_format($provider['monthly_revenue'] ?? 0, 2, ',', '.') }}</span><br>
                                <strong>Custos:</strong>
                                <span class="text-danger">R$
                                    {{ number_format($provider['monthly_costs'] ?? 0, 2, ',', '.') }}</span><br>
                                <strong>Lucro:</strong>
                                <span
                                    class="{{ ($provider['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    R$ {{ number_format($provider['profit'] ?? 0, 2, ',', '.') }}
                                </span><br>
                                <strong>Margem:</strong>
                                @php
                                    $margin = $provider['profit_margin'] ?? 0;
                                    $marginClass = match (true) {
                                        $margin >= 20 => 'bg-success',
                                        $margin >= 10 => 'bg-warning',
                                        default => 'bg-danger',
                                    };
                                @endphp
                                <span
                                    class="badge {{ $marginClass }}">{{ number_format($margin, 1) }}%</span><br>
                                <strong>Clientes:</strong>
                                <span class="badge bg-info">{{ $provider['customer_count'] ?? 0 }}</span><br>
                                <strong>Status:</strong>
                                @php
                                    $statusClass = match ($provider['status'] ?? 'inactive') {
                                        'active' => 'bg-success',
                                        'suspended' => 'bg-danger',
                                        default => 'bg-warning',
                                    };
                                    $statusText = match ($provider['status'] ?? 'inactive') {
                                        'active' => 'Ativo',
                                        'suspended' => 'Suspenso',
                                        default => 'Inativo',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </p>
                            <div class="d-flex gap-2 mt-3">
                                <x-ui.button type="link" :href="'/admin/financial/providers/' . $provider['id'] . '/details'" variant="primary" size="sm" icon="chart-line" label="Detalhes" class="flex-grow-1" />
                                <x-ui.button type="link" :href="route('admin.enterprises.show', $provider['id'])" variant="info" size="sm" icon="building" label="Empresa" class="flex-grow-1" />
                            </div>
                        </x-ui.card>
                    @endforeach
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-5">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhum dado financeiro encontrado</h5>
                    <p class="text-muted">Não há dados para exibir com os filtros aplicados.</p>
                    <x-ui.button type="link" :href="route('admin.enterprises.index')" variant="primary" icon="building" label="Ver Empresas" />
                </div>
            @endif
        </x-ui.card>
    </x-layout.page-container>
</x-app-layout>

@push('scripts')
    <script>
        $(document).ready(function() {
            // Auto-refresh budget alerts every 30 seconds if they exist
            @if (isset($budgetAlerts) && count($budgetAlerts) > 0)
                setInterval(refreshBudgetAlerts, 30000);
            @endif
        });

        function exportReports() {
            const startDate = prompt('Data inicial (YYYY-MM-DD):', new Date().toISOString().slice(0, 7) + '-01');
            const endDate = prompt('Data final (YYYY-MM-DD):', new Date().toISOString().slice(0, 10));

            if (startDate && endDate) {
                window.location.href = `/admin/financial/reports/export?start_date=${startDate}&end_date=${endDate}`;
            }
        }

        function refreshBudgetAlerts() {
            $.ajax({
                url: '{{ route('admin.financial.budget-alerts') }}',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        // Reload page to update alerts
                        location.reload();
                    }
                },
                error: function() {
                    console.log('Erro ao atualizar alertas');
                }
            });
        }
    </script>
@endsection
