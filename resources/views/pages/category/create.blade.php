@extends('layouts.app')

@section('title', 'Nova Categoria')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Nova Categoria"
        icon="plus-circle"
        :breadcrumb-items="[
                'Categorias' => route('provider.categories.index'),
                'Nova' => '#'
            ]">
        <p class="text-muted mb-0">Preencha os dados para criar uma nova categoria</p>
    </x-page-header>

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
            <form action="{{ route('provider.categories.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Nome da Categoria"
                                value="{{ old('name') }}" required>
                            <label for="name">Nome da Categoria *</label>
                            @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="parent_id" class="form-label">Categoria Pai (opcional)</label>
                            <select class="form-select tom-select" id="parent_id" name="parent_id">
                                <option value="">Sem categoria pai</option>
                                @foreach ($parents ?? collect() as $p)
                                <option value="{{ $p->id }}"
                                    {{ (string) old('parent_id') === (string) $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                value="1" {{ old('is_active', $defaults['is_active'] ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Ativo</label>
                            @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <x-back-button index-route="provider.categories.index" label="Cancelar" />
                    </div>
                    <x-button type="submit" icon="check-circle" label="Criar" />
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
