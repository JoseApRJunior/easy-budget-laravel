@extends('layouts.app')
@section('title', 'Faturas')
@section('content')
    <div class="container-fluid py-1">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-receipt me-2"></i>Faturas
                </h1>
                <p class="text-muted">Lista de todas as faturas registradas no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listar</li>
                </ol>
            </nav>
        </div>

        {{-- Card de Filtros --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('provider.invoices.index') }}" class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Buscar</label>
                        <input type="text" class="form-control" name="search" id="search"
                            value="{{ old('search', $filters['search'] ?? '') }}"
                            placeholder="Código, cliente...">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" name="status" id="status">
                            <option value="">Todos</option>
                            @foreach ($statusOptions as $status)
                                <option value="{{ $status->value }}"
                                    {{ old('status', $filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                                    {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="customer_id" class="form-label">Cliente</label>
                        <select class="form-select" name="customer_id" id="customer_id">
                            <option value="">Todos</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                    {{ old('customer_id', $filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Data inicial</label>
                        <input type="date" class="form-control" name="date_from" id="date_from"
                            value="{{ old('date_from', $filters['date_from'] ?? '') }}">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Data final</label>
                        <input type="date" class="form-control" name="date_to" id="date_to"
                            value="{{ old('date_to', $filters['date_to'] ?? '') }}">
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-nowrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filtrar
                            </button>
                            <a href="{{ route('provider.invoices.index') }}" class="btn btn-secondary">
                                <i class="bi bi-x me-1"></i>Limpar
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Card de Tabela --}}
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                        <h5 class="mb-0 d-flex align-items-center flex-wrap">
                            <span class="me-2">
                                <i class="bi bi-list-ul me-1"></i>
                                <span class="d-none d-sm-inline">Lista de Faturas</span>
                                <span class="d-sm-none">Faturas</span>
                            </span>
                            <span class="text-muted" style="font-size: 0.875rem;">
                                ({{ method_exists($invoices, 'total') ? $invoices->total() : $invoices->count() }})
                            </span>
                        </h5>
                    </div>
                    <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                        <div class="d-flex justify-content-start justify-content-lg-end">
                            <a href="{{ route('provider.invoices.create') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus"></i>
                                <span class="ms-1">Novo</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                {{-- Desktop View --}}
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Vencimento</th>
                                    <th>Valor Total</th>
                                    <th>Status</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td><strong>{{ $invoice->code }}</strong></td>
                                        <td>{{ $invoice->customer->name ?? 'N/A' }}</td>
                                        <td>{{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}</td>
                                        <td><strong>R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</strong></td>
                                        <td>
                                            @php
                                                $badgeClass = match ($invoice->status) {
                                                    'pending' => 'bg-warning',
                                                    'paid' => 'bg-success',
                                                    'overdue' => 'bg-danger',
                                                    'cancelled' => 'bg-secondary',
                                                    default => 'bg-light text-dark',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $invoice->status->name ?? ucfirst($invoice->status) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="action-btn-group">
                                                <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="action-btn action-btn-view" title="Visualizar">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                @if ($invoice->status === 'pending')
                                                    <a href="{{ route('provider.invoices.edit', $invoice->code) }}" class="action-btn action-btn-edit" title="Editar">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                            <br>Nenhuma fatura encontrada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile View --}}
                <div class="mobile-view">
                    <div class="list-group">
                        @forelse($invoices as $invoice)
                            <a href="{{ route('provider.invoices.show', $invoice->code) }}" class="list-group-item list-group-item-action py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-receipt text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">{{ $invoice->code }}</div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            @php
                                                $badgeClass = match ($invoice->status) {
                                                    'pending' => 'bg-warning',
                                                    'paid' => 'bg-success',
                                                    'overdue' => 'bg-danger',
                                                    'cancelled' => 'bg-secondary',
                                                    default => 'bg-light text-dark',
                                                };
                                            @endphp
                                            <span class="badge {{ $badgeClass }}">
                                                {{ $invoice->status->name ?? ucfirst($invoice->status) }}
                                            </span>
                                        </div>
                                        <div class="small text-muted">
                                            <div>Cliente: {{ $invoice->customer->name ?? 'N/A' }}</div>
                                            <div>Total: R$ {{ number_format($invoice->total_amount, 2, ',', '.') }}</div>
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted ms-2"></i>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                <br>Nenhuma fatura encontrada.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @if (method_exists($invoices, 'hasPages') && $invoices->hasPages())
                @include('partials.components.paginator', ['p' => $invoices->appends(request()->query()), 'show_info' => true])
            @endif
        </div>
    </div>
@endsection
