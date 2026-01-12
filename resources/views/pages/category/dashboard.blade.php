@extends('layouts.app')

@section('title', 'Dashboard de Categorias')

@section('content')
<x-page-container>
    <!-- Cabeçalho -->
    <x-page-header
        title="Dashboard de Categorias"
        icon="tags"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => '#'
        ]">
        <p class="text-muted mb-0 small">Visão geral das suas categorias.</p>
    </x-page-header>

    @php
    $total = $stats['total_categories'] ?? 0;
    $active = $stats['active_categories'] ?? 0;
    $inactive = $stats['inactive_categories'] ?? 0;
    $deleted = $stats['deleted_categories'] ?? 0;
    $recent = $stats['recent_categories'] ?? collect();
    $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <x-stat-card
            title="Total"
            :value="$total"
            description="Ativas e inativas."
            icon="tags"
            variant="primary"
            isCustom
        />

        <x-stat-card
            title="Ativas"
            :value="$active"
            description="Disponíveis para uso."
            icon="check-circle-fill"
            variant="success"
            isCustom
        />

        <x-stat-card
            title="Inativas"
            :value="$inactive"
            description="Suspensas temporariamente."
            icon="pause-circle-fill"
            variant="secondary"
            isCustom
        />

        <x-stat-card
            title="Deletadas"
            :value="$deleted"
            description="Na lixeira."
            icon="trash3-fill"
            variant="danger"
            isCustom
        />

        <x-stat-card
            title="Taxa Uso"
            :value="$activityRate . '%'"
            description="Percentual de ativas."
            icon="percent"
            variant="info"
            isCustom
        />
    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">
        <!-- Categorias Recentes -->
        <div class="col-lg-8">
            <x-resource-list-card
                title="Categorias Recentes"
                mobileTitle="Recentes"
                icon="clock-history"
                class="h-100"
            >
                               @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource-table>
                            <x-slot:thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th>Tipo</th>
                                    <th>Status</th>
                                    <th>Criada em</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </x-slot:thead>
                            <x-slot:tbody>
                                @foreach ($recent as $category)
                                    <tr>
                                        <td>
                                            <x-resource-info
                                                :title="$category->name"
                                                icon="tag"
                                                :subtitle="$category->parent ? 'Subcategoria de ' . $category->parent->name : null"
                                            />
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $category->parent_id ? 'Subcategoria' : 'Categoria' }}</small>
                                        </td>
                                        <td>
                                            <x-status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $category->created_at?->format('d/m/Y') }}</small>
                                        </td>
                                        <x-table-actions>
                                            <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" size="sm" title="Visualizar" />
                                            <x-button type="link" :href="route('provider.categories.edit', $category->slug)" variant="primary" icon="pencil-square" size="sm" title="Editar" />
                                        </x-table-actions>
                                    </tr>
                                @endforeach
                            </x-slot:tbody>
                        </x-resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $category)
                            <x-resource-mobile-item>
                                <x-resource-info
                                    :title="$category->name"
                                    icon="tag"
                                />
                                <x-slot:description>
                                    @if($category->parent)
                                        <x-resource-info
                                            :title="'Pai: ' . $category->parent->name"
                                            icon="arrow-return-right"
                                            class="mt-1"
                                        />
                                    @endif
                                    <div class="mt-2">
                                        <x-status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                    </div>
                                </x-slot:description>
                                <x-slot:footer>
                                    <small class="text-muted">{{ $category->created_at?->format('d/m/Y') }}</small>
                                </x-slot:footer>
                                <x-slot:actions>
                                    <x-table-actions mobile>
                                        <x-button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" size="sm" />
                                        <x-button type="link" :href="route('provider.categories.edit', $category->slug)" variant="primary" icon="pencil-square" size="sm" />
                                    </x-table-actions>
                                </x-slot:actions>
                            </x-resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-empty-state resource="categorias recentes" />
                @endif
            </x-resource-list-card>
        </div>

        <!-- Insights e Atalhos -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li class="mb-2">
                            <i class="bi bi-diagram-3-fill text-primary me-2"></i>
                            Mantenha a estrutura hierárquica organizada para facilitar a navegação.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-tag-fill text-success me-2"></i>
                            Use nomes descritivos para suas categorias.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                            Revise categorias inativas que ainda podem ser úteis para o negócio.
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h6 class="mb-0">
                        <i class="bi bi-link-45deg me-2"></i>Atalhos
                    </h6>
                </div>
                <div class="card-body d-grid gap-2">
                    <x-button type="link" :href="route('provider.categories.create')" variant="success" size="sm" icon="plus-circle" label="Nova Categoria" />
                    <x-button type="link" :href="route('provider.categories.index')" variant="primary" outline size="sm" icon="tags" label="Listar Categorias" />
                    <x-button type="link" :href="route('provider.categories.index', ['deleted' => 'only'])" variant="secondary" outline size="sm" icon="archive" label="Ver Deletadas" />
                </div>
            </div>
        </div>
    </div>
</x-page-container>
@endsection
