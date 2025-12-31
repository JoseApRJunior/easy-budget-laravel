@extends('layouts.app')

@section('title', 'Relatórios')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-bar-chart-line me-2"></i>
                    Relatórios
                </h1>
                <p class="text-muted">Central de relatórios e analytics do sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('reports.dashboard') }}">Relatórios</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Listar</li>
                </ol>
            </nav>
        </div>

        <div class="row">
            <div class="col-12">
                <!-- Tipos de Relatório -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                    <span class="me-2">
                                        <i class="bi bi-bar-chart-line me-1"></i>
                                        <span class="d-none d-sm-inline">Tipos de Relatório</span>
                                        <span class="d-sm-none">Relatórios</span>
                                    </span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <!-- Mobile View -->
                        <div class="mobile-view">
                            <div class="list-group list-group-flush">
                                <a href="{{ route('reports.financial') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-graph-up text-primary me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Financeiro</div>
                                            <p class="text-muted small mb-2">Receitas, despesas e análises</p>
                                            <span class="badge bg-primary-subtle text-primary">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('reports.customers') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-people text-info me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Clientes</div>
                                            <p class="text-muted small mb-2">Base de clientes e CRM</p>
                                            <span class="badge bg-info-subtle text-info">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('reports.products') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-box text-success me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Produtos</div>
                                            <p class="text-muted small mb-2">Catálogo e inventário</p>
                                            <span class="badge bg-success-subtle text-success">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('reports.services') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-gear text-warning me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Serviços</div>
                                            <p class="text-muted small mb-2">Prestação de serviços</p>
                                            <span class="badge bg-warning-subtle text-warning">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('reports.budgets') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-receipt text-danger me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Orçamentos</div>
                                            <p class="text-muted small mb-2">Propostas e vendas</p>
                                            <span class="badge bg-danger-subtle text-danger">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('reports.analytics') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-pie-chart text-secondary me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Analytics</div>
                                            <p class="text-muted small mb-2">Métricas avançadas</p>
                                            <span class="badge bg-secondary-subtle text-secondary">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>

                                <a href="{{ route('provider.inventory.report') }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-boxes text-info me-3 mt-1" style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">Estoque</div>
                                            <p class="text-muted small mb-2">Controle de inventário</p>
                                            <span class="badge bg-info-subtle text-info">Relatório</span>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>
                            </div>
                        </div>

                        <!-- Versão Desktop: Cards -->
                        <div class="desktop-view">
                            <div class="row g-3 p-3">
                                <!-- Card Financeiro -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-primary mb-2">
                                                <i class="bi bi-graph-up fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Financeiro</h5>
                                            <p class="card-text text-muted small">Receitas, despesas e análises</p>
                                            <a href="{{ route('reports.financial') }}" class="btn btn-primary btn-sm">
                                                <i class="bi bi-graph-up me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Clientes -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-info mb-2">
                                                <i class="bi bi-people fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Clientes</h5>
                                            <p class="card-text text-muted small">Base de clientes e CRM</p>
                                            <a href="{{ route('reports.customers') }}" class="btn btn-info btn-sm text-white">
                                                <i class="bi bi-people me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Produtos -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-success mb-2">
                                                <i class="bi bi-box fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Produtos</h5>
                                            <p class="card-text text-muted small">Catálogo e inventário</p>
                                            <a href="{{ route('reports.products') }}" class="btn btn-success btn-sm">
                                                <i class="bi bi-box me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Serviços -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-warning mb-2">
                                                <i class="bi bi-gear fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Serviços</h5>
                                            <p class="card-text text-muted small">Prestação de serviços</p>
                                            <a href="{{ route('reports.services') }}" class="btn btn-warning btn-sm">
                                                <i class="bi bi-gear me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Orçamentos -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-danger mb-2">
                                                <i class="bi bi-receipt fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Orçamentos</h5>
                                            <p class="card-text text-muted small">Propostas e vendas</p>
                                            <a href="{{ route('reports.budgets') }}" class="btn btn-danger btn-sm">
                                                <i class="bi bi-receipt me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Analytics -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-secondary mb-2">
                                                <i class="bi bi-pie-chart fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Analytics</h5>
                                            <p class="card-text text-muted small">Métricas avançadas</p>
                                            <a href="{{ route('reports.analytics') }}" class="btn btn-secondary btn-sm">
                                                <i class="bi bi-pie-chart me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Card Estoque -->
                                <div class="col-lg-4 col-md-6">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <div class="text-info mb-2">
                                                <i class="bi bi-boxes fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Estoque</h5>
                                            <p class="card-text text-muted small">Controle de inventário</p>
                                            <a href="{{ route('provider.inventory.report') }}"
                                                class="btn btn-info btn-sm text-white">
                                                <i class="bi bi-boxes me-1"></i>Ver Relatório
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Relatórios Recentes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                                <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                    <span class="me-2">
                                        <i class="bi bi-clock-history me-1"></i>
                                        <span class="d-none d-sm-inline">Relatórios Recentes</span>
                                        <span class="d-sm-none">Recentes</span>
                                    </span>
                                    <span class="text-muted" style="font-size: 0.875rem;">
                                        @if ($recent_reports && count($recent_reports) > 0)
                                            ({{ count($recent_reports) }})
                                        @endif
                                    </span>
                                </h5>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        <!-- Mobile View -->
                        <div class="mobile-view">
                            @if ($recent_reports && count($recent_reports) > 0)
                                <div class="list-group list-group-flush">
                                    @foreach ($recent_reports as $report)
                                        <div class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-file-earmark-pdf text-danger me-3 mt-1"
                                                    style="font-size: 1.2rem;"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-1">{{ ucfirst($report->type ?? 'N/A') }}
                                                    </div>
                                                    <p class="text-muted small mb-2">{{ $report->period ?? 'N/A' }}</p>
                                                    <small class="text-muted">
                                                        @if ($report->date)
                                                            {{ \Carbon\Carbon::parse($report->date)->format('d/m/Y H:i') }}
                                                        @else
                                                            {{ $report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('d/m/Y H:i') : 'N/A' }}
                                                        @endif
                                                    </small>
                                                    <div class="mt-2">
                                                        <span class="badge bg-success">Concluído</span>
                                                    </div>
                                                </div>
                                                <div class="btn-group" role="group">
                                                    @if (isset($report->download_url))
                                                        <a href="{{ $report->download_url }}"
                                                            class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-download"></i>
                                                        </a>
                                                    @endif
                                                    @if (isset($report->view_url))
                                                        <a href="{{ $report->view_url }}"
                                                            class="btn btn-sm btn-outline-secondary">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    <span class="d-none d-sm-inline">Nenhum relatório recente encontrado.</span>
                                    <span class="d-sm-none">Nenhum relatório recente.</span>
                                    <br>
                                    <small>Gere seu primeiro relatório clicando em um dos tipos acima.</small>
                                </div>
                            @endif
                        </div>

                        <!-- Versão Desktop: Tabela -->
                        <div class="desktop-view">
                            @if ($recent_reports && count($recent_reports) > 0)
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                        <thead>
                                            <tr>
                                                <th width="60"><i class="bi bi-file-earmark-pdf"
                                                        aria-hidden="true"></i></th>
                                                <th>Tipo</th>
                                                <th>Período</th>
                                                <th width="150">Gerado em</th>
                                                <th width="120">Status</th>
                                                <th width="150" class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recent_reports as $report)
                                                <tr>
                                                    <td>
                                                        <div class="item-icon">
                                                            <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="item-name-cell">
                                                            {{ ucfirst($report->type ?? 'N/A') }}
                                                        </div>
                                                    </td>
                                                    <td>{{ $report->period ?? 'N/A' }}</td>
                                                    <td>
                                                        <small class="text-muted">
                                                            @if ($report->date)
                                                                {{ \Carbon\Carbon::parse($report->date)->format('d/m/Y H:i') }}
                                                            @else
                                                                {{ $report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('d/m/Y H:i') : 'N/A' }}
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="modern-badge badge-active">Concluído</span>
                                                    </td>
                                                    <td>
                                                        <div class="action-btn-group">
                                                            @if (isset($report->download_url))
                                                                <a href="{{ $report->download_url }}"
                                                                    class="action-btn action-btn-view" title="Download">
                                                                    <i class="bi bi-download"></i>
                                                                </a>
                                                            @endif
                                                            @if (isset($report->view_url))
                                                                <a href="{{ $report->view_url }}"
                                                                    class="action-btn action-btn-edit" title="Visualizar">
                                                                    <i class="bi bi-eye"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    Nenhum relatório recente encontrado.
                                    <br>
                                    <small>Gere seu primeiro relatório clicando em um dos tipos acima.</small>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
