@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">

        @php
            $user        = auth()->user();
            $pendingPlan = $user?->pendingPlan();
        @endphp

        {{-- Alertas de plano --}}
        @includeWhen( $user?->isTrialExpired(), 'partials.components.provider.plan-alert' )
        @includeWhen( $user?->isTrial() || ( $pendingPlan && $pendingPlan->status === 'pending' ), 'partials.components.provider.plan-modal' )

        <h1 class="mb-4">Painel do Prestador</h1>

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
            <div class="col-12 col-lg-6">
                <x-financial-summary :summary="$financial_summary" />
            </div>
            <div class="col-12 col-lg-6">
                <x-activities :activities="$activities" :translations="$translations" :total="$total_activities" />
            </div>
        </div>

        {{-- Linha 2: Ações Rápidas --}}
        <div class="row g-4 mt-2">
            <div class="col-12">
                <x-quick-actions />
            </div>
        </div>

        {{-- Linha 3: Orçamentos + Compromissos --}}
        <div class="row g-4 mt-2">
            <div class="col-12 col-lg-6">
                <x-recent-budgets :budgets="$budgets" />
            </div>
            <div class="col-12 col-lg-6">
                <x-upcoming-events :events="$events ?? []" />
            </div>
        </div>

    </div>
@endsection
