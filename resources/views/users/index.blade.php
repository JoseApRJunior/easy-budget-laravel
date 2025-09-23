@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Gerenciar Usuários</h5>
          <a href="{{ route( 'users.create' ) }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Novo Usuário
          </a>
        </div>
        <div class="card-body">
          {{-- Mensagens de erro/sucesso --}}
          @if( session( 'success' ) )
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session( 'success' ) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          @if( session( 'error' ) )
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session( 'error' ) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          @endif

          {{-- Tabela de Usuários --}}
          <div class="table-responsive">
            <table class="table table-hover">
              <thead class="table-light">
                <tr>
                  <th>Nome</th>
                  <th>Email</th>
                  <th>Perfil</th>
                  <th>Status</th>
                  <th>Data de Criação</th>
                  <th>Ações</th>
                </tr>
              </thead>
              <tbody>
                {{-- Exemplo de linha - será populado com dados reais --}}
                <tr>
                  <td colspan="6" class="text-center py-4 text-muted">
                    <i class="bi bi-info-circle me-2"></i>
                    Nenhum usuário encontrado. Comece criando o primeiro usuário.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {{-- Paginação --}}
          <div class="d-flex justify-content-center mt-4">
            {{-- Paginação será implementada quando houver dados --}}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Funcionalidades JavaScript para gerenciamento de usuários
  console.log('Users index page loaded');
});
</script>
@endsection