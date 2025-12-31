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
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('provider.budgets.index') }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_code" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Código</label>
                                <input type="text" class="form-control form-control-sm" id="filter_code" name="filter_code"
                                    value="{{ request('filter_code') }}" placeholder="Ex: ORC-001">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_start_date" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Data Início</label>
                                <input type="date" class="form-control form-control-sm" id="filter_start_date" name="filter_start_date"
                                    value="{{ request('filter_start_date') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_end_date" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Data Fim</label>
                                <input type="date" class="form-control form-control-sm" id="filter_end_date" name="filter_end_date"
                                    value="{{ request('filter_end_date') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="filter_customer" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Cliente</label>
                                <input type="text" class="form-control form-control-sm" id="filter_customer" name="filter_customer"
                                    value="{{ request('filter_customer') }}" placeholder="Nome do cliente...">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label for="filter_status" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Status</label>
                                <select class="form-select form-select-sm tom-select" id="filter_status" name="filter_status">
                                    <option value="">Todos</option>
                                    @foreach(\App\Enums\BudgetStatus::cases() as $status)
                                        <option value="{{ $status->value }}" {{ request('filter_status') == $status->value ? 'selected' : '' }}>
                                            {{ $status->getDescription() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <div class="form-group">
                                <label for="per_page" class="text-muted small text-uppercase fw-bold" style="font-size: 0.7rem;">Itens</label>
                                <select class="form-select form-select-sm tom-select" id="per_page" name="per_page">
                                    @php($pp = (int) request('per_page', 10))
                                    <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                    <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                    <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <x-button type="link" :href="route('provider.budgets.index')" variant="outline-secondary" size="sm" icon="x-circle" label="Limpar Filtros" />
                                <x-button type="submit" variant="primary" size="sm" icon="search" label="Filtrar Resultados" />
                            </div>
                        </div>
                    </div>
                </form>
        </div>
    </div>

    <!-- Card de Tabela -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-12 col-lg-8 mb-2 mb-lg-0">
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
                </div>
                <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                    <div class="d-flex justify-content-start justify-content-lg-end">
                        <x-button type="link" :href="route('provider.budgets.create')" size="sm" icon="plus" label="Novo" />
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Desktop View -->
            <div class="desktop-view">
                <div class="table-responsive">
                    <table class="modern-table table mb-0">
                        <thead>
                            <tr>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Código</th>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Cliente</th>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Data</th>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Vencimento</th>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Valor</th>
                                <th class="text-muted small text-uppercase" style="font-size: 0.7rem;">Status</th>
                                <th class="text-center text-muted small text-uppercase" style="font-size: 0.7rem;">Ações</th>
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
                                        <x-button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                        <x-button type="link" :href="route('provider.budgets.edit', $budget->code)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                        <x-button variant="danger" size="sm" icon="trash"
                                            data-bs-toggle="modal" data-bs-target="#deleteBudgetModal"
                                            data-budget-code="{{ $budget->code }}"
                                            data-budget-id="{{ $budget->id }}" title="Excluir" />
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    <i class="bi bi-inbox mb-2" aria-hidden="true" style="font-size: 2rem;"></i>
                                    <br>
                                    Nenhum orçamento encontrado.
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
                    <a href="{{ route('provider.budgets.show', $budget->code) }}"
                        class="list-group-item list-group-item-action py-3 border-bottom">
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

                        <div class="row g-2">
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
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        <p class="mb-0">Nenhum orçamento encontrado.</p>
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

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteBudgetModal" tabindex="-1" aria-labelledby="deleteBudgetModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteBudgetModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-3">
                    <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5>Confirmar Exclusão</h5>
                <p class="text-muted">
                    Tem certeza que deseja excluir o orçamento <strong id="budgetCodeToDelete" class="text-dark"></strong>?
                    <br>Esta ação não pode ser desfeita e removerá todos os dados vinculados.
                </p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <x-button variant="outline-secondary" data-bs-dismiss="modal" label="Cancelar" icon="x-circle" />
                <form id="deleteBudgetForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Sim, Excluir" icon="trash" />
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const deleteBudgetModal = document.getElementById('deleteBudgetModal');
        if (deleteBudgetModal) {
            deleteBudgetModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const budgetCode = button.getAttribute('data-budget-code');
                const budgetId = button.getAttribute('data-budget-id');

                const budgetCodeToDelete = deleteBudgetModal.querySelector('#budgetCodeToDelete');
                const deleteForm = deleteBudgetModal.querySelector('#deleteBudgetForm');

                budgetCodeToDelete.textContent = budgetCode;
                deleteForm.action = `{{ url('provider/budgets') }}/${budgetId}`;
            });
        }
    });
</script>
@endpush
