@extends('layouts.app')
@section('title', 'Faturas')

@php
    // Nenhuma função auxiliar necessária aqui, usamos x-status-badge
@endphp

@section('content')
    <div class="container-fluid py-1">
        <x-page-header
            title="Faturas"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => route('provider.invoices.dashboard'),
                'Listar' => '#'
            ]">
            <p class="text-muted mb-0">Lista de todas as faturas registradas no sistema</p>
        </x-page-header>

        {{-- Card de Filtros --}}
        <x-filter-form :route="route('provider.invoices.index')" id="filtersFormInvoices" :filters="$filters">
            <div class="col-md-3">
                <x-filter-field
                    label="Buscar"
                    name="search"
                    id="search"
                    :value="$filters['search'] ?? ''"
                    placeholder="Código, cliente..." />
            </div>
            <div class="col-md-2">
                <x-filter-field
                    label="Status"
                    name="status"
                    id="status"
                    type="select">
                    <option value="">Todos</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status->value }}"
                            {{ ($filters['status'] ?? '') == $status->value ? 'selected' : '' }}>
                            {{ $status->getDescription() }}
                        </option>
                    @endforeach
                </x-filter-field>
            </div>
            <div class="col-md-3">
                <x-filter-field
                    label="Cliente"
                    name="customer_id"
                    id="customer_id"
                    type="select">
                    <option value="">Todos</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}"
                            {{ ($filters['customer_id'] ?? '') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }}
                        </option>
                    @endforeach
                </x-filter-field>
            </div>
            <div class="col-md-2">
                <x-filter-field
                    label="Período Inicial"
                    name="date_from"
                    id="date_from"
                    type="date"
                    :value="$filters['date_from'] ?? ''" />
            </div>
            <div class="col-md-2">
                <x-filter-field
                    label="Período Final"
                    name="date_to"
                    id="date_to"
                    type="date"
                    :value="$filters['date_to'] ?? ''" />
            </div>
        </x-filter-form>

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
                            <x-button type="link" :href="route('provider.invoices.create')" variant="primary" size="sm" icon="plus" label="Novo" />
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
                                        <td><strong>{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</strong>
                                        </td>
                                        <td>
                                            <x-status-badge :item="$invoice" />
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <x-button type="link" :href="route('provider.invoices.show', $invoice->code)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                @if ($invoice->status === 'PENDING')
                                                    <x-button type="link" :href="route('provider.invoices.edit', $invoice->code)" variant="primary" size="sm" icon="pencil" title="Editar" />
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <x-empty-state
                                                icon="receipt"
                                                title="Nenhuma fatura encontrada"
                                                description="Tente ajustar seus filtros para encontrar o que procura." />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Mobile View --}}
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        @forelse($invoices as $invoice)
                            <a href="{{ route('provider.invoices.show', $invoice->code) }}"
                                class="list-group-item list-group-item-action py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-receipt text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">{{ $invoice->code }}</div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            <x-status-badge :item="$invoice" />
                                        </div>
                                        <div class="small text-muted">
                                            <div>Cliente: {{ $invoice->customer->name ?? 'N/A' }}</div>
                                            <div>Total: {{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</div>
                                        </div>
                                    </div>
                                    <i class="bi bi-chevron-right text-muted ms-2"></i>
                                </div>
                            </a>
                        @empty
                            <div class="text-center py-5 text-muted">
                                <x-empty-state
                                    icon="receipt"
                                    title="Nenhuma fatura encontrada"
                                    description="Tente ajustar seus filtros para encontrar o que procura." />
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @if (method_exists($invoices, 'hasPages') && $invoices->hasPages())
                @include('partials.components.paginator', [
                    'p' => $invoices->appends(request()->query()),
                    'show_info' => true,
                ])
            @endif
        </div>
    </div>
@endsection
