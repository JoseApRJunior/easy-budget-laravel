@extends('layouts.app')

@section('title', 'Detalhes da Categoria')

@section('content')
@php
    $isCurrentlyActive = $category->is_active;
    $hasChildren = $category->children_count > 0;
    // Uma categoria só não pode ser desativada se tiver serviços ou produtos vinculados.
    // Subcategorias não bloqueiam a desativação, pois serão desativadas em cascata.
    $canDeactivate = $category->services_count === 0 && $category->products_count === 0;

    // Uma subcategoria não pode ser ativada se o pai estiver inativo ou na lixeira.
    $parentIsInactive = false;
    if ($category->parent_id && $category->parent_id !== $category->id && $category->parent) {
        $parentIsInactive = !$category->parent->is_active || $category->parent->trashed();
    }
    $canActivate = !$parentIsInactive;

    // Lógica para o botão de toggle status
    $toggleDisabled = ($isCurrentlyActive && !$canDeactivate) || (!$isCurrentlyActive && !$canActivate);

    // Lógica para exclusão
    $canDelete = $category->children_count === 0 && $category->services_count === 0 && $category->products_count === 0;

    $toggleLabel = $isCurrentlyActive ? 'Desativar' : 'Ativar';
    $toggleIcon = $isCurrentlyActive ? 'slash-circle' : 'check-lg';
    $toggleVariant = $isCurrentlyActive ? 'warning' : 'success';
@endphp

