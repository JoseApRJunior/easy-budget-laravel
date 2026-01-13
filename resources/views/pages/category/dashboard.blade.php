@extends('layouts.app')

@section('title', 'Dashboard de Categorias')

@section('content')
<x-layout.page-container>
    <!-- Cabeçalho -->
    <x-layout.page-header
        title="Dashboard de Categorias"
        icon="tags"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => '#'
        ]">
        <p class="text-muted mb-0 small">Visão geral das suas categorias.</p>
    </x-layout.page-header>

    @php
    $total = $stats['total_categories'] ?? 0;
    $active = $stats['active_categories'] ?? 0;
    $inactive = $stats['inactive_categories'] ?? 0;
    $deleted = $stats['deleted_categories'] ?? 0;
    $recent = $stats['recent_categories'] ?? collect();
    $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
    @endphp

    <!-- Cards de Métricas -->
    <x-layout.grid-row class="mb-4">
        <x-dashboard.stat-card
            title="Total"
            :value="$total"
            description="Ativas e inativas."
            icon="tags"
            variant="primary"
            isCustom
        />

        <x-dashboard.stat-card
            title="Ativas"
            :value="$active"
            description="Disponíveis para uso."
            icon="check-circle-fill"
            variant="success"
            isCustom
        />

        <x-dashboard.stat-card
            title="Inativas"
            :value="$inactive"
            description="Suspensas temporariamente."
            icon="pause-circle-fill"
            variant="secondary"
            isCustom
        />

        <x-dashboard.stat-card
            title="Deletadas"
            :value="$deleted"
            description="Na lixeira."
            icon="trash3-fill"
            variant="danger"
            isCustom
        />

        <x-dashboard.stat-card
            title="Taxa Uso"
            :value="$activityRate . '%'"
            description="Percentual de ativas."
            icon="percent"
            variant="info"
            isCustom
        />
    </x-layout.grid-row>

    <!-- Conteúdo Principal -->
    <x-layout.grid-row>
        <!-- Categorias Recentes -->
        <x-layout.grid-col lg="8">
            <x-resource.resource-list-card
                title="Categorias Recentes"
                mobileTitle="Recentes"
                icon="clock-history"
                class="h-100"
            >
                               @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
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
                                            <x-resource.resource-info
                                                :title="$category->name"
                                                icon="tag"
                                                :subtitle="$category->parent ? 'Subcategoria de ' . $category->parent->name : null"
                                            />
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $category->parent_id ? 'Subcategoria' : 'Categoria' }}</small>
                                        </td>
                                        <td>
                                            <x-ui.status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $category->created_at?->format('d/m/Y') }}</small>
                                        </td>
                                        <x-resource.table-actions>
                                            <x-ui.button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" size="sm" title="Visualizar" />
                                            <x-ui.button type="link" :href="route('provider.categories.edit', $category->slug)" variant="primary" icon="pencil-square" size="sm" title="Editar" />
                                        </x-resource.table-actions>
                                    </tr>
                                @endforeach
                            </x-slot:tbody>
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $category)
                            <x-resource.resource-mobile-item>
                                <x-resource.resource-info
                                    :title="$category->name"
                                    icon="tag"
                                />
                                <x-slot:description>
                                    @if($category->parent)
                                        <x-resource.resource-info
                                            :title="'Pai: ' . $category->parent->name"
                                            icon="arrow-return-right"
                                            class="mt-1"
                                        />
                                    @endif
                                    <div class="mt-2">
                                        <x-ui.status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                    </div>
                                </x-slot:description>
                                <x-slot:footer>
                                    <small class="text-muted">{{ $category->created_at?->format('d/m/Y') }}</small>
                                </x-slot:footer>
                                <x-slot:actions>
                                    <x-resource.table-actions mobile>
                                        <x-ui.button type="link" :href="route('provider.categories.show', $category->slug)" variant="info" icon="eye" size="sm" />
                                        <x-ui.button type="link" :href="route('provider.categories.edit', $category->slug)" variant="primary" icon="pencil-square" size="sm" />
                                    </x-resource.table-actions>
                                </x-slot:actions>
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state resource="categorias recentes" />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Insights e Atalhos -->
        <x-layout.grid-col lg="4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <x-layout.v-stack gap="3">
                        <x-dashboard.insight-item
                            icon="diagram-3-fill"
                            variant="primary"
                            description="Mantenha a estrutura hierárquica organizada para facilitar a navegação."
                        />
                        <x-dashboard.insight-item
                            icon="tag-fill"
                            variant="success"
                            description="Use nomes descritivos para suas categorias."
                        />
                        <x-dashboard.insight-item
                            icon="exclamation-triangle-fill"
                            variant="warning"
                            description="Revise categorias inativas que ainda podem ser úteis para o negócio."
                        />
                    </x-layout.v-stack>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-link-45deg me-2"></i>Atalhos
                    </h6>
                </div>
                <div class="card-body">
                    <x-resource.quick-actions>
                        <x-ui.button type="link" :href="route('provider.categories.create')" variant="success" size="sm" icon="plus-circle" label="Nova Categoria" />
                        <x-ui.button type="link" :href="route('provider.categories.index')" variant="primary" outline size="sm" icon="tags" label="Listar Categorias" />
                        <x-ui.button type="link" :href="route('provider.categories.index', ['deleted' => 'only'])" variant="secondary" outline size="sm" icon="archive" label="Ver Deletadas" />
                    </x-resource.quick-actions>
                </div>
            </div>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
