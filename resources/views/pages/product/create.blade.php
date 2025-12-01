@extends('layouts.app')

@section('title', 'Novo Produto')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Novo Produto</h3>
                    <div class="card-tools">
                        <a href="{{ route('provider.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>

                <form action="{{ route('provider.products.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="card-body">
                        <div class="row">
                            <!-- Nome do Produto -->
                            <div class="col-12 col-lg-6 mb-3">
                                <div class="form-group">
                                    <label for="name">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}"
                                        placeholder="Digite o nome do produto" required>
                                    @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- SKU -->
                            <div class="col-6 col-lg-3 mb-3">
                                <div class="form-group">
                                    <label for="sku">SKU</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                        id="sku" name="sku" value="{{ old('sku') }}"
                                        placeholder="Auto-gerado">
                                    @error('sku')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted d-none d-lg-block">Deixe em branco para gerar automaticamente</small>
                                </div>
                            </div>

                            <!-- Preço -->
                            <div class="col-6 col-lg-3 mb-3">
                                <div class="form-group">
                                    <label for="price">Preço <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">R$</span>
                                        </div>
                                        <input type="text" class="form-control @error('price') is-invalid @enderror"
                                            id="price" name="price" value="{{ old('price', 'R$ 0,00') }}"
                                            inputmode="numeric" placeholder="R$ 0,00" required>
                                        @error('price')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Categoria -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="category_id">Categoria</label>
                                    <select class="form-control @error('category_id') is-invalid @enderror"
                                        id="category_id" name="category_id">
                                        <option value="">Selecione uma categoria</option>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('category_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Unidade -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unit">Unidade</label>
                                    <input type="text" class="form-control @error('unit') is-invalid @enderror"
                                        id="unit" name="unit" value="{{ old('unit') }}"
                                        placeholder="Ex: kg, un, m²">
                                    @error('unit')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="active">Status</label>
                                    <div class="custom-control custom-switch">
                                        <input type="hidden" name="active" value="0">
                                        <input type="checkbox" class="custom-control-input"
                                            id="active" name="active" value="1"
                                            {{ old('active', true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="active">
                                            Produto ativo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description">Descrição</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror"
                                        id="description" name="description" rows="3"
                                        placeholder="Detalhe o produto">{{ old('description') }}</textarea>
                                    @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Imagem -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image">Imagem do Produto</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('image') is-invalid @enderror"
                                            id="image" name="image" accept="image/*">
                                        <label class="custom-file-label" for="image">Escolher arquivo</label>
                                        @error('image')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <small class="form-text text-muted">
                                        Formatos aceitos: JPG, PNG, GIF. Tamanho máximo: 2MB
                                    </small>
                                </div>
                            </div>

                            <!-- Preview da Imagem -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Preview</label>
                                    <div id="image-preview" class="text-center">
                                        <div class="bg-light d-flex align-items-center justify-content-center"
                                            style="width: 150px; height: 150px; border: 2px dashed #dee2e6; border-radius: 5px; margin: 0 auto;">
                                            <i class="fas fa-image text-muted fa-2x"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Criar Produto
                        </button>
                        <a href="{{ route('provider.products.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
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
    document.querySelector('form[action*="provider/products"]').addEventListener('submit', function(e) {
        const price = document.getElementById('price');
        if (price) {
            let num = 0;
            if (window.parseCurrencyBRLToNumber) {
                num = window.parseCurrencyBRLToNumber(price.value) || 0;
            } else {
                num = parseFloat((price.value || '0').replace(/\./g, '').replace(',', '.').replace(/[^0-9\.]/g, '')) || 0;
            }
            price.value = num.toFixed(2);
        }
    });
    // Preview da imagem
    document.getElementById('image').addEventListener('change', function(e) {
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
                <i class="fas fa-image text-muted fa-2x"></i>
            </div>
        `;
        }
    });

    // Atualizar label do file input
    document.querySelector('.custom-file-input').addEventListener('change', function(e) {
        const fileName = e.target.files[0]?.name || 'Escolher arquivo';
        const label = e.target.nextElementSibling;
        label.textContent = fileName;
    });
</script>
@endpush
