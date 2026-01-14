@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard Financeiro"
            icon="currency-dollar"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Financeiro' => '#'
            ]"
            description="Visão geral das finanças do seu negócio com métricas de receita e faturamento."
        >
            <x-slot:actions>
                <select class="form-select form-select-sm w-auto" id="periodSelect" onchange="changePeriod()">
                    @foreach ($periods as $key => $label)
                        <option value="{{ $key }}" {{ $period === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </x-slot:actions>
        </x-layout.page-header>

        <!-- KPI Cards -->
        <x-layout.grid-row>
            <!-- Receita -->
            <x-dashboard.stat-card
                title="Receita"
                :value="'R$ ' . number_format($revenue['current'], 2, ',', '.')"
                icon="currency-dollar"
                variant="primary"
            >
                <x-slot:description>
                    <small class="text-{{ $revenue['growth_positive'] ? 'success' : 'danger' }}">
                        <i class="bi bi-arrow-{{ $revenue['growth_positive'] ? 'up' : 'down' }}"></i>
                        {{ abs($revenue['growth']) }}% vs anterior
                    </small>
                </x-slot:description>
            </x-dashboard.stat-card>

            <!-- Faturas Pagas -->
            <x-dashboard.stat-card
                title="Faturas Pagas"
                :value="$invoices['paid']"
                icon="check-circle"
                variant="success"
                :description="'de ' . $invoices['total'] . ' faturas (' . $invoices['conversion_rate'] . '%)'"
            />

            <!-- Faturas Pendentes -->
            <x-dashboard.stat-card
                title="Faturas Pendentes"
                :value="$invoices['pending'] + $invoices['overdue']"
                icon="clock"
                variant="warning"
                :description="'R$ ' . number_format($invoices['pending_amount'], 2, ',', '.') . ' em aberto'"
            />

            <!-- Ticket Médio -->
            <x-dashboard.stat-card
                title="Ticket Médio"
                :value="'R$ ' . number_format($payments['average_ticket'], 2, ',', '.')"
                icon="graph-up"
                variant="info"
                description="Média por pagamento recebido"
            />
        </x-layout.grid-row>

        <!-- Charts Row -->
        <x-layout.grid-row>
            <!-- Receita Timeline -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Receita dos Últimos 30 Dias"
                    icon="graph-up"
                    padding="p-4"
                >
                    <div style="height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Status das Faturas -->
            <x-layout.grid-col size="col-lg-4">
                <x-resource.resource-list-card
                    title="Status das Faturas"
                    icon="pie-chart"
                    padding="p-4"
                >
                    <div style="height: 300px;">
                        <canvas id="invoiceStatusChart"></canvas>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <!-- Métodos de Pagamento e Orçamentos -->
        <x-layout.grid-row>
            <!-- Métodos de Pagamento -->
            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Métodos de Pagamento"
                    icon="credit-card"
                    padding="p-4"
                >
                    @if (!empty($payments['by_method']))
                        <x-layout.v-stack gap="3">
                            @foreach ($payments['by_method'] as $method => $data)
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-dark">{{ \App\Models\Payment::getPaymentMethods()[$method] ?? $method }}</div>
                                        <small class="text-muted">{{ $data['count'] }} pagamentos</small>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold text-success">R$ {{ number_format($data['total'], 2, ',', '.') }}</span>
                                    </div>
                                </div>
                                @if(!$loop->last) <hr class="my-0 opacity-5"> @endif
                            @endforeach
                        </x-layout.v-stack>
                    @else
                        <x-resource.empty-state
                            title="Sem pagamentos"
                            description="Nenhum pagamento registrado no período selecionado."
                            icon="cash-stack"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Orçamentos -->
            <x-layout.grid-col size="col-lg-6">
                <x-resource.resource-list-card
                    title="Performance de Orçamentos"
                    icon="file-earmark-text"
                    padding="p-4"
                >
                    <x-layout.grid-row class="text-center mb-4">
                        <x-layout.grid-col size="col-6">
                            <h3 class="fw-bold text-primary mb-1">{{ $budgets['total'] }}</h3>
                            <p class="text-muted small text-uppercase mb-0">Total Gerado</p>
                        </x-layout.grid-col>
                        <x-layout.grid-col size="col-6">
                            <h3 class="fw-bold text-success mb-1">{{ $budgets['approved'] }}</h3>
                            <p class="text-muted small text-uppercase mb-0">Aprovados</p>
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <div class="text-center p-3 bg-light rounded-3">
                        <div class="display-6 fw-bold text-dark mb-1">{{ $budgets['approval_rate'] }}%</div>
                        <p class="text-muted small text-uppercase mb-0">Taxa de Aprovação</p>
                    </div>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
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
