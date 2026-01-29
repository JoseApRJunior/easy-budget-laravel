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

    <x-layout.grid-row>
        <x-layout.grid-col size="col-12">
            <x-resource.resource-list-card
                title="Dados do Orçamento"
                icon="file-earmark-text"
                padding="p-4">
                <x-ui.form.form id="edit-budget-form" action="{{ route('provider.budgets.update', $budget->code) }}" method="PUT">

                    <x-layout.grid-row>
                        <!-- Cliente (readonly) -->
                        <x-layout.grid-col size="col-md-6">
                            <x-ui.form.input
                                name="customer_display"
                                label="Cliente"
                                :value="($budget->customer->commonData ? ($budget->customer->commonData->company_name ?: ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)) : 'Nome não informado') . ' (' . ($budget->customer->commonData ? ($budget->customer->commonData->cnpj ? \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj) : \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf)) : 'Sem documento') . ')'"
                                disabled
                                help="O cliente não pode ser alterado."
                                readonly
                                wrapper-class="mb-3" />
                            <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                        </x-layout.grid-col>

                        <!-- Data de Vencimento -->
                        <x-layout.grid-col size="col-md-3">
                            <x-ui.form.input
                                type="date"
                                name="due_date"
                                label="Data de Vencimento"
                                :min="date('Y-m-d')"
                                :value="\App\Helpers\DateHelper::formatDateOrDefault(old('due_date', $budget->due_date ? $budget->due_date->format('Y-m-d') : ''), 'Y-m-d', $budget->due_date ? $budget->due_date->format('Y-m-d') : '')"
                                required
                                help="A data de vencimento deve ser igual ou posterior a hoje."
                                wrapper-class="mb-3" />
                        </x-layout.grid-col>

                        <!-- Status Atual (readonly) -->
                        <x-layout.grid-col size="col-md-3">
                            <x-ui.form.input
                                name="status_display"
                                label="Status Atual"
                                :value="$budget->status->label()"
                                readonly
                                disabled
                                help="O status será alterado para 'Pendente' após salvar"
                                wrapper-class="mb-3" />
                            <input type="hidden" name="status" value="{{ $budget->status->value }}">
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <!-- Descrição e Detalhes -->
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-12">
                            <x-ui.form.textarea
                                name="description"
                                label="Descrição"
                                rows="4"
                                maxlength="255"
                                placeholder="Ex: Projeto de reforma da cozinha, incluindo instalação de armários e pintura."
                                help="{{ 255 - strlen($budget->description) }} caracteres restantes"
                                id="description"
                                :value="old('description', $budget->description)"
                                wrapper-class="mb-3" />
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-12">
                            <x-ui.form.textarea
                                name="payment_terms"
                                label="Condições de Pagamento (Opcional)"
                                rows="2"
                                maxlength="255"
                                placeholder="Ex: 50% de entrada e 50% na conclusão."
                                :value="old('payment_terms', $budget->payment_terms)"
                                wrapper-class="mb-3" />
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <!-- Valores -->
                    <x-layout.grid-row>
                        <x-layout.grid-col size="col-md-6">
                            <x-ui.form.input
                                name="total_display"
                                label="Total (R$)"
                                :value="\App\Helpers\CurrencyHelper::format(old('total', $budget->total))"
                                class="bg-light currency-brl"
                                help="O valor total é calculado com base nos itens do orçamento."
                                readonly
                                tabindex="-1"
                                wrapper-class="mb-3" />
                            <input type="hidden" id="total" name="total" value="{{ old('total', $budget->total) }}">
                        </x-layout.grid-col>

                        <x-layout.grid-col size="col-md-6">
                            <x-ui.form.input
                                name="discount_display"
                                label="Desconto (R$)"
                                :value="\App\Helpers\CurrencyHelper::format(old('discount', $budget->discount))"
                                class="bg-light currency-brl"
                                help="O desconto e somado apartir dos descontos aplicados aos serviços."
                                readonly
                                tabindex="-1"
                                wrapper-class="mb-3" />
                            <input type="hidden" id="discount" name="discount" value="{{ old('discount', $budget->discount) }}">
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    <!-- Botões -->
                    <x-layout.actions-bar alignment="between" class="align-items-center mt-4 pt-3 border-top" mb="0">
                        <x-ui.back-button index-route="provider.budgets.show" :route-params="[$budget->code]" label="Cancelar" />
                        <x-ui.button type="submit" variant="primary" icon="check-circle" label="Salvar Alterações" />
                    </x-layout.actions-bar>
                </x-ui.form.form>
            </x-resource.resource-list-card>
        </x-layout.grid-col>
    </x-layout.grid-row>
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
