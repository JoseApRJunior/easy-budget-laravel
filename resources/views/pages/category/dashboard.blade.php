@extends('layouts.app')

@section('title', 'Dashboard de Categorias')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="flex-grow-1">
                    <h1 class="h4 h3-md mb-1">
                        <i class="bi bi-tags me-2"></i>
                        <span class="d-none d-sm-inline">Dashboard de Categorias</span>
                        <span class="d-sm-none">Categorias</span>
                    </h1>
                </div>
                <nav aria-label="breadcrumb" class="d-none d-md-block">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Dashboard de Categorias
                        </li>
                    </ol>
                </nav>
            </div>
            <p class="text-muted mb-0 small">
                Visão geral das suas categorias.
            </p>
        </div>

        @php
            $total = $stats['total_categories'] ?? 0;
            $active = $stats['active_categories'] ?? 0;
            $inactive = $stats['inactive_categories'] ?? 0;
            $recent = $stats['recent_categories'] ?? collect();
            $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
        @endphp

        <!-- Cards de Métricas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-tags text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total de Categorias</h6>
                                <h3 class="mb-0">{{ $total }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Total de categorias cadastradas.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-check-circle-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Categorias Ativas</h6>
                                <h3 class="mb-0">{{ $active }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Categorias disponíveis para uso em produtos e serviços.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-secondary bg-gradient me-3">
                                <i class="bi bi-pause-circle-fill text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Categorias Inativas</h6>
                                <h3 class="mb-0">{{ $inactive }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Categorias desativadas, não disponíveis para novos lançamentos.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-info bg-gradient me-3">
                                <i class="bi bi-graph-up-arrow text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Taxa de Atividade</h6>
                                <h3 class="mb-0">{{ $activityRate }}%</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Percentual de categorias ativas em relação ao total cadastrado.
                        </p>
                    </div>
                </div>
            </div>
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
                                                            <span class="badge bg-primary ms-2" title="Personalizada"><i
                                                                    class="bi bi-person-fill"></i></span>
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
                                                        @if ($category->is_active)
                                                            <span class="badge bg-success-subtle text-success">Ativa</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">Inativa</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ optional($category->created_at)->format('d/m/Y') }}
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('categories.show', $category->slug) }}"
                                                            class="btn btn-sm btn-outline-secondary">
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
                                    @foreach ($recent as $category)
                                        <a href="{{ route('categories.show', $category->slug) }}"
                                            class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                @if ($category->parent)
                                                    <i
                                                        class="bi bi-arrow-return-right text-muted me-2 mt-1 subcategory-icon-mobile"></i>
                                                @endif
                                                <i class="bi bi-tag text-muted me-2 mt-1"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">{{ $category->name }}</div>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <span class="badge bg-primary" title="Personalizada"><i
                                                                class="bi bi-person-fill"></i></span>
                                                        @if ($category->is_active)
                                                            <span class="badge bg-success-subtle text-success">Ativa</span>
                                                        @else
                                                            <span class="badge bg-danger-subtle text-danger">Inativa</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <i class="bi bi-chevron-right text-muted ms-2"></i>
                                            </div>
                                        </a>
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
                        <a href="{{ route('categories.create') }}" class="btn btn-sm btn-success">
                            <i class="bi bi-plus-circle me-2"></i>Nova Categoria
                        </a>
                        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-tags me-2"></i>Listar Categorias
                        </a>
                        <a href="{{ route('categories.index', ['deleted' => 'only']) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-archive me-2"></i>Ver Deletadas
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
