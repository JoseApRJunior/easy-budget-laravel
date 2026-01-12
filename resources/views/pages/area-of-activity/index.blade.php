@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Áreas de Atividade"
            icon="diagram-3"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Áreas de Atividade' => '#'
            ]">
            <x-ui.button type="link" :href="url('/admin/area-of-activities/create')" variant="primary" icon="plus-circle" label="Adicionar Área de Atividade" />
        </x-layout.page-header>

        <!-- Tabela de Áreas de Atividade -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                @if ($areaOfActivities && $areaOfActivities->count() > 0)
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
                                @foreach ($areaOfActivities as $areaOfActivity)
                                    <tr>
                                        <td class="px-4 py-3 fw-medium">{{ $areaOfActivity->id }}</td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-diagram-3 text-muted me-2"></i>
                                                {{ $areaOfActivity->name }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <code class="text-muted">{{ $areaOfActivity->slug }}</code>
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ $areaOfActivity->createdAt->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="d-flex justify-content-center gap-2">
                                                <x-ui.button type="link" :href="url('/admin/area-of-activities/' . $areaOfActivity->id)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                <x-ui.button type="link" :href="url('/admin/area-of-activities/' . $areaOfActivity->id . '/edit')" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                <x-ui.button variant="danger" size="sm" icon="trash" title="Excluir"
                                                    onclick="confirmDelete('{{ $areaOfActivity->id }}', '{{ $areaOfActivity->name }}')" />
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-diagram-3 text-muted" style="font-size: 3rem;"></i>
                        <h5 class="mt-3 text-muted">Nenhuma área de atividade encontrada</h5>
                        <p class="text-muted mb-4">Comece adicionando sua primeira área de atividade.</p>
                        <a href="{{ url('/admin/area-of-activities/create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Adicionar Área de Atividade
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
                    <p>Tem certeza que deseja excluir a área de atividade <strong id="areaOfActivityName"></strong>?</p>
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

    @push('scripts')
        <script>
            function confirmDelete(areaOfActivityId, areaOfActivityName) {
                document.getElementById('areaOfActivityName').textContent = areaOfActivityName;
                document.getElementById('deleteForm').action = '{{ url('/admin/area-of-activities') }}/' + areaOfActivityId;

                const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
                modal.show();
            }
        </script>
    @endpush
