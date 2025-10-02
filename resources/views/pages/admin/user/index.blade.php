@extends( 'layout.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Gerenciar Usuários</h5>
                        <a href="{{ url( '/admin/users/create' ) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Novo Usuário
                        </a>
                    </div>
                    <div class="card-body">
                        <p>Lista de usuários do sistema</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
