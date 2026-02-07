<x-app-layout title="Nova Profissão">
    <x-layout.page-container>
        <x-layout.page-header
            title="Nova Profissão"
            icon="plus-circle"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Profissões' => url('/admin/professions'),
                'Nova' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="url('/admin/professions')" variant="secondary" outline icon="arrow-left" label="Voltar" />
            </x-slot:actions>
        </x-layout.page-header>

        <!-- Formulário -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ url('/admin/professions/store') }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Nome -->
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    id="name" name="name" placeholder="Nome da Profissão"
                                    value="{{ old('name') }}" required>
                                <label for="name">Nome da Profissão *</label>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ex: Desenvolvedor, Designer, Contador, etc.</div>
                            </div>
                        </div>

                        <!-- Slug será gerado automaticamente -->
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ url('/admin/professions') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i>Voltar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-layout.page-container>
</x-app-layout>
