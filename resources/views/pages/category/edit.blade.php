@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square me-2"></i>Editar Categoria
            </h1>

            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categorias</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('categories.show', $category) }}">{{ $category->name }}</a></li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>

        <!-- Alerts -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @php
            $hasChildren = $category->hasChildren();
            $hasServices = $category->services()->exists();
            $hasProducts = \App\Models\Product::where('category_id', $category->id)->whereNull('deleted_at')->exists();
            $canDeactivate = !($hasChildren || $hasServices || $hasProducts);
        @endphp

        <form action="{{ route('categories.update', $category->slug) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" id="tenantId" value="{{ optional(auth()->user())->tenant_id }}">

            <div class="row g-4">
                <!-- Formulário Principal -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0">
                                <i class="bi bi-tags me-2"></i>Informações da Categoria
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nome -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Nome da Categoria <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror"
                                            id="name" name="name" value="{{ old('name', $category->name) }}"
                                            required>
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Categoria Pai -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="parent_id" class="form-label">Categoria Pai</label>
                                        <select class="form-select @error('parent_id') is-invalid @enderror" id="parent_id"
                                            name="parent_id">
                                            <option value="">Sem categoria pai</option>
                                            @foreach ($parents ?? collect() as $p)
                                                <option value="{{ $p->id }}"
                                                    {{ (string) old('parent_id', $category->parent_id) === (string) $p->id ? 'selected' : '' }}>
                                                    {{ $p->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Slug (apenas para admin) -->
                                @role('admin')
                                    <div class="col-12">
                                        <div class="mb-3">
                                            <label for="slugPreview" class="form-label">Slug (gerado automaticamente)</label>
                                            <input type="text" class="form-control bg-light" id="slugPreview"
                                                name="slugPreview" value="{{ $category->slug }}" placeholder="slug" disabled>
                                            <div class="form-text" id="slugStatus"></div>
                                        </div>
                                    </div>
                                @endrole

                                <!-- Status -->
                                <div class="col-12">
                                    @if ($canDeactivate)
                                        <input type="hidden" name="is_active" value="0">
                                    @else
                                        <input type="hidden" name="is_active" value="1">
                                    @endif

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                            value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                            {{ $canDeactivate ? '' : 'disabled' }}>
                                        <label class="form-check-label" for="is_active">Categoria ativa</label>
                                    </div>

                                    @if (!$canDeactivate)
                                        <div class="alert alert-warning mt-2 mb-0" role="alert">
                                            <i class="bi bi-exclamation-triangle me-2"></i>
                                            Não é possível desativar esta categoria: possui subcategorias ou está vinculada
                                            a produtos/serviços.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <a href="{{ route('categories.show', $category) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Atualizar Categoria
                </button>
            </div>
        </form>
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
                var url = window.location.origin + '/categories/ajax/check-slug' + '?slug=' + encodeURIComponent(slug) +
                    (tenantId ? '&tenant_id=' + tenantId : '');

                fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.exists && slug !== '{{ $category->slug }}') {
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
                    if (isAdmin) {
                        slugInput.value = slugify(nameInput.value || '');
                        var s = slugInput.value;
                        if (s) {
                            checkSlug(s);
                        }
                    }
                });

                if (isAdmin && nameInput.value) {
                    checkSlug(slugify(nameInput.value));
                }
            }
        })();
    </script>
@endpush
