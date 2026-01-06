@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Funções"
            icon="person-badge"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Funções' => '#'
            ]">
            <x-button type="link" :href="url('/admin/roles/create')" variant="primary" icon="plus-circle" label="Adicionar Função" />
        </x-page-header>

        <!-- Tabela de Funções -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if ($roles && count($roles) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="px-4 py-3">#</th>
                                    <th scope="col" class="px-4 py-3">Nome</th>
                                    <th scope="col" class="px-4 py-3">Slug</th>
                                    <th scope="col" class="px-4 py-3">Criado em</th>
                                    <th scope="col" class="px-4 py-3 text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $role)
                                    <tr>
                                        <td class="px-4 py-3 fw-medium">{{ $role->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person-badge text-muted me-2"></i>
                                                {{ $role->name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <code class="text-muted">{{ $role->slug }}</code>
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ \Carbon\Carbon::parse($role->createdAt)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <x-button type="link" :href="url('/admin/roles/' . $role->id)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                <x-button type="link" :href="url('/admin/roles/' . $role->id . '/edit')" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                <x-button variant="danger" size="sm" icon="trash" title="Excluir"
                                                    onclick="confirmDelete('{{ $role->id }}', '{{ $role->name }}')" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-person-badge text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Nenhuma função encontrada</h5>
                        <p class="text-muted mb-4">Comece adicionando sua primeira função.</p>
                        <a href="{{ url('/admin/roles/create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Adicionar Função
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>Confirmar Exclusão
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir a função <strong id="roleName"></strong>?</p>
                    <p class="text-muted small mb-0">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@section('scripts')
    <script>
        function confirmDelete(roleId, roleName) {
            document.getElementById('roleName').textContent = roleName;
            document.getElementById('deleteForm').action = '/admin/roles/' + roleId;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
@endsection
