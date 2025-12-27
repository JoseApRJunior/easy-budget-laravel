@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('content')
    <div class="container-fluid py-1">
        <x-page-header
            title="Editar Categoria"
            icon="pencil-square"
            :breadcrumb-items="[
                'Categorias' => route('provider.categories.index'),
                $category->name => route('provider.categories.show', $category->slug),
                'Editar' => '#'
            ]"
        >
            <p class="text-muted mb-0">Atualize as informações da categoria</p>
        </x-page-header>

        @php
            $hasChildren = $category->children_count > 0;
            // Uma categoria só não pode ser desativada se tiver serviços ou produtos vinculados.
            // Subcategorias não bloqueiam a desativação, pois serão desativadas em cascata.
            $canDeactivate = $category->services_count === 0 && $category->products_count === 0;

            // Uma categoria só não pode mudar de pai se tiver subcategorias OU serviços OU produtos vinculados.
            $canChangeParent = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0;

            // Uma subcategoria não pode ser ativada se o pai estiver inativo.
            $parentIsInactive = $category->parent && !$category->parent->is_active;
            $canActivate = !$parentIsInactive;

            // Lógica consolidada para o checkbox
            $isCurrentlyActive = old('is_active', $category->is_active);
            $isDisabled = ($isCurrentlyActive && !$canDeactivate) || (!$isCurrentlyActive && !$canActivate);
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
                <form action="{{ route('provider.categories.update', $category->slug) }}" method="POST">
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
                            <div class="form-group mb-3">
                                <label for="parent_id" class="form-label">Categoria Pai (opcional)</label>
                                @if (!$canChangeParent)
                                    <input type="hidden" name="parent_id" value="{{ $category->parent_id }}">
                                    <select class="form-select tom-select" id="parent_id" disabled>
                                        <option value="">Sem categoria pai</option>
                                        @foreach ($parents ?? collect() as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string) $category->parent_id === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} {{ !$p->is_active ? '(Inativo)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="alert alert-warning mt-2 mb-0" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                        Esta categoria não pode ter a categoria pai alterada porque possui subcategorias, serviços ou produtos vinculados.
                                    </div>
                                @else
                                    <select class="form-select tom-select" id="parent_id" name="parent_id">
                                        <option value="">Sem categoria pai</option>
                                        @foreach ($parents ?? collect() as $p)
                                            <option value="{{ $p->id }}"
                                                {{ (string) old('parent_id', $category->parent_id) === (string) $p->id ? 'selected' : '' }}>
                                                {{ $p->name }} {{ !$p->is_active ? '(Inativo)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            @if ($isDisabled)
                                <input type="hidden" name="is_active" value="{{ $isCurrentlyActive ? '1' : '0' }}">
                            @else
                                <input type="hidden" name="is_active" value="0">
                            @endif
                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ $isCurrentlyActive ? 'checked' : '' }}
                                    {{ $isDisabled ? 'disabled' : '' }}>
                                <label class="form-check-label" for="is_active">Ativo</label>
                                @error('is_active')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            @if ($parentIsInactive)
                                <div class="alert alert-info mt-2 mb-0" role="alert">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Esta subcategoria não pode ser ativada porque a categoria pai <strong>{{ $category->parent->name }}</strong> está inativa.
                                </div>
                            @endif
                            @if (!$canDeactivate)
                                <div class="alert alert-warning mt-2 mb-0" role="alert">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Não é possível desativar esta categoria: ela possui serviços ou produtos vinculados.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <x-back-button index-route="provider.categories.index" label="Cancelar" />
                        </div>
                        <x-button type="submit" icon="check-circle" label="Salvar" />
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
