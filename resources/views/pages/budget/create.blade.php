@extends('layouts.app')

@section('title', 'Novo Orçamento')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Novo Orçamento"
        icon="file-earmark-plus"
        :breadcrumb-items="[
                'Orçamentos' => route('provider.budgets.index'),
                'Novo' => '#'
            ]">
        <p class="text-muted mb-0">Preencha os dados para criar um novo orçamento</p>
    </x-page-header>

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
                        <label for="customer_id" class="form-label">Cliente *</label>
                        <select class="form-select tom-select @error('customer_id') is-invalid @enderror"
                            id="customer_id" name="customer_id" required>
                            <option value="">Selecione um cliente...</option>
                            @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}"
                                {{ (old('customer_id') == $customer->id || ($selectedCustomer && $selectedCustomer->id == $customer->id)) ? 'selected' : '' }}>
                                {{ $customer->commonData
                                            ? ($customer->commonData->company_name ?: ($customer->commonData->first_name . ' ' . $customer->commonData->last_name))
                                            : 'Nome não informado' }}
                                ({{ $customer->commonData ? ($customer->commonData->cnpj ?: $customer->commonData->cpf) : 'Sem documento' }})
                            </option>
                            @endforeach
                        </select>
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
                        <x-back-button index-route="provider.budgets.index" label="Cancelar" />
                    </div>
                    <x-button type="submit" icon="check-circle" label="Criar" />
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
    });
</script>
@endpush
