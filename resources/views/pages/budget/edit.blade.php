@extends('layouts.app')

@section('title', 'Editar Orçamento')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Editar Orçamento"
        icon="pencil-square"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            $budget->code => route('provider.budgets.show', $budget->code),
            'Editar' => '#'
        ]">
        <p class="text-muted mb-0">Atualize as informações do orçamento <strong>{{ $budget->code }}</strong></p>
    </x-layout.page-header>

    <form id="edit-budget-form" action="{{ route('provider.budgets.update', $budget->code) }}" method="POST">
        @csrf
        @method('PUT')

        <x-layout.grid-row class="g-4">
            <!-- Informações Básicas -->
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 d-flex align-items-center text-primary fw-bold">
                            <i class="bi bi-info-circle me-2"></i>
                            <span>Informações Básicas</span>
                        </h5>
                    </x-slot:header>
                    
                    <div class="p-2">
                        <div class="row g-3">
                            <!-- Cliente (readonly) -->
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="customer_display" 
                                    label="Cliente" 
                                    :value="$budget->customer->commonData ? ($budget->customer->commonData->company_name ?: ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)) : 'Nome não informado' . ' (' . ($budget->customer->commonData ? ($budget->customer->commonData->cnpj ? \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) : \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf)) : 'Sem documento') . ')'"
                                    disabled 
                                    readonly 
                                />
                                <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                            </div>

                            <!-- Data de Vencimento -->
                            <div class="col-md-3">
                                <x-ui.form.input 
                                    type="date" 
                                    name="due_date" 
                                    label="Data de Vencimento" 
                                    :min="date('Y-m-d')" 
                                    :value="\App\Helpers\DateHelper::formatDateOrDefault(old('due_date', $budget->due_date ? $budget->due_date->format('Y-m-d') : ''), 'Y-m-d', $budget->due_date ? $budget->due_date->format('Y-m-d') : '')" 
                                    required 
                                    help="A data de vencimento deve ser igual ou posterior a hoje."
                                />
                            </div>

                            <!-- Status Atual (readonly) -->
                            <div class="col-md-3">
                                <x-ui.form.input 
                                    name="status_display" 
                                    label="Status Atual" 
                                    :value="$budget->status->label()" 
                                    readonly 
                                    disabled 
                                    help="O status será alterado para 'Pendente' após salvar"
                                />
                                <input type="hidden" name="status" value="{{ $budget->status->value }}">
                            </div>

                            <!-- Descrição -->
                            <div class="col-12">
                                <x-ui.form.textarea 
                                    name="description" 
                                    label="Descrição" 
                                    rows="4" 
                                    maxlength="255" 
                                    placeholder="Ex: Projeto de reforma da cozinha, incluindo instalação de armários e pintura."
                                    help="{{ 255 - strlen($budget->description) }} caracteres restantes"
                                    id="description"
                                >{{ old('description', $budget->description) }}</x-ui.form.textarea>
                            </div>

                            <!-- Condições de Pagamento -->
                            <div class="col-12">
                                <x-ui.form.textarea 
                                    name="payment_terms" 
                                    label="Condições de Pagamento (Opcional)" 
                                    rows="2" 
                                    maxlength="255" 
                                    placeholder="Ex: 50% de entrada e 50% na conclusão."
                                >{{ old('payment_terms', $budget->payment_terms) }}</x-ui.form.textarea>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Valores -->
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 d-flex align-items-center text-success fw-bold">
                            <i class="bi bi-cash-stack me-2"></i>
                            <span>Valores</span>
                        </h5>
                    </x-slot:header>
                    
                    <div class="p-2">
                        <div class="row g-3">
                            <!-- Valor Total -->
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="total_display" 
                                    label="Valor Total" 
                                    :value="\App\Helpers\CurrencyHelper::format(old('total', $budget->total))" 
                                    class="bg-light currency-brl" 
                                    readonly 
                                    tabindex="-1" 
                                />
                                <input type="hidden" id="total" name="total" value="{{ old('total', $budget->total) }}">
                            </div>

                            <!-- Desconto -->
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="discount_display" 
                                    label="Desconto" 
                                    :value="\App\Helpers\CurrencyHelper::format(old('discount', $budget->discount))" 
                                    class="bg-light currency-brl" 
                                    readonly 
                                    tabindex="-1" 
                                />
                                <input type="hidden" id="discount" name="discount" value="{{ old('discount', $budget->discount) }}">
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>

        <!-- Botões -->
        <div class="d-flex justify-content-between align-items-center mt-5">
            <x-ui.back-button index-route="provider.budgets.show" :route-params="[$budget->code]" label="Cancelar" />
            <x-ui.button type="submit" variant="primary" icon="check-circle" label="Salvar Alterações" />
        </div>
    </form>
</x-layout.page-container>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar máscaras de moeda
        if (typeof VanillaMask === 'function') {
            new VanillaMask('total_display', 'currency');
            new VanillaMask('discount_display', 'currency');
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
