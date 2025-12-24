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
                            <label for="filter_code">Código</label>
                            <input type="text" class="form-control" id="filter_code" name="filter_code"
                                value="{{ request('filter_code') }}" placeholder="Código...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filter_start_date">Data Início</label>
                            <input type="date" class="form-control" id="filter_start_date" name="filter_start_date"
                                value="{{ request('filter_start_date') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filter_end_date">Data Fim</label>
                            <input type="date" class="form-control" id="filter_end_date" name="filter_end_date"
                                value="{{ request('filter_end_date') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="filter_customer">Cliente</label>
                            <input type="text" class="form-control" id="filter_customer" name="filter_customer"
                                value="{{ request('filter_customer') }}" placeholder="Nome do cliente...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label for="filter_status">Status</label>
                            <select class="form-select tom-select" id="filter_status" name="filter_status">
                                <option value="">Todos</option>
                                <option value="DRAFT" {{ request('filter_status') == 'DRAFT' ? 'selected' : '' }}>Rascunho</option>
                                <option value="SENT" {{ request('filter_status') == 'SENT' ? 'selected' : '' }}>Enviado</option>
                                <option value="APPROVED" {{ request('filter_status') == 'APPROVED' ? 'selected' : '' }}>Aprovado</option>
                                <option value="REJECTED" {{ request('filter_status') == 'REJECTED' ? 'selected' : '' }}>Rejeitado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label for="per_page">Por página</label>
                            <select class="form-select tom-select" id="per_page" name="per_page">
                                @php($pp = (int) request('per_page', 10))
                                <option value="10" {{ $pp === 10 ? 'selected' : '' }}>10</option>
                                <option value="20" {{ $pp === 20 ? 'selected' : '' }}>20</option>
                                <option value="50" {{ $pp === 50 ? 'selected' : '' }}>50</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-nowrap">
                            <x-button type="submit" icon="search" label="Filtrar" />
                            <x-button type="link" :href="route('provider.budgets.index')" variant="secondary" icon="x" label="Limpar" />
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
                                <td>{{ $budget->code }}</td>
                                <td>
                                    @if ($budget->customer && $budget->customer->commonData)
                                    @if ($budget->customer->commonData->company_name)
                                    {{ $budget->customer->commonData->company_name }}
                                    @else
                                    {{ $budget->customer->commonData->first_name }}
                                    {{ $budget->customer->commonData->last_name }}
                                    @endif
                                    @else
                                    Cliente não informado
                                    @endif
                                </td>
                                <td>{{ $budget->created_at->format('d/m/Y') }}</td>
                                <td>{{ $budget->due_date ? $budget->due_date->format('d/m/Y') : '-' }}</td>
                                <td>R$ {{ number_format($budget->total, 2, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-secondary">{{ $budget->status->value }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="action-btn-group">
                                        <x-button type="link" :href="route('provider.budgets.show', $budget->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                        <x-button type="link" :href="route('provider.budgets.edit', $budget->code)" size="sm" icon="pencil-square" title="Editar" />
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
                <div class="list-group">
                    @forelse($budgets as $budget)
                    <a href="{{ route('provider.budgets.show', $budget->code) }}"
                        class="list-group-item list-group-item-action py-3">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-file-earmark-text text-muted me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold mb-2">{{ $budget->code }}</div>
                                <div class="small text-muted mb-2">
                                    @if ($budget->customer && $budget->customer->commonData)
                                    @if ($budget->customer->commonData->company_name)
                                    {{ $budget->customer->commonData->company_name }}
                                    @else
                                    {{ $budget->customer->commonData->first_name }}
                                    {{ $budget->customer->commonData->last_name }}
                                    @endif
                                    @else
                                    Cliente não informado
                                    @endif
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-secondary">{{ $budget->status->value }}</span>
                                    <span class="badge bg-success">R$ {{ number_format($budget->total, 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <i class="bi bi-chevron-right text-muted ms-2"></i>
                        </div>
                    </a>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                        <br>
                        Nenhum orçamento encontrado.
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
            <div class="modal-body">
                Tem certeza que deseja excluir o orçamento <strong id="budgetCodeToDelete"></strong>?
                <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
            </div>
            <div class="modal-footer">
                <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form id="deleteBudgetForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Excluir" />
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
