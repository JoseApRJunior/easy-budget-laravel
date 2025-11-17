@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-receipt me-2"></i>Faturas
        </h1>
        <div class="d-flex gap-2">
            <a href="{{ route('provider.invoices.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Nova Fatura
            </a>
        </div>
    </div>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Faturas</li>
        </ol>
    </nav>

    <!-- Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('provider.invoices.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text"
                           class="form-control"
                           name="search"
                           id="search"
                           value="{{ old('search', $filters['search'] ?? '') }}"
                           placeholder="Código, cliente, descrição...">
                </div>
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">Todos os status</option>
                        @foreach($statusOptions as $status)
                            <option value="{{ $status->value }}"
                                    {{ old('status', $filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                {{ $status->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="customer_id" class="form-label">Cliente</label>
                    <select class="form-select" name="customer_id" id="customer_id">
                        <option value="">Todos os clientes</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Data inicial</label>
                    <input type="date"
                           class="form-control"
                           name="date_from"
                           id="date_from"
                           value="{{ old('date_from', $filters['date_from'] ?? '') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Data final</label>
                    <input type="date"
                           class="form-control"
                           name="date_to"
                           id="date_to"
                           value="{{ old('date_to', $filters['date_to'] ?? '') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estatísticas Rápidas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-receipt-cutoff" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="text-primary">{{ $invoices->count() }}</h4>
                    <p class="text-muted mb-0">Total de Faturas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-clock" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="text-warning">{{ $invoices->where('status', 'pending')->count() }}</h4>
                    <p class="text-muted mb-0">Pendentes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="text-success">{{ $invoices->where('status', 'paid')->count() }}</h4>
                    <p class="text-muted mb-0">Pagas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                    </div>
                    <h4 class="text-danger">{{ $invoices->where('status', 'overdue')->count() }}</h4>
                    <p class="text-muted mb-0">Vencidas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Faturas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Cliente</th>
                                <th>Serviço</th>
                                <th>Data de Vencimento</th>
                                <th>Valor Total</th>
                                <th>Status</th>
                                <th width="150">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                                <tr>
                                    <td>
                                        <strong>{{ $invoice->code }}</strong>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong>
                                        </div>
                                        <small class="text-muted">
                                            {{ $invoice->customer->email ?? '' }}
                                        </small>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="badge bg-light text-dark">
                                                {{ $invoice->service->code ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            {{ Str::limit($invoice->service->description ?? '', 30) }}
                                        </small>
                                    </td>
                                    <td>
                                        <div>{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</div>
                                        @if($invoice->due_date && $invoice->due_date < now())
                                            <small class="text-danger">
                                                <i class="bi bi-exclamation-triangle me-1"></i>
                                                Vencida
                                            </small>
                                        @elseif($invoice->due_date && $invoice->due_date->diffInDays(now()) <= 7)
                                            <small class="text-warning">
                                                <i class="bi bi-clock me-1"></i>
                                                Vence em {{ $invoice->due_date->diffInDays(now()) }} dias
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}
                                        </strong>
                                    </td>
                                    <td>
                                        @php
                                            $status = $invoice->status;
                                            $badgeClass = match($status) {
                                                'pending' => 'bg-warning',
                                                'paid' => 'bg-success',
                                                'overdue' => 'bg-danger',
                                                'cancelled' => 'bg-secondary',
                                                default => 'bg-light text-dark'
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}">
                                            {{ $status->name ?? ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('provider.invoices.show', $invoice->code) }}"
                                               class="btn btn-outline-primary"
                                               title="Visualizar">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('provider.invoices.print', $invoice) }}"
                                               class="btn btn-outline-secondary"
                                               title="Imprimir"
                                               target="_blank">
                                                <i class="bi bi-printer"></i>
                                            </a>
                                            @if($invoice->status === 'pending')
                                                <a href="{{ route('provider.invoices.edit', $invoice->code) }}"
                                                   class="btn btn-outline-warning"
                                                   title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginação -->
                @if(method_exists($invoices, 'links'))
                    <div class="card-footer bg-white">
                        {{ $invoices->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="bi bi-receipt-cutoff text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">Nenhuma fatura encontrada</h5>
                    <p class="text-muted">
                        @if(!empty(array_filter($filters)))
                            Tente ajustar os filtros de busca.
                        @else
                            Comece criando sua primeira fatura.
                        @endif
                    </p>
                    <a href="{{ route('provider.invoices.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Criar Primeira Fatura
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit do formulário quando os filtros mudarem
    const filterSelects = document.querySelectorAll('#status, #customer_id');
    filterSelects.forEach(function(select) {
        select.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // Destacar faturas vencidas
    const today = new Date();
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(function(row) {
        const dueDateCell = row.querySelector('td:nth-child(4)');
        if (dueDateCell) {
            const dueDateText = dueDateCell.textContent.trim();
            // Aqui você pode adicionar lógica adicional para destacar faturas vencidas
        }
    });
});
</script>
@endpush
@endsection
