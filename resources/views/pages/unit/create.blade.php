@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Nova Unidade"
            icon="plus-circle"
            :breadcrumb-items="[
                'Dashboard' => route('admin.dashboard'),
                'Unidades' => route('admin.units.index'),
                'Nova' => '#'
            ]">
            <x-ui.button :href="route('admin.units.index')" variant="secondary" outline icon="arrow-left" label="Voltar" feature="manage-units" />
        </x-layout.page-header>

        <!-- Formulário -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route('admin.units.store') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Nome -->
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" placeholder="Nome da Unidade" value="{{ old('name') }}"
                                    required>
                                <label for="name">Nome da Unidade *</label>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Abreviação -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control @error('abbreviation') is-invalid @enderror"
                                    id="abbreviation" name="abbreviation" placeholder="Abreviação"
                                    value="{{ old('abbreviation') }}" maxlength="10" required>
                                <label for="abbreviation">Abreviação *</label>
                                @error('abbreviation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ex: kg, m, un, l, etc.</div>
                            </div>
                        </div>

                    </div>

                    <!-- Botões -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Salvar Unidade</button>
                        <a href="{{ route('admin.units.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </x-layout.page-container>
</x-app-layout>
