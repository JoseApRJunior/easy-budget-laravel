@extends('layouts.app')

@section('title', 'Gestão de Serviços')

@push('styles')
<style>
    /* Ocultar placeholder nativo do Chrome para inputs de data vazios */
    input[type="date"]::-webkit-datetime-edit-fields-wrapper {
        color: transparent;
    }
    input[type="date"]:focus::-webkit-datetime-edit-fields-wrapper,
    input[type="date"]:not(:placeholder-shown)::-webkit-datetime-edit-fields-wrapper,
    input[type="date"]:valid::-webkit-datetime-edit-fields-wrapper {
        color: inherit;
    }
</style>
@endpush

@section('content')
    <x-page-container>
        <x-page-header
            title="Lista de Serviços"
            icon="tools"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Serviços' => route('provider.services.dashboard'),
                'Lista' => '#'
            ]"
        >
            <p class="text-muted mb-0">Lista de todos os serviços registrados no sistema</p>
        </x-page-header>

        <x-grid-row>
            <x-grid-col size="col-12">
                <!-- Filtros de Busca -->
                <x-filter-form :route="route('provider.services.index')" id="filtersFormServices" :filters="$filters">
                            <x-filter-field
                                col="col-md-2"
                                name="search"
                                label="Buscar"
                                placeholder="Código ou Descrição"
                                :filters="$filters"
                            />

                            <x-filter-field
                                type="select"
                                col="col-md-2"
                                name="category_id"
                                label="Categoria"
                                :filters="$filters"
                            >
                                <option value="">Todas</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </x-filter-field>

                            <x-filter-field
                                type="select"
                                col="col-md-2"
                                name="status"
                                label="Status"
                                :filters="$filters"
                            >
                                <option value="">Todos</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}"
                                        {{ ($filters['status'] ?? '') == $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </x-filter-field>

                            <x-filter-field
                                type="date"
                                col="col-md-2"
                                name="start_date"
                                label="Início"
                                :filters="$filters"
                            />

                            <x-filter-field
                                type="date"
                                col="col-md-2"
                                name="end_date"
                                label="Fim"
                                :filters="$filters"
                            />
                        </x-filter-form>
                    </div>

                <!-- Card de Tabela -->
                <x-resource-list-card
                    title="Lista de Serviços"
                    mobileTitle="Serviços"
                    icon="list-ul"
                    :total="$services->total()"
                    class="border-0 shadow-sm"
                >
                    <x-slot name="headerActions">
                        <div class="col-12 col-lg-4 text-lg-end">
                            <x-table-header-actions
                                resource="services"
                                :filters="$filters"
                                createLabel="Novo"
                                :showExport="false"
                            />
                        </div>
                    </x-slot>

                    @if($services->isNotEmpty())
                        <x-slot name="desktop">
                            <x-resource-table>
                                <x-slot name="thead">
                                    <x-table-row>
                                        <x-table-cell header>Código</x-table-cell>
                                        <x-table-cell header>Cliente</x-table-cell>
                                        <x-table-cell header>Categoria</x-table-cell>
                                        <x-table-cell header>Prazo</x-table-cell>
                                        <x-table-cell header>Valor</x-table-cell>
                                        <x-table-cell header>Status</x-table-cell>
                                        <x-table-cell header align="center">Ações</x-table-cell>
                                    </x-table-row>
                                </x-slot>

                                @foreach($services as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                    @endphp
                                    <x-table-row>
                                        <x-table-cell class="fw-bold text-dark">{{ $service->code }}</x-table-cell>
                                        <x-table-cell>
                                            <x-table-cell-truncate :text="$customerName" />
                                        </x-table-cell>
                                        <x-table-cell>{{ $service->category->name ?? 'N/A' }}</x-table-cell>
                                        <x-table-cell class="text-muted small">
                                            {{ $service->due_date ? $service->due_date->format('d/m/Y') : '-' }}
                                        </x-table-cell>
                                        <x-table-cell class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</x-table-cell>
                                        <x-table-cell>
                                            <x-status-badge :item="$service" statusField="status" />
                                        </x-table-cell>
                                        <x-table-cell align="center">
                                            <x-action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                size="sm"
                                            />
                                        </x-table-cell>
                                    </x-table-row>
                                @endforeach
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach($services as $service)
                                @php
                                    $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                @endphp
                                <x-resource-mobile-item
                                    icon="tools"
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <x-resource-mobile-header
                                        :title="$service->code"
                                        :subtitle="$service->due_date ? $service->due_date->format('d/m/Y') : '-'"
                                    />

                                    <x-resource-mobile-field
                                        label="Cliente"
                                        :value="$customerName"
                                    />

                                    <x-grid-row g="2">
                                        <x-resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($service->total)"
                                            col="col-6"
                                        />
                                        <x-resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-status-badge :item="$service" statusField="status" />
                                        </x-resource-mobile-field>
                                    </x-grid-row>
                                </x-resource-mobile-item>
                            @endforeach
                        </x-slot>
                    @else
                        <x-empty-state
                            title="Nenhum serviço encontrado"
                            description="Não encontramos serviços com os filtros aplicados."
                            icon="gear"
                        />
                    @endif

                    @if($services->hasPages())
                        <x-slot name="footer">
                            {{ $services->appends(request()->query())->links() }}
                        </x-slot>
                    @endif
                </x-resource-list-card>
            </x-grid-col>
        </x-grid-row>
    </x-page-container>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormServices');

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;
                const start = new Date(startDate.value);
                const end = new Date(endDate.value);

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

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateDates()) {
                        e.preventDefault();
                        return;
                    }
                });
            }
        });
    </script>
@endpush
@endsection
