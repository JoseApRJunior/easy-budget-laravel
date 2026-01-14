<div class="card border-0 shadow-sm mb-4" @style([
    "--text-primary: " . config('theme.colors.text', '#1e293b') . ";",
    "--text-secondary: " . config('theme.colors.secondary', '#94a3b8') . ";",
])>
    <div class="card-header border-1 py-3 bg-transparent d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <i class="bi bi-calendar-event me-2" style="color: {{ config('theme.colors.warning') }};"></i>
            <h5 class="card-title mb-0 fw-bold" style="color: var(--text-primary);">Próximos Compromissos</h5>
        </div>
        <span class="badge rounded-pill px-3" style="background-color: {{ config('theme.colors.warning') }}1a; color: {{ config('theme.colors.warning') }};">{{ count($events) }}</span>
    </div>

    <div class="card-body p-0">
        @if (empty($events))
            <div class="text-center py-4">
                <i class="bi bi-calendar-x" style="font-size: var(--icon-size-xl); color: var(--text-secondary); opacity: 0.5;"></i>
                <p class="mt-3 mb-0" style="color: var(--text-secondary);">Nenhum compromisso agendado</p>
                <small class="small-text" style="color: var(--text-secondary); opacity: 0.8;">Adicione compromissos para acompanhar seus prazos</small>
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($events as $event)
                    <li class="list-group-item d-flex justify-content-between align-items-center border-light px-4">
                        <div>
                            <h6 class="mb-1 fw-semibold" style="color: var(--text-primary);">{{ $event->service->name ?? 'Serviço' }}</h6>
                            <small style="color: var(--text-secondary);">
                                <i class="bi bi-clock me-1"></i>
                                {{ \Carbon\Carbon::parse($event->start_date_time)->format('d/m/Y H:i') }}
                            </small>
                        </div>
                        <span class="badge" style="background-color: {{ config('theme.colors.primary') }}1a; color: {{ config('theme.colors.primary') }};">Agendado</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    @if (count($events) >= 5)
        <div class="card-footer bg-white border-top-0 pb-4 d-flex justify-content-between align-items-center">
            <small class="small-text" style="color: var(--text-secondary);">Mostrando {{ count($events) }} de {{ $total ?? count($events) }}</small>
            <x-ui.button type="link" :href="route('provider.schedules.index')" variant="warning" size="sm" icon="calendar-week" label="Ver Todos" />
        </div>
    @endif
</div>
