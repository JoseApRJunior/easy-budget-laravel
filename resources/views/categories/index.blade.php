@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">
      <i class="bi bi-tags me-2"></i>Categorias
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Categorias</li>
      </ol>
    </nav>
  </div>

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

  {{-- Botão de Adicionar --}}
  <div class="mb-4">
    <a href="{{ route( 'categories.create' ) }}" class="btn btn-primary">
      <i class="bi bi-plus-circle me-2"></i>Nova Categoria
    </a>
  </div>

  {{-- Tabela de Categorias --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="bg-light">
            <tr>
              <th scope="col" class="ps-4">ID</th>
              <th scope="col">Nome</th>
              <th scope="col">Slug</th>
              <th scope="col">Criado em</th>
              <th scope="col" class="text-end pe-4">Ações</th>
            </tr>
          </thead>
          <tbody>
            {{-- Exemplo de dados - será populado com dados reais --}}
            <tr>
              <td colspan="5" class="text-center py-4">
                <div class="d-flex flex-column align-items-center">
                  <i class="bi bi-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                  <p class="mb-0">Nenhuma categoria encontrada</p>
                  <p class="text-muted small">Clique em "Nova Categoria" para adicionar</p>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Modal de Confirmação de Exclusão --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p>Tem certeza que deseja excluir a categoria <strong id="categoryName"></strong>?</p>
        <p class="text-danger"><small>Esta ação não pode ser desfeita.</small></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <form id="deleteForm" action="" method="POST">
          @csrf
          <button type="submit" class="btn btn-danger">Excluir</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Configurar modal de exclusão
  const deleteButtons = document.querySelectorAll('.delete-category');
  const deleteForm = document.getElementById('deleteForm');
  const categoryNameElement = document.getElementById('categoryName');

  deleteButtons.forEach(button => {
    button.addEventListener('click', function() {
      const categoryId = this.getAttribute('data-id');
      const categoryName = this.getAttribute('data-name');
      deleteForm.action = `/categories/${categoryId}`;
      categoryNameElement.textContent = categoryName;
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      deleteModal.show();
    });
  });
});
</script>
@endsection