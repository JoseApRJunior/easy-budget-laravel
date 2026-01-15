@extends('layouts.app')

@section('title', 'Orçamentos')

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
<div class="container-fluid py-4">
    <x-layout.page-header
        title="Lista de Orçamentos"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            'Lista' => '#'
        ]">
        <p class="text-muted mb-0 small">Consulte e gerencie todos os orçamentos registrados no sistema.</p>
    </x-layout.page-header>

    <!-- Card de Filtros -->
    <x-form.filter-form
        id="budgetFilterForm"
        :route="route('provider.budgets.index')"
        :filters="$filters"
    >
        <x-form.filter-field
            col="col-md-2"
            name="code"
            label="Código"
            placeholder="Ex: ORC-001"
            :filters="$filters"
        />

        <x-form.filter-field
            type="date"
            col="col-md-2"
            name="start_date"
            label="Cadastro Inicial"
            :filters="$filters"
        />

        <x-form.filter-field
            type="date"
            col="col-md-2"
            name="end_date"
            label="Cadastro Final"
            :filters="$filters"
        />

        <x-form.filter-field
            col="col-md-3"
            name="customer_name"
            label="Cliente"
            placeholder="Nome do cliente..."
            :filters="$filters"
        />

        <x-form.filter-field
            type="select"
            col="col-md-2"
            name="status"
            label="Status"
            :filters="$filters"
            :options="['all' => 'Todos'] + collect(\App\Enums\BudgetStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->getDescription()])->toArray()"
        />

        <x-form.filter-field
            type="select"
            col="col-md-1"
            name="per_page"
            label="Itens"
            :filters="$filters"
            :options="[10 => '10', 20 => '20', 50 => '50']"
        />
    </x-form.filter-form>

    <!-- Card de Tabela -->
    <x-resource.resource-list-card
        title="Lista de Orçamentos"
        mobileTitle="Orçamentos"
        icon="file-earmark-text"
        :total="$budgets->total()"
        padding="p-0"
    >
        <x-slot:headerActions>
            <x-resource.table-header-actions
                resource="budgets"
                :filters="$filters"
                createLabel="Novo"
                :showExport="false"
            />
        </x-slot:headerActions>

        <x-slot:desktop>
            <x-resource.resource-table>
                <x-slot:thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Data</th>
                        <th>Vencimento</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </x-slot:thead>

                @forelse($budgets as $budget)
                    <tr>
                        <td class="fw-bold text-dark">{{ $budget->code }}</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="text-truncate" style="max-width: 200px;">
                                    @php
                                        $customerName = 'Cliente não informado';
                                        if ($budget->customer && $budget->customer->commonData) {
                                            $commonData = $budget->customer->commonData;
                                            $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                                        }
                                    @endphp
                                    {{ $customerName ?: 'Cliente não informado' }}
                                </div>
                            </div>
                        </td>
                        <td class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</td>
                        <td class="text-muted small">
                            @if($budget->due_date)
                                <span class="{{ $budget->due_date->isPast() ? 'text-danger fw-bold' : '' }}">
                                    {{ $budget->due_date->format('d/m/Y') }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="fw-bold text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</td>
                        <td>
                            <x-ui.status-badge :item="$budget" statusField="status" />
                        </td>
                        <td class="text-center">
                            <x-resource.action-buttons
                                :item="$budget"
                                resource="budgets"
                                identifier="code"
                                size="sm"
                            />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <x-resource.empty-state
                                title="Nenhum orçamento encontrado"
                                description="Não encontramos orçamentos com os filtros aplicados."
                                icon="file-earmark-text"
                            />
                        </td>
                    </tr>
                @endforelse
            </x-resource.resource-table>
        </x-slot:desktop>

        <x-slot:mobile>
            @forelse($budgets as $budget)
                <x-resource.resource-mobile-item
                    icon="file-earmark-text"
                    :href="route('provider.budgets.show', $budget->code)"
                >
                    <x-resource.resource-mobile-header
                        :title="$budget->code"
                        :subtitle="optional($budget->created_at)->format('d/m/Y')"
                    />

                    <x-slot:description>
                        @php
                            $customerName = 'Não informado';
                            if ($budget->customer && $budget->customer->commonData) {
                                $commonData = $budget->customer->commonData;
                                $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                            }
                        @endphp
                        <x-resource.resource-mobile-field
                            label="Cliente"
                            :value="$customerName ?: 'Não informado'"
                        />
                    </x-slot:description>

                    <x-slot:footer>
                        <div class="row g-2 w-100">
                            <x-resource.resource-mobile-field
                                col="col-6"
                                label="Valor Total"
                            >
                                <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</span>
                            </x-resource.resource-mobile-field>

                            <x-resource.resource-mobile-field
                                col="col-6"
                                align="end"
                                label="Status"
                            >
                                <x-ui.status-badge :item="$budget" />
                            </x-resource.resource-mobile-field>
                        </div>
                    </x-slot:footer>

                    <x-slot:actions>
                        <x-resource.action-buttons
                            :item="$budget"
                            resource="budgets"
                            identifier="code"
                            size="sm"
                        />
                    </x-slot:actions>
                </x-resource.resource-mobile-item>
            @empty
                <div class="py-5">
                    <x-resource.empty-state
                        title="Nenhum orçamento encontrado"
                        description="Não encontramos orçamentos com os filtros aplicados."
                        icon="file-earmark-text"
                    />
                </div>
            @endforelse
        </x-slot:mobile>

        @if ($budgets instanceof \Illuminate\Pagination\LengthAwarePaginator && $budgets->hasPages())
            <x-slot:footer>
                @include('partials.components.paginator', ['p' => $budgets->appends(request()->query()), 'show_info' => true])
            </x-slot:footer>
        @endif
    </x-resource.resource-list-card>
</div>

{{-- Modal de Exclusão --}}
<x-ui.confirm-modal
    id="deleteBudgetModal"
    title="Excluir Orçamento"
    message="Tem certeza que deseja excluir este orçamento? Esta ação não poderá ser desfeita."
    confirmLabel="Excluir"
    confirmVariant="danger"
    method="DELETE"
    route="provider.budgets.destroy"
/>

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal de Exclusão
        const deleteModal = document.getElementById('deleteBudgetModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const budgetCode = button.getAttribute('data-budget-code') || button.getAttribute('data-item-id');
                const form = this.querySelector('form');
                const baseUrl = "{{ route('provider.budgets.destroy', ':code') }}";
                form.action = baseUrl.replace(':code', encodeURIComponent(budgetCode));
            });
        }

        // Validação de Datas
        const filterForm = document.getElementById('budgetFilterForm');
        if (filterForm) {
            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                const startDate = document.getElementsByName('filter_start_date')[0];
                const endDate = document.getElementsByName('filter_end_date')[0];
                if (!startDate || !endDate || !startDate.value || !endDate.value) return true;

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

            filterForm.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }
            });
        }
    });
</script>
@endpush
