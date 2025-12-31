@props( [ 'budgets' => [] ] )

<div class="card hover-card mb-4">
    <div class="card-header bg-success">
        <h5 class="card-title mb-0">Orçamentos Recentes</h5>
    </div>
    <div class="card-body">
        <ul class="list-group">
            @forelse( $budgets as $budget )
                <li class="list-group-item border-top">
                    <div class="d-flex justify-content-between">
                        <div class="d-flex flex-column flex-grow-1">

                            {{-- Cabeçalho com cliente e código --}}
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
                                <div class="d-flex align-items-center flex-wrap gap-2">
                                        <x-button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" />
                                        <span class="fw-bold">{{ $budget->first_name }} {{ $budget->last_name }}</span>
                                    <span class="badge bg-secondary">{{ $budget->code }}</span>
                                    <x-status-badge :item="$budget" statusField="status" />
                                </div>
                            </div>

                            {{-- Informações principais --}}
                            <div class="d-flex justify-content-between">
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

                                    {{-- Serviços --}}
                                    @if( $budget->service_descriptions )
                                        <div class="mt-2 budget-section">
                                            <small class="text-muted d-block mb-1">
                                                <i class="bi bi-tools me-1"></i>Serviços ({{ $budget->service_count }})
                                            </small>
                                            <div>
                                                @php $services = explode( ',', $budget->service_descriptions ); @endphp
                                                @foreach( array_slice( $services, 0, 2 ) as $service )
                                                    <small class="d-block text-secondary">
                                                        <i class="bi bi-dot"></i>{{ $service }}
                                                    </small>
                                                @endforeach
                                                @if( count( $services ) > 2 )
                                                    <small class="d-block text-muted fst-italic">
                                                        <i class="bi bi-plus-circle"></i> mais {{ count( $services ) - 2 }}
                                                        serviço(s)...
                                                    </small>
                                                @endif
                                            </div>
                                            <small class="text-success fw-bold d-block mt-1">
                                                Subtotal: R$ {{ number_format( $budget->service_total, 2, ',', '.' ) }}
                                            </small>
                                        </div>
                                    @endif
                                </div>

                                {{-- Total --}}
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
                    <i class="bi bi-inbox me-2"></i> Nenhum orçamento recente encontrado
                </li>
            @endforelse
        </ul>
    </div>
</div>
