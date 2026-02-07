<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Data/Hora</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Saldo</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movements as $movement)
                @php
                    $typeClass = '';
                    $typeIcon = '';
                    switch($movement->type) {
                        case 'entry':
                            $typeClass = 'success';
                            $typeIcon = 'fa-arrow-down';
                            break;
                        case 'exit':
                            $typeClass = 'danger';
                            $typeIcon = 'fa-arrow-up';
                            break;
                        case 'adjustment':
                            $typeClass = 'warning';
                            $typeIcon = 'fa-sliders-h';
                            break;
                        case 'service':
                            $typeClass = 'info';
                            $typeIcon = 'fa-cogs';
                            break;
                    }
                @endphp
                <tr>
                    <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-center">
                        <span class="badge badge-{{ $typeClass }}">
                            <i class="fas {{ $typeIcon }}"></i>
                            {{ ucfirst($movement->type) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="{{ $movement->type == 'exit' ? 'text-danger' : 'text-success' }}">
                            {{ $movement->type == 'exit' ? '-' : '+' }}{{ $movement->quantity }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-secondary">{{ $movement->current_quantity }}</span>
                    </td>
                    <td>
                        <small>{{ $movement->reason }}</small>
                        @if($movement->reference_type && $movement->reference_id)
                            <br><small class="text-muted">
                                Ref: {{ ucfirst($movement->reference_type) }} #{{ $movement->reference_id }}
                            </small>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">
                        Nenhuma movimentação encontrada
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($movements->count() > 0)
    <div class="d-flex justify-content-center mt-3">
        {{ $movements->links() }}
    </div>
@endif