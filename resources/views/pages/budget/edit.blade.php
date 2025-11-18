@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square me-2"></i>
                Editar Orçamento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.budgets.index' ) }}">Orçamentos</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.budgets.show', $budget->code ) }}">{{ $budget->code }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
        </div>

        <!-- Budget Edit Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="edit-budget-form" action="{{ route( 'provider.budgets.update', $budget->code ) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-4">
                        <!-- Client Display -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_display" class="form-label fw-semibold">
                                    <i class="bi bi-person-check me-2"></i>Cliente
                                </label>
                                <input type="text" id="customer_display" class="form-control" 
                                    value="{{ $budget->customer->commonData ? ($budget->customer->commonData->company_name ?: ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)) : 'Nome não informado' }} ({{ $budget->customer->commonData ? ($budget->customer->commonData->cnpj ?: $budget->customer->commonData->cpf) : 'Sem documento' }})" 
                                    disabled readonly>
                                <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="due_date" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-event me-2"></i>Data de Vencimento
                                </label>
                                <input type="date" id="due_date" name="due_date" class="form-control"
                                    value="{{ old( 'due_date', $budget->due_date ? $budget->due_date->format('Y-m-d') : '' ) }}" required>
                                @error( 'due_date' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Current Status (Readonly) -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="form-label fw-semibold">
                                    <i class="bi bi-info-circle me-2"></i>Status Atual
                                </label>
                                <input type="text" class="form-control" value="{{ $budget->status->label() }}" readonly disabled>
                                <input type="hidden" name="status" value="{{ $budget->status->value }}">
                                <small class="text-muted">O status será alterado para "Pendente" após salvar</small>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="description" class="form-label fw-semibold">
                                    <i class="bi bi-card-text me-2"></i>Descrição
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="4" maxlength="255"
                                    placeholder="Ex: Projeto de reforma da cozinha, incluindo instalação de armários e pintura.">{{ old( 'description', $budget->description ) }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small id="char-count" class="text-muted mt-2">{{ 255 - strlen($budget->description) }} caracteres restantes</small>
                                </div>
                                @error( 'description' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Payment Terms -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="payment_terms" class="form-label fw-semibold">
                                    <i class="bi bi-credit-card me-2"></i>Condições de Pagamento (Opcional)
                                </label>
                                <textarea id="payment_terms" name="payment_terms" class="form-control" rows="2"
                                    maxlength="255"
                                    placeholder="Ex: 50% de entrada e 50% na conclusão.">{{ old( 'payment_terms', $budget->payment_terms ) }}</textarea>
                                @error( 'payment_terms' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="total" class="form-label fw-semibold">
                                    <i class="bi bi-currency-dollar me-2"></i>Valor Total
                                </label>
                                <input type="number" id="total" name="total" class="form-control" 
                                    value="{{ old( 'total', $budget->total ) }}" step="0.01" min="0">
                                @error( 'total' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Discount -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="discount" class="form-label fw-semibold">
                                    <i class="bi bi-percent me-2"></i>Desconto (%)
                                </label>
                                <input type="number" id="discount" name="discount" class="form-control" 
                                    value="{{ old( 'discount', $budget->discount ) }}" step="0.01" min="0" max="100">
                                @error( 'discount' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                        <a href="{{ route( 'provider.budgets.show', $budget->code ) }}" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-2"></i>Atualizar Orçamento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Character counter for description
            const description = document.getElementById( 'description' );
            const charCount = document.getElementById( 'char-count' );
            const maxLength = 255;

            if ( description && charCount ) {
                description.addEventListener( 'input', function () {
                    const remaining = maxLength - this.value.length;
                    charCount.textContent = remaining + ' caracteres restantes';
                } );
            }
        } );
    </script>
@endpush