@extends('layouts.app')

@section('title', 'Dashboard de Categorias')

@section('content')
<x-layout.page-container>
    <!-- Cabeçalho -->
    <x-layout.page-header
        title="Dashboard de Categorias"
        icon="tags"
        description="Visão geral das suas categorias."
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Categorias' => '#'
        ]"
    />

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
        <x-layout.grid-col size="col-lg-8">
            <x-resource.resource-list-card
                title="Categorias Recentes"
                mobileTitle="Recentes"
                icon="clock-history"
                :total="$recent->count()"
                class="h-100"
            >
                @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Categoria</x-resource.table-cell>
                                    <x-resource.table-cell header>Tipo</x-resource.table-cell>
                                    <x-resource.table-cell header>Status</x-resource.table-cell>
                                    <x-resource.table-cell header>Criada em</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:thead>

                            @foreach ($recent as $category)
                                <x-resource.table-row>
                                    <x-resource.table-cell>
                                        <x-resource.resource-info
                                            :title="$category->name"
                                            :subtitle="$category->slug"
                                            titleClass="fw-bold text-dark"
                                        />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        <x-ui.badge
                                            :variant="$category->parent_id ? 'info' : 'secondary'"
                                            :label="$category->parent_id ? 'Subcategoria' : 'Principal'"
                                        />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        <x-ui.status-badge :item="$category" statusField="active" />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="text-muted small">
                                        {{ $category->created_at->format('d/m/Y') }}
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center">
                                        <x-resource.action-buttons
                                            :item="$category"
                                            resource="categories"
                                            identifier="slug"
                                            :can-delete="false"
                                        />
                                    </x-resource.table-cell>
                                </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $category)
                            <x-resource.resource-mobile-item
                                :href="route('provider.categories.index', ['search' => $category->name])"
                            >
                                <x-resource.resource-mobile-header
                                    :title="$category->name"
                                    :subtitle="$category->created_at->format('d/m/Y')"
                                />
                                <x-resource.resource-mobile-field
                                    label="Tipo"
                                >
                                    <x-ui.badge
                                        :variant="$category->parent_id ? 'info' : 'secondary'"
                                        :label="$category->parent_id ? 'Subcategoria' : 'Principal'"
                                    />
                                </x-resource.resource-mobile-field>
                                <x-resource.resource-mobile-field label="Status">
                                    <x-ui.status-badge :item="$category" statusField="active" />
                                </x-resource.resource-mobile-field>
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state
                        title="Nenhuma categoria recente"
                        description="Suas categorias aparecerão aqui conforme forem criadas."
                        :icon="null"
                    />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Insights e Atalhos -->
        <x-layout.grid-col size="col-lg-4">
            <x-layout.v-stack gap="4">
                <!-- Insights -->
                <x-resource.resource-list-card
                    title="Insights Rápidos"
                    icon="lightbulb"
                    padding="p-3"
                    gap="3"
                >
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
                </x-resource.resource-list-card>

                <!-- Atalhos -->
                <x-resource.quick-actions
                    title="Ações de Categoria"
                    icon="lightning-charge"
                >
                    <x-ui.button type="link" :href="route('provider.categories.create')" variant="success" icon="plus-lg" label="Nova Categoria" />
                    <x-ui.button type="link" :href="route('provider.categories.index')" variant="primary" icon="tags" label="Listar Categorias" />
                    <x-ui.button type="link" :href="route('provider.categories.index', ['deleted' => 'only'])" variant="secondary" icon="trash" label="Ver Deletadas" />
                </x-resource.quick-actions>
            </x-layout.v-stack>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
