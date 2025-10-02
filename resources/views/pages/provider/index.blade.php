@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        @if ( checkPlan()->slug == 'free' || ( checkPlanPending() && checkPlanPending()->status == 'pending' ) )

            {{-- Modal de Alerta de Plano --}}
            <div class="modal fade" id="planAlertModal" tabindex="-1" aria-labelledby="planAlertModalLabel">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0">
                        {{-- Header do Modal --}}
                        <div class="modal-header border-0">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-info-circle text-primary me-2"></i>
                                <h5 class="modal-title" id="planAlertModalLabel">
                                    Informação do Plano
                                </h5>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>

                        {{-- Corpo do Modal --}}
                        <div class="modal-body">
                            @if ( checkPlanPending() && checkPlanPending()->status == 'pending' )
                                <p class="text-muted mb-3">
                                    Você possui uma assinatura para o plano
                                    <strong>{{ checkPlanPending()->name }}</strong>
                                    aguardando pagamento. O que você gostaria de fazer?
                                </p>
                                <div class="d-flex flex-column gap-2">
                                    <a href="{{ route( 'plans.status' ) }}" class="btn btn-primary d-grid">
                                        <i class="bi bi-hourglass-split me-2"></i>
                                        Verificar Status do Pagamento
                                    </a>
                                    <form action="{{ route( 'plans.cancel-pending' ) }}" method="post" class="d-grid">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cancelar e Escolher Outro Plano
                                        </button>
                                    </form>
                                </div>
                            @else
                                <p class="text-muted mb-3">
                                    Seu plano atual possui algumas limitações. Para uma melhor experiência, considere
                                    atualizar para um plano com mais recursos.
                                </p>
                                <div class="d-flex flex-column gap-2">
                                    <a href="{{ route( 'plans.index' ) }}" class="btn btn-outline-primary">
                                        <i class="bi bi-arrow-up-circle me-2"></i>
                                        Conhecer Planos
                                    </a>
                                </div>
                            @endif
                        </div>

                        {{-- Footer do Modal --}}
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-link btn-sm text-muted" data-bs-dismiss="modal">
                                Continuar com o plano atual
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <h1 class="mb-4">Painel do Prestador</h1>

        <div class="row">
            <!-- Resumo Financeiro -->
            <div class="col-12 col-md-6 mb-4">
                <div class="card hover-card mb-4">
                    <div class="card-header bg-primary">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Resumo Financeiro</h5>
                            <span class="badge bg-light text-primary">
                                {{ month_year_pt( now() ) }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- Faturamento Mensal --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">Faturamento Mensal</h6>
                                <small class="text-muted">Total aprovado/pago</small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0 text-success">
                                    R$
                                    {{ number_format( $financial_summary[ 'monthly_revenue' ], 2, ',', '.' ) }}
                                </h5>
                            </div>
                        </div>

                        {{-- Orçamentos Pendentes --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">Orçamentos Pendentes</h6>
                                <small class="text-muted">
                                    {{ $financial_summary[ 'pending_budgets' ][ 'count' ] }} orçamento(s)
                                </small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0 text-warning">
                                    R$
                                    {{ number_format( $financial_summary[ 'pending_budgets' ][ 'total' ], 2, ',', '.' ) }}
                                </h5>
                            </div>
                        </div>

                        {{-- Pagamentos Atrasados --}}
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h6 class="mb-0">Pagamentos Atrasados</h6>
                                <small class="text-muted">
                                    {{ $financial_summary[ 'overdue_payments' ][ 'count' ] }} pagamento(s)
                                </small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0 text-danger">
                                    R$
                                    {{ number_format( $financial_summary[ 'overdue_payments' ][ 'total' ], 2, ',', '.' ) }}
                                </h5>
                            </div>
                        </div>

                        {{-- Projeção Próximo Mês --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0">Projeção Próximo Mês</h6>
                                <small class="text-muted">{{ now()->addMonth()->format( 'M/Y' ) }}</small>
                            </div>
                            <div class="text-end">
                                <h5 class="mb-0 text-info">
                                    R$
                                    {{ number_format( $financial_summary[ 'next_month_projection' ], 2, ',', '.' ) }}
                                </h5>
                            </div>
                        </div>

                        {{-- Gráfico ou Indicadores --}}
                        <div class="mt-4">
                            @php
                                $monthly_revenue     = $financial_summary[ 'monthly_revenue' ] ?? 0;
                                $pending_total       = $financial_summary[ 'pending_budgets' ][ 'total' ] ?? 0;
                                $total               = $monthly_revenue + $pending_total;
                                $approved_percentage = $total > 0 ? ( $monthly_revenue / $total ) * 100 : 0;
                                $pending_percentage  = $total > 0 ? ( $pending_total / $total ) * 100 : 0;
                            @endphp

                            @if ( $total > 0 )
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar"
                                        style="width: {{ $approved_percentage }}%" aria-valuenow="{{ $approved_percentage }}"
                                        aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
                                        title="Faturado: {{ round( $approved_percentage ) }}%">
                                    </div>
                                    <div class="progress-bar bg-warning" role="progressbar"
                                        style="width: {{ $pending_percentage }}%" aria-valuenow="{{ $pending_percentage }}"
                                        aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip"
                                        title="Pendente: R$ {{ number_format( $pending_total, 2, ',', '.' ) }} ({{ round( $pending_percentage ) }}%)">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small class="text-success">{{ round( $approved_percentage ) }}% Faturado</small>
                                    <small class="text-warning">{{ round( $pending_percentage ) }}% Pendente</small>
                                </div>
                            @else
                                <div class="alert alert-light text-center mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Nenhum valor registrado para o período
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Última atualização: {{ now()->format( 'd/m/Y H:i' ) }}</small>
                            <a href="{{ route( 'provider.reports.financial' ) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-graph-up me-1"></i>Ver Relatório Completo
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Atividades Recentes -->
            <div class="col-md-6 mb-4">
                <div class="card border-0 shadow-sm hover-card">
                    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-activity me-2"></i>Atividades Recentes
                        </h5>
                        <span class="badge bg-white text-info rounded-pill px-3">{{ count( $activities ) }}</span>
                    </div>

                    <div class="card-body p-0">
                        @if ( empty( $activities ) )
                            <div class="text-center py-1">
                                <i class="bi bi-calendar-x text-muted" style="font-size: var(--icon-size-xl);"></i>
                                <p class="text-muted mt-3 mb-0">Nenhuma atividade recente encontrada</p>
                                <small class="small-text">As atividades aparecerão aqui conforme você utiliza o
                                    sistema</small>
                            </div>
                        @else
                            <div class="activity-timeline" style="max-height: 400px; overflow-y: auto;">
                                @foreach ( $activities as $activity )
                                    <div class="activity-item p-3 @if ( !$loop->last ) border-bottom @endif">
                                        <div class="d-flex">
                                            <!-- Ícone com círculo colorido -->
                                            <div class="me-3">
                                                <div class="activity-icon-circle">
                                                    <i
                                                        class="bi {{ $translations[ 'actionIcons' ][ $activity->action_type ] ?? 'bi-clock-history' }} {{ $translations[ 'textColors' ][ $activity->action_type ] ?? 'text-primary' }}"></i>
                                                </div>
                                            </div>
                                            <!-- Conteúdo da atividade -->
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-semibold">
                                                        {{ $translations[ 'descriptionTranslation' ][ $activity->action_type ] ?? $activity->action_type }}
                                                    </span>
                                                    <span
                                                        class="badge rounded-pill activity-badge-{{ last( explode( '_', $activity->action_type ) ) }}">
                                                        {{ time_diff( $activity->created_at ) }}
                                                    </span>
                                                </div>

                                                <p class="mb-1 text-truncate small-text" style="max-width: 100%;"
                                                    data-bs-toggle="tooltip" title="{{ $activity->description }}">
                                                    Descrição: {{ $activity->description }}
                                                </p>

                                                <div class="d-flex align-items-center">
                                                    <small class="small-text"> por {{ $activity->user_name }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ( count( $activities ) >= 5 )
                        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                            <small class="small-text">Mostrando {{ count( $activities ) }} de
                                {{ $total_activities ?? count( $activities ) }}</small>
                            <a href="{{ route( 'activities.index' ) }}" class="btn btn-sm btn-outline-info">
                                <i class="bi bi-clock-history me-1"></i>Ver Histórico Completo
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Ações Rápidas -->
            <div class="col-md-12 mb-4">
                <h3>Ações Rápidas</h3>
                <div class="d-flex flex-wrap">
                    <a href="{{ route( 'provider.budgets.create' ) }}" class="btn btn-primary me-2 mb-2">Criar Novo
                        Orçamento</a>
                    <a href="{{ route( 'provider.reports.index' ) }}" class="btn btn-info me-2 mb-2">Ver Relatórios</a>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Orçamentos Recentes -->
            @php
                $statusClass = [
                    'NOVO'         => 'bg-primary',
                    'EM PROGRESSO' => 'bg-warning',
                    'COMPLETADO'   => 'bg-success',
                    'CANCELADO'    => 'bg-danger',
                ];
            @endphp
            <div class="col-md-6 mb-4">
                <div class="card hover-card mb-4">
                    <div class="card-header bg-success">
                        <h5 class="card-title mb-0">Orçamentos Recentes</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            @forelse ( $budgets as $budget )
                                <li class="list-group-item border-top">
                                    <div class="d-flex justify-content-between">
                                        <div class="d-flex flex-column flex-grow-1">
                                            {{-- Informações do Cliente --}}
                                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                                <div class="d-flex align-items-center flex-wrap gap-2">
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="{{ route( 'provider.budgets.show', $budget->code ) }}"
                                                            class="btn btn-warning">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    </div>
                                                    <span class="fw-bold">{{ $budget->first_name }}
                                                        {{ $budget->last_name }}</span>
                                                    <span class="badge bg-secondary">{{ $budget->code }}</span>
                                                    <span class="badge" style="background-color: {{ $budget->color }}">
                                                        <i class="bi {{ $budget->icon }}"></i>
                                                        {{ $budget->name }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between">
                                                {{-- Informações e Serviços - Lado Esquerdo --}}
                                                <div class="flex-grow-1">
                                                    <div class="mb-2">
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-file-text me-1"></i>
                                                            {{ $budget->description }}
                                                        </small>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-clock-history me-1"></i>
                                                            Atualizado em: {{ $budget->updated_at->format( 'd/m/Y' ) }}
                                                        </small>
                                                        <small class="text-muted d-block">
                                                            <i class="bi bi-calendar-event me-1"></i>
                                                            Vencimento em:
                                                            {{ \Carbon\Carbon::parse( $budget->due_date )->format( 'd/m/Y' ) }}
                                                        </small>
                                                    </div>

                                                    @if ( $budget->service_descriptions )
                                                        <div class="mt-2 budget-section">
                                                            <div>
                                                                <small class="text-muted d-block mb-1">
                                                                    <i class="bi bi-tools me-1"></i>Serviços
                                                                    ({{ $budget->service_count }})
                                                                </small>
                                                                <div>
                                                                    @php
                                                                        $services = explode( ',', $budget->service_descriptions );
                                                                    @endphp
                                                                    @foreach ( array_slice( $services, 0, 2 ) as $service )
                                                                        <small class="d-block text-secondary">
                                                                            <i class="bi bi-dot"></i>{{ $service }}
                                                                        </small>
                                                                    @endforeach
                                                                    @if ( count( $services ) > 2 )
                                                                        <small class="d-block text-muted fst-italic">
                                                                            <i class="bi bi-plus-circle"></i> mais
                                                                            {{ count( $services ) - 2 }}
                                                                            serviço(s)...
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                                <small class="text-success fw-bold d-block mt-1">
                                                                    Subtotal: R$
                                                                    {{ number_format( $budget->service_total, 2, ',', '.' ) }}
                                                                </small>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- Total - Lado Direito --}}
                                                <div class="ms-3 text-end">
                                                    <span class="badge bg-success rounded-pill px-3 py-1">
                                                        R$ {{ number_format( $budget->total, 2, ',', '.' ) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @empty
                                <li class="list-group-item text-center text-muted">
                                    Nenhum orçamento recente encontrado
                                </li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a href="{{ route( 'provider.budgets.index' ) }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-list-ul me-1"></i>Ver Todos
                        </a>
                        <small class="text-muted">
                            {{ count( $budgets ) }} orçamento(s) recente(s)
                        </small>
                    </div>
                </div>
            </div>

            <!-- Próximos Compromissos -->
            <div class="col-md-6 mb-4">
                <div class="card hover-card mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="card-title mb-0">Próximos Compromissos</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>15/06/2023</strong>
                                - Reunião com Cliente A
                            </li>
                            <li class="list-group-item">
                                <strong>18/06/2023</strong>
                                - Entrega do Projeto B
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer">
                        <a href="/agenda" class="btn btn-sm btn-outline-warning">Ver Agenda Completa</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            var planAlertModalElement = document.getElementById( 'planAlertModal' );
            if ( planAlertModalElement ) {
                var myModal = new bootstrap.Modal( planAlertModalElement );
                myModal.show();
            }
        } );
    </script>
@endpush
