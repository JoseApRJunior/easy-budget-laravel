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

        <div class="row">
            <div class="col-12">
                <!-- Filtros de Busca -->
                    <div class="card-body">
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
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Categoria</th>
                                        <th>Prazo</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </x-slot>

                                @foreach($services as $service)
                                    <tr>
                                        <td class="fw-bold text-dark">{{ $service->code }}</td>
                                        <td>
                                            @php
                                                $customerName = 'N/A';
                                                if ($service->budget && $service->budget->customer && $service->budget->customer->commonData) {
                                                    $commonData = $service->budget->customer->commonData;
                                                    $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                                                }
                                            @endphp
                                            {{ $customerName }}
                                        </td>
                                        <td>{{ $service->category->name ?? 'N/A' }}</td>
                                        <td class="text-muted small">
                                            {{ $service->due_date ? $service->due_date->format('d/m/Y') : '-' }}
                                        </td>
                                        <td class="fw-bold text-dark">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</td>
                                        <td>
                                            <x-status-badge :item="$service" statusField="status" />
                                        </td>
                                        <td class="text-center">
                                            <x-action-buttons
                                                :item="$service"
                                                resource="services"
                                                identifier="code"
                                                size="sm"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach($services as $service)
                                @php
                                    $customerName = 'N/A';
                                    if ($service->budget && $service->budget->customer && $service->budget->customer->commonData) {
                                        $commonData = $service->budget->customer->commonData;
                                        $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                                    }
                                @endphp
                                <x-resource-mobile-item
                                    icon="tools"
                                    :href="route('provider.services.show', $service->code)"
                                >
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark">{{ $service->code }}</span>
                                        <span class="text-muted small">{{ $service->due_date ? $service->due_date->format('d/m/Y') : '-' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Cliente</small>
                                        <div class="text-dark fw-semibold text-truncate">{{ $customerName }}</div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Valor</small>
                                            <span class="fw-bold text-primary">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                        </div>
                                        <div class="col-6 text-end">
                                            <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Status</small>
                                            <x-status-badge :item="$service" statusField="status" />
                                        </div>
                                    </div>
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
            </div>
        </div>
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
