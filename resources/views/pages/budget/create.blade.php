@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-plus me-2"></i>
                Novo Orçamento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'budget.index' ) }}">Orçamentos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Novo</li>
                </ol>
            </nav>
        </div>

        <!-- Budget Creation Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="create-budget-form" action="{{ route( 'budget.store' ) }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Client Search -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="customer_search" class="form-label fw-semibold">
                                    <i class="bi bi-person-check me-2"></i>Cliente
                                </label>
                                <div class="input-group">
                                    <input type="text" id="customer_search" name="customer_name" class="form-control"
                                        placeholder="Digite para buscar..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="clear-customer-btn"
                                        style="display: none;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="customer_id" name="customer_id">
                                <div id="customer-search-results" class="list-group position-absolute w-auto"
                                    style="z-index: 1000;"></div>
                                @error( 'customer_id' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Due Date -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="due_date" class="form-label fw-semibold">
                                    <i class="bi bi-calendar-event me-2"></i>Data de Vencimento
                                </label>
                                <input type="date" id="due_date" name="due_date" class="form-control"
                                    value="{{ old( 'due_date' ) }}" required>
                                @error( 'due_date' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <div class="form-group">
                                <label for="description" class="form-label fw-semibold">
                                    <i class="bi bi-card-text me-2"></i>Descrição
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="4" maxlength="255"
                                    placeholder="Ex: Projeto de reforma da cozinha, incluindo instalação de armários e pintura.">{{ old( 'description' ) }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small id="char-count" class="text-muted mt-2">255 caracteres restantes</small>
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
                                    placeholder="Ex: 50% de entrada e 50% na conclusão.">{{ old( 'payment_terms' ) }}</textarea>
                                @error( 'payment_terms' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                        <a href="{{ route( 'budget.index' ) }}" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-2"></i>Salvar Orçamento
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

            // Customer search functionality
            const customerSearch = document.getElementById( 'customer_search' );
            const customerId = document.getElementById( 'customer_id' );
            const searchResults = document.getElementById( 'customer-search-results' );
            const clearCustomerBtn = document.getElementById( 'clear-customer-btn' );

            if ( customerSearch && customerId && searchResults && clearCustomerBtn ) {
                customerSearch.addEventListener( 'keyup', function () {
                    const query = this.value;
                    if ( query.length < 2 ) {
                        searchResults.innerHTML = '';
                        return;
                    }

                    fetch( `{{ route( 'api.customer.search' ) }}?q=${query}` )
                        .then( response => response.json() )
                        .then( data => {
                            searchResults.innerHTML = '';
                            if ( data.length > 0 ) {
                                data.forEach( customer => {
                                    const item = document.createElement( 'a' );
                                    item.href = '#';
                                    item.classList.add( 'list-group-item', 'list-group-item-action' );
                                    item.textContent = `${customer.first_name} ${customer.last_name} (${customer.cpf || customer.cnpj})`;
                                    item.addEventListener( 'click', function ( e ) {
                                        e.preventDefault();
                                        customerSearch.value = item.textContent;
                                        customerId.value = customer.id;
                                        searchResults.innerHTML = '';
                                        clearCustomerBtn.style.display = 'block';
                                        customerSearch.disabled = true;
                                    } );
                                    searchResults.appendChild( item );
                                } );
                            } else {
                                const noResult = document.createElement( 'span' );
                                noResult.classList.add( 'list-group-item' );
                                noResult.textContent = 'Nenhum cliente encontrado';
                                searchResults.appendChild( noResult );
                            }
                        } );
                } );

                clearCustomerBtn.addEventListener( 'click', function () {
                    customerSearch.value = '';
                    customerId.value = '';
                    this.style.display = 'none';
                    customerSearch.disabled = false;
                    customerSearch.focus();
                } );

                document.addEventListener( 'click', function ( e ) {
                    if ( !customerSearch.contains( e.target ) && !searchResults.contains( e.target ) ) {
                        searchResults.innerHTML = '';
                    }
                } );
            }
        } );
    </script>
@endpush
