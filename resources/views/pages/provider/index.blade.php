@extends('layouts.app')

@section('content')
    <div class="container-fluid py-1">

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
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center bg-gradient-primary rounded-3 p-4 text-white">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i>Painel do Prestador
                        </h1>
                        <p class="mb-0 opacity-75">Bem-vindo de volta,
                            {{ $user->provider?->commonData?->first_name ?? ($user->provider?->commonData?->company_name ?? $user->name) }}!
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="fs-5 fw-semibold">{{ now()->format('d/m/Y') }}</div>
                        <div class="small opacity-75">{{ now()->format('H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Cards de Métricas Rápidas --}}
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="avatar-circle bg-primary bg-opacity-10 mx-auto mb-3">
                            <i class="bi bi-currency-dollar text-primary fs-4"></i>
                        </div>
                        <h4 class="text-primary mb-1">R$
                            {{ number_format($financial_summary['monthly_revenue'] ?? 0, 2, ',', '.') }}</h4>
                        <p class="text-muted mb-0 small">Faturamento do Mês</p>
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
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="avatar-circle bg-warning bg-opacity-10 mx-auto mb-3">
                            <i class="bi bi-file-earmark-text text-warning fs-4"></i>
                        </div>
                        <h4 class="text-warning mb-1">{{ $financial_summary['pending_budgets']['count'] ?? 0 }}</h4>
                        <p class="text-muted mb-0 small">Orçamentos Pendentes</p>
                        <small class="text-muted">R$
                            {{ number_format($financial_summary['pending_budgets']['total'] ?? 0, 2, ',', '.') }}</small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="avatar-circle bg-success bg-opacity-10 mx-auto mb-3">
                            <i class="bi bi-people text-success fs-4"></i>
                        </div>
                        <h4 class="text-success mb-1">
                            {{ App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->count() }}</h4>
                        <p class="text-muted mb-0 small">Total de Clientes</p>
                        <small class="text-success">
                            <i class="bi bi-person-plus"></i>
                            {{ App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->whereMonth('created_at', now())->count() }}
                            este mês
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="avatar-circle bg-info bg-opacity-10 mx-auto mb-3">
                            <i class="bi bi-calendar-check text-info fs-4"></i>
                        </div>
                        <h4 class="text-info mb-1">{{ count($events ?? []) }}</h4>
                        <p class="text-muted mb-0 small">Compromissos Hoje</p>
                        <small class="text-info">
                            <i class="bi bi-clock"></i> {{ now()->format('H:i') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Linha 1: Resumo Financeiro + Alertas de Estoque --}}
        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <x-financial-summary :summary="$financial_summary" />
            </div>
            <div class="col-12 col-lg-4">
                <x-inventory-alerts :items="$low_stock_items" :count="$low_stock_count" />
            </div>
        </div>

        {{-- Linha 2: Ações Rápidas --}}
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0">
                        <h5 class="card-title mb-0 py-1">
                            <i class="bi bi-lightning-charge me-2 text-warning"></i>
                            Ações Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.budgets.create')" variant="primary" class="w-100 py-3 hover-card">
                                    <i class="bi bi-file-earmark-plus fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Novo Orçamento</span>
                                </x-button>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.customers.create')" variant="success" class="w-100 py-3 hover-card">
                                    <i class="bi bi-person-plus fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Novo Cliente</span>
                                </x-button>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.services.index')" variant="info" class="w-100 py-3 hover-card">
                                    <i class="bi bi-tools fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Serviços</span>
                                </x-button>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.invoices.create')" variant="warning" class="w-100 py-3 hover-card">
                                    <i class="bi bi-receipt fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Nova Fatura</span>
                                </x-button>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.inventory.index')" variant="secondary" class="w-100 py-3 hover-card">
                                    <i class="bi bi-box-seam fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Estoque</span>
                                </x-button>
                            </div>
                            <div class="col-6 col-md-2 mb-3">
                                <x-button type="link" :href="route('provider.qrcode.index')" variant="dark" class="w-100 py-3 hover-card">
                                    <i class="bi bi-qr-code-scan fs-4 d-block mb-1"></i>
                                    <span class="small fw-bold">Gerar QR Code</span>
                                </x-button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Linha 3: Orçamentos + Compromissos --}}
        <div class="row g-4 mt-2">
            <div class="col-12 col-lg-8">
                <x-recent-budgets :budgets="$budgets" />
            </div>
            <div class="col-12 col-lg-4">
                <x-upcoming-events :events="$events ?? []" />
            </div>
        </div>

    </div>
@endsection

@push('styles')
    <style>
        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .avatar-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hover-card {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .hover-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1) !important;
        }
    </style>
@endpush
