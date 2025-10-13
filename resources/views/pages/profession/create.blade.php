@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-plus-circle me-2"></i>Nova Profissão
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url( '/admin' ) }}">Dashboard Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ url( '/admin/professions' ) }}">Profissões</a></li>
                    <li class="breadcrumb-item active">Nova</li>
                </ol>
            </nav>
        </div>

        <!-- Formulário -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ url( '/admin/professions/store' ) }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Nome -->
                        <div class="col-12">
                            <div class="form-floating">
                                <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                    name="name" placeholder="Nome da Profissão" value="{{ old( 'name' ) }}" required>
                                <label for="name">Nome da Profissão *</label>
                                @error( 'name' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ex: Desenvolvedor, Designer, Contador, etc.</div>
                            </div>
                        </div>

                        <!-- Slug será gerado automaticamente -->
                    </div>

                    <div class="mt-4 d-flex justify-content-between">
                        <a href="{{ url( '/admin/professions' ) }}" class="btn btn-outline-secondary">
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
