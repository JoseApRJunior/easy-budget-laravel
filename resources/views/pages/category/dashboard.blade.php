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
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        <span class="d-none d-sm-inline">Categorias Recentes</span>
                        <span class="d-sm-none">Recentes</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Categoria</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                        <th>Criada em</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recent as $category)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if ($category->parent)
                                                <i
                                                    class="bi bi-arrow-return-right text-muted me-2 subcategory-icon"></i>
                                                @endif
                                                <i class="bi bi-tag me-2 text-muted"></i>
                                                <span>{{ $category->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            @if ($category->parent)
                                            <small class="text-muted">Subcategoria</small>
                                            @else
                                            <small class="text-muted">Categoria</small>
                                            @endif
                                        </td>
                                        <td>
                                            <x-status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                        </td>
                                        <td>
                                            {{ optional($category->created_at)->format('d/m/Y') }}
                                        </td>
                                        <td class="text-center">
                                            <x-button type="link" :href="route('provider.categories.show', $category->slug)"
                                                    variant="info" size="sm" icon="eye" />
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
                            @foreach ($recent as $category)
                            <div class="list-group-item py-3">
                                <div class="d-flex align-items-start">
                                    @if ($category->parent)
                                    <i
                                        class="bi bi-arrow-return-right text-muted me-2 mt-1 subcategory-icon-mobile"></i>
                                    @endif
                                    <i class="bi bi-tag text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">{{ $category->name }}</div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <x-status-badge :item="$category" activeLabel="Ativa" inactiveLabel="Inativa" />
                                        </div>
                                    </div>
                                    <div class="ms-2">
                                        <x-button type="link" :href="route('provider.categories.show', $category->slug)"
                                            variant="info" size="sm" icon="eye" />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                    <div class="p-4">
                        <p class="text-muted mb-0">
                            Nenhuma categoria recente encontrada. Cadastre novas categorias para visualizar
                            aqui.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
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
