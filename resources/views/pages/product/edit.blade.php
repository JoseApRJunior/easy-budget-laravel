@extends('layouts.app')

@section('title', 'Editar Produto: ' . $product->name)

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Editar Produto"
        icon="pencil-square"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Produtos' => route('provider.products.dashboard'),
            $product->name => route('provider.products.show', $product->sku),
            'Editar' => '#'
        ]"
    >
        <p class="text-muted mb-0">Atualize as informações do produto</p>
    </x-layout.page-header>

    <form action="{{ route('provider.products.update', $product->sku) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <x-layout.grid-row class="g-4">
            <!-- Informações do Produto -->
            <x-layout.grid-col size="col-lg-8">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-box me-2"></i>Informações do Produto
                        </h5>
                    </x-slot:header>
                    <div class="p-2">
                        <x-layout.grid-row>
                            <!-- Nome -->
                            <x-layout.grid-col size="col-md-6">
                                <x-ui.form.input 
                                    name="name" 
                                    label="Nome do Produto" 
                                    required 
                                    :value="old('name', $product->name)" 
                                />
                            </x-layout.grid-col>

                            <!-- SKU (Visualização Apenas) -->
                            <x-layout.grid-col size="col-md-3">
                                <x-ui.form.input 
                                    name="sku" 
                                    label="SKU" 
                                    :value="$product->sku" 
                                    readonly 
                                    class="bg-light"
                                    help="Código único - não editável"
                                />
                            </x-layout.grid-col>

                            <!-- Preço de Custo -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label for="cost_price" class="form-label fw-bold small text-muted text-uppercase">Preço de Custo</label>
                                    <div class="input-group">
                                        <div class="input-group-text bg-light border-end-0">R$</div>
                                        <input type="text" class="form-control currency-brl border-start-0 @error('cost_price') is-invalid @enderror"
                                            id="cost_price" name="cost_price"
                                            value="{{ old('cost_price', number_format((float) $product->cost_price, 2, ',', '.')) }}"
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
                                    <label for="price" class="form-label fw-bold small text-muted text-uppercase">Preço de Venda <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-text bg-light border-end-0">R$</div>
                                        <input type="text" class="form-control currency-brl border-start-0 @error('price') is-invalid @enderror"
                                            id="price" name="price"
                                            value="{{ old('price', number_format((float) $product->price, 2, ',', '.')) }}"
                                            inputmode="numeric" required>
                                        @error('price')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div id="margin-preview" class="form-text mt-1 small">
                                        <!-- Preenchido via JS -->
                                    </div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Categoria -->
                            <x-layout.grid-col size="col-md-6">
                                <x-ui.form.select 
                                    name="category_id" 
                                    label="Categoria" 
                                    id="category_id"
                                    :selected="old('category_id', $product->category_id)"
                                >
                                    <option value="">Selecione uma categoria</option>
                                    @foreach ($categories as $category)
                                        @if ($category->parent_id === null)
                                            @if ($category->children->isEmpty())
                                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                            @else
                                                <optgroup label="{{ $category->name }}">
                                                    <option value="{{ $category->id }}">{{ $category->name }} (Geral)</option>
                                                    @foreach ($category->children as $subcategory)
                                                        <option value="{{ $subcategory->id }}">{{ $subcategory->name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                        @endif
                                    @endforeach
                                </x-ui.form.select>
                            </x-layout.grid-col>

                            <!-- Status -->
                            <x-layout.grid-col size="col-md-3">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input type="hidden" name="active" value="0">
                                        <input class="form-check-input" type="checkbox" id="active" name="active"
                                            value="1" {{ old('active', $product->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">
                                            Produto ativo
                                        </label>
                                    </div>
                                </div>
                            </x-layout.grid-col>

                            <!-- Descrição -->
                            <x-layout.grid-col size="col-12">
                                <x-ui.form.textarea 
                                    name="description" 
                                    label="Descrição" 
                                    rows="4" 
                                    placeholder="Detalhes técnicos, benefícios e especificações..."
                                >{{ old('description', $product->description) }}</x-ui.form.textarea>
                            </x-layout.grid-col>
                        </x-layout.grid-row>
                    </div>
                </x-ui.card>
            </x-layout.grid-col>

            <!-- Imagem -->
            <x-layout.grid-col size="col-lg-4">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-image me-2"></i>Imagem do Produto
                        </h5>
                    </x-slot:header>
                    <div class="p-4 text-center">
                        <!-- Imagem Atual -->
                        @if ($product->image)
                            <div class="mb-4 text-start">
                                <label class="form-label fw-bold small text-muted text-uppercase">Imagem Atual</label>
                                <div class="d-flex align-items-center p-3 border rounded bg-light">
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                        class="img-thumbnail shadow-sm me-3"
                                        style="width: 80px; height: 80px; object-fit: cover;">
                                    <div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="remove_image"
                                                name="remove_image" value="1">
                                            <label class="form-check-label text-danger small fw-bold" for="remove_image">
                                                Remover imagem
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Preview da Nova Imagem -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted text-uppercase d-block text-start">Preview Nova Imagem</label>
                            <div id="image-preview" class="mx-auto" style="max-width: 300px;">
                                <div class="bg-light d-flex align-items-center justify-content-center border rounded shadow-sm"
                                    style="width: 100%; height: 200px; border: 2px dashed #dee2e6 !important;">
                                    <div class="text-muted small">
                                        <i class="bi bi-cloud-upload d-block fs-2 mb-2"></i>
                                        Selecione uma nova imagem
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Seleção de Arquivo -->
                        <div class="mb-3 text-start">
                            <label for="image" class="form-label fw-bold small text-muted text-uppercase">Substituir Imagem</label>
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
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            <x-ui.back-button index-route="provider.products.index" label="Cancelar" />
            <x-ui.button type="submit" variant="primary" icon="check-circle" label="Salvar Alterações" class="px-4" feature="products" />
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

            // Funções de utilidade para manipulação de valores monetários
            function unformat(val) {
                if (!val) return 0;
                // Remove pontos de milhar e substitui vírgula decimal por ponto
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
                    if (costPriceInfo) costPriceInfo.classList.add('d-none'); // Esconde a mensagem informativa se houver erro
                    if (!document.getElementById('cost-price-error')) {
                        const errorDiv = document.createElement('div');
                        errorDiv.id = 'cost-price-error';
                        errorDiv.className = 'invalid-feedback';
                        errorDiv.innerText = 'O preço de custo não pode ser maior que o preço de venda.';
                        costPriceInput.parentNode.appendChild(errorDiv);
                    }
                } else {
                    costPriceInput.classList.remove('is-invalid');
                    if (costPriceInfo) costPriceInfo.classList.remove('d-none'); // Mostra a mensagem informativa se não houver erro
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

            // Chamar inicialmente para preencher se já houver valores
            validatePrices();

            // Validar ao digitar
            priceInput.addEventListener('input', validatePrices);
            costPriceInput.addEventListener('input', validatePrices);

            // Impedir envio se houver erro
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
        });

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
                                 class="img-fluid rounded shadow-sm border"
                                 style="width: 100%; height: 200px; object-fit: contain; background: #fff;">
                        `;
                    };
                    reader.readAsDataURL(file);
                } else {
                    preview.innerHTML = `
                        <div class="bg-light d-flex align-items-center justify-content-center border rounded shadow-sm"
                            style="width: 100%; height: 200px; border: 2px dashed #dee2e6 !important;">
                            <div class="text-muted small">
                                <i class="bi bi-cloud-upload d-block fs-2 mb-2"></i>
                                Selecione uma nova imagem
                            </div>
                        </div>
                    `;
                }
            });
        }
    </script>
@endpush
