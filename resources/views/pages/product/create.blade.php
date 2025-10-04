@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-box me-2"></i>Criar Novo Produto
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.products.index' ) }}">Produtos</a></li>
                    <li class="breadcrumb-item active">Novo</li>
                </ol>
            </nav>
        </div>

        <form id="createForm" action="{{ route( 'provider.products.store' ) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <!-- Informações Básicas -->
                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2"></i>Informações Básicas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Nome do Produto -->
                                <div class="col-md-12">
                                    <label for="name" class="form-label">Nome do Produto</label>
                                    <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                        name="name" value="{{ old( 'name' ) }}" required maxlength="255"
                                        placeholder="Nome do produto">
                                    @error( 'name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Preço -->
                                <div class="col-md-4">
                                    <label for="price" class="form-label">Preço</label>
                                    <div class="input-group">
                                        <input type="text"
                                            class="form-control money-input @error( 'price' ) is-invalid @enderror"
                                            id="price" name="price" value="{{ old( 'price', '0,00' ) }}" required
                                            placeholder="0,00">
                                    </div>
                                    @error( 'price' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Status -->
                                <div class="col-md-4">
                                    <label for="active" class="form-label">Status</label>
                                    <select class="form-select @error( 'active' ) is-invalid @enderror" id="active"
                                        name="active" required>
                                        <option value="1" {{ old( 'active', 1 ) == 1 ? 'selected' : '' }}>Ativo</option>
                                        <option value="0" {{ old( 'active' ) == 0 ? 'selected' : '' }}>Inativo</option>
                                    </select>
                                    @error( 'active' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Imagem do Produto -->
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-image me-2"></i>Imagem do Produto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="image-preview-container"
                                    style="border: 1px dashed #ccc; background-color: #f8f9fa; width: 120px; height: 120px;">
                                    <img id="imagePreview" src="{{ asset( 'assets/img/img_not_found.png' ) }}"
                                        class="img-fluid rounded" alt="Preview da imagem">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Selecionar Imagem</label>
                                <div class="position-relative">
                                    <input type="file" class="form-control @error( 'image' ) is-invalid @enderror"
                                        id="image" name="image" accept="image/jpeg,image/png" style="padding-right: 120px;">
                                    <button type="button" id="uploadButton" class="btn btn-sm btn-primary position-absolute"
                                        style="right: 5px; top: 5px; z-index: 5;">
                                        <i class="bi bi-cloud-arrow-up me-1"></i>Escolher
                                    </button>
                                </div>
                                @error( 'image' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text mt-2">
                                    <small><i class="bi bi-info-circle me-1"></i>Formatos aceitos: JPG, PNG. Tamanho
                                        máximo: 2MB</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-text me-2"></i>Descrição do Produto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <textarea class="form-control @error( 'description' ) is-invalid @enderror" id="description"
                                    name="description" rows="4" maxlength="500" placeholder="Descreva o produto..."
                                    style="resize: none;">{{ old( 'description' ) }}</textarea>
                                <div class="d-flex justify-content-end mt-2">
                                    <small class="text-muted">
                                        <span id="char-count-value" class="fw-semibold">500</span> caracteres restantes
                                    </small>
                                </div>
                                @error( 'description' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="createButton">
                    <i class="bi bi-check-circle me-2"></i>Criar Produto
                </button>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/product_create.js' ) }}" type="module"></script>
@endpush
