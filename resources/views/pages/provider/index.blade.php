@extends('layouts.app')

@section('content')
    <x-layout.page-container>

        @php
            $user = auth()->user();
            $pendingPlan = $user?->pendingPlan();
        @endphp

        {{-- Alertas de plano --}}
        @includeWhen($user?->isTrialExpired(), 'partials.components.provider.plan-alert')
        @includeWhen(
            $user?->isTrial() || ($pendingPlan && $pendingPlan->status === 'pending'),
            'partials.components.provider.plan-modal')

        {{-- Cabeçalho Moderno --}}
        <x-layout.grid-row class="mb-4">
            <x-layout.grid-col>
                <div class="d-flex justify-content-between align-items-center bg-gradient-primary rounded-3 p-4 text-white shadow-sm">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i>Painel do Prestador
                        </h1>
                        <p class="mb-0 opacity-75">Bem-vindo de volta,
                            {{ $user->provider?->commonData?->first_name ?? ($user->provider?->commonData?->company_name ?? $user->name) }}!
                        </p>
                    </div>
                    <div class="text-end d-none d-md-block">
                        <div class="fs-5 fw-semibold">{{ now()->format('d/m/Y') }}</div>
                        <div class="small opacity-75">{{ now()->format('H:i') }}</div>
                    </div>
                </div>
            </x-layout.grid-col>
        </x-layout.grid-row>

        {{-- Cards de Métricas Rápidas --}}
        <x-layout.grid-row>
            {{-- Faturamento --}}
            <x-dashboard.stat-card
                title="Faturamento do Mês"
                :value="'R$ ' . number_format($financial_summary['monthly_revenue'] ?? 0, 2, ',', '.')"
                icon="currency-dollar"
                variant="primary"
                col="col-12 col-md-6 col-lg-3"
            >
                <x-slot:description>
                    @if(isset($financial_summary['trends']['growth_rate']))
                        <small class="text-{{ $financial_summary['trends']['growth_rate'] >= 0 ? 'success' : 'danger' }}">
                            <i class="bi bi-arrow-{{ $financial_summary['trends']['growth_rate'] >= 0 ? 'up' : 'down' }}"></i>
                            {{ number_format(abs($financial_summary['trends']['growth_rate']), 1, ',', '.') }}% vs anterior
                        </small>
                    @else
                        <small class="text-muted">
                            <i class="bi bi-dash"></i> Sem dados comparativos
                        </small>
                    @endif
                </x-slot:description>
            </x-dashboard.stat-card>

            {{-- Orçamentos Pendentes --}}
            <x-dashboard.stat-card
                title="Orçamentos Pendentes"
                :value="$financial_summary['pending_budgets']['count'] ?? 0"
                icon="file-earmark-text"
                variant="warning"
                col="col-12 col-md-6 col-lg-3"
                description="Total: R$ {{ number_format($financial_summary['pending_budgets']['total'] ?? 0, 2, ',', '.') }}"
            />

            {{-- Total de Clientes --}}
            <x-dashboard.stat-card
                title="Total de Clientes"
                :value="App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->count()"
                icon="people"
                variant="success"
                col="col-12 col-md-6 col-lg-3"
            >
                <x-slot:description>
                    <small class="text-success">
                        <i class="bi bi-person-plus"></i>
                        {{ App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->whereMonth('created_at', now())->count() }}
                        este mês
                    </small>
                </x-slot:description>
            </x-dashboard.stat-card>

            {{-- Compromissos --}}
            <x-dashboard.stat-card
                title="Compromissos Hoje"
                :value="count($events ?? [])"
                icon="calendar-check"
                variant="info"
                col="col-12 col-md-6 col-lg-3"
            >
                <x-slot:description>
                    <small class="text-info">
                        <i class="bi bi-clock"></i> {{ now()->format('H:i') }}
                    </small>
                </x-slot:description>
            </x-dashboard.stat-card>
        </x-layout.grid-row>

        {{-- Linha 1: Resumo Financeiro + Alertas de Estoque --}}
        <x-layout.grid-row>
            <x-layout.grid-col size="col-12 col-lg-8">
                <x-dashboard.financial-summary :summary="$financial_summary" />
            </x-layout.grid-col>
            <x-layout.grid-col size="col-12 col-lg-4">
                <x-dashboard.inventory-alerts :items="$low_stock_items" :count="$low_stock_count" />
            </x-layout.grid-col>
        </x-layout.grid-row>

        {{-- Linha 2: Ações Rápidas --}}
        <x-layout.grid-row class="mt-2">
            <x-layout.grid-col>
                <x-resource.resource-list-card
                    title="Ações Rápidas"
                    icon="lightning-charge"
                    padding="p-4"
                >
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.budgets.create')" variant="outline-primary" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-file-earmark-plus fs-4"></i>
                                <span class="small fw-bold">Novo Orçamento</span>
                            </x-ui.button>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.customers.create')" variant="outline-success" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-person-plus fs-4"></i>
                                <span class="small fw-bold">Novo Cliente</span>
                            </x-ui.button>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.services.index')" variant="outline-info" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-tools fs-4"></i>
                                <span class="small fw-bold">Serviços</span>
                            </x-ui.button>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.invoices.create')" variant="outline-warning" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-receipt fs-4"></i>
                                <span class="small fw-bold">Nova Fatura</span>
                            </x-ui.button>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.inventory.index')" variant="outline-secondary" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-box-seam fs-4"></i>
                                <span class="small fw-bold">Estoque</span>
                            </x-ui.button>
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-6 col-md-2">
                            <x-ui.button type="link" :href="route('provider.qrcode.index')" variant="outline-dark" class="w-100 py-3 h-100 d-flex flex-column align-items-center justify-content-center gap-2">
                                <i class="bi bi-qr-code-scan fs-4"></i>
                                <span class="small fw-bold">QR Code</span>
                            </x-ui.button>
                        </x-layout.grid-col>
                    </x-layout.grid-row>
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        {{-- Linha 3: Orçamentos + Compromissos --}}
        <x-layout.grid-row class="mt-2">
            <x-layout.grid-col size="col-12 col-lg-8">
                <x-dashboard.recent-budgets :budgets="$budgets" />
            </x-layout.grid-col>
            <x-layout.grid-col size="col-12 col-lg-4">
                <x-dashboard.upcoming-events :events="$events ?? []" />
            </x-layout.grid-col>
        </x-layout.grid-row>

    </x-layout.page-container>
@endsection

@push('styles')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
@endpush
