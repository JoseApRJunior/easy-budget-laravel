@extends('layouts.app')

@section('title', 'Editar Produto: ' . $product->name)

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Editar Produto
                </h1>
                <p class="text-muted mb-0">Atualize as informações do produto</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.index') }}">Produtos</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.show', $product->sku) }}">{{ $product->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route('provider.products.update', $product->sku) }}" method="POST"
            enctype="multipart/form-data">
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

                                <!-- SKU -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="sku" class="form-label">SKU</label>
                                        <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                            id="sku" name="sku" value="{{ old('sku', $product->sku) }}">
                                        @error('sku')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Código único para identificação</div>
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
                                                value="{{ old('price', number_format($product->price, 2, ',', '.')) }}"
                                                inputmode="numeric" required>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Categoria Principal -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parent_category_id" class="form-label">Categoria Principal</label>
                                        <select class="form-select" id="parent_category_id">
                                            <option value="">Selecione uma categoria</option>
                                            @foreach ($categories->whereNull('parent_id') as $category)
                                                @php($isSelected = $product->category && ($product->category->parent_id == $category->id || $product->category_id == $category->id))
                                                <option value="{{ $category->id }}" {{ $isSelected ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Subcategoria -->
                                <div class="col-md-6" id="subcategory-wrapper">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Subcategoria</label>
                                        <select class="form-select @error('category_id') is-invalid @enderror"
                                            id="category_id" name="category_id" {{ $product->category && $product->category->parent_id ? '' : 'disabled' }}>
                                            @if($product->category && $product->category->parent_id)
                                                <option value="">Selecione uma subcategoria</option>
                                                @foreach ($categories->where('parent_id', $product->category->parent_id) as $child)
                                                    <option value="{{ $child->id }}" {{ $product->category_id == $child->id ? 'selected' : '' }}>
                                                        {{ $child->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value="">Selecione uma categoria principal primeiro</option>
                                            @endif
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
                    <a href="{{ url()->previous(route('provider.products.index')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Salvar
                </button>
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
                        num = parseFloat((price.value || '0').replace(/\./g, '').replace(',', '.').replace(/[^0-9\.]/g,
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
                    if (!confirm('Tem certeza que deseja remover a imagem atual? Esta ação não pode ser desfeita.')) {
                        e.target.checked = false;
                    }
                }
            });
        }

        // Categoria dinâmica
        const parentCategorySelect = document.getElementById('parent_category_id');
        const subcategoryWrapper = document.getElementById('subcategory-wrapper');
        const subcategorySelect = document.getElementById('category_id');

        if (parentCategorySelect && subcategoryWrapper && subcategorySelect) {
            parentCategorySelect.addEventListener('change', function() {
                const parentId = this.value;
                
                if (!parentId) {
                    subcategorySelect.innerHTML = '<option value="">Selecione uma categoria principal primeiro</option>';
                    subcategorySelect.disabled = true;
                    return;
                }

                subcategorySelect.disabled = true;
                subcategorySelect.innerHTML = '<option value="">Carregando...</option>';

                fetch(`/categories/${parentId}/children`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.children && data.children.length > 0) {
                            subcategorySelect.innerHTML = '<option value="">Selecione uma subcategoria</option>';
                            data.children.forEach(child => {
                                const option = document.createElement('option');
                                option.value = child.id;
                                option.textContent = child.name;
                                subcategorySelect.appendChild(option);
                            });
                            subcategorySelect.disabled = false;
                        } else {
                            subcategorySelect.innerHTML = `<option value="${parentId}" selected>${this.options[this.selectedIndex].text}</option>`;
                            subcategorySelect.disabled = false;
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao buscar subcategorias:', error);
                        subcategorySelect.innerHTML = '<option value="">Erro ao carregar</option>';
                        subcategorySelect.disabled = true;
                    });
            });
        }
    </script>
@endpush
