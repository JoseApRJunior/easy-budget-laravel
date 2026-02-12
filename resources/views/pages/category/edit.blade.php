@extends('layouts.app')

@section('title', 'Editar Categoria')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Editar Categoria"
            icon="pencil-square"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Categorias' => route('provider.categories.dashboard'),
                $category->name => route('provider.categories.show', $category->slug),
                'Editar' => '#'
            ]"
        >
            <p class="text-muted mb-0">Atualize as informações da categoria</p>
        </x-layout.page-header>

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
            <form action="{{ route('provider.categories.update', $category->slug) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Nome da Categoria"
                                value="{{ old('name', $category->name) }}" required autofocus>
                            <label for="name">Nome da Categoria *</label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="parent_id" class="form-label small fw-bold text-muted">Categoria Pai (opcional)</label>
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
                                <div class="alert alert-warning mt-2 mb-0 border-0 bg-warning bg-opacity-10 py-2" role="alert">
                                    <div class="d-flex align-items-center small text-dark">
                                        <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                                        <span>Bloqueado: Esta categoria possui subcategorias, serviços ou produtos vinculados.</span>
                                    </div>
                                </div>
                            @else
                                <select class="form-select tom-select" id="parent_id" name="parent_id">
                                    <option value="">Sem categoria pai (Esta será uma Categoria Principal)</option>
                                    @foreach ($parents ?? collect() as $p)
                                        <option value="{{ $p->id }}"
                                            {{ (string) old('parent_id', $category->parent_id) === (string) $p->id ? 'selected' : '' }}>
                                            {{ $p->name }} {{ !$p->is_active ? '(Inativo)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="form-text small">
                                    Subcategorias herdam o comportamento e status da categoria pai.
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                <i class="bi bi-gear-fill me-2 text-primary"></i>Configurações
                            </h6>

                            @if ($isDisabled)
                                <input type="hidden" name="is_active" value="{{ $isCurrentlyActive ? '1' : '0' }}">
                            @else
                                <input type="hidden" name="is_active" value="0">
                            @endif

                            <div class="form-check form-switch custom-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ $isCurrentlyActive ? 'checked' : '' }}
                                    {{ $isDisabled ? 'disabled' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Categoria Ativa</label>
                                <p class="text-muted small mb-0">Status atual: <strong>{{ $isCurrentlyActive ? 'Ativo' : 'Inativo' }}</strong></p>
                                
                                @if ($parentIsInactive)
                                    <div class="alert alert-info mt-2 mb-0 border-0 bg-info bg-opacity-10 py-2" role="alert">
                                        <div class="d-flex align-items-center small text-dark">
                                            <i class="bi bi-info-circle-fill me-2 text-info"></i>
                                            <span>O pai está inativo. Ative o pai primeiro.</span>
                                        </div>
                                    </div>
                                @endif

                                @if (!$canDeactivate && $isCurrentlyActive)
                                    <div class="alert alert-warning mt-2 mb-0 border-0 bg-warning bg-opacity-10 py-2" role="alert">
                                        <div class="d-flex align-items-center small text-dark">
                                            <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>
                                            <span>Possui itens vinculados.</span>
                                        </div>
                                    </div>
                                @endif

                                @error('is_active')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                <div class="d-flex justify-content-between align-items-center">
                    <x-ui.back-button index-route="provider.categories.index" label="Cancelar" />
                    <x-ui.button type="submit" icon="check-circle" label="Salvar Alterações" variant="primary" feature="categories" />
                </div>
            </form>
        </div>
    </div>
    </x-layout.page-container>
@endsection
