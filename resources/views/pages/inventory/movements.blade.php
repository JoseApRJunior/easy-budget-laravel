@extends('layouts.app')

@section('title', 'Movimentações de Estoque')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Movimentações de Estoque"
        icon="arrow-left-right"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            'Movimentações' => '#'
        ]">
        <p class="text-muted mb-0">Histórico completo de movimentações de estoque</p>
    </x-layout.page-header>

    <!-- Filtros de Busca -->
    <x-form.filter-form
        id="filtersFormMovements"
        :route="route('provider.inventory.movements')"
        :filters="$filters ?? request()->all()"
    >
        @if(request('product_id'))
            <input type="hidden" name="product_id" value="{{ request('product_id') }}">
        @endif
        @if(request('sku'))
            <input type="hidden" name="sku" value="{{ request('sku') }}">
        @endif

        <x-form.filter-field
            col="col-md-3"
            name="search"
            label="Buscar Produto"
            placeholder="Nome ou SKU"
            :filters="$filters ?? request()->all()"
            :value="request('search') ?? request('sku')"
        />

        <x-form.filter-field
            type="select"
            col="col-md-3"
            name="type"
            label="Tipo"
            :filters="$filters ?? request()->all()"
            :options="[
                '' => 'Todos os Tipos',
                'entry' => 'Entrada',
                'exit' => 'Saída',
                'adjustment' => 'Ajuste',
                'reservation' => 'Reserva',
                'cancellation' => 'Cancelamento'
            ]"
        />

        <x-form.filter-field
            type="date"
            col="col-md-3"
            name="start_date"
            label="Data Inicial"
            :filters="$filters ?? request()->all()"
        />

        <x-form.filter-field
            type="date"
            col="col-md-3"
            name="end_date"
            label="Data Final"
            :filters="$filters ?? request()->all()"
        />
    </x-form.filter-form>

    <x-resource.resource-list-card
        title="Registros de Movimentação"
        mobileTitle="Movimentações"
        icon="arrow-left-right"
        :total="$movements->total()"
    >
        <x-slot:headerActions>
            <x-resource.table-header-actions
                resource="inventory.movements"
                exportRoute="provider.inventory.export-movements"
                :filters="request()->all()"
                :showCreate="false"
            />
        </x-slot:headerActions>

        @if($movements->count() > 0)
            <x-slot:desktop>
                <x-resource.resource-table>
                    <x-slot:thead>
                        <tr>
                            <th width="60"><i class="bi bi-clock" aria-hidden="true"></i></th>
                            <th>Data/Hora</th>
                            <th>Produto / SKU</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Quantidade</th>
                            <th class="text-center">Saldo Atual</th>
                            <th>Motivo</th>
                            <th width="150" class="text-center">Ações</th>
                        </tr>
                    </x-slot:thead>

                    <x-slot:tbody>
                        @foreach($movements as $movement)
                            <tr>
                                <td>
                                    <div class="item-icon">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </div>
                                </td>
                                <td>
                                    <x-resource.table-cell-datetime :datetime="$movement->created_at" />
                                </td>
                                <td>
                                    <x-resource.product-info :name="$movement->product->name" :sku="$movement->product->sku" />
                                </td>
                                <td class="text-center">
                                    <x-ui.movement-type-badge :type="$movement->type" />
                                </td>
                                <td class="text-center">
                                    <x-resource.movement-quantity :type="$movement->type" :quantity="$movement->quantity" />
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    {{ \App\Helpers\CurrencyHelper::format($movement->new_quantity ?? 0, 0, false) }}
                                </td>
                                <td>
                                    <small class="text-muted" title="{{ $movement->reason }}">
                                        {{ Str::limit($movement->reason, 30) }}
                                    </small>
                                </td>
                                <x-resource.table-actions>
                                    <x-ui.button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="info" icon="eye" title="Ver Detalhes" feature="inventory" />
                                    <x-ui.button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="secondary" icon="box" title="Ver Inventário" feature="inventory" />
                                </x-resource.table-actions>
                            </tr>
                        @endforeach
                    </x-slot:tbody>
                </x-resource.resource-table>
            </x-slot:desktop>

            <x-slot:mobile>
                    @foreach($movements as $movement)
                        <x-resource.resource-mobile-item icon="arrow-left-right">
                            <x-resource.resource-info
                                :title="$movement->product->name"
                                :subtitle="$movement->product->sku"
                                icon="box"
                                class="mb-2"
                            />

                            <x-slot:description>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <x-ui.movement-type-badge :type="$movement->type" />
                                    <x-resource.movement-quantity :type="$movement->type" :quantity="$movement->quantity" />
                                </div>
                            </x-slot:description>

                            <x-slot:footer>
                                <x-resource.table-cell-datetime :datetime="$movement->created_at" :stack="false" />
                            </x-slot:footer>

                            <x-slot:actions>
                                <x-resource.table-actions mobile>
                                    <x-ui.button type="link" :href="route('provider.inventory.movements.show', $movement->id)" variant="info" size="sm" icon="eye" feature="inventory" />
                                    <x-ui.button type="link" :href="route('provider.inventory.show', $movement->product->sku)" variant="secondary" size="sm" icon="box" feature="inventory" />
                                </x-resource.table-actions>
                            </x-slot:actions>
                        </x-resource.resource-mobile-item>
                    @endforeach
                </x-slot:mobile>
        @else
            <x-resource.empty-state
                resource="movimentações"
                :isTrashView="false"
                message="Nenhuma movimentação de estoque encontrada para os filtros aplicados."
            />
            @endif

        <x-slot:footer>
            @if ($movements instanceof \Illuminate\Pagination\LengthAwarePaginator && $movements->hasPages())
                @include('partials.components.paginator', [
                    'p' => $movements->appends(
                        collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()
                    ),
                    'show_info' => true,
                ])
            @endif
        </x-slot:footer>
    </x-resource.resource-list-card>
</x-layout.page-container>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const form = document.getElementById('filtersFormMovements');

    if (!form || !startDate || !endDate) return;

    const parseDate = (str) => {
        if (!str) return null;
        // Suporta YYYY-MM-DD (input date) ou DD/MM/AAAA (antigo)
        if (str.includes('-')) {
            const d = new Date(str);
            return isNaN(d.getTime()) ? null : d;
        }
        const parts = str.split('/');
        if (parts.length === 3) {
            const d = new Date(parts[2], parts[1] - 1, parts[0]);
            return isNaN(d.getTime()) ? null : d;
        }
        return null;
    };

    const validateDates = () => {
        if (!startDate.value || !endDate.value) return true;

        const start = parseDate(startDate.value);
        const end = parseDate(endDate.value);

        if (start && end && start > end) {
            const message = 'A data inicial não pode ser maior que a data final.';
            if (window.easyAlert) {
                window.easyAlert.warning(message);
            } else {
                alert(message);
            }
            return false;
        }
        return true;
    };

    form.addEventListener('submit', function(e) {
        if (!validateDates()) {
            e.preventDefault();
            return;
        }

        if (startDate.value && !endDate.value) {
            e.preventDefault();
            const message = 'Para filtrar por período, informe as datas inicial e final.';
            if (window.easyAlert) {
                window.easyAlert.error(message);
            } else {
                alert(message);
            }
            endDate.focus();
        } else if (!startDate.value && endDate.value) {
            e.preventDefault();
            const message = 'Para filtrar por período, informe as datas inicial e final.';
            if (window.easyAlert) {
                window.easyAlert.error(message);
            } else {
                alert(message);
            }
            startDate.focus();
        }
    });
});
</script>
@endpush
