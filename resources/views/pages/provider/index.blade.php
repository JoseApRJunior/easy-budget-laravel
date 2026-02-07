@extends('layouts.app')

@section('content')
    <x-layout.page-container>
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

        {{-- Linha 3: Orçamentos + Sidebar --}}
        <x-layout.grid-row class="mt-2">
            <x-layout.grid-col size="col-12 col-lg-8">
                <x-layout.v-stack gap="4">
                    <x-dashboard.recent-budgets :budgets="$budgets" />

                    {{-- Log de Atividades (Resgatado da view obsoleta) --}}
                    @php
                        $translations = [
                            'actionIcons' => [
                                'created_budget' => 'bi-file-earmark-plus',
                                'updated_budget' => 'bi-pencil-square',
                                'deleted_budget' => 'bi-trash',
                            ],
                            'textColors' => [
                                'created_budget' => 'text-success',
                                'updated_budget' => 'text-warning',
                                'deleted_budget' => 'text-danger',
                            ],
                            'descriptionTranslation' => [
                                'created_budget' => 'Orçamento Criado',
                                'updated_budget' => 'Orçamento Atualizado',
                                'deleted_budget' => 'Orçamento Removido',
                            ],
                        ];
                    @endphp
                                    </x-layout.v-stack>
            </x-layout.grid-col>

            <x-layout.grid-col size="col-12 col-lg-4">
                <x-layout.v-stack gap="4">
                    {{-- Ações Rápidas --}}
                    <x-resource.quick-actions
                        title="Ações Rápidas"
                        icon="lightning-charge"
                    >
                        <x-ui.button type="link" :href="route('provider.customers.create')" variant="outline-success" icon="person-plus" label="Novo Cliente" />
                        <x-ui.button type="link" :href="route('provider.budgets.create')" variant="outline-success" icon="plus-lg" label="Novo Orçamento" />
                        <x-ui.button type="link" :href="route('provider.services.create')" variant="outline-success" icon="plus-lg" label="Novo Serviço" />
                        <x-ui.button type="link" :href="route('provider.services.index')" variant="outline-primary" icon="tools" label="Listar Serviços" />
                        <x-ui.button type="link" :href="route('provider.inventory.index')" variant="outline-primary" icon="box-seam" label="Listar Estoque" />
                        <x-ui.button type="link" :href="route('provider.qrcode.index')" variant="outline-secondary" icon="qr-code-scan" label="QR Code" />
                    </x-resource.quick-actions>

                    {{-- Próximos Compromissos --}}
                    <x-dashboard.upcoming-events :events="$events ?? []" />
                </x-layout.v-stack>
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
