@extends('layouts.app')

@section('title', 'Novo Orçamento')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-file-earmark-plus me-2"></i>Novo Orçamento
                </h1>
                <p class="text-muted mb-0">Preencha os dados para criar um novo orçamento</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.budgets.index') }}">Orçamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Novo</li>
                </ol>
            </nav>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="create-budget-form" action="{{ route('provider.budgets.store') }}" method="POST">
                    @csrf

                    <div class="row g-4">
                        <!-- Cliente -->
                        <div class="col-md-6">
                            <label for="customer_search" class="form-label">Cliente *</label>
                            <div class="input-group">
                                <input type="text" id="customer_search" name="customer_name"
                                    class="form-control @error('customer_id') is-invalid @enderror"
                                    placeholder="Digite o nome do cliente..." autocomplete="off"
                                    value="@if ($selectedCustomer){{ $selectedCustomer->commonData ? ($selectedCustomer->commonData->company_name ?: ($selectedCustomer->commonData->first_name . ' ' . $selectedCustomer->commonData->last_name)) : 'Nome não informado' }} ({{ $selectedCustomer->commonData ? ($selectedCustomer->commonData->cnpj ?: $selectedCustomer->commonData->cpf) : 'Sem documento' }})@endif"
                                    @if ($selectedCustomer) disabled @endif>
                                <button class="btn btn-outline-secondary" type="button" id="clear-customer-btn"
                                    style="@if ($selectedCustomer) display: block; @else display: none; @endif">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <input type="hidden" id="customer_id" name="customer_id"
                                value="{{ $selectedCustomer?->id ?? '' }}">
                            <div id="customer-search-results" class="list-group position-absolute w-100"
                                style="z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                            @error('customer_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Data de Vencimento -->
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Data de Vencimento *</label>
                            <input type="date" id="due_date" name="due_date"
                                class="form-control @error('due_date') is-invalid @enderror"
                                value="{{ old('due_date') }}" required>
                            @error('due_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Descrição -->
                        <div class="col-12">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                                rows="4" maxlength="255" placeholder="Ex: Projeto de reforma da cozinha...">{{ old('description') }}</textarea>
                            <div class="d-flex justify-content-end">
                                <small id="char-count" class="text-muted mt-2">255 caracteres restantes</small>
                            </div>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Condições de Pagamento -->
                        <div class="col-12">
                            <label for="payment_terms" class="form-label">Condições de Pagamento (Opcional)</label>
                            <textarea id="payment_terms" name="payment_terms"
                                class="form-control @error('payment_terms') is-invalid @enderror" rows="2" maxlength="255"
                                placeholder="Ex: 50% de entrada e 50% na conclusão.">{{ old('payment_terms') }}</textarea>
                            @error('payment_terms')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="{{ url()->previous(route('provider.budgets.index')) }}"
                                class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Criar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Character counter for description
            const description = document.getElementById('description');
            const charCount = document.getElementById('char-count');
            const maxLength = 255;

            if (description && charCount) {
                description.addEventListener('input', function() {
                    const remaining = maxLength - this.value.length;
                    charCount.textContent = remaining + ' caracteres restantes';
                });
            }

            // Customer search functionality
            const customers = {!! json_encode(
                $customers->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'name' => $customer->commonData
                            ? ($customer->commonData->company_name ?:
                                $customer->commonData->first_name . ' ' . $customer->commonData->last_name)
                            : 'Nome não informado',
                        'document' => $customer->commonData ? ($customer->commonData->cnpj ?: $customer->commonData->cpf) : '',
                        'email' => $customer->contact ? $customer->contact->email_personal : '',
                    ];
                }),
            ) !!};

            const selectedCustomer = {!! json_encode($selectedCustomer) !!};

            const customerSearch = document.getElementById('customer_search');
            const customerId = document.getElementById('customer_id');
            const searchResults = document.getElementById('customer-search-results');
            const clearCustomerBtn = document.getElementById('clear-customer-btn');

            if (customerSearch && customerId && searchResults && clearCustomerBtn) {
                customerSearch.addEventListener('input', function() {
                    const query = this.value.toLowerCase();
                    searchResults.innerHTML = '';

                    if (query.length < 2) {
                        clearCustomerBtn.style.display = 'none';
                        customerId.value = '';
                        return;
                    }

                    clearCustomerBtn.style.display = 'block';

                    const filteredCustomers = customers.filter(customer =>
                        customer.name.toLowerCase().includes(query) ||
                        (customer.document && customer.document.includes(query)) ||
                        (customer.email && customer.email.toLowerCase().includes(query))
                    );

                    if (filteredCustomers.length > 0) {
                        filteredCustomers.slice(0, 10).forEach(customer => {
                            const item = document.createElement('a');
                            item.href = '#';
                            item.classList.add('list-group-item', 'list-group-item-action');
                            item.innerHTML = `
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${customer.name}</strong>
                                        ${customer.document ? `<br><small class="text-muted">${customer.document}</small>` : ''}
                                    </div>
                                    ${customer.email ? `<small class="text-muted">${customer.email}</small>` : ''}
                                </div>
                            `;

                            item.addEventListener('click', function(e) {
                                e.preventDefault();
                                customerSearch.value =
                                    `${customer.name} (${customer.document || 'Sem documento'})`;
                                customerId.value = customer.id;
                                searchResults.innerHTML = '';
                                customerSearch.disabled = true;
                            });

                            searchResults.appendChild(item);
                        });
                    } else {
                        const noResult = document.createElement('div');
                        noResult.classList.add('list-group-item');
                        noResult.textContent = 'Nenhum cliente encontrado';
                        searchResults.appendChild(noResult);
                    }
                });

                clearCustomerBtn.addEventListener('click', function() {
                    customerSearch.value = '';
                    customerId.value = '';
                    searchResults.innerHTML = '';
                    this.style.display = 'none';
                    customerSearch.disabled = false;
                    customerSearch.focus();
                });

                // Hide results when clicking outside
                document.addEventListener('click', function(e) {
                    if (!customerSearch.contains(e.target) && !searchResults.contains(e.target)) {
                        searchResults.innerHTML = '';
                    }
                });
            }
        });
    </script>
@endpush
