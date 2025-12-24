@extends('layouts.app')

@section('title', 'Editar Produto: ' . $product->name)

@section('content')
    <div class="container-fluid py-1">
        <x-page-header 
            title="Editar Produto" 
            icon="pencil-square" 
            :breadcrumb-items="[
                'Produtos' => route('provider.products.index'),
                $product->name => route('provider.products.show', $product->sku),
                'Editar' => '#'
            ]"
        >
            <p class="text-muted mb-0">Atualize as informações do produto</p>
        </x-page-header>

        <form action="{{ route('provider.products.update', $product->sku) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <!-- Informações do Produto -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="bi bi-box me-2"></i>Informações do Produto
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nome -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome do Produto <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $product->name) }}"
                                            required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- SKU (Visualização Apenas) -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control" id="sku" name="sku"
                                            value="{{ $product->sku }}" readonly disabled>
                                        <div class="form-text">Código único - não editável</div>
                                    </div>
                                </div>

                                <!-- Preço -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Preço <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-text">R$</div>
                                            <input type="text" class="form-control @error('price') is-invalid @enderror"
                                                id="price" name="price"
                                                value="{{ old('price', number_format((float) $product->price, 2, ',', '.')) }}"
                                                inputmode="numeric" required>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Categoria -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoria</label>
                                        <select class="form-select @error('category_id') is-invalid @enderror"
                                            id="category_id" name="category_id">
                                            <option value="">Selecione uma categoria</option>
                                            @foreach ($categories as $category)
                                                @if ($category->parent_id === null)
                                                    @if ($category->children->isEmpty())
                                                        <option value="{{ $category->id }}"
                                                            {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @else
                                                        <optgroup label="{{ $category->name }}">
                                                            <option value="{{ $category->id }}"
                                                                {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }} (Geral)
                                                            </option>
                                                            @foreach ($category->children as $subcategory)
                                                                <option value="{{ $subcategory->id }}"
                                                                    {{ old('category_id', $product->category_id) == $subcategory->id ? 'selected' : '' }}>
                                                                    {{ $subcategory->name }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('category_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <div class="form-check form-switch mt-2">
                                            <input type="hidden" name="active" value="0">
                                            <input class="form-check-input" type="checkbox" id="active" name="active"
                                                value="1" {{ old('active', $product->active) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="active">
                                                Produto ativo
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descrição -->
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Descrição</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                            rows="3" placeholder="Detalhe o produto">{{ old('description', $product->description) }}</textarea>
                                        @error('description')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Imagem -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="bi bi-image me-2"></i>Imagem do Produto
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Imagem Atual -->
                            @if ($product->image)
                                <div class="mb-3">
                                    <label class="form-label">Imagem Atual</label>
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                            class="img-thumbnail me-3"
                                            style="width: 100px; height: 100px; object-fit: cover;">
                                        <div>
                                            <p class="mb-2">Imagem atual do produto</p>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="remove_image"
                                                    name="remove_image" value="1">
                                                <label class="form-check-label text-danger" for="remove_image">
                                                    Remover imagem atual
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Nova Imagem -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Nova Imagem</label>
                                <input type="file" class="form-control @error('image') is-invalid @enderror"
                                    id="image" name="image" accept="image/*">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Formatos: JPG, PNG, GIF. Máximo: 2MB</div>
                            </div>

                            <!-- Preview -->
                            <div class="mb-3">
                                <label class="form-label">Preview</label>
                                <div id="image-preview" class="text-center">
                                    <div class="bg-light d-flex align-items-center justify-content-center"
                                        style="width: 150px; height: 150px; border: 2px dashed #dee2e6; border-radius: 5px; margin: 0 auto;">
                                        <small class="text-muted">Selecione uma nova imagem</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <div>
                    <x-back-button index-route="provider.products.index" label="Cancelar" />
                </div>
                <x-button type="submit" icon="check-circle" label="Salvar" />
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Máscara para preço
        if (window.VanillaMask) {
            new VanillaMask('price', 'currency');
        } else {
            const priceInput = document.getElementById('price');
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    const digits = this.value.replace(/\D/g, '');
                    const num = (parseInt(digits || '0', 10) / 100);
                    const integer = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                    const cents = Math.round((num - Math.floor(num)) * 100).toString().padStart(2, '0');
                    this.value = integer + ',' + cents;
                });
            }
        }

        // Converter para decimal no submit
        const productForm = document.querySelector('form[action*="products"]');
        if (productForm) {
            productForm.addEventListener('submit', function(e) {
                const price = document.getElementById('price');
                if (price) {
                    let num = 0;
                    if (window.parseCurrencyBRLToNumber) {
                        num = window.parseCurrencyBRLToNumber(price.value) || 0;
                    } else {
                        num = parseFloat((price.value || '0').replace(/\./g, '').replace(',', '.').replace(
                            /[^0-9\.]/g,
                            '')) || 0;
                    }
                    price.value = num.toFixed(2);
                }
            });
        }

        // Preview da nova imagem
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('image-preview');

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `
                            <img src="${e.target.result}" alt="Preview"
                                 style="width: 150px; height: 150px; object-fit: cover; border-radius: 5px;">
                        `;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `
                        <div class="bg-light d-flex align-items-center justify-content-center"
                             style="width: 150px; height: 150px; border: 2px dashed #dee2e6; border-radius: 5px; margin: 0 auto;">
                            <small class="text-muted">Selecione uma nova imagem</small>
                        </div>
                    `;
                }
            });
        }

        // Confirmação para remoção de imagem
        const removeImageCheckbox = document.getElementById('remove_image');
        if (removeImageCheckbox) {
            removeImageCheckbox.addEventListener('change', function(e) {
                if (e.target.checked) {
                    if (!confirm(
                            'Tem certeza que deseja remover a imagem atual? Esta ação não pode ser desfeita.')) {
                        e.target.checked = false;
                    }
                }
            });
        }
    </script>
@endpush
