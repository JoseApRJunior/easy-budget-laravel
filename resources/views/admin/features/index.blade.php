<x-app-layout title="Gerenciamento de Módulos (Feature Flags) - EasyBudget">
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Módulos do Sistema"
            icon="toggle-on"
            :breadcrumb-items="[
                'Admin' => route('admin.index'),
                'Módulos' => '#'
            ]">
            <x-ui.button 
                variant="primary" 
                data-bs-toggle="modal" 
                data-bs-target="#createFeatureModal"
                icon="plus-lg"
                label="Novo Módulo" />
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nome</th>
                                    <th>Slug</th>
                                    <th>Status</th>
                                    <th class="text-center">Em Dev</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($features as $feature)
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $feature->name }}</div>
                                        </td>
                                        <td>
                                            <code>{{ $feature->slug }}</code>
                                        </td>
                                        <td>
                                            @if($feature->status === \App\Models\Resource::STATUS_ACTIVE)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">Ativo</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inativo</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('admin.features.toggle-dev', $feature) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-sm border-0 bg-transparent p-0" title="Alternar status de desenvolvimento">
                                                    @if($feature->in_dev)
                                                        <i class="bi bi-cone-striped text-warning fs-5" title="Em Desenvolvimento (Restrito)"></i>
                                                    @else
                                                        <i class="bi bi-check-circle-fill text-success fs-5" title="Produção (Liberado)"></i>
                                                    @endif
                                                </button>
                                            </form>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
                                                <button type="button" 
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editFeatureModal"
                                                    data-id="{{ $feature->id }}"
                                                    data-name="{{ $feature->name }}"
                                                    data-description="{{ $feature->description }}"
                                                    onclick="populateEditModal(this)">
                                                    <i class="bi bi-pencil me-1"></i> Editar
                                                </button>

                                                <form action="{{ route('admin.features.toggle', $feature) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm {{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                        <i class="bi bi-{{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'toggle-on' : 'toggle-off' }} me-1"></i>
                                                        {{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'Desativar' : 'Ativar' }}
                                                    </button>
                                                </form>
                                                
                                                <form action="{{ route('admin.features.destroy', $feature) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este módulo?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>

    <!-- Create Feature Modal -->
    <div class="modal fade" id="createFeatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('admin.features.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Novo Módulo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome do Módulo</label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="Ex: Módulo de Estoque">
                        </div>
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (Identificador único)</label>
                            <input type="text" class="form-control" id="slug" name="slug" required placeholder="Ex: estoque">
                            <div class="form-text">Use apenas letras minúsculas e hifens.</div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Breve descrição da funcionalidade..."></textarea>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="in_dev" name="in_dev" checked>
                            <label class="form-check-label" for="in_dev">Módulo em Desenvolvimento</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Módulo</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Feature Modal -->
    <div class="modal fade" id="editFeatureModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form id="editFeatureForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Módulo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Nome do Módulo</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Descrição</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function populateEditModal(button) {
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');
            
            const form = document.getElementById('editFeatureForm');
            const nameInput = document.getElementById('edit_name');
            const descriptionInput = document.getElementById('edit_description');
            
            // Update form action
            form.action = `/admin/features/${id}`;
            
            // Populate fields
            nameInput.value = name;
            descriptionInput.value = description || '';
        }
    </script>
    @endpush
</x-app-layout>