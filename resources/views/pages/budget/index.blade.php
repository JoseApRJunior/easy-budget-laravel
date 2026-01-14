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
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0 py-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 d-flex align-items-center flex-wrap fw-bold text-dark">
                    <span class="me-2">
                        <i class="bi bi-list-ul me-1"></i>
                        <span class="d-none d-sm-inline">Lista de Orçamentos</span>
                        <span class="d-sm-none">Orçamentos</span>
                    </span>
                    <span class="text-muted small fw-normal">
                        ({{ $budgets->total() }})
                    </span>
                </h5>
                <x-resource.table-header-actions
                    resource="budgets"
                    :filters="$filters"
                    createLabel="Novo"
                    :showExport="false"
                />
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Desktop View -->
            <div class="desktop-view">
                <div class="table-responsive">
                    <table class="modern-table table mb-0">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
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
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="mobile-view">
                <div class="list-group list-group-flush">
                    @forelse($budgets as $budget)
                    <div class="list-group-item py-3">
                        <a href="{{ route('provider.budgets.show', $budget->code) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar-circle-sm bg-light me-2">
                                    <i class="bi bi-file-earmark-text"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">{{ $budget->code }}</span>
                                        <span class="text-muted small">{{ optional($budget->created_at)->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Cliente</small>
                                <div class="text-dark fw-semibold">
                                    @php
                                        $customerName = 'Não informado';
                                        if ($budget->customer && $budget->customer->commonData) {
                                            $commonData = $budget->customer->commonData;
                                            $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                                        }
                                    @endphp
                                    {{ $customerName ?: 'Não informado' }}
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Valor Total</small>
                                    <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total ?? 0) }}</span>
                                </div>
                                <div class="col-6 text-end">
                                    <small class="text-muted d-block text-uppercase mb-1 small fw-bold">Status</small>
                                    <x-ui.status-badge :item="$budget" />
                                </div>
                            </div>
                        </a>
                        <div class="d-flex justify-content-end">
                            <x-resource.action-buttons
                                :item="$budget"
                                resource="budgets"
                                identifier="code"
                                size="sm"
                            />
                        </div>
                    </div>
                    @empty
                    <div class="py-5">
                        <x-resource.empty-state
                            title="Nenhum orçamento encontrado"
                            description="Não encontramos orçamentos com os filtros aplicados."
                            icon="file-earmark-text"
                        />
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
        @if ($budgets instanceof \Illuminate\Pagination\LengthAwarePaginator && $budgets->hasPages())
        @include('partials.components.paginator', ['p' => $budgets->appends(request()->query()), 'show_info' => true])
        @endif
    </div>
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
