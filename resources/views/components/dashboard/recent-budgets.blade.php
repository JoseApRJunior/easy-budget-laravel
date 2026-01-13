<div class="card border-0 shadow-sm mb-4" @style([
    "--text-primary: " . config('theme.colors.text', '#1e293b') . ";",
    "--text-secondary: " . config('theme.colors.secondary', '#94a3b8') . ";",
])>
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <h5 class="card-title mb-0 fw-bold" style="color: var(--text-primary);">
            <i class="bi bi-file-earmark-text me-2" style="color: {{ config('theme.colors.success') }};"></i>Orçamentos Recentes
        </h5>
    </div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            @forelse( $budgets as $budget )
                <li class="list-group-item px-0 border-light">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex flex-column flex-grow-1">

                            {{-- Cabeçalho com cliente e código --}}
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                        <x-ui.button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" />
                                        <span class="fw-bold" style="color: var(--text-primary);">{{ $budget->first_name }} {{ $budget->last_name }}</span>
                                    <span class="badge" style="background-color: {{ config('theme.colors.secondary') }}33; color: {{ config('theme.colors.text') }};">{{ $budget->code }}</span>
                                    <x-ui.status-badge :item="$budget" statusField="status" />
                                </div>
                            </div>

                            {{-- Informações principais --}}
                            <div class="d-flex justify-content-between">
                                <div class="flex-grow-1">
                                    <div class="mb-2 mt-2">
                                        <small class="d-block" style="color: var(--text-secondary);">
                                            <i class="bi bi-file-text me-1"></i>
                                            {{ $budget->description }}
                                        </small>
                                        <small class="d-block" style="color: var(--text-secondary);">
                                            <i class="bi bi-clock-history me-1"></i>
                                            Atualizado em: {{ $budget->updated_at->format( 'd/m/Y' ) }}
                                        </small>
                                        <small class="d-block" style="color: var(--text-secondary);">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            Vencimento em:
                                            {{ \Carbon\Carbon::parse( $budget->due_date )->format( 'd/m/Y' ) }}
                                        </small>
                                    </div>

                                    {{-- Serviços --}}
                                    @if( $budget->service_descriptions )
                                        <div class="mt-2 budget-section">
                                            <small class="d-block mb-1" style="color: var(--text-secondary);">
                                                <i class="bi bi-tools me-1"></i>Serviços ({{ $budget->service_count }})
                                            </small>
                                            <div>
                                                @php $services = explode( ',', $budget->service_descriptions ); @endphp
                                                @foreach( array_slice( $services, 0, 2 ) as $service )
                                                    <small class="d-block" style="color: var(--text-secondary); opacity: 0.8;">
                                                        <i class="bi bi-dot"></i>{{ $service }}
                                                    </small>
                                                @endforeach
                                                @if( count( $services ) > 2 )
                                                    <small class="d-block fst-italic" style="color: var(--text-secondary); opacity: 0.6;">
                                                        <i class="bi bi-plus-circle"></i> mais {{ count( $services ) - 2 }}
                                                        serviço(s)...
                                                    </small>
                                                @endif
                                            </div>
                                            <small class="fw-bold d-block mt-1" style="color: {{ config('theme.colors.success') }};">
                                                Subtotal: R$ {{ number_format( $budget->service_total, 2, ',', '.' ) }}
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Total --}}
                                <div class="ms-3 text-end">
                                    <span class="badge rounded-pill px-3 py-1" style="background-color: {{ config('theme.colors.success') }}; color: #fff;">
                                        R$ {{ number_format( $budget->total, 2, ',', '.' ) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @empty
                <li class="list-group-item text-center py-4" style="color: var(--text-secondary);">
                    <i class="bi bi-inbox mb-2 d-block" style="font-size: 2rem; opacity: 0.5;"></i> Nenhum orçamento recente encontrado
                </li>
            @endforelse
        </ul>
    </div>
</div>
