@props( [ 'events' => [] ] )

<div class="card hover-card mb-4">
    <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-calendar-event me-2"></i>Próximos Compromissos
        </h5>
        <span class="badge bg-dark text-white rounded-pill px-3">{{ count( $events ) }}</span>
    </div>

    <div class="card-body p-0">
        @if( empty( $events ) )
            <div class="text-center py-4">
                <i class="bi bi-calendar-x text-muted" style="font-size: var(--icon-size-xl);"></i>
                <p class="text-muted mt-3 mb-0">Nenhum compromisso agendado</p>
                <small class="small-text">Adicione compromissos para acompanhar seus prazos</small>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach( $events as $event )
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">{{ $event->service->name ?? 'Serviço' }}</h6>
                            <small class="text-muted">
                                <i class="bi bi-clock me-1"></i>
                                {{ \Carbon\Carbon::parse( $event->start_date_time )->format( 'd/m/Y H:i' ) }}
                            </small>
                        </div>
                        <span class="badge bg-primary">Agendado</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if( count( $events ) >= 5 )
        <div class="card-footer bg-light d-flex justify-content-between align-items-center">
            <small class="small-text">Mostrando {{ count( $events ) }} de {{ $total ?? count( $events ) }}</small>
            <a href="{{ route( 'provider.schedules.index' ) }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-calendar-week me-1"></i>Ver Todos
            </a>
        </div>
    @endif
</div>
