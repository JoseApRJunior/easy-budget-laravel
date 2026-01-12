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
    <x-layout.page-container>
        <x-layout.page-header
            title="Lista de Serviços"
            icon="tools"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Serviços' => route('provider.services.dashboard'),
                'Lista' => '#'
            ]"
        >
            <p class="text-muted mb-0">Lista de todos os serviços registrados no sistema</p>
        </x-layout.page-header>

        <x-layout.grid-row>
            <x-layout.grid-col size="col-12">
                <!-- Filtros de Busca -->
                <x-form.filter-form :route="route('provider.services.index')" id="filtersFormServices" :filters="$filters">
                            <x-form.filter-field
                                col="col-md-2"
                                name="search"
                                label="Buscar"
                                placeholder="Código ou Descrição"
                                :filters="$filters"
                            />

                            <x-form.filter-field
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
                            </x-form.filter-field>

                            <x-form.filter-field
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
                            </x-form.filter-field>

                            <x-form.filter-field
                                type="date"
                                col="col-md-2"
                                name="start_date"
                                label="Início"
                                :filters="$filters"
                            />

                            <x-form.filter-field
                                type="date"
                                col="col-md-2"
                                name="end_date"
                                label="Fim"
                                :filters="$filters"
                            />
                </x-form.filter-form>

                <!-- Card de Tabela -->
                <x-resource.resource-list-card
                    title="Lista de Serviços"
                    mobileTitle="Serviços"
                    icon="list-ul"
                    :total="$services->total()"
                    class="border-0 shadow-sm"
                >
                    <x-slot name="headerActions">
                        <div class="col-12 col-lg-4 text-lg-end">
                            <x-resource.table-header-actions
                                resource="services"
                                :filters="$filters"
                                createLabel="Novo"
                                :showExport="false"
                            />
                        </div>
                    </x-slot>

                    @if($services->isNotEmpty())
                        <x-slot name="desktop">
                            <x-resource.resource-table>
                                <x-slot name="thead">
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Código</x-resource.table-cell>
                                        <x-resource.table-cell header>Cliente</x-resource.table-cell>
                                        <x-resource.table-cell header>Categoria</x-resource.table-cell>
                                        <x-resource.table-cell header>Prazo</x-resource.table-cell>
                                        <x-resource.table-cell header>Valor</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot>

                                @foreach($services as $service)
                                    @php
                                        $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                    @endphp
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ $service->code }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.table-cell-truncate :text="$customerName" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>{{ $service->category->name ?? 'N/A' }}</x-resource.table-cell>
                                        <x-resource.table-cell class="text-muted small">
                                            {{ $service->due_date ? $service->due_date->format('d/m/Y') : '-' }}
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.status-badge :item="$service" statusField="status" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center">
                                            <x-resource.action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                size="sm"
                                            />
                                        </x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach($services as $service)
                                @php
                                    $customerName = $service->budget->customer->commonData?->full_name ?? 'N/A';
                                @endphp
                                <x-resource.resource-mobile-item
                                    icon="tools"
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <x-resource.resource-mobile-header
                                        :title="$service->code"
                                        :subtitle="$service->due_date ? $service->due_date->format('d/m/Y') : '-'"
                                    />

                                    <x-resource.resource-mobile-field
                                        label="Cliente"
                                        :value="$customerName"
                                    />

                                    <x-layout.grid-row g="2">
                                        <x-resource.resource-mobile-field
                                            label="Valor"
                                            :value="\App\Helpers\CurrencyHelper::format($service->total)"
                                            col="col-6"
                                        />
                                        <x-resource.resource-mobile-field
                                            label="Status"
                                            col="col-6"
                                            align="end"
                                        >
                                            <x-ui.status-badge :item="$service" statusField="status" />
                                        </x-resource.resource-mobile-field>
                                    </x-layout.grid-row>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot>
                    @else
                        <x-resource.empty-state
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
                </x-resource.resource-list-card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>

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
