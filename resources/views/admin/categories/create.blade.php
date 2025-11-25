@extends( 'layouts.admin' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item"><a href="{{ route( 'admin.categories.index' ) }}">Categorias</a></li>
    <li class="breadcrumb-item active">Nova</li>
@endsection

@section( 'admin_content' )
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="bi bi-tag-plus me-2"></i>Nova Categoria
        </h1>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form action="{{ route( 'admin.categories.store' ) }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="form-floating">
                            <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                name="name" placeholder="Nome da Categoria" value="{{ old( 'name' ) }}" required>
                            <label for="name">Nome da Categoria *</label>
                            @error( 'name' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-between">
                    <a href="{{ route( 'admin.categories.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
