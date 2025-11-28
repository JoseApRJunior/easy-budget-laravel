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
                <input type="hidden" id="tenantId" value="{{ optional(auth()->user())->tenant_id }}">
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" placeholder="Nome da Categoria" value="{{ old('name', $category->name) }}" required>
                            <label for="name">Nome da Categoria *</label>
                        </div>
                        <div class="form-floating mb-3">
                            <select class="form-control" id="parent_id" name="parent_id">
                                <option value="">Sem categoria pai</option>
                                @foreach(($parents ?? collect()) as $p)
                                <option value="{{ $p->id }}" {{ (string)old('parent_id', $category->parent_id) === (string)$p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                                @endforeach
                            </select>
                            <label for="parent_id">Categoria (opcional)</label>
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control" id="slugPreview" name="slugPreview"
                                value="{{ Str::slug(old('name', $category->name)) }}" placeholder="slug" disabled>
                            <label for="slugPreview">Slug (gerado automaticamente)</label>
                        </div>
                        <div class="form-text" id="slugStatus"></div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Ativo</label>
                        </div>
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
        var tenantIdEl = document.getElementById('tenantId');
        var tenantId = tenantIdEl && tenantIdEl.value ? parseInt(tenantIdEl.value) : null;
        var isAdmin = false;
        @role('admin')
        isAdmin = true;
        @endrole

        function slugify(text) {
            return text.toString().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }

        function checkSlug(slug) {
            var url = window.location.origin + '/categories/ajax/check-slug' + '?slug=' + encodeURIComponent(slug) + (tenantId ? '&tenant_id=' + tenantId : '');
            fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    if (data.exists && slug !== '{{ Str::slug($category->name) }}') {
                        statusEl.textContent = 'Este nome já está em uso.';
                        statusEl.className = 'form-text text-danger';
                        submitBtn.disabled = true;
                        nameInput.classList.add('is-invalid');
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