<x-layout.page-container>
    <x-layout.page-header
        title="Detalhes da Categoria"
        icon="tag"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => route('provider.categories.dashboard'),
            $category->name => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas da categoria</p>
    </x-layout.page-header>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- Primeira Linha: Informações Principais --}}
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-3">
                        <div class="item-icon">
                            <i class="bi bi-tag"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Nome da Categoria</label>
                            <h5 class="mb-0 fw-bold">{{ $category->name }}</h5>
                        </div>
                    </div>
                </div>

                @if ($category->parent)
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="item-icon">
                            <i class="bi bi-folder2-open"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Categoria Pai</label>
                            <h5 class="mb-0">
                                <a href="{{ route('provider.categories.show', $category->parent->slug) }}"
                                    class="text-decoration-none text-primary fw-semibold">
                                    {{ $category->parent->name }}
                                </a>
                            </h5>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-md-3">
                    <div class="d-flex align-items-center gap-3 {{ !$category->parent ? 'offset-md-4' : '' }}">
                        <div class="item-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Status Atual</label>
                            <x-ui.status-badge :item="$category" />
                        </div>
                    </div>
                </div>

                {{-- Segunda Linha: Datas e Auditoria --}}
                <div class="col-12 mt-0">
                    <hr class="text-muted opacity-25">
                </div>

                <div class="col-md-4 mt-2">
                    <x-resource.resource-info
                        title="Criado em"
                        :subtitle="$category->created_at?->format('d/m/Y H:i')"
                        icon="calendar-plus"
                        class="small"
                    />
                </div>

                <div class="col-md-4 mt-2">
                    <x-resource.resource-info
                        title="Atualizado em"
                        :subtitle="$category->updated_at?->format('d/m/Y H:i')"
                        icon="calendar-check"
                        class="small"
                    />
                </div>

                @if($category->deleted_at)
                <div class="col-md-4 mt-2">
                    <x-resource.resource-info
                        title="Deletado em"
                        :subtitle="$category->deleted_at->format('d/m/Y H:i')"
                        icon="calendar-x"
                        class="small"
                        iconClass="text-danger"
                        titleClass="text-danger"
                    />
                </div>
                @endif
            </div>

            @if (!$category->parent_id)
                @php
                    $children = $category->children;
                @endphp
                @if ($children->isNotEmpty())
                    <div class="mt-5">
                        <x-resource.resource-list-card
                            title="Subcategorias"
                            mobileTitle="Subcategorias"
                            icon="diagram-3"
                            :count="$children->count()"
                            class="border shadow-none"
                        >
                            <x-slot:desktop>
                                <x-resource.resource-table>
                                    <x-slot:thead>
                                        <tr>
                                            <th>Nome</th>
                                            <th width="150" class="text-center">Tipo</th>
                                            <th width="120" class="text-center">Status</th>
                                            <th width="180">Criado em</th>
                                            <th width="100" class="text-center">Ações</th>
                                        </tr>
                                    </x-slot:thead>
                                    <x-slot:tbody>
                                        @foreach ($children as $child)
                                            <tr>
                                                <td>
                                                    <x-resource.resource-info
                                                        :title="$child->name"
                                                        icon="tag"
                                                    />
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light text-primary border border-primary-subtle px-2 py-1">Subcategoria</span>
                                                </td>
                                                <td class="text-center">
                                                    <x-ui.status-badge :item="$child" />
                                                </td>
                                                <td>
                                                    <small class="text-muted">{{ $child->created_at?->format('d/m/Y H:i') }}</small>
                                                </td>
                                                <td class="text-center">
                                                    <x-ui.button type="link" :href="route('provider.categories.show', $child->slug)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </x-slot:tbody>
                                </x-resource.resource-table>
                            </x-slot:desktop>

                            <x-slot:mobile>
                                @foreach ($children as $child)
                                    <x-resource.resource-mobile-item>
                                        <x-resource.resource-info
                                            :title="$child->name"
                                            icon="tag"
                                        />
                                        <x-slot:description>
                                            <div class="mt-2">
                                                <x-ui.status-badge :item="$child" />
                                            </div>
                                        </x-slot:description>
                                        <x-slot:footer>
                                            <small class="text-muted">{{ $child->created_at?->format('d/m/Y') }}</small>
                                        </x-slot:footer>
                                        <x-slot:actions>
                                            <x-resource.table-actions mobile>
                                                <x-ui.button type="link" :href="route('provider.categories.show', $child->slug)" variant="info" icon="eye" size="sm" />
                                            </x-resource.table-actions>
                                        </x-slot:actions>
                                    </x-resource.resource-mobile-item>
                                @endforeach
                            </x-slot:mobile>
                        </x-resource.resource-list-card>
                    </div>
                @endif
            @endif

            @if ($parentIsInactive && !$isCurrentlyActive)
                <div class="alert alert-info mt-4 mb-0" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    Esta subcategoria não pode ser ativada porque a categoria pai <strong>{{ $category->parent->name }}</strong> está {{ $category->parent->trashed() ? 'na lixeira' : 'inativa' }}.
                </div>
            @endif

            @if (!$canDeactivate && $isCurrentlyActive)
                <div class="alert alert-warning mt-4 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Não é possível desativar esta categoria: ela possui serviços ou produtos vinculados.
                </div>
            @endif

            @if (!$canDelete && !$category->deleted_at)
                <div class="alert alert-warning mt-4 mb-0" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Não é possível excluir esta categoria: ela possui subcategorias, serviços ou produtos vinculados.
                </div>
            @endif
        </div>
    </div>

    <div class="mt-auto pt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto order-2 order-md-1">
                <x-ui.back-button index-route="provider.categories.index" class="w-100 w-md-auto px-md-3" />
            </div>

            <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                <small class="text-muted">
                    Última atualização: {{ $category->updated_at?->format('d/m/Y H:i') }}
                </small>
            </div>

            <div class="col-12 col-md-auto order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    @if ($category->deleted_at)
                    @php
                        $parentIsTrashed = $category->parent_id && $category->parent && $category->parent->trashed();
                    @endphp
                    <x-ui.button variant="success" style="min-width: 120px; {{ $parentIsTrashed ? 'cursor: not-allowed;' : '' }}"
                         data-bs-toggle="modal"
                         data-bs-target="{{ $parentIsTrashed ? '' : '#restoreModal-' . $category->slug }}"
                         icon="arrow-counterclockwise"
                         label="Restaurar"
                         title="{{ $parentIsTrashed ? 'Restaure o pai primeiro' : 'Restaurar' }}"
                         :class="$parentIsTrashed ? 'opacity-50' : ''"
                         onclick="{{ $parentIsTrashed ? 'easyAlert.warning("<strong>Ação Bloqueada</strong><br>Não é possível restaurar esta subcategoria porque a categoria pai está na lixeira. Restaure o pai primeiro.", { duration: 8000 }); return false;' : '' }}" />
                    @else
                    <x-ui.button type="link" :href="route('provider.categories.edit', $category->slug)" style="min-width: 120px;" icon="pencil-fill" label="Editar" />

                    @php
                        $blockedMessage = '';
                        if ($isCurrentlyActive && !$canDeactivate) {
                            $blockedMessage = 'Não é possível desativar esta categoria porque ela possui serviços ou produtos vinculados.';
                        } elseif (!$isCurrentlyActive && !$canActivate) {
                            $parentName = $category->parent ? $category->parent->name : 'superior';
                            $parentStatus = $category->parent && $category->parent->trashed() ? 'está na lixeira' : 'está inativa';
                            $blockedMessage = "Não é possível ativar esta subcategoria porque a categoria pai {$parentName} {$parentStatus}.";
                        }
                    @endphp

                    <x-ui.button :variant="$toggleVariant"
                        style="min-width: 120px; {{ $toggleDisabled ? 'cursor: not-allowed;' : '' }}"
                        data-bs-toggle="modal"
                        data-bs-target="{{ $toggleDisabled ? '' : '#toggleModal-' . $category->slug }}"
                        :icon="$toggleIcon"
                        :label="$toggleLabel"
                        :class="$toggleDisabled ? 'opacity-50' : ''"
                        onclick="{{ $toggleDisabled ? 'easyAlert.warning(\'<strong>Ação Bloqueada</strong><br>\' + \'' . addslashes($blockedMessage) . '\', { duration: 8000 }); return false;' : '' }}" />

                    @if ($canDelete)
                        <x-ui.button variant="outline-danger" style="min-width: 120px;" data-bs-toggle="modal"
                            data-bs-target="#deleteModal-{{ $category->slug }}" icon="trash-fill" label="Excluir" />
                    @else
                        <x-ui.button variant="outline-danger"
                            style="min-width: 120px; cursor: not-allowed;"
                            icon="trash-fill"
                            label="Excluir"
                            class="opacity-50"
                            onclick="easyAlert.warning('<strong>Exclusão Bloqueada</strong><br>Não é possível excluir esta categoria porque ela possui subcategorias, serviços ou produtos vinculados.', { duration: 8000 }); return false;" />
                    @endif
                    @endif
            </div>
        </div>
    </div>

    {{-- Modais --}}
    <div class="modal fade" id="deleteModal-{{ $category->slug }}" tabindex="-1"
        aria-labelledby="deleteModalLabel-{{ $category->slug }}" aria-hidden="true">
        {{-- ... conteúdo do modal ... --}}
    </div>
    {{-- ... outros modais ... --}}
</x-layout.page-container>
@endsection
