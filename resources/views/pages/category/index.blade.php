@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<div class="container-fluid py-1">
    <x-page-header
        title="Categorias"
        icon="tags"
        :breadcrumb-items="[
            'Categorias' => route('provider.categories.dashboard'),
            'Listar' => '#'
        ]">
        <p class="text-muted mb-0">Lista de suas categorias</p>
    </x-page-header>

    <div class="row">
        <div class="col-12">
            <!-- Filtros de Busca -->
            <x-filter-form
                id="filtersFormCategories"
                :route="route('provider.categories.index')"
                :filters="$filters"
            >
                <x-filter-field
                    type="text"
                    name="search"
                    label="Buscar"
                    placeholder="Categoria, Subcategoria"
                    :filters="$filters"
                />

                <x-filter-field
                    type="select"
                    name="active"
                    label="Status"
                    col="col-md-2"
                    :options="[
                        '1' => 'Ativo',
                        '0' => 'Inativo',
                        'all' => 'Todos'
                    ]"
                    :filters="$filters"
                />

                <x-filter-field
                    type="select"
                    name="per_page"
                    label="Por página"
                    col="col-md-2"
                    :options="[
                        10 => '10',
                        20 => '20',
                        50 => '50'
                    ]"
                    :filters="$filters"
                />

                <x-filter-field
                    type="select"
                    name="deleted"
                    label="Registros"
                    col="col-md-2"
                    :options="[
                        'current' => 'Atuais',
                        'only' => 'Deletados',
                        'all' => 'Todos'
                    ]"
                    :filters="$filters"
                />

                <x-filter-field
                    type="date"
                    name="start_date"
                    label="Cadastro Inicial"
                    col="col-md-2"
                    :filters="$filters"
                />

                <x-filter-field
                    type="date"
                    name="end_date"
                    label="Cadastro Final"
                    col="col-md-2"
                    :filters="$filters"
                />
            </x-filter-form>

            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Categorias</span>
                                    <span class="d-sm-none">Categorias</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                    ({{ $categories->total() }})
                                    @else
                                    ({{ $categories->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <x-table-header-actions
                            resource="categories"
                            :filters="$filters"
                            createLabel="Nova"
                        />
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($categories as $category)
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    <div class="me-3 mt-1">
                                        <div class="avatar-circle"
                                            style="width: 40px; height: 40px; background-color: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                            <i class="bi bi-tag-fill"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-1">
                                            {{ $category->parent_id && $category->parent ? $category->parent->name : $category->name }}
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap mb-2">
                                            <x-status-badge :item="$category" />
                                        </div>
                                        @if ($category->parent_id)
                                        <div class="mb-2">
                                            <small class="text-muted">Subcategoria:
                                                {{ $category->name }}</small>
                                        </div>
                                        @endif

                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                {{ $category->created_at?->format('d/m/Y') ?? '—' }}
                                            </small>
                                            <x-action-buttons
                                                :item="$category"
                                                resource="categories"
                                                identifier="slug"
                                                :canDelete="$category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0"
                                                size="sm"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <x-empty-state
                                resource="categorias"
                                :isTrashView="($filters['deleted'] ?? '') === 'only'"
                            />
                            @endforelse
                        </div>
                    </div>

                    <!-- Versão Desktop: Tabela -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th width="60"><i class="bi bi-tag" aria-hidden="true"></i></th>
                                        <th>Categoria</th>
                                        <th>Subcategoria</th>
                                        <th width="120">Status</th>
                                        <th width="150">Criado em</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($categories as $category)
                                    <tr>
                                        <td>
                                            <div class="item-icon">
                                                <i class="bi bi-tag-fill"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="item-name-cell">
                                                @if ($category->parent_id && $category->parent)
                                                {{ $category->parent->name }}
                                                @else
                                                {{ $category->name }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($category->parent_id)
                                            <span class="text-muted">{{ $category->name }}</span>
                                            @else
                                            <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <x-status-badge :item="$category" />
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $category->created_at?->format('d/m/Y H:i') ?? '—' }}
                                            </small>
                                        </td>
                                        <td>
                                            @php($parentIsTrashed = $category->parent_id && $category->parent && $category->parent->trashed())
                                            <x-action-buttons
                                                :item="$category"
                                                resource="categories"
                                                identifier="slug"
                                                :canDelete="$category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0"
                                                :restoreBlocked="$parentIsTrashed"
                                                restoreBlockedMessage="<strong>Ação Bloqueada</strong><br>Não é possível restaurar esta subcategoria porque a categoria pai está na lixeira. Restaure o pai primeiro."
                                            />
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6">
                                            <x-empty-state
                                                resource="categorias"
                                                :isTrashView="($filters['deleted'] ?? '') === 'only'"
                                            />
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                @include('partials.components.paginator', [
                'p' => $categories->appends(
                collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()),
                'show_info' => true,
                ])
                @endif
            {{-- Modais de Confirmação --}}
            <x-confirm-modal
                id="deleteModal"
                type="delete"
                resource="categoria"
                method="DELETE"
            />

            <x-confirm-modal
                id="restoreModal"
                type="restore"
                resource="categoria"
                method="POST"
            />
            </div>
        </div>
        <div class="modal fade" id="confirmAllCategoriesModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Listar todas as categorias?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Fechar"></button>
                    </div>
                    <div class="modal-body">
                        <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                    </div>
                    <div class="modal-footer">
                        <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                        <x-button type="button" class="btn-confirm-all-categories" label="Listar todos" />
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/category.js') }}?v={{ time() }}"></script>
    // Validação de datas no formulário de filtros
    document.addEventListener('DOMContentLoaded', function() {
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        const form = document.getElementById('filtersFormCategories');

        if (form && startDate && endDate) {
            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    const message = 'A data inicial não pode ser maior que a data final.';
                    if (window.easyAlert) {
                        window.easyAlert.warning(message);
                    } else {
                        alert(message);
                    }
                    return false;
                }
                return true;
            };

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }

                if (startDate.value && !endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    endDate.focus();
                } else if (!startDate.value && endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    startDate.focus();
                }
            });
        }
    });
</script>
@endpush
