@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Nova Área de Atividade"
            icon="plus-circle"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Áreas de Atividade' => url('/admin/area-of-activities'),
                'Nova' => '#'
            ]">
            <x-button :href="url('/admin/area-of-activities')" variant="secondary" outline icon="arrow-left" label="Voltar" />
        </x-page-header>

        <!-- Formulário -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ url('/admin/area-of-activities/store') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Nome -->
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" placeholder="Nome da Área de Atividade"
                                    value="{{ old('name') }}" required>
                                <label for="name">Nome da Área de Atividade *</label>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ex: Tecnologia, Saúde, Educação, Comércio, etc.</div>
                            </div>
                        </div>

                        <!-- Slug será gerado automaticamente -->
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ url('/admin/area-of-activities') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
