@extends('layouts.app')

@section('title', 'Novo Orçamento')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Novo Orçamento"
        icon="file-earmark-plus"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            'Novo' => '#'
        ]">
        <p class="text-muted mb-0">Preencha os dados para criar um novo orçamento</p>
    </x-layout.page-header>

    <x-ui.card>
        <div class="p-2">
            @if ($errors->any())
                <x-ui.alert variant="danger" title="Ops! Verifique os erros abaixo:">
                    <ul class="mb-0 mt-2 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-ui.alert>
            @endif

            <form id="create-budget-form" action="{{ route('provider.budgets.store') }}" method="POST">
                @csrf

                <x-layout.grid-row class="g-4">
                    <!-- Cliente -->
                    <div class="col-md-6">
                        <x-ui.form.select 
                            name="customer_id" 
                            label="Cliente *" 
                            class="tom-select" 
                            required 
                            :selected="$selectedCustomer ? $selectedCustomer->id : null"
                        >
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
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id || ($selectedCustomer && $selectedCustomer->id == $customer->id) ? 'selected' : '' }}>
                                    {{ $customerName }} ({{ $doc }})
                                </option>
                            @endforeach
                        </x-ui.form.select>
                    </div>

                    <!-- Data de Vencimento -->
                    <div class="col-md-6">
                        <x-ui.form.input 
                            type="date" 
                            name="due_date" 
                            label="Data de Vencimento *" 
                            :min="date('Y-m-d')" 
                            :value="\App\Helpers\DateHelper::formatDateOrDefault(old('due_date', date('Y-m-d', strtotime('+7 days'))), 'Y-m-d', date('Y-m-d', strtotime('+7 days')))" 
                            required 
                        />
                    </div>

                    <!-- Valores (Somente Leitura) -->
                    <div class="col-md-6">
                        <x-ui.form.input 
                            name="total_display" 
                            label="Valor Total Estimado" 
                            value="0,00" 
                            class="bg-light currency-brl" 
                            readonly 
                            tabindex="-1" 
                            help="O valor final será calculado após adicionar os serviços."
                        />
                    </div>

                    <!-- Descrição -->
                    <div class="col-12">
                        <x-ui.form.textarea 
                            name="description" 
                            label="Descrição" 
                            rows="4" 
                            maxlength="255" 
                            placeholder="Ex: Projeto de reforma da cozinha..."
                            help="255 caracteres restantes"
                            id="description"
                        >{{ old('description') }}</x-ui.form.textarea>
                    </div>

                    <!-- Condições de Pagamento -->
                    <div class="col-12">
                        <x-ui.form.textarea 
                            name="payment_terms" 
                            label="Condições de Pagamento (Opcional)" 
                            rows="2" 
                            maxlength="255" 
                            placeholder="Ex: 50% de entrada e 50% na conclusão."
                        >{{ old('payment_terms') }}</x-ui.form.textarea>
                    </div>
                </x-layout.grid-row>

                <div class="d-flex justify-content-between align-items-center mt-5">
                    <x-ui.back-button index-route="provider.budgets.index" label="Cancelar" feature="budgets" />
                    <x-ui.button type="submit" variant="primary" icon="check-circle" label="Criar Orçamento" feature="budgets" />
                </div>
            </form>
        </div>
    </x-ui.card>
</x-layout.page-container>
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
        const charCount = description?.parentNode.querySelector('.form-text');
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
