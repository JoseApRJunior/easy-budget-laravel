@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')
    <div class="container-fluid py-1">
        <x-page-header
            title="Novo Produto"
            icon="bag-plus"
            :breadcrumb-items="[
                'Produtos' => route('provider.products.index'),
                'Novo' => '#'
            ]"
        >
            <p class="text-muted mb-0">Preencha os dados para criar um novo produto</p>
        </x-page-header>

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
                                            id="sku" name="sku" value="{{ old('sku', $defaults['sku'] ?? '') }}">
                                        @error('sku')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text">Você pode usar o código sugerido ou digitar um personalizado</div>
                                    </div>
                                </div>

                                <!-- Preço de Custo -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="cost_price" class="form-label">Preço de Custo</label>
                                        <div class="input-group">
                                            <div class="input-group-text">R$</div>
                                            <input type="text" class="form-control currency-brl @error('cost_price') is-invalid @enderror"
                                                id="cost_price" name="cost_price"
                                                value="{{ old('cost_price', '0,00') }}"
                                                inputmode="numeric">
                                            @error('cost_price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div id="cost-price-info" class="form-text">Usado para calcular sua margem de lucro</div>
                                    </div>
                                </div>

                                <!-- Preço de Venda -->
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Preço de Venda <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-text">R$</div>
                                            <input type="text" class="form-control currency-brl @error('price') is-invalid @enderror"
                                                id="price" name="price"
                                                value="{{ old('price', '0,00') }}"
                                                inputmode="numeric" required>
                                            @error('price')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div id="margin-preview" class="form-text mt-1 d-none">
                                            <!-- Preenchido via JS -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Categoria -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Categoria</label>
                                        <select class="form-select tom-select @error('category_id') is-invalid @enderror"
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
                    <x-back-button index-route="provider.products.index" label="Cancelar" />
                </div>
                <x-button type="submit" icon="check-circle" label="Criar" />
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('price');
            const costPriceInput = document.getElementById('cost_price');
            const form = priceInput.closest('form');

            function unformat(val) {
                if (!val) return 0;
                return parseFloat(val.replace(/\./g, '').replace(',', '.').replace(/[^\d.-]/g, '')) || 0;
            }

            function validatePrices() {
                const price = unformat(priceInput.value);
                const costPrice = unformat(costPriceInput.value);
                const costPriceInfo = document.getElementById('cost-price-info');
                const marginPreview = document.getElementById('margin-preview');

                // Validação visual de erro
                if (costPrice > price && price > 0) {
                    costPriceInput.classList.add('is-invalid');
                    costPriceInfo.classList.add('d-none'); // Esconde a mensagem informativa se houver erro
                    if (!document.getElementById('cost-price-error')) {
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'cost-price-error';
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.innerText = 'O preço de custo não pode ser maior que o preço de venda.';
                        costPriceInput.parentNode.appendChild(errorDiv);
                    }
                } else {
                    costPriceInput.classList.remove('is-invalid');
                    costPriceInfo.classList.remove('d-none'); // Mostra a mensagem informativa se não houver erro
                    const errorDiv = document.getElementById('cost-price-error');
                    if (errorDiv) errorDiv.remove();
                }

                // Cálculo de Margem em tempo real
                if (price > 0) {
                    const profit = price - costPrice;
                    const margin = (profit / price) * 100;

                    marginPreview.classList.remove('d-none');
                    if (profit < 0) {
                        marginPreview.className = 'form-text mt-1 text-danger fw-bold';
                        marginPreview.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> Prejuízo: ${margin.toFixed(2)}% (R$ ${profit.toLocaleString('pt-BR', {minimumFractionDigits: 2})})`;
                    } else if (profit > 0) {
                        marginPreview.className = 'form-text mt-1 text-success fw-bold';
                        marginPreview.innerHTML = `<i class="bi bi-graph-up-arrow me-1"></i> Margem: ${margin.toFixed(2)}% (Lucro: R$ ${profit.toLocaleString('pt-BR', {minimumFractionDigits: 2})})`;
                    } else {
                        marginPreview.className = 'form-text mt-1 text-muted';
                        marginPreview.innerHTML = `Margem: 0% (Ponto de equilíbrio)`;
                    }
                } else {
                    marginPreview.classList.add('d-none');
                }
            }

            priceInput.addEventListener('input', validatePrices);
            costPriceInput.addEventListener('input', validatePrices);

            form.addEventListener('submit', function(e) {
                validatePrices();
                if (costPriceInput.classList.contains('is-invalid')) {
                    e.preventDefault();
                    costPriceInput.focus();
                }
            });

            // Aplicar máscara via VanillaMask
            if (window.VanillaMask) {
                new VanillaMask('price', 'currency');
                new VanillaMask('cost_price', 'currency');
            } else {
                // Fallback manual se o VanillaMask não estiver disponível
                const maskCurrency = (el) => {
                    el.addEventListener('input', function() {
                        const digits = this.value.replace(/\D/g, '');
                        const num = (parseInt(digits || '0', 10) / 100);
                        const integer = Math.floor(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                        const cents = Math.round((num - Math.floor(num)) * 100).toString().padStart(2, '0');
                        this.value = integer + ',' + cents;
                    });
                };
                maskCurrency(priceInput);
                maskCurrency(costPriceInput);
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
        });
    </script>
@endpush
