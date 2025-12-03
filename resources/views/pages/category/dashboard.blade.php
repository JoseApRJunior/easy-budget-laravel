@extends('layouts.app')

@section('title', 'Dashboard de Categorias')

@section('content')
    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-tags me-2"></i>Dashboard de Categorias
                </h1>
                @php
                    $isAdminView = false;
                @endphp
                @role('admin')
                    @php
                        $isAdminView = true;
                    @endphp
                @endrole
                <p class="text-muted mb-0">
                    {{ $isAdminView ? 'Visão geral das categorias globais do sistema com atalhos de gestão.' : 'Visão geral das suas categorias (custom + sistema) com atalhos de gestão.' }}
                </p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('categories.dashboard') }}">Categorias</a>
                    </li>
                </ol>
            </nav>
        </div>



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
                                <h3 class="mb-0">{{ $total_categories }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Quantidade total de categorias cadastradas e disponíveis para uso.
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
                                <h3 class="mb-0">{{ $active_categories }}</h3>
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
                                <h3 class="mb-0">{{ $inactive_categories }}</h3>
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
                                <h3 class="mb-0">{{ $activity_rate }}%</h3>
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
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Categorias Recentes
                        </h5>
                        <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-primary">
                            Ver todas
                        </a>
                    </div>
                    <div class="card-body">
                        @if ($recent_categories instanceof \Illuminate\Support\Collection && $recent_categories->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Categoria</th>
                                            <th>Tipo</th>
                                            <th>Status</th>
                                            <th>Criada em</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($recent_categories as $category)
                                            @php
                                                $tenantId = auth()->user()->tenant_id ?? null;
                                                $isCustom = $tenantId ? $category->isCustomFor($tenantId) : false;
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        @if ($category->parent)
                                                            <i class="bi bi-arrow-return-right text-muted me-2"
                                                                style="font-size: 0.8rem;"></i>
                                                        @endif
                                                        <i class="bi bi-tag me-2 text-muted"></i>
                                                        <span>{{ $category->name }}</span>
                                                        @if (!$category->parent)
                                                            @if ($isCustom)
                                                                <span class="badge bg-primary ms-2">Pessoal</span>
                                                            @else
                                                                <span class="badge bg-secondary ms-2">Sistema</span>
                                                            @endif
                                                        @endif
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($category->parent)
                                                        <small class="text-muted">Subcategoria</small>
                                                    @else
                                                        <small class="text-muted">Categoria Principal</small>
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
                                                    @php
                                                        $isAdminDate = false;
                                                    @endphp
                                                    @role('admin')
                                                        @php
                                                            $isAdminDate = true;
                                                        @endphp
                                                    @endrole
                                                    @php
                                                        $isGlobalDate = method_exists($category, 'isGlobal')
                                                            ? $category->isGlobal()
                                                            : false;
                                                    @endphp
                                                    @if ($isAdminDate || !$isGlobalDate)
                                                        {{ optional($category->created_at)->format('d/m/Y') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td class="text-end">
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
                        @else
                            <p class="text-muted mb-0">
                                Nenhuma categoria recente encontrada. Cadastre novas categorias para visualizar aqui.
                            </p>
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
                        @if ($isAdminView)
                            <ul class="list-unstyled mb-0 small text-muted">
                                <li class="mb-2">
                                    <i class="bi bi-shield-lock-fill text-primary me-2"></i>
                                    Gerencie categorias globais com cautela; mudanças afetam todos os espaços.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-code-slash text-success me-2"></i>
                                    Mantenha padronização em nomes e slugs para consistência.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-diagram-3-fill text-warning me-2"></i>
                                    Revise hierarquias e subcategorias para garantir coerência.
                                </li>
                            </ul>
                        @else
                            <ul class="list-unstyled mb-0 small text-muted">
                                <li class="mb-2">
                                    <i class="bi bi-diagram-3-fill text-primary me-2"></i>
                                    Mantenha a estrutura hierárquica organizada para facilitar a navegação.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-tag-fill text-success me-2"></i>
                                    Use categorias de sistema quando possível para padronizar com outros usuários.
                                </li>
                                <li class="mb-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                                    Revise categorias inativas que ainda podem ser úteis para o negócio.
                                </li>
                            </ul>
                        @endif
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
                        <a href="{{ route('categories.export', ['format' => 'xlsx']) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-text me-2"></i>Exportar (Excel)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .avatar-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
@endpush
