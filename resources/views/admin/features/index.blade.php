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
                                            @if($feature->in_dev)
                                                <i class="bi bi-check-circle-fill text-primary" title="Em Desenvolvimento"></i>
                                            @else
                                                <i class="bi bi-dash-circle text-muted"></i>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex justify-content-end gap-2">
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
</x-app-layout>
