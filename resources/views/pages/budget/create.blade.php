@extends('layouts.app')

@section('title', 'Novo Orçamento')

@section('content')
<div class="container-fluid py-4">
    <x-page-header
        title="Novo Orçamento"
        icon="file-earmark-plus"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
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
                        <label for="customer_id" class="form-label small fw-bold text-muted text-uppercase">Cliente *</label>
                        <select class="form-select tom-select @error('customer_id') is-invalid @enderror"
                            id="customer_id" name="customer_id" required>
                            <option value="">Selecione um cliente...</option>
                            @foreach ($customers as $customer)
                            @php
                                $customerName = 'Nome não informado';
                                if ($customer->commonData) {
                                    $commonData = $customer->commonData;
                                    $customerName = $commonData->company_name ?? trim(($commonData->first_name ?? '') . ' ' . ($commonData->last_name ?? ''));
                                }
                                $doc = 'Sem documento';
                                if ($customer->commonData) {
                                    $doc = $customer->commonData->cnpj ? \App\Helpers\DocumentHelper::formatCnpj($customer->commonData->cnpj) : \App\Helpers\DocumentHelper::formatCpf($customer->commonData->cpf);
                                }
                            @endphp
                            <option value="{{ $customer->id }}"
                                {{ (old('customer_id') == $customer->id || ($selectedCustomer && $selectedCustomer->id == $customer->id)) ? 'selected' : '' }}>
                                {{ $customerName }} ({{ $doc }})
                            </option>
                            @endforeach
                        </select>
                        @error('customer_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Data de Vencimento -->
                    <div class="col-md-6">
                        <label for="due_date" class="form-label small fw-bold text-muted text-uppercase">Data de Vencimento *</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                            id="due_date" name="due_date"
                            min="{{ date('Y-m-d') }}"
                            value="{{ \App\Helpers\DateHelper::formatDateOrDefault(old('due_date', date('Y-m-d', strtotime('+7 days'))), 'Y-m-d', date('Y-m-d', strtotime('+7 days'))) }}"
                            required>
                        @error('due_date')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Valores (Somente Leitura) -->
                    <div class="col-md-6">
                        <label for="total_display" class="form-label small fw-bold text-muted text-uppercase">Valor Total Estimado</label>
                        <input type="text" id="total_display"
                            class="form-control bg-light currency-brl"
                            value="0,00" readonly tabindex="-1">
                        <div class="form-text text-muted small">O valor final será calculado após adicionar os serviços.</div>
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <label for="description" class="form-label small fw-bold text-muted text-uppercase">Descrição</label>
                        <textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror"
                            rows="4" maxlength="255" placeholder="Ex: Projeto de reforma da cozinha...">{{ old('description') }}</textarea>
                        <div class="d-flex justify-content-end mt-1">
                            <small id="char-count" class="text-muted small">255 caracteres restantes</small>
                        </div>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Condições de Pagamento -->
                    <div class="col-12">
                        <label for="payment_terms" class="form-label small fw-bold text-muted text-uppercase">Condições de Pagamento (Opcional)</label>
                        <textarea id="payment_terms" name="payment_terms"
                            class="form-control @error('payment_terms') is-invalid @enderror" rows="2" maxlength="255"
                            placeholder="Ex: 50% de entrada e 50% na conclusão.">{{ old('payment_terms') }}</textarea>
                        @error('payment_terms')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-5">
                    <x-button type="link" :href="route('provider.budgets.index')" variant="outline-secondary" icon="x-circle" label="Cancelar" />
                    <x-button type="submit" variant="primary" icon="check-circle" label="Criar Orçamento" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar máscara de moeda para o total
        if (typeof VanillaMask === 'function') {
            new VanillaMask('total_display', 'currency');
        }

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
