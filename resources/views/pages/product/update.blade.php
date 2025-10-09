@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-box me-2"></i>Editar Produto
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.products.index' ) }}">Produtos</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route( 'provider.products.show', $product->id ) }}">{{ $product->code }}</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>

        <form id="updateForm" action="{{ route( 'provider.products.update', $product->id ) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method( 'PUT' )

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
                                <!-- Código do Produto -->
                                <div class="col-md-6">
                                    <label for="code" class="form-label">Código</label>
                                    <input type="text" class="form-control" id="code" value="{{ $product->code }}" disabled>
                                </div>

                                <!-- Nome do Produto -->
                                <div class="col-md-12">
                                    <label for="name" class="form-label">Nome do Produto</label>
                                    <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                        name="name" value="{{ old( 'name', $product->name ) }}" required maxlength="255"
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
                                            id="price" name="price"
                                            value="{{ old( 'price', number_format( $product->price, 2, ',', '.' ) ) }}"
                                            required placeholder="0,00">
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
                                        <option value="1" {{ old( 'active', $product->active ) == 1 ? 'selected' : '' }}>Ativo
                                        </option>
                                        <option value="0" {{ old( 'active', $product->active ) == 0 ? 'selected' : '' }}>
                                            Inativo
                                        </option>
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
                                    @if ( $product->image )
                                        <img id="imagePreview" src="{{ asset( 'storage/products/' . $product->image ) }}"
                                            class="img-fluid rounded" alt="Imagem do produto">
                                    @else
                                        <img id="imagePreview" src="{{ asset( 'assets/img/img_not_found.png' ) }}"
                                            class="img-fluid rounded" alt="Sem imagem">
                                    @endif
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="image" class="form-label">Alterar Imagem</label>
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

                                @if ( $product->image )
                                    <div class="form-check mt-3">
                                        <input class="form-check-input" type="checkbox" id="removeImage" name="remove_image"
                                            value="1">
                                        <label class="form-check-label" for="removeImage">
                                            Remover imagem atual
                                        </label>
                                    </div>
                                @endif
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
                                    style="resize: none;">{{ old( 'description', $product->description ) }}</textarea>
                                <div class="d-flex justify-content-end mt-2">
                                    <small class="text-muted">
                                        <span id="char-count-value"
                                            class="fw-semibold">{{ 500 - strlen( old( 'description', $product->description ) ) }}</span>
                                        caracteres restantes
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
                <a href="{{ route( 'provider.products.show', $product->id ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="updateButton">
                    <i class="bi bi-check-circle me-2"></i>Salvar Alterações
                </button>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/product_update.js' ) }}" type="module"></script>
    <script>
        // Script para desabilitar o campo de upload quando o checkbox de remover imagem está marcado
        document.addEventListener( 'DOMContentLoaded', function () {
            const removeImageCheckbox = document.getElementById( 'removeImage' );
            const imageInput = document.getElementById( 'image' );
            const uploadButton = document.getElementById( 'uploadButton' );

            if ( removeImageCheckbox && imageInput && uploadButton ) {
                removeImageCheckbox.addEventListener( 'change', function () {
                    if ( this.checked ) {
                        imageInput.disabled = true;
                        uploadButton.disabled = true;
                        uploadButton.classList.add( 'btn-secondary' );
                        uploadButton.classList.remove( 'btn-primary' );
                    } else {
                        imageInput.disabled = false;
                        uploadButton.disabled = false;
                        uploadButton.classList.add( 'btn-primary' );
                        uploadButton.classList.remove( 'btn-secondary' );
                    }
                } );
            }
        } );
    </script>
@endpush
