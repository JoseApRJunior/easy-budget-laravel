@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">

        @php
            $user        = auth()->user();
            $pendingPlan = $user?->pendingPlan();
        @endphp

        {{-- Alertas de plano --}}
        @includeWhen( $user?->isTrialExpired(), 'partials.components.provider.plan-alert' )
        @includeWhen( $user?->isTrial() || ( $pendingPlan && $pendingPlan->status === 'pending' ), 'partials.components.provider.plan-modal' )

        {{-- Cabeçalho Moderno --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center bg-gradient-primary rounded-3 p-4 text-white">
                    <div>
                        <h1 class="h3 mb-1 fw-bold">
                            <i class="bi bi-speedometer2 me-2"></i>Painel do Prestador
                        </h1>
                        <p class="mb-0 opacity-75">Bem-vindo de volta, {{ $user->name }}!</p>
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
                        <h4 class="text-primary mb-1">R$ {{ number_format( ($financial_summary['monthly_revenue'] ?? 0), 2, ',', '.' ) }}</h4>
                        <p class="text-muted mb-0 small">Faturamento do Mês</p>
                        <small class="text-success">
                            <i class="bi bi-arrow-up"></i> +12% vs mês anterior
                        </small>
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
                        <small class="text-muted">R$ {{ number_format( $financial_summary['pending_budgets']['total'] ?? 0, 2, ',', '.' ) }}</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <div class="avatar-circle bg-success bg-opacity-10 mx-auto mb-3">
                            <i class="bi bi-people text-success fs-4"></i>
                        </div>
                        <h4 class="text-success mb-1">{{ App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->count() }}</h4>
                        <p class="text-muted mb-0 small">Total de Clientes</p>
                        <small class="text-success">
                            <i class="bi bi-person-plus"></i> {{ App\Models\Customer::where('tenant_id', $user->tenant_id ?? 0)->whereMonth('created_at', now())->count() }} este mês
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

        {{-- Linha 1: Resumo Financeiro + Atividades --}}
        @php
            $translations = [
                'actionIcons'            => [
                    'created_budget' => 'bi-file-earmark-plus',
                    'updated_budget' => 'bi-pencil-square',
                    'deleted_budget' => 'bi-trash',
                ],
                'textColors'             => [
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

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <x-financial-summary :summary="$financial_summary" />
            </div>
            <div class="col-12 col-lg-4">
                <x-activities :activities="$activities" :translations="$translations" :total="$total_activities" />
            </div>
        </div>

        {{-- Linha 2: Ações Rápidas --}}
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-lightning-charge me-2 text-warning"></i>
                            Ações Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="{{ route( 'provider.budgets.create' ) }}" class="btn btn-primary w-100">
                                    <i class="bi bi-file-earmark-plus me-2"></i>
                                    Novo Orçamento
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route( 'provider.customers.create' ) }}" class="btn btn-success w-100">
                                    <i class="bi bi-person-plus me-2"></i>
                                    Novo Cliente
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-info w-100">
                                    <i class="bi bi-tools me-2"></i>
                                    Serviços
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="{{ route( 'provider.invoices.create' ) }}" class="btn btn-warning w-100">
                                    <i class="bi bi-receipt me-2"></i>
                                    Nova Fatura
                                </a>
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
        box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
    }
</style>
@endpush
