@extends('layouts.app')

@section('title', 'Status do Pagamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Status do Pagamento"
            icon="credit-card"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Planos' => route('provider.plans.index'),
                'Status do Pagamento' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('provider.plans.index')" variant="secondary" outline icon="arrow-left" label="Voltar para Planos" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row class="justify-content-center">
            <div class="col-md-8 col-lg-6">
                <x-ui.card class="text-center">
                    <x-slot:header>
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-wallet2 me-2"></i>Resultado da Transação
                        </h4>
                    </x-slot:header>
                    
                    <div class="p-3">
                        @if ($status === 'approved')
                            <div class="text-success mb-3">
                                <i class="bi bi-check-circle-fill display-1"></i>
                            </div>
                            <h4 class="fw-bold text-success mb-3">Pagamento Aprovado!</h4>
                            <p class="lead mb-4">Seu plano foi ativado com sucesso.</p>
                            
                        @elseif ($status === 'pending')
                            <div class="text-warning mb-3">
                                <i class="bi bi-hourglass-split display-1"></i>
                            </div>
                            <h4 class="fw-bold text-warning mb-3">Pagamento Pendente</h4>
                            <p class="lead mb-4">Estamos aguardando a confirmação do pagamento.</p>
                            
                        @elseif ($status === 'rejected')
                            <div class="text-danger mb-3">
                                <i class="bi bi-x-circle-fill display-1"></i>
                            </div>
                            <h4 class="fw-bold text-danger mb-3">Pagamento Rejeitado</h4>
                            <p class="lead mb-4">Houve um problema com seu pagamento. Por favor, tente novamente.</p>
                            
                        @else
                            <div class="text-info mb-3">
                                <i class="bi bi-info-circle-fill display-1"></i>
                            </div>
                            <h4 class="fw-bold text-info mb-3">Status Desconhecido</h4>
                            <p class="lead mb-4">Não foi possível determinar o status do pagamento no momento.</p>
                        @endif

                        <div class="d-flex flex-column gap-2">
                            @if (isset($plan_slug))
                                <x-ui.button :href="route('provider.plans.show', $plan_slug)" variant="primary" size="lg" icon="eye" label="Ver Detalhes do Plano" />
                            @endif
                            
                            <x-ui.button :href="route('provider.plans.index')" variant="secondary" outline size="lg" icon="arrow-left" label="Voltar para Planos" />
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
