@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-bag-plus me-2"></i>Novo Produto
                </h1>
                <p class="text-muted mb-0">Preencha os dados para criar um novo produto</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.products.index') }}">Produtos</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Novo</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route('provider.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

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
                                            id="name" name="name" value="{{ old('name') }}" required>
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
                                            id="sku" name="sku" value="{{ old('sku') }}">
                                        @error('sku')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Deixe em branco para gerar automaticamente</div>
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
                                                value="{{ old('price') ? 'R$ ' . number_format((float) old('price'), 2, ',', '.') : 'R$ 0,00' }}"
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
                                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                            {{ $category->name }}
                                                        </option>
                                                    @else
                                                        <optgroup label="{{ $category->name }}">
                                                            <option value="{{ $category->id }}"
                                                                {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                                                {{ $category->name }} (Geral)
                                                            </option>
                                                            @foreach ($category->children as $subcategory)
                                                                <option value="{{ $subcategory->id }}"
                                                                    {{ old('category_id') == $subcategory->id ? 'selected' : '' }}>
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
                                                value="1" {{ old('active', true) ? 'checked' : '' }}>
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
                                            rows="3" placeholder="Detalhe o produto">{{ old('description') }}</textarea>
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
                            <!-- Imagem -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Imagem do Produto</label>
                                <input type="file" class="form-control @error('image') is-invalid @enderror"
                                    id="image" name="image" accept="image/*">
                                @error('image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">
                                    Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB
                                </div>
                            </div>

                            <!-- Preview da Imagem -->
                            <div class="mb-3">
                                <label class="form-label">Preview</label>
                                <div id="image-preview-container" class="text-center">
                                    <img id="image-preview" src="{{ asset('assets/img/img_not_found.png') }}"
                                        alt="Preview da Imagem"
                                        style="width: 100%; height: 150px; object-fit: contain; border-radius: 5px; border: 2px dashed #dee2e6;">
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
                    <i class="bi bi-check-circle me-2"></i>Criar
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Aplicar máscara via VanillaMask
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
                    this.value = 'R$ ' + integer + ',' + cents;
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
        // Preview da imagem
        const imageInput = document.getElementById('image');
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                const preview = document.getElementById('image-preview');
                const defaultImage = "{{ asset('assets/img/img_not_found.png') }}";

                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.src = defaultImage;
                }
            });
        }
    </script>
@endpush
