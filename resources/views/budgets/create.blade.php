@extends( 'layouts.app' )

@section( 'title', 'Novo Orçamento' )

@section( 'content' )
    <div class="budget-create">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">Novo Orçamento</h1>
                <p class="text-muted mb-0">Crie um orçamento detalhado para seu cliente</p>
            </div>
            <div>
                <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <form id="budgetForm" method="POST" action="{{ route( 'budgets.store' ) }}">
            @csrf

            <!-- Informações Básicas -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>Informações Básicas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="customer_id" class="form-label">Cliente *</label>
                            <select class="form-select @error( 'customer_id' ) is-invalid @enderror" id="customer_id"
                                name="customer_id" required>
                                <option value="">Selecione um cliente</option>
                                @foreach( $customers as $customer )
                                    <option value="{{ $customer->id }}" {{ old( 'customer_id' ) == $customer->id ? 'selected' : '' }}>
                                        {{ $customer->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error( 'customer_id' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="valid_until" class="form-label">Validade</label>
                            <input type="date" class="form-control" id="valid_until" name="valid_until"
                                value="{{ old( 'valid_until' ) }}" min="{{ date( 'Y-m-d' ) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="global_discount_percentage" class="form-label">Desconto Global (%)</label>
                            <input type="number" class="form-control" id="global_discount_percentage"
                                name="global_discount_percentage" value="{{ old( 'global_discount_percentage', 0 ) }}"
                                step="0.01" min="0" max="100">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control @error( 'description' ) is-invalid @enderror" id="description"
                            name="description" rows="3"
                            placeholder="Descreva o objetivo e escopo deste orçamento">{{ old( 'description' ) }}</textarea>
                        @error( 'description' )
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Itens do Orçamento -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Itens do Orçamento
                    </h5>
                    <button type="button" class="btn btn-primary btn-sm" onclick="addItem()">
                        <i class="bi bi-plus-lg me-2"></i>Adicionar Item
                    </button>
                </div>
                <div class="card-body">
                    <div id="itemsContainer">
                        <!-- Item inicial -->
                        <div class="budget-item border rounded p-3 mb-3" data-item-index="0">
                            <div class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Categoria</label>
                                    <select class="form-select" name="items[0][budget_item_category_id]">
                                        <option value="">Sem categoria</option>
                                        @foreach( $categories as $category )
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Título *</label>
                                    <input type="text" class="form-control" name="items[0][title]"
                                        placeholder="Título do item" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Descrição</label>
                                    <input type="text" class="form-control" name="items[0][description]"
                                        placeholder="Descrição detalhada do item">
                                </div>
                            </div>
                            <div class="row g-3 mt-2">
                                <div class="col-md-2">
                                    <label class="form-label">Quantidade *</label>
                                    <input type="number" class="form-control item-quantity" name="items[0][quantity]"
                                        value="1" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Unidade</label>
                                    <select class="form-select" name="items[0][unit]">
                                        <option value="un">Unidade</option>
                                        <option value="kg">Quilograma</option>
                                        <option value="hora">Hora</option>
                                        <option value="dia">Dia</option>
                                        <option value="mês">Mês</option>
                                        <option value="serviço">Serviço</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Preço Unitário *</label>
                                    <input type="number" class="form-control item-unit-price" name="items[0][unit_price]"
                                        value="0.00" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Desconto (%)</label>
                                    <input type="number" class="form-control item-discount"
                                        name="items[0][discount_percentage]" value="0" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Imposto (%)</label>
                                    <input type="number" class="form-control item-tax" name="items[0][tax_percentage]"
                                        value="0" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem(0)">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <!-- Cálculos do item -->
                            <div class="item-calculations mt-3 p-3 bg-light rounded">
                                <div class="row text-sm">
                                    <div class="col-md-3">
                                        <strong>Total do Item:</strong>
                                        <span class="item-total">R$ 0,00</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Desconto:</strong>
                                        <span class="item-discount-amount">R$ 0,00</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Impostos:</strong>
                                        <span class="item-tax-amount">R$ 0,00</span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Total Líquido:</strong>
                                        <span class="item-net-total text-primary fw-bold">R$ 0,00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resumo do Orçamento -->
                    <div class="budget-summary mt-4 p-4 bg-light rounded">
                        <div class="row">
                            <div class="col-md-3">
                                <h6>Subtotal:</h6>
                                <h3 class="text-primary mb-0" id="summarySubtotal">R$ 0,00</h3>
                            </div>
                            <div class="col-md-3">
                                <h6>Descontos:</h6>
                                <h4 class="text-danger mb-0" id="summaryDiscounts">R$ 0,00</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Impostos:</h6>
                                <h4 class="text-info mb-0" id="summaryTaxes">R$ 0,00</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>Total:</h6>
                                <h2 class="text-success mb-0" id="summaryTotal">R$ 0,00</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações -->
            <div class="d-flex justify-content-between">
                <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Cancelar
                </a>
                <div>
                    <button type="button" class="btn btn-outline-primary me-2" onclick="saveDraft()">
                        <i class="bi bi-save me-2"></i>Salvar Rascunho
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-2"></i>Criar Orçamento
                    </button>
                </div>
            </div>
        </form>
    </div>

    @push( 'scripts' )
        <script>
            let itemIndex = 1;

            function addItem() {
                const container = document.getElementById( 'itemsContainer' );
                const itemHtml = `
                <div class="budget-item border rounded p-3 mb-3" data-item-index="${itemIndex}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Categoria</label>
                            <select class="form-select" name="items[${itemIndex}][budget_item_category_id]">
                                <option value="">Sem categoria</option>
                                @foreach( $categories as $category )
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Título *</label>
                            <input type="text"
                                   class="form-control"
                                   name="items[${itemIndex}][title]"
                                   placeholder="Título do item"
                                   required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Descrição</label>
                            <input type="text"
                                   class="form-control"
                                   name="items[${itemIndex}][description]"
                                   placeholder="Descrição detalhada do item">
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-2">
                            <label class="form-label">Quantidade *</label>
                            <input type="number"
                                   class="form-control item-quantity"
                                   name="items[${itemIndex}][quantity]"
                                   value="1"
                                   step="0.01"
                                   min="0.01"
                                   required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Unidade</label>
                            <select class="form-select" name="items[${itemIndex}][unit]">
                                <option value="un">Unidade</option>
                                <option value="kg">Quilograma</option>
                                <option value="hora">Hora</option>
                                <option value="dia">Dia</option>
                                <option value="mês">Mês</option>
                                <option value="serviço">Serviço</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Preço Unitário *</label>
                            <input type="number"
                                   class="form-control item-unit-price"
                                   name="items[${itemIndex}][unit_price]"
                                   value="0.00"
                                   step="0.01"
                                   min="0"
                                   required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Desconto (%)</label>
                            <input type="number"
                                   class="form-control item-discount"
                                   name="items[${itemIndex}][discount_percentage]"
                                   value="0"
                                   step="0.01"
                                   min="0"
                                   max="100">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Imposto (%)</label>
                            <input type="number"
                                   class="form-control item-tax"
                                   name="items[${itemIndex}][tax_percentage]"
                                   value="0"
                                   step="0.01"
                                   min="0"
                                   max="100">
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger w-100" onclick="removeItem(${itemIndex})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Cálculos do item -->
                    <div class="item-calculations mt-3 p-3 bg-light rounded">
                        <div class="row text-sm">
                            <div class="col-md-3">
                                <strong>Total do Item:</strong>
                                <span class="item-total">R$ 0,00</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Desconto:</strong>
                                <span class="item-discount-amount">R$ 0,00</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Impostos:</strong>
                                <span class="item-tax-amount">R$ 0,00</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Total Líquido:</strong>
                                <span class="item-net-total text-primary fw-bold">R$ 0,00</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;

                container.insertAdjacentHTML( 'beforeend', itemHtml );
                itemIndex++;

                // Adicionar eventos de cálculo
                attachCalculationEvents();
            }

            function removeItem( index ) {
                const item = document.querySelector( `[data-item-index="${index}"]` );
                if ( item ) {
                    item.remove();
                    calculateTotals();
                }
            }

            function attachCalculationEvents() {
                // Eventos para cálculos em tempo real
                document.querySelectorAll( '.item-quantity, .item-unit-price, .item-discount, .item-tax' ).forEach( input => {
                    input.addEventListener( 'input', calculateTotals );
                } );

                // Evento para desconto global
                document.getElementById( 'global_discount_percentage' ).addEventListener( 'input', calculateTotals );
            }

            function calculateTotals() {
                let subtotal = 0;
                let totalDiscounts = 0;
                let totalTaxes = 0;

                // Calcular cada item
                document.querySelectorAll( '.budget-item' ).forEach( item => {
                    const quantity = parseFloat( item.querySelector( '.item-quantity' ).value ) || 0;
                    const unitPrice = parseFloat( item.querySelector( '.item-unit-price' ).value ) || 0;
                    const discount = parseFloat( item.querySelector( '.item-discount' ).value ) || 0;
                    const tax = parseFloat( item.querySelector( '.item-tax' ).value ) || 0;

                    const itemTotal = quantity * unitPrice;
                    const itemDiscountAmount = itemTotal * ( discount / 100 );
                    const subtotalAfterDiscount = itemTotal - itemDiscountAmount;
                    const itemTaxAmount = subtotalAfterDiscount * ( tax / 100 );
                    const itemNetTotal = subtotalAfterDiscount + itemTaxAmount;

                    // Atualizar cálculos do item
                    item.querySelector( '.item-total' ).textContent = formatCurrency( itemTotal );
                    item.querySelector( '.item-discount-amount' ).textContent = formatCurrency( itemDiscountAmount );
                    item.querySelector( '.item-tax-amount' ).textContent = formatCurrency( itemTaxAmount );
                    item.querySelector( '.item-net-total' ).textContent = formatCurrency( itemNetTotal );

                    // Somar totais
                    subtotal += itemTotal;
                    totalDiscounts += itemDiscountAmount;
                    totalTaxes += itemTaxAmount;
                } );

                // Aplicar desconto global
                const globalDiscount = parseFloat( document.getElementById( 'global_discount_percentage' ).value ) || 0;
                const globalDiscountAmount = subtotal * ( globalDiscount / 100 );
                totalDiscounts += globalDiscountAmount;
                subtotal -= globalDiscountAmount;

                const grandTotal = subtotal + totalTaxes;

                // Atualizar resumo
                document.getElementById( 'summarySubtotal' ).textContent = formatCurrency( subtotal );
                document.getElementById( 'summaryDiscounts' ).textContent = formatCurrency( totalDiscounts );
                document.getElementById( 'summaryTaxes' ).textContent = formatCurrency( totalTaxes );
                document.getElementById( 'summaryTotal' ).textContent = formatCurrency( grandTotal );
            }

            function formatCurrency( value ) {
                return 'R$ ' + value.toLocaleString( 'pt-BR', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                } );
            }

            function saveDraft() {
                // Marcar como rascunho e salvar
                const form = document.getElementById( 'budgetForm' );
                const statusInput = document.createElement( 'input' );
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = 'rascunho';
                form.appendChild( statusInput );

                form.submit();
            }

            // Inicializar cálculos
            document.addEventListener( 'DOMContentLoaded', function () {
                attachCalculationEvents();
                calculateTotals();
            } );
        </script>
    @endpush
@endsection
