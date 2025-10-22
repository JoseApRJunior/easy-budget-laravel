@props( [ 'activities' => [], 'translations' => [], 'total' => null ] )

<div class="card border-0 shadow-sm hover-card">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-activity me-2"></i>Atividades Recentes
        </h5>
        <span class="badge bg-white text-info rounded-pill px-3">{{ count( $activities ) }}</span>
    </div>

    <div class="card-body p-0">
        @if( empty( $activities ) )
            <div class="text-center py-4">
                <i class="bi bi-calendar-x text-muted" style="font-size: var(--icon-size-xl);"></i>
                <p class="text-muted mt-3 mb-0">Nenhuma atividade recente encontrada</p>
                <small class="small-text">As atividades aparecerão aqui conforme você utiliza o sistema</small>
            </div>
        @else
            <div class="activity-timeline" style="max-height: 400px; overflow-y: auto;">
                @foreach( $activities as $activity )
                    <div class="activity-item p-3 @if( !$loop->last ) border-bottom @endif">
                        <div class="d-flex">
                            <!-- Ícone -->
                            <div class="me-3">
                                <div class="activity-icon-circle">
                                    <i
                                        class="bi {{ $translations[ 'actionIcons' ][ $activity->action_type ] ?? 'bi-clock-history' }}
                                                              {{ $translations[ 'textColors' ][ $activity->action_type ] ?? 'text-primary' }}"></i>
                                </div>
                            </div>

                            <!-- Conteúdo -->
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

                                <p class="mb-1 text-truncate small-text" style="max-width: 100%;" data-bs-toggle="tooltip"
                                    title="{{ $activity->description }}">
                                    Descrição: {{ $activity->description }}
                                </p>

                                <div class="d-flex align-items-center">
                                    <small class="small-text">por {{ $activity->user_name }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if( count( $activities ) >= 5 )
        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <small class="small-text">
                Mostrando {{ count( $activities ) }} de {{ $total ?? count( $activities ) }}
            </small>
            <a href="{{ route( 'activities.index' ) }}" class="btn btn-sm btn-outline-info">
                <i class="bi bi-clock-history me-1"></i>Ver Histórico Completo
            </a>
        </div>
    @endif
</div>
