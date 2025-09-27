@extends( 'layouts.app' )

@section( 'title', 'Novo Orçamento - Easy Budget' )

@section( 'content' )
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left me-1"></i>
                    Voltar
                </a>
                <h1 class="h3 mb-0">
                    <i class="bi bi-plus-circle text-primary me-2"></i>
                    Criar Novo Orçamento
                </h1>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route( 'budgets.store' ) }}">
                        @csrf

                        <div class="row">
                            <!-- Código -->
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">
                                    Código do Orçamento
                                </label>
                                <input type="text" class="form-control @error( 'code' ) is-invalid @enderror" id="code"
                                    name="code" value="{{ old( 'code' ) }}" placeholder="ORC-001">
                                <div class="form-text">Deixe em branco para gerar automaticamente</div>
                                @error( 'code' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_name" class="form-label">
                                    Nome do Cliente <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error( 'client_name' ) is-invalid @enderror"
                                    id="client_name" name="client_name" value="{{ old( 'client_name' ) }}" required
                                    placeholder="Nome completo do cliente">
                                @error( 'client_name' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email do Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_email" class="form-label">
                                    Email do Cliente
                                </label>
                                <input type="email" class="form-control @error( 'client_email' ) is-invalid @enderror"
                                    id="client_email" name="client_email" value="{{ old( 'client_email' ) }}"
                                    placeholder="cliente@exemplo.com">
                                @error( 'client_email' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Telefone do Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_phone" class="form-label">
                                    Telefone do Cliente
                                </label>
                                <input type="tel" class="form-control @error( 'client_phone' ) is-invalid @enderror"
                                    id="client_phone" name="client_phone" value="{{ old( 'client_phone' ) }}"
                                    placeholder="(11) 99999-9999">
                                @error( 'client_phone' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Valor -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">
                                    Valor <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control @error( 'amount' ) is-invalid @enderror" id="amount" name="amount"
                                        value="{{ old( 'amount' ) }}" required placeholder="1500.00">
                                </div>
                                @error( 'amount' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Usuário Responsável -->
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">
                                    Usuário Responsável
                                </label>
                                <select class="form-select @error( 'user_id' ) is-invalid @enderror" id="user_id"
                                    name="user_id">
                                    <option value="">Selecionar usuário...</option>
                                    @foreach( \App\Models\User::where( 'status', 'active' )->get() as $userOption )
                                        <option value="{{ $userOption->id }}" {{ old( 'user_id' ) == $userOption->id ? 'selected' : '' }}>
                                            {{ $userOption->name }} - {{ $userOption->email }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text">Deixe em branco para usar o usuário atual</div>
                                @error( 'user_id' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error( 'status' ) is-invalid @enderror" id="status" name="status">
                                <option value="draft" {{ old( 'status', 'draft' ) === 'draft' ? 'selected' : '' }}>Rascunho
                                </option>
                                <option value="pending" {{ old( 'status' ) === 'pending' ? 'selected' : '' }}>Pendente</option>
                                <option value="approved" {{ old( 'status' ) === 'approved' ? 'selected' : '' }}>Aprovado
                                </option>
                                <option value="rejected" {{ old( 'status' ) === 'rejected' ? 'selected' : '' }}>Rejeitado
                                </option>
                            </select>
                            @error( 'status' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Observações -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                Observações
                            </label>
                            <textarea class="form-control @error( 'notes' ) is-invalid @enderror" id="notes" name="notes"
                                rows="4" placeholder="Observações sobre o orçamento...">{{ old( 'notes' ) }}</textarea>
                            @error( 'notes' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Itens do Orçamento -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul text-primary me-2"></i>
                                    Itens do Orçamento
                                </h5>
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-item">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    Adicionar Item
                                </button>
                            </div>

                            <div id="budget-items">
                                <div class="budget-item border rounded p-3 mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="form-label">Descrição</label>
                                            <input type="text" name="items[0][description]" class="form-control"
                                                placeholder="Descrição do item">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Quantidade</label>
                                            <input type="number" name="items[0][quantity]" class="form-control" min="1"
                                                value="1" step="0.01">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Valor Unitário</label>
                                            <input type="number" name="items[0][unit_price]" class="form-control"
                                                step="0.01" min="0" placeholder="0.00">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Total</label>
                                            <input type="number" class="form-control bg-light" readonly>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="button" class="btn btn-outline-danger w-100 remove-item"
                                                style="display: none;">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-9"></div>
                                <div class="col-md-3">
                                    <div class="border-top pt-2">
                                        <strong>Total: R$ <span id="total-amount">0,00</span></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </a>
                            <div>
                                <button type="button" class="btn btn-outline-info me-2" onclick="saveDraft()">
                                    <i class="bi bi-file-earmark me-2"></i>
                                    Salvar Rascunho
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Criar Orçamento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script>
        let itemCount = 1;

        // Adicionar novo item
        document.getElementById( 'add-item' ).addEventListener( 'click', function () {
            const itemsContainer = document.getElementById( 'budget-items' );
            const newItem = itemsContainer.querySelector( '.budget-item' ).cloneNode( true );

            // Atualizar nomes dos campos
            const inputs = newItem.querySelectorAll( 'input' );
            inputs.forEach( input => {
                const name = input.name.replace( '[0]', '[' + itemCount + ']' );
                input.name = name;
                input.value = '';
            } );

            // Mostrar botão de remover
            newItem.querySelector( '.remove-item' ).style.display = 'block';

            itemsContainer.appendChild( newItem );
            itemCount++;

            // Recalcular totais
            calculateTotals();
        } );

        // Remover item
        document.addEventListener( 'click', function ( e ) {
            if ( e.target.classList.contains( 'remove-item' ) || e.target.closest( '.remove-item' ) ) {
                e.target.closest( '.budget-item' ).remove();
                calculateTotals();
            }
        } );

        // Calcular total do item
        document.addEventListener( 'input', function ( e ) {
            if ( e.target.name && e.target.name.includes( '[quantity]' ) || e.target.name.includes( '[unit_price]' ) ) {
                const item = e.target.closest( '.budget-item' );
                const quantity = parseFloat( item.querySelector( 'input[name*="[quantity]"]' ).value ) || 0;
                const unitPrice = parseFloat( item.querySelector( 'input[name*="[unit_price]"]' ).value ) || 0;
                const total = quantity * unitPrice;

                item.querySelector( 'input[readonly]' ).value = total.toFixed( 2 );
                calculateTotals();
            }
        } );

        // Calcular total geral
        function calculateTotals() {
            const items = document.querySelectorAll( '.budget-item' );
            let total = 0;

            items.forEach( item => {
                const itemTotal = parseFloat( item.querySelector( 'input[readonly]' ).value ) || 0;
                total += itemTotal;
            } );

            document.getElementById( 'total-amount' ).textContent = total.toFixed( 2 ).replace( '.', ',' );
            document.getElementById( 'amount' ).value = total.toFixed( 2 );
        }

        // Salvar como rascunho
        function saveDraft() {
            document.getElementById( 'status' ).value = 'draft';
            document.querySelector( 'form' ).submit();
        }

        // Máscaras para telefone
        document.getElementById( 'client_phone' ).addEventListener( 'input', function ( e ) {
            let value = e.target.value.replace( /\D/g, '' );
            if ( value.length <= 11 ) {
                if ( value.length <= 10 ) {
                    value = value.replace( /(\d{2})(\d{4})(\d{4})/, '($1) $2-$3' );
                } else {
                    value = value.replace( /(\d{2})(\d{5})(\d{4})/, '($1) $2-$3' );
                }
            }
            e.target.value = value;
        } );
    </script>
@endsection
