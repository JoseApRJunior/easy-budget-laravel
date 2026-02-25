<div class="card border-0 shadow-sm mb-4">
    <div class="card-header border-1 py-3 bg-transparent d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar-event me-2 text-primary"></i>
            <h5 class="card-title mb-0 fw-bold text-dark">Próximos Compromissos</h5>
        </div>
        <span class="badge rounded-pill px-3 bg-primary bg-opacity-10 text-primary">{{ count($events) }}</span>
    </div>

    <div class="card-body p-0">
        @if (empty($events))
            <div class="text-center py-4">
                <i class="bi bi-calendar-x fs-1 text-muted opacity-50"></i>
                <p class="mt-3 mb-0 text-muted">Nenhum compromisso agendado</p>
                <small class="text-muted opacity-75">Adicione compromissos para acompanhar seus prazos</small>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($events as $event)
                    @php
                        $customer = $event->service->customer ?? null;
                        $commonData = $customer?->commonData;
                        $customerName = $commonData ? $commonData->full_name : 'Cliente não identificado';
                        $serviceTitle = $event->service->description ? \Illuminate\Support\Str::limit($event->service->description, 40) : 'Serviço #' . $event->service->code;
                    @endphp
                    <li class="list-group-item border-light px-4 py-3">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="flex-grow-1">
                                <a href="{{ route('provider.services.show', $event->service->code) }}" class="text-decoration-none">
                                    <h6 class="mb-0 fw-bold text-primary">{{ $customerName }}</h6>
                                    <p class="mb-1 text-dark small">{{ $serviceTitle }}</p>
                                </a>
                            </div>
                            <span class="badge bg-{{ $event->status->color() ?? 'primary' }} bg-opacity-10 text-{{ $event->status->color() ?? 'primary' }} small">
                                {{ $event->status->label() }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center text-muted small">
                            <i class="bi bi-calendar3 me-2"></i>
                            <span class="me-3">{{ \Carbon\Carbon::parse($event->start_date_time)->format('d/m/Y') }}</span>
                            <i class="bi bi-clock me-2"></i>
                            <span>{{ \Carbon\Carbon::parse($event->start_date_time)->format('H:i') }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if (count($events) >= 5)
        <div class="card-footer bg-transparent border-top-0 pb-4 d-flex justify-content-between align-items-center">
            <small class="text-muted">Mostrando {{ count($events) }} de {{ $total ?? count($events) }}</small>
            <x-ui.button type="link" :href="route('provider.schedules.index')" variant="primary" size="sm" icon="calendar-week" label="Ver Todos" />
        </div>
    @endif
</div>
