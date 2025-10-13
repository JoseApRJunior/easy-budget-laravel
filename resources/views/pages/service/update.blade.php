@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-tools me-2"></i>Editar Serviço
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.services.index' ) }}">Serviços</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route( 'provider.services.show', $service->code ) }}">{{ $service->code }}</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>
        <!-- Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="update-service-form" action="{{ route( 'provider.services.update', $service->id ) }}"
                    method="POST">
                    @csrf
                    @method( 'PUT' )
                    <fieldset {{ !StatusHelper::status_allows_edit( $service->status->slug ) ? 'disabled' : '' }}>

                        <!-- Service Information -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-hash me-2"></i>Código
                                    </label>
                                    <input type="text" class="form-control bg-light" value="{{ $service->code }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-calendar-check me-2"></i>Data de Criação
                                    </label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ DateHelper::formatBR( $service->created_at, 'd/m/Y H:i' ) }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="budget_code" class="form-label fw-semibold">
                                        <i class="bi bi-file-earmark-text me-2"></i>Orçamento
                                    </label>
                                    <input type="text" class="form-control bg-light" value="{{ $service->budget_code }}"
                                        disabled>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category" class="form-label fw-semibold">
                                        <i class="bi bi-bookmark me-2"></i>Categoria
                                    </label>
                                    <select id="category" name="category_id" class="form-select" required>
                                        <option value="">Selecione uma categoria</option>
                                        @foreach ( $categories as $category )
                                            <option value="{{ $category->id }}" {{ $category->id == $service->category_id ? 'selected' : '' }}>
                                                {{ $category->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error( 'category_id' )
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <!-- Due Date -->
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="due_date" class="form-label fw-semibold">
                                        <i class="bi bi-calendar me-2"></i>Previsão de Vencimento
                                    </label>
                                    <input type="date" id="due_date" name="due_date" class="form-control date-input-br"
                                        value="{{ DateHelper::formatBR( $service->due_date, 'Y-m-d' ) }}" required />
                                    @error( 'due_date' )
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Customer and Category Section -->
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-person me-2"></i>Cliente
                                    </label>
                                    <input type="text" class="form-control bg-light" value="{{ $service->customer_name }}"
                                        disabled>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label for="description" class="form-label fw-semibold">
                                        <i class="bi bi-card-text me-2"></i>Descrição
                                    </label>
                                    <textarea id="description" name="description" class="form-control" rows="3"
                                        maxlength="255"
                                        placeholder="Descreva o serviço detalhadamente">{{ $service->description }}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <small id="char-count" class="text-muted mt-2">
                                            <span>{{ 255 - strlen( $service->description ) }}</span> caracteres restantes
                                        </small>
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
                                    <tbody id="items-tbody"></tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-end fw-bold">Total do Serviço:</td>
                                            <td colspan="2" class="fw-bold text-success">
                                                <span id="total-service">R$
                                                    {{ number_format( $service->total, 2, ',', '.' ) }}</span>
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
                    </fieldset>
                </form>
                <!-- Form action buttons and navigation -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <div>
                        <a href="{{ route( 'provider.services.show', $service->code ) }}"
                            class="btn btn-outline-secondary px-4" data-bs-toggle="tooltip"
                            title="Voltar para detalhes sem salvar">
                            <i class="bi bi-x-circle me-2"></i>Cancelar
                        </a>
                    </div>
                    <div>
                        @if ( StatusHelper::status_allows_edit( $service->status->slug ) )
                            <button type="submit" form="update-service-form" class="btn btn-primary px-4"
                                data-bs-toggle="tooltip" title="Salvar as alterações feitas no serviço">
                                <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                            </button>
                        @else
                            <div class="alert alert-info mb-0 py-2 px-3" role="alert" data-bs-toggle="tooltip"
                                title="Este serviço não pode ser editado no status atual: {{ $service->status->name }}.">
                                <i class="bi bi-info-circle-fill me-2"></i>Não Editável ({{ $service->status->name }})
                            </div>
                        @endif
                    </div>
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
                                <button class="btn btn-outline-secondary" type="button" id="search-button">
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

    <!-- Cancel Service Modal -->
    <div class="modal fade" id="cancelServiceModal" tabindex="-1" aria-labelledby="cancelServiceModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelServiceModalLabel">Cancelar Serviço</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja cancelar o serviço <strong>{{ $service->code }}</strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
                    <form action="{{ route( 'provider.services.cancel', $service->id ) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-danger">Confirmar Cancelamento</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    {{-- Mask plugins --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/masks/masks.js' ) }}" type="module"></script>
    <script>
        // Initial data
        const service = @json( $service );
        const serviceItems = @json( $serviceItems );
        const products = @json( $products );
    </script>
    <script src="{{ asset( 'assets/js/service_update.js' ) }}"></script>
@endpush
