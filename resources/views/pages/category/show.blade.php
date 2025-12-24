@extends('layouts.app')

@section('title', 'Detalhes da Categoria')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Detalhes da Categoria"
        icon="tag"
        :breadcrumb-items="[
            'Categorias' => route('provider.categories.index'),
            $category->name => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas da categoria</p>
    </x-page-header>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4">

                @php($isAdmin = false)
                @role('admin')
                @php($isAdmin = true)
                @endrole

                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Nome</label>
                        <h5 class="mb-0">
                            {{ $category->name }}
                        </h5>
                    </div>
                </div>

                @if ($category->parent)
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Categoria Pai</label>
                        <h5 class="mb-0">
                            <a href="{{ route('provider.categories.show', $category->parent->slug) }}"
                                class="text-decoration-none">
                                {{ $category->parent->name }}
                            </a>
                        </h5>
                    </div>
                </div>
                @endif


                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Criado em</label>
                        <h5 class="mb-0">{{ $category->created_at?->format('d/m/Y H:i') }}</h5>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="d-flex flex-column">
                        <label class="text-muted small mb-1">Atualizado em</label>
                        <h5 class="mb-0">{{ $category->updated_at?->format('d/m/Y H:i') }}</h5>
                    </div>
                </div>
            </div>

            @if (!$category->parent_id)
            @php($children = $category->children)
            @if ($children->isNotEmpty())
            <div class="mt-4">
                <h5 class="mb-3"><i class="bi bi-diagram-3 me-2"></i>Subcategorias ({{ $children->count() }})
                </h5>
                <!-- Desktop View -->
                <div class="desktop-view">
                    <div class="table-responsive">
                        <table class="modern-table table mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Status</th>
                                    <th>Criado em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($children as $child)
                                <tr>
                                    <td>{{ $child->name }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">Subcategoria</span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="modern-badge {{ $child->is_active ? 'badge-active' : 'badge-inactive' }}">
                                            {{ $child->is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </td>
                                    <td>{{ $child->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('provider.categories.show', $child->slug) }}"
                                            class="btn btn-sm btn-outline-secondary" title="Visualizar">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mobile View -->
                <div class="mobile-view">
                    <div class="list-group">
                        @foreach ($children as $child)
                        <a href="{{ route('provider.categories.show', $child->slug) }}"
                            class="list-group-item list-group-item-action py-3">
                            <div class="d-flex align-items-start">
                                <i class="bi bi-tag text-muted me-2 mt-1"></i>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold mb-2">{{ $child->name }}</div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span
                                            class="modern-badge {{ $child->is_active ? 'badge-active' : 'badge-inactive' }}">
                                            {{ $child->is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                    </div>
                                </div>
                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>

    <div class="mt-auto pt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto order-2 order-md-1">
                <x-back-button index-route="provider.categories.index" class="w-100 w-md-auto px-md-3" />
            </div>

            <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                <small class="text-muted">
                    Última atualização: {{ $category->updated_at?->format('d/m/Y H:i') }}
                </small>
            </div>

            <div class="col-12 col-md-auto order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    @if ($category->deleted_at)
                    <button type="button" class="btn btn-success" style="min-width: 120px;" data-bs-toggle="modal"
                        data-bs-target="#restoreModal-{{ $category->slug }}">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restaurar
                    </button>
                    @else
                    <a href="{{ route('provider.categories.edit', $category->slug) }}" class="btn btn-primary" style="min-width: 120px;">
                        <i class="bi bi-pencil-fill me-2"></i>Editar
                    </a>

                    <button type="button" class="btn {{ $category->is_active ? 'btn-warning' : 'btn-success' }}" style="min-width: 120px;"
                        data-bs-toggle="modal" data-bs-target="#toggleModal-{{ $category->slug }}">
                        <i class="bi bi-{{ $category->is_active ? 'slash-circle' : 'check-lg' }} me-2"></i>
                        {{ $category->is_active ? 'Desativar' : 'Ativar' }}
                    </button>

                    @php($canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0)
                    @if ($canDelete)
                    <button type="button" class="btn btn-outline-danger" style="min-width: 120px;" data-bs-toggle="modal"
                        data-bs-target="#deleteModal-{{ $category->slug }}">
                        <i class="bi bi-trash-fill me-2"></i>Excluir
                    </button>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="modal fade" id="deleteModal-{{ $category->slug }}" tabindex="-1"
    aria-labelledby="deleteModalLabel-{{ $category->slug }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel-{{ $category->slug }}">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja excluir a categoria <strong>"{{ $category->name }}"</strong>?
                <br><small class="text-muted">Esta ação pode ser desfeita através da lixeira.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('provider.categories.destroy', $category->slug) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Ativação/Desativação -->
<div class="modal fade" id="toggleModal-{{ $category->slug }}" tabindex="-1"
    aria-labelledby="toggleModalLabel-{{ $category->slug }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleModalLabel-{{ $category->slug }}">
                    Confirmar {{ $category->is_active ? 'Desativação' : 'Ativação' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja {{ $category->is_active ? 'desativar' : 'ativar' }} a categoria
                <strong>"{{ $category->name }}"</strong>?
                <br><small class="text-muted">Esta ação altera a visibilidade da categoria no sistema.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('provider.categories.toggle-status', $category->slug) }}" method="POST"
                    class="d-inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $category->is_active ? 'btn-warning' : 'btn-success' }}">
                        <i class="bi bi-{{ $category->is_active ? 'slash-circle' : 'check-lg' }} me-2"></i>
                        {{ $category->is_active ? 'Desativar' : 'Ativar' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Restauração -->
<div class="modal fade" id="restoreModal-{{ $category->slug }}" tabindex="-1"
    aria-labelledby="restoreModalLabel-{{ $category->slug }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="restoreModalLabel-{{ $category->slug }}">Confirmar Restauração</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                Tem certeza de que deseja restaurar a categoria <strong>"{{ $category->name }}"</strong>?
                <br><small class="text-muted">A categoria será restaurada e ficará disponível novamente.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('provider.categories.restore', $category->slug) }}" method="POST"
                    class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Restaurar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
