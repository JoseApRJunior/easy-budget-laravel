<x-resource.resource-list-card
    title="Orçamentos Recentes"
    icon="file-earmark-text"
    :total="$budgets->count()"
    variant="success"
>
    @if($budgets->isNotEmpty())
        <x-slot:desktop>
            <x-resource.resource-table>
                <x-slot:thead>
                    <x-resource.table-row>
                        <x-resource.table-cell header>Código</x-resource.table-cell>
                        <x-resource.table-cell header>Cliente</x-resource.table-cell>
                        <x-resource.table-cell header>Valor</x-resource.table-cell>
                        <x-resource.table-cell header>Status</x-resource.table-cell>
                        <x-resource.table-cell header>Data</x-resource.table-cell>
                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                    </x-resource.table-row>
                </x-slot:thead>

                @foreach ($budgets as $budget)
                    <x-resource.table-row>
                        <x-resource.table-cell class="fw-bold text-dark">{{ $budget->code }}</x-resource.table-cell>
                        <x-resource.table-cell>
                            <x-resource.table-cell-truncate :text="$budget->first_name . ' ' . $budget->last_name" />
                        </x-resource.table-cell>
                        <x-resource.table-cell class="fw-bold text-dark">
                            R$ {{ number_format($budget->total, 2, ',', '.') }}
                        </x-resource.table-cell>
                        <x-resource.table-cell>
                            <x-ui.status-badge :item="$budget" statusField="status" />
                        </x-resource.table-cell>
                        <x-resource.table-cell class="text-muted small">
                            {{ $budget->updated_at->format('d/m/Y') }}
                        </x-resource.table-cell>
                        <x-resource.table-cell align="center">
                            <x-resource.action-buttons
                                :item="$budget"
                                resource="budgets"
                                identifier="code"
                                :can-delete="false"
                                size="sm"
                            />
                        </x-resource.table-cell>
                    </x-resource.table-row>
                @endforeach
            </x-resource.resource-table>
        </x-slot:desktop>

        <x-slot:mobile>
            @foreach ($budgets as $budget)
                <x-resource.resource-mobile-item
                    icon="file-earmark-text"
                    :href="route('provider.budgets.show', $budget->code)"
                >
                    <x-resource.resource-mobile-header
                        :title="$budget->code"
                        :subtitle="$budget->updated_at->format('d/m/Y')"
                    />
                    <x-resource.resource-mobile-field
                        label="Cliente"
                        :value="$budget->first_name . ' ' . $budget->last_name"
                    />
                    <x-layout.grid-row g="2">
                        <x-resource.resource-mobile-field
                            label="Valor"
                            :value="'R$ ' . number_format($budget->total, 2, ',', '.')"
                            col="col-6"
                        />
                        <x-resource.resource-mobile-field
                            label="Status"
                            col="col-6"
                            align="end"
                        >
                            <x-ui.status-badge :item="$budget" statusField="status" />
                        </x-resource.resource-mobile-field>
                    </x-layout.grid-row>
                </x-resource.resource-mobile-item>
            @endforeach
        </x-slot:mobile>
    @else
        <x-resource.empty-state
            title="Nenhum orçamento recente"
            description="Crie novos orçamentos para acompanhar aqui."
            icon="file-earmark-text"
        />
    @endif
</x-resource.resource-list-card>
