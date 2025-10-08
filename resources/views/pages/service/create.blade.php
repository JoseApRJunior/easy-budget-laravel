@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-tools me-2"></i>Novo Serviço
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.services.index' ) }}">Serviços</a></li>
                    <li class="breadcrumb-item active">Novo</li>
                </ol>
            </nav>
        </div>
        <!-- Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="create-service-form" action="{{ route( 'provider.services.store' ) }}" method="POST">
                    @csrf
                    <!-- Budget search -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="budget_code" class="form-label fw-semibold">
                                    <i class="bi bi-search me-2"></i>Buscar Orçamento (Opcional)
                                </label>
                                <div class="input-group">
                                    <input type="text" id="budget_code" name="budget_code" class="form-control"
                                        placeholder="Digite o código do orçamento">
                                    <button class="btn btn-outline-secondary" type="button" id="search-budget-button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                                <div id="budget-feedback" class="mt-2"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Service information -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="customer_name" class="form-label fw-semibold">
                                    <i class="bi bi-person me-2"></i>Cliente
                                </label>
                                <input type="text" id="customer_name" name="customer_name" class="form-control"
                                    placeholder="Nome do cliente" required>
                                @error( 'customer_name' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category" class="form-label fw-semibold">
                                    <i class="bi bi-bookmark me-2"></i>Categoria
                                </label>
                                <select id="category" name="category_id" class="form-select" required>
                                    <option value="">Selecione uma categoria</option>
                                    @foreach ( $categories as $category )
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error( 'category_id' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="due_date" class="form-label fw-semibold">
                                    <i class="bi bi-calendar me-2"></i>Previsão de Vencimento
                                </label>
                                <input type="date" id="due_date" name="due_date" class="form-control" required>
                                @error( 'due_date' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description" class="form-label fw-semibold">
                                    <i class="bi bi-card-text me-2"></i>Descrição
                                </label>
                                <textarea id="description" name="description" class="form-control" rows="3" maxlength="255"
                                    placeholder="Descreva o serviço detalhadamente">{{ old( 'description' ) }}</textarea>
                                <div class="d-flex justify-content-end">
                                    <small id="char-count" class="text-muted mt-2">255 caracteres restantes</small>
                                </div>
                                @error( 'description' )
                                    <div class="text-danger mt-1">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Service Items -->
                    <div class="mt-5">
                        <h5 class="mb-3">
                            <i class="bi bi-list-check me-2"></i>Itens do Serviço
                        </h5>
                        <div class="table-responsive">
                            <table id="items-table" class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 5%">#</th>
                                        <th style="width: 10%">Código</th>
                                        <th style="width: 30%">Produto</th>
                                        <th style="width: 15%">Valor Unitário</th>
                                        <th style="width: 10%">Quantidade</th>
                                        <th style="width: 15%">Total</th>
                                        <th style="width: 15%">Ações</th>
                                    </tr>
                                </thead>
                                <tbody id="items-tbody">
                                    <!-- Items will be added here via JavaScript -->
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-end fw-bold">Total do Serviço:</td>
                                        <td colspan="2" class="fw-bold text-success">
                                            <span id="total-service">R$ 0,00</span>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="mt-3">
                            <button type="button" id="add-item-button" class="btn btn-outline-primary">
                                <i class="bi bi-plus-circle me-2"></i>Adicionar Item
                            </button>
                        </div>
                    </div>
                </form>
                <!-- Form action buttons -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                    <button type="submit" form="create-service-form" class="btn btn-primary px-4">
                        <i class="bi bi-check-lg me-2"></i>Salvar Serviço
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addItemModalLabel">Adicionar Item ao Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="input-group">
                                <input type="text" id="product-search" class="form-control"
                                    placeholder="Buscar produto por nome ou código...">
                                <button class="btn btn-outline-secondary" type="button" id="search-product-button">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-hover" id="products-table">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Código</th>
                                    <th>Nome</th>
                                    <th>Preço</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody id="products-list">
                                <!-- Products will be loaded here -->
                            </tbody>
                        </table>
                    </div>
                    <div id="selected-product-form" class="mt-4 d-none">
                        <h6 class="mb-3">Produto Selecionado</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="selected-product-name" class="form-label">Nome</label>
                                    <input type="text" id="selected-product-name" class="form-control" readonly>
                                    <input type="hidden" id="selected-product-id">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="selected-product-price" class="form-label">Preço</label>
                                    <input type="text" id="selected-product-price" class="form-control money-input"
                                        readonly>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="selected-product-quantity" class="form-label">Quantidade</label>
                                    <input type="number" id="selected-product-quantity" class="form-control" value="1"
                                        min="1">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="selected-product-total" class="form-label">Total</label>
                                    <input type="text" id="selected-product-total" class="form-control money-input"
                                        readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirm-add-item" disabled>Adicionar Item</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <!-- Mask plugins -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/masks/masks.js' ) }}" type="module"></script>
    <script>
        // Initial data
        const products = @json( $products );
        const budgetSearchUrl = '{{ route( "provider.budgets.search", [ "code" => ":code" ] ) }}';
    </script>
    <script src="{{ asset( 'assets/js/service_create.js' ) }}"></script>
@endpush
