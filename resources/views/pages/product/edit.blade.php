@extends('layouts.app')

@section('title', 'Editar Produto: ' . $product->name)

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Produto: {{ $product->name }}
                    </h3>
                    <div class="card-tools">
                        <a href="{{ route('products.show', $product->sku) }}" class="btn btn-info btn-sm">
                            <i class="fas fa-eye"></i> Visualizar
                        </a>
                        <a href="{{ route('provider.products.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </div>

                <form action="{{ route('products.update', $product->sku) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <div class="row">
                            <!-- Nome do Produto -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">Nome do Produto <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                           id="name" name="name" value="{{ old('name', $product->name) }}"
                                           placeholder="Digite o nome do produto" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- SKU -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="sku">SKU</label>
                                    <input type="text" class="form-control @error('sku') is-invalid @enderror"
                                           id="sku" name="sku" value="{{ old('sku', $product->sku) }}"
                                           placeholder="Código único do produto">
                                    @error('sku')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Código único para identificação</small>
                                </div>
                            </div>

                            <!-- Preço -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="price">Preço <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">R$</span>
                                        </div>
                                        <input type="number" class="form-control @error('price') is-invalid @enderror"
                                               id="price" name="price" value="{{ old('price', $product->price) }}"
                                               step="0.01" min="0" placeholder="0,00" required>
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
                                                    {{ (old('category_id', $product->category_id) == $category->id) ? 'selected' : '' }}>
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
                                           id="unit" name="unit" value="{{ old('unit', $product->unit) }}"
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
                                               {{ old('active', $product->active) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="active">
                                            Produto ativo
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Imagem Atual -->
                        @if($product->image)
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Imagem Atual</label>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $product->image }}" alt="{{ $product->name }}"
                                             class="img-thumbnail mr-3" style="width: 100px; height: 100px; object-fit: cover;">
                                        <div>
                                            <p class="mb-1">Imagem atual do produto</p>
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input"
                                                       id="remove_image" name="remove_image" value="1">
                                                <label class="custom-control-label" for="remove_image">
                                                    <small class="text-danger">Remover imagem atual</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Nova Imagem -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="image">{{ $product->image ? 'Substituir' : 'Adicionar' }} Imagem</label>
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
                                        @if($product->image)
                                            <br><strong>Nota:</strong> Selecionar uma nova imagem substituirá a atual
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <!-- Preview da Nova Imagem -->
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Preview da Nova Imagem</label>
                                    <div id="image-preview" class="text-center">
                                        @if($product->image)
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                 style="width: 150px; height: 150px; border: 2px dashed #dee2e6; border-radius: 5px; margin: 0 auto;">
                                                <small class="text-muted">Selecione uma nova imagem</small>
                                            </div>
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center"
                                                 style="width: 150px; height: 150px; border: 2px dashed #dee2e6; border-radius: 5px; margin: 0 auto;">
                                                <i class="fas fa-image text-muted fa-2x"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Atualizar Produto
                        </button>
                        <a href="{{ route('products.show', $product->sku) }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <div class="float-right">
                            <a href="{{ route('provider.products.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar à Lista
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
// Preview da nova imagem
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
                <small class="text-muted">Selecione uma nova imagem</small>
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

// Confirmação para remoção de imagem
document.getElementById('remove_image')?.addEventListener('change', function(e) {
    if (e.target.checked) {
        if (!confirm('Tem certeza que deseja remover a imagem atual? Esta ação não pode ser desfeita.')) {
            e.target.checked = false;
        }
    }
});
</script>
@endsection
