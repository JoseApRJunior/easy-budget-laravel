@extends('layouts.app')

@section('title', 'Nova Categoria')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Nova Categoria"
        icon="plus-circle"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => route('provider.categories.dashboard'),
            'Nova' => '#'
        ]">
        <p class="text-muted mb-0">Preencha os dados para criar uma nova categoria</p>
    </x-layout.page-header>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route('provider.categories.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-7">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Nome da Categoria"
                                value="{{ old('name') }}" required autofocus>
                            <label for="name">Nome da Categoria *</label>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="parent_id" class="form-label small fw-bold text-muted">Categoria Pai (opcional)</label>
                            <select class="form-select tom-select" id="parent_id" name="parent_id">
                                <option value="">Sem categoria pai (Esta será uma Categoria Principal)</option>
                                @foreach ($parents ?? collect() as $p)
                                    <option value="{{ $p->id }}"
                                        {{ (string) old('parent_id') === (string) $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text small">
                                Subcategorias herdam o comportamento e status da categoria pai.
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="bg-light p-3 rounded-3 border h-100">
                            <h6 class="fw-bold mb-3 d-flex align-items-center">
                                <i class="bi bi-gear-fill me-2 text-primary"></i>Configurações
                            </h6>

                            <div class="form-check form-switch custom-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                    value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold" for="is_active">Categoria Ativa</label>
                                <p class="text-muted small mb-0">Categorias inativas não podem ser selecionadas em novos registros.</p>
                                @error('is_active')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4 opacity-25">

                <div class="d-flex justify-content-between align-items-center">
                    <x-ui.back-button index-route="provider.categories.index" label="Voltar para Lista" />
                    <x-ui.button type="submit" icon="check-circle" label="Criar Categoria" variant="primary" />
                </div>
            </form>
        </div>
    </div>
</x-layout.page-container>
@endsection
