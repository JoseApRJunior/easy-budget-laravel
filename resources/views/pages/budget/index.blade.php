@extends('layouts.app')

@section('title', 'Orçamentos')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Orçamentos"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Orçamentos' => '#'
        ]">
        <p class="text-muted mb-0">Lista de todos os orçamentos registrados no sistema</p>
    </x-page-header>

    <!-- Card de Filtros -->
    <x-filter-form
        id="budgetFilterForm"
        :route="route('provider.budgets.index')"
        :filters="$filters"
    >
        <x-filter-field
            col="col-md-2"
            name="filter_code"
            label="Código"
            placeholder="Ex: ORC-001"
            :filters="$filters"
        />

        <x-filter-field
            type="date"
            col="col-md-2"
            name="filter_start_date"
            label="Data Início"
            placeholder="DD/MM/AAAA"
            :filters="$filters"
        />

        <x-filter-field
            type="date"
            col="col-md-2"
            name="filter_end_date"
            label="Data Fim"
            placeholder="DD/MM/AAAA"
            :filters="$filters"
        />

        <x-filter-field
            col="col-md-3"
            name="filter_customer"
            label="Cliente"
            placeholder="Nome do cliente..."
            :filters="$filters"
        />

        <x-filter-field
            type="select"
            col="col-md-2"
            name="filter_status"
            label="Status"
            :filters="$filters"
            :options="['' => 'Todos'] + collect(\App\Enums\BudgetStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->getDescription()])->toArray()"
        />

        <x-filter-field
            type="select"
            col="col-md-1"
            name="per_page"
            label="Itens"
            :filters="$filters"
            :options="[10 => '10', 20 => '20', 50 => '50']"
        />
    </x-filter-form>

    <!-- Card de Tabela -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                    <span class="me-2">
                        <i class="bi bi-list-ul me-1"></i>
                        <span class="d-none d-sm-inline">Lista de Orçamentos</span>
                        <span class="d-sm-none">Orçamentos</span>
                    </span>
                    <span class="text-muted" style="font-size: 0.875rem;">
                        ({{ $budgets->total() }})
                    </span>
                </h5>
                <x-table-header-actions
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
                                        <div class="avatar avatar-xs me-2 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                            <i class="bi bi-person text-primary small"></i>
                                        </div>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            @if ($budget->customer && $budget->customer->commonData)
                                                @if ($budget->customer->commonData->company_name)
                                                    {{ $budget->customer->commonData->company_name }}
                                                @else
                                                    {{ $budget->customer->commonData->first_name }} {{ $budget->customer->commonData->last_name }}
                                                @endif
                                            @else
                                                <span class="text-muted italic">Cliente não informado</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small">{{ $budget->created_at->format('d/m/Y') }}</td>
                                <td class="text-muted small">
                                    @if($budget->due_date)
                                        <span class="{{ $budget->due_date->isPast() ? 'text-danger fw-bold' : '' }}">
                                            {{ $budget->due_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold text-dark">R$ {{ number_format($budget->total, 2, ',', '.') }}</td>
                                <td>
                                    <span class="badge rounded-pill" style="background-color: {{ $budget->status->getColor() }}20; color: {{ $budget->status->getColor() }}; border: 1px solid {{ $budget->status->getColor() }}40;">
                                        <i class="bi {{ $budget->status->getIcon() }} me-1"></i>
                                        {{ $budget->status->getDescription() }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <x-action-buttons
                                            :item="$budget"
                                            resource="budgets"
                                            identifier="code"
                                            size="sm"
                                        />
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7">
                                    <x-empty-state
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
                    <div class="list-group-item py-3 border-bottom">
                        <a href="{{ route('provider.budgets.show', $budget->code) }}" class="text-decoration-none">
                            <div class="d-flex align-items-center mb-2">
                                <div class="avatar avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">{{ $budget->code }}</span>
                                        <span class="text-muted small">{{ $budget->created_at->format('d/m/Y') }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Cliente</small>
                                <div class="text-dark fw-semibold">
                                    @if ($budget->customer && $budget->customer->commonData)
                                        @if ($budget->customer->commonData->company_name)
                                            {{ $budget->customer->commonData->company_name }}
                                        @else
                                            {{ $budget->customer->commonData->first_name }} {{ $budget->customer->commonData->last_name }}
                                        @endif
                                    @else
                                        <span class="text-muted italic small">Não informado</span>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-2 mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Valor Total</small>
                                    <span class="fw-bold text-primary">R$ {{ number_format($budget->total, 2, ',', '.') }}</span>
                                </div>
                                <div class="col-6 text-end">
                                    <small class="text-muted d-block text-uppercase mb-1" style="font-size: 0.65rem; letter-spacing: 0.5px;">Status</small>
                                    <span class="badge rounded-pill" style="background-color: {{ $budget->status->getColor() }}20; color: {{ $budget->status->getColor() }}; border: 1px solid {{ $budget->status->getColor() }}40; font-size: 0.7rem;">
                                        {{ $budget->status->getDescription() }}
                                    </span>
                                </div>
                            </div>
                        </a>
                        <div class="d-flex justify-content-end gap-2">
                            <x-action-buttons
                                :item="$budget"
                                resource="budgets"
                                identifier="code"
                                size="sm"
                            />
                        </div>
                    </div>
                    @empty
                    <div class="py-5">
                        <x-empty-state
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
<x-confirm-modal
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
    });
</script>
@endpush
