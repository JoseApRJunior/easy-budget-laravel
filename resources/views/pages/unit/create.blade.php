@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-plus-circle me-2"></i>Nova Unidade
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'admin.dashboard' ) }}">Dashboard Admin</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'admin.units.index' ) }}">Unidades</a></li>
                    <li class="breadcrumb-item active">Nova</li>
                </ol>
            </nav>
        </div>

        <!-- Formulário -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form action="{{ route( 'admin.units.store' ) }}" method="POST">
                    @csrf
                    <div class="row g-4">
                        <!-- Nome -->
                        <div class="col-md-8">
                            <div class="form-floating">
                                <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                    name="name" placeholder="Nome da Unidade" value="{{ old( 'name' ) }}" required>
                                <label for="name">Nome da Unidade *</label>
                                @error( 'name' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Abreviação -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control @error( 'abbreviation' ) is-invalid @enderror"
                                    id="abbreviation" name="abbreviation" placeholder="Abreviação"
                                    value="{{ old( 'abbreviation' ) }}" maxlength="10" required>
                                <label for="abbreviation">Abreviação *</label>
                                @error( 'abbreviation' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Ex: kg, m, un, l, etc.</div>
                            </div>
                        </div>

                    </div>

                    <!-- Botões -->
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Salvar Unidade</button>
                        <a href="{{ route( 'admin.units.index' ) }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
