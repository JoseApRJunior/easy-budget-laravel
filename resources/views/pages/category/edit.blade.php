@extends('layouts.app')

@section('content')
<div class="container-fluid py-1">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-pencil-square me-2"></i>Editar Categoria
        </h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('categories.update', $category->id) }}" method="POST">
                @csrf
                @method( 'PUT' )

                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" placeholder="Nome da Categoria" value="{{ old('name', $category->name) }}" required>
                            <label for="name">Nome da Categoria *</label>
                            @error( 'name' )
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="slugPreview" name="slugPreview"
                                value="{{ Str::slug(old('name', $category->name)) }}" placeholder="slug" disabled>
                            <label for="slugPreview">Slug (gerado automaticamente)</label>
                        </div>
                        <div class="form-text" id="slugStatus"></div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function() {
        var nameInput = document.getElementById('name');
        var slugInput = document.getElementById('slugPreview');
        var statusEl = document.getElementById('slugStatus');
        var submitBtn = document.querySelector('form button[type="submit"]');

        function slugify(text) {
            return text.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }

        function checkSlug(slug) {
            var url = '{{ url(' / categories / ajax / check - slug ') }}' + '?slug=' + encodeURIComponent(slug);
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.attached && slug !== '{{ Str::slug($category->name) }}') {
                        statusEl.innerHTML = 'Já existe uma categoria com este slug neste tenant. ' + (data.edit_url ? '<a href="' + data.edit_url + '" class="text-danger">Editar</a>' : '');
                        statusEl.className = 'form-text text-danger';
                        submitBtn.disabled = true;
                        nameInput.classList.add('is-invalid');
                    } else if (data.exists && slug !== '{{ Str::slug($category->name) }}') {
                        statusEl.textContent = 'Slug disponível: categoria existente será vinculada ao seu tenant.';
                        statusEl.className = 'form-text text-warning';
                        submitBtn.disabled = false;
                        nameInput.classList.remove('is-invalid');
                    } else {
                        statusEl.textContent = '';
                        statusEl.className = 'form-text';
                        submitBtn.disabled = false;
                        nameInput.classList.remove('is-invalid');
                    }
                })
                .catch(function() {
                    statusEl.textContent = '';
                    statusEl.className = 'form-text';
                    submitBtn.disabled = false;
                    nameInput.classList.remove('is-invalid');
                });
        }
        if (nameInput && slugInput) {
            nameInput.addEventListener('input', function() {
                slugInput.value = slugify(nameInput.value || '');
                var s = slugInput.value;
                if (s) {
                    checkSlug(s);
                }
            });
            if (nameInput.value) {
                checkSlug(slugify(nameInput.value));
            }
        }
    })();
</script>
@endpush
