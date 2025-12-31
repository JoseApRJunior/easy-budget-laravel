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
    <div class="container-fluid py-1">
        <x-page-header
            title="Serviços"
            icon="gear"
            :breadcrumb-items="[
                'Serviços' => '#'
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
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 py-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap fw-bold text-dark">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Serviços</span>
                                    <span class="d-sm-none">Serviços</span>
                                </span>
                                <span class="text-muted small fw-normal">
                            ({{ count($services) }})
                        </span>
                            </h5>
                            <x-table-header-actions
                                resource="services"
                                :filters="$filters"
                                createLabel="Novo"
                                :showExport="false"
                            />
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Categoria</th>
                                        <th>Prazo</th>
                                        <th>Valor</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($services as $service)
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
                                            <td class="fw-bold text-dark">R$ {{ number_format($service->total, 2, ',', '.') }}</td>
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
                                    @empty
                                        <tr>
                                            <td colspan="7">
                                                <x-empty-state
                                                    title="Nenhum serviço encontrado"
                                                    description="Não encontramos serviços com os filtros aplicados."
                                                    icon="gear"
                                                />
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($services->hasPages())
                            <div class="card-footer bg-transparent border-0 py-3">
                                {{ $services->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

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
