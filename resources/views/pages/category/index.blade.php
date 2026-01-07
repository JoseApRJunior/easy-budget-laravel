@extends('layouts.app')

@section('title', 'Categorias')

@section('content')
<x-page-container>
    <x-page-header
        title="Categorias"
        icon="tags"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => route('provider.categories.dashboard'),
            'Lista' => '#'
        ]">
        <p class="text-muted mb-0">Lista de suas categorias</p>
    </x-page-header>

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

            <x-resource-list-card
                class="mt-3 mt-md-0"
                title="Lista de Categorias"
                mobileTitle="Categorias"
                icon="list-ul"
                :total="$categories instanceof \Illuminate\Pagination\LengthAwarePaginator ? $categories->total() : $categories->count()"
            >
                <x-slot:headerActions>
                    <x-table-header-actions
                        resource="categories"
                        :filters="$filters"
                        createLabel="Nova"
                    />
                </x-slot:headerActions>

                <x-slot:desktop>
                    <x-resource-table>
                        <x-slot:thead>
                            <tr>
                                <th>Nome</th>
                                <th>Categoria Pai</th>
                                <th width="120">Status</th>
                                <th width="150">Criado em</th>
                                <th width="150" class="text-center">Ações</th>
                            </tr>
                        </x-slot:thead>

                        <x-slot:tbody>
                            @forelse($categories as $category)
                                <tr>
                                    <td>
                                        <x-resource-info
                                            :title="$category->name"
                                            icon="tag"
                                        />
                                    </td>
                                    <td>
                                        @if ($category->parent_id && $category->parent)
                                            <x-resource-info
                                                :title="$category->parent->name"
                                                icon="tag"
                                            />
                                        @else
                                            <span class=" small">—</span>
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
                                    <x-table-actions>
                                        @php($parentIsTrashed = $category->parent_id && $category->parent && $category->parent->trashed())
                                        <x-action-buttons
                                            :item="$category"
                                            resource="categories"
                                            identifier="slug"
                                            :canDelete="$category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0"
                                            :restoreBlocked="$parentIsTrashed"
                                            restoreBlockedMessage="<strong>Ação Bloqueada</strong><br>Não é possível restaurar esta subcategoria porque a categoria pai está na lixeira. Restaure o pai primeiro."
                                        />
                                    </x-table-actions>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-empty-state
                                            resource="categorias"
                                            :isTrashView="($filters['deleted'] ?? '') === 'only'"
                                        />
                                    </td>
                                </tr>
                            @endforelse
                        </x-slot:tbody>
                    </x-resource-table>
                </x-slot:desktop>

                <x-slot:mobile>
                    @forelse($categories as $category)
                            <x-resource-mobile-item>
                                <x-resource-info
                                    :title="$category->name"
                                    icon="tag"
                                />

                                <x-slot:description>
                                    <div class="d-flex gap-2 flex-wrap mb-1">
                                        <x-status-badge :item="$category" />
                                    </div>
                                    @if ($category->parent_id && $category->parent)
                                        <x-resource-info
                                            :title="'Pai: ' . $category->parent->name"
                                            icon="arrow-return-right"
                                            class="mt-1"
                                        />
                                    @endif
                                </x-slot:description>

                                <x-slot:footer>
                                    <small class="text-muted">
                                        {{ $category->created_at?->format('d/m/Y') ?? '—' }}
                                    </small>
                                </x-slot:footer>

                                <x-slot:actions>
                                    <x-table-actions mobile>
                                        <x-action-buttons
                                            :item="$category"
                                            resource="categories"
                                            identifier="slug"
                                            :canDelete="$category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0"
                                            size="sm"
                                        />
                                    </x-table-actions>
                                </x-slot:actions>
                            </x-resource-mobile-item>
                        @empty
                            <x-empty-state
                                resource="categorias"
                                :isTrashView="($filters['deleted'] ?? '') === 'only'"
                            />
                        @endforelse
                    </x-slot:mobile>

                <x-slot:footer>
                    @if ($categories instanceof \Illuminate\Pagination\LengthAwarePaginator && $categories->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $categories->appends(collect(request()->query())->map(fn($v) => is_null($v) ? '' : $v)->toArray()),
                            'show_info' => true,
                        ])
                    @endif
                </x-slot:footer>
            </x-resource-list-card>
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
    </x-page-container>

    {{-- Modal de Confirmação para Listar Tudo --}}
    <x-modal
        id="confirmAllCategoriesModal"
        title="Listar todas as categorias?"
    >
        <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>

        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
            <x-button type="button" class="btn-confirm-all-categories" label="Listar todos" />
        </x-slot:footer>
    </x-modal>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/category.js') }}?v={{ time() }}"></script>

@endpush
