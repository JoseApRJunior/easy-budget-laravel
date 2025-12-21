@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('content')
    <div class="container-fluid py-1">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil-square me-2"></i>Editar Categoria
                </h1>
                <p class="text-muted mb-0">Atualize as informações da categoria</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categorias</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Editar</li>
                </ol>
            </nav>
        </div>

        @php
            $hasChildren = $category->children_count > 0;
            $canDeactivate = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0;
        @endphp

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form action="{{ route('categories.update', $category->slug) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" placeholder="Nome da Categoria"
                                    value="{{ old('name', $category->name) }}" required>
                                <label for="name">Nome da Categoria *</label>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-floating mb-3">
                                @if ($hasChildren)
                                    <input type="hidden" name="parent_id" value="{{ $category->parent_id }}">
                                    <select class="form-control" id="parent_id" disabled>
                                        <option value="">Sem categoria pai</option>
                                        @foreach ($parents ?? collect() as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string) $category->parent_id === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="parent_id">Categoria Pai (opcional)</label>
                                    <div class="alert alert-warning mt-2 mb-0" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta categoria possui subcategorias e não pode ter categoria pai alterada.
                                    </div>
                                @else
                                    <select class="form-control" id="parent_id" name="parent_id">
                                        <option value="">Sem categoria pai</option>
                                        @foreach ($parents ?? collect() as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string) old('parent_id', $category->parent_id) === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    <label for="parent_id">Categoria Pai (opcional)</label>
                                @endif
                            </div>
                            @if ($canDeactivate)
                                <input type="hidden" name="is_active" value="0">
                            @else
                                <input type="hidden" name="is_active" value="1">
                            @endif
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}
                                    {{ $canDeactivate ? '' : 'disabled' }}>
                                <label class="form-check-label" for="is_active">Ativo</label>
                                @error('is_active')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            @if (!$canDeactivate)
                                <div class="alert alert-warning mt-2 mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Não é possível desativar esta categoria: possui subcategorias ou está vinculada a
                                    produtos/serviços.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <a href="{{ url()->previous(route('categories.index')) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Cancelar
                            </a>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
