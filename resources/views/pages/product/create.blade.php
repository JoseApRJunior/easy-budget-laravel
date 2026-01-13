@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Novo Produto"
        icon="bag-plus"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => route('provider.products.dashboard'),
            'Novo' => '#'
        ]"
    >
        <p class="text-muted mb-0">Preencha os dados para criar um novo produto</p>
    </x-layout.page-header>

    <form action="{{ route('provider.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <x-layout.grid-row class="g-4">
            <!-- Informações do Produto -->
            <x-layout.grid-col size="col-lg-8">
                <div class="card h-100">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-box me-2 text-primary"></i>Informações do Produto
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <x-layout.grid-row>
                            <!-- Nome -->
                            <x-layout.grid-col size="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label small fw-bold text-muted text-uppercase">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </x-layout.grid-col>

                            <!-- SKU -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label for="sku" class="form-label small fw-bold text-muted text-uppercase">SKU</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                        id="sku" name="sku" value="{{ old('sku', $defaults['sku'] ?? '') }}">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text small">Código sugerido ou personalizado</div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Preço de Custo -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label small fw-bold text-muted text-uppercase">Preço de Custo</label>
                                    <div class="input-group">
                                        <div class="input-group-text bg-light border-end-0">R$</div>
                                        <input type="text" class="form-control currency-brl border-start-0 @error('cost_price') is-invalid @enderror"
                                            id="cost_price" name="cost_price"
                                            value="{{ old('cost_price', '0,00') }}"
                                            inputmode="numeric">
                                        @error('cost_price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div id="cost-price-info" class="form-text small text-muted">Para cálculo de margem</div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Preço de Venda -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label for="price" class="form-label small fw-bold text-muted text-uppercase">Preço de Venda <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text bg-light border-end-0">R$</div>
                                        <input type="text" class="form-control currency-brl border-start-0 @error('price') is-invalid @enderror"
                                            id="price" name="price"
                                            value="{{ old('price', '0,00') }}"
                                            inputmode="numeric" required>
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div id="margin-preview" class="form-text mt-1 d-none small">
                                        <!-- Preenchido via JS -->
                                    </div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Categoria -->
                            <x-layout.grid-col size="col-md-6">
                                <div class="mb-3">
                                    <label for="category_id" class="form-label small fw-bold text-muted text-uppercase">Categoria</label>
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
                            </x-layout.grid-col>

                            <!-- Status -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="active" value="0">
                                        <input class="form-check-input" type="checkbox" id="active" name="active"
                                            value="1" {{ old('active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            Produto ativo
                                        </label>
                                    </div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Descrição -->
                            <x-layout.grid-col size="col-12">
                                <div class="mb-3">
                                    <label for="description" class="form-label small fw-bold text-muted text-uppercase">Descrição</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                        rows="4" placeholder="Detalhes técnicos, benefícios e especificações...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </x-layout.grid-col>
                        </x-layout.grid-row>
                    </div>
                </div>
            </x-layout.grid-col>

            <!-- Imagem -->
            <x-layout.grid-col size="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-transparent border-bottom py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-image me-2 text-primary"></i>Imagem do Produto
                        </h5>
                    </div>
                    <div class="card-body p-4 text-center">
                        <!-- Preview da Imagem -->
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted text-uppercase d-block text-start">Preview</label>
                            <div id="image-preview-container" class="mx-auto" style="max-width: 300px;">
                                <img id="image-preview" src="{{ asset('assets/img/img_not_found.png') }}"
                                    alt="Preview da Imagem"
                                    class="img-fluid rounded border shadow-sm"
                                    style="width: 100%; height: 250px; object-fit: contain; background: #f8f9fa;">
                            </div>
                        </div>

                        <!-- Seleção de Arquivo -->
                        <div class="mb-3 text-start">
                            <label for="image" class="form-label small fw-bold text-muted text-uppercase">Upload de Imagem</label>
                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                id="image" name="image" accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text small mt-2">
                                <i class="bi bi-info-circle me-1"></i>JPG, PNG, GIF. Máx 2MB.
                            </div>
                        </div>
                    </div>
                </div>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <x-ui.back-button index-route="provider.products.index" label="Cancelar" />
            <x-ui.button type="submit" variant="primary" icon="check-circle" label="Criar Produto" class="px-4" />
        </div>
    </form>
</x-layout.page-container>
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
