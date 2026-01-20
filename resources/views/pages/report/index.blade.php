@extends('layouts.app')

@section('title', 'Relatórios')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Relatórios"
            icon="bar-chart-line"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => route('reports.dashboard'),
                'Lista' => '#'
            ]">
            <p class="text-muted mb-0 small">Central de relatórios e analytics do sistema</p>
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <!-- Tipos de Relatório -->
                <x-ui.card class="mb-4">
                    <x-slot:header>
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
                    </x-slot:header>
                    <div class="p-0">
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
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-primary mb-2">
                                                <i class="bi bi-graph-up fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Financeiro</h5>
                                            <p class="card-text text-muted small">Receitas, despesas e análises</p>
                                            <x-ui.button type="link" :href="route('reports.financial')" variant="primary" size="sm" icon="graph-up" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Clientes -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-info mb-2">
                                                <i class="bi bi-people fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Clientes</h5>
                                            <p class="card-text text-muted small">Base de clientes e CRM</p>
                                            <x-ui.button type="link" :href="route('reports.customers')" variant="info" size="sm" icon="people" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Produtos -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-success mb-2">
                                                <i class="bi bi-box fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Produtos</h5>
                                            <p class="card-text text-muted small">Catálogo e inventário</p>
                                            <x-ui.button type="link" :href="route('reports.products')" variant="success" size="sm" icon="box" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Serviços -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-warning mb-2">
                                                <i class="bi bi-gear fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Serviços</h5>
                                            <p class="card-text text-muted small">Prestação de serviços</p>
                                            <x-ui.button type="link" :href="route('reports.services')" variant="warning" size="sm" icon="gear" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Orçamentos -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-danger mb-2">
                                                <i class="bi bi-receipt fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Orçamentos</h5>
                                            <p class="card-text text-muted small">Propostas e vendas</p>
                                            <x-ui.button type="link" :href="route('reports.budgets')" variant="danger" size="sm" icon="receipt" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Analytics -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-secondary mb-2">
                                                <i class="bi bi-pie-chart fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Analytics</h5>
                                            <p class="card-text text-muted small">Métricas avançadas</p>
                                            <x-ui.button type="link" :href="route('reports.analytics')" variant="secondary" size="sm" icon="pie-chart" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>

                                <!-- Card Estoque -->
                                <div class="col-lg-4 col-md-6">
                                    <x-ui.card class="h-100 border-0 shadow-sm">
                                        <div class="text-center">
                                            <div class="text-info mb-2">
                                                <i class="bi bi-boxes fa-2x"></i>
                                            </div>
                                            <h5 class="card-title">Estoque</h5>
                                            <p class="card-text text-muted small">Controle de inventário</p>
                                            <x-ui.button type="link" :href="route('provider.inventory.report')" variant="info" size="sm" icon="boxes" label="Ver Relatório" />
                                        </div>
                                    </x-ui.card>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>
        </div>

        <!-- Relatórios Recentes -->
        <div class="row">
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
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
                    </x-slot:header>
                    <div class="p-0">

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
                                                <div class="d-flex gap-1">
                                                        @if (isset($report->download_url))
                                                            <x-ui.button type="link" :href="$report->download_url" variant="primary" outline size="sm" icon="download" />
                                                        @endif
                                                        @if (isset($report->view_url))
                                                            <x-ui.button type="link" :href="$report->view_url" variant="info" size="sm" icon="eye" />
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
                                                        <div class="d-flex justify-content-center gap-1">
                                                            @if (isset($report->download_url))
                                                                <x-ui.button type="link" :href="$report->download_url" variant="primary" outline size="sm" icon="download" title="Download" />
                                                            @endif
                                                            @if (isset($report->view_url))
                                                                <x-ui.button type="link" :href="$report->view_url" variant="info" size="sm" icon="eye" title="Visualizar" />
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
                </x-ui.card>
            </div>
        </div>
    </div>
@endsection
