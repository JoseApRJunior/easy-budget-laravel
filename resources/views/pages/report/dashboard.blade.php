@extends('layouts.app')

@section('title', 'Dashboard de Relatórios')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Dashboard de Relatórios"
            icon="bar-chart-line"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Relatórios' => '#'
            ]">
            <p class="text-muted mb-0 small">Visão geral dos relatórios gerados e métricas de uso do sistema.</p>
        </x-page-header>

        @php
            $totalReports = $stats['total_reports'] ?? 0;
            $recentReports = $stats['recent_reports'] ?? collect();
            $reportsByType = $stats['reports_by_type'] ?? collect();
            $mostUsedReport = $stats['most_used_report'] ?? null;
        @endphp

        <!-- Cards de Métricas -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-primary bg-gradient me-3">
                                <i class="bi bi-file-earmark-pdf text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total de Relatórios</h6>
                                <h3 class="mb-0">{{ $totalReports }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Relatórios gerados no sistema.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-success bg-gradient me-3">
                                <i class="bi bi-graph-up-arrow text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Relatório Mais Usado</h6>
                                <h3 class="mb-0">{{ $mostUsedReport ? ucfirst($mostUsedReport) : 'N/A' }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Tipo de relatório mais acessado.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-info bg-gradient me-3">
                                <i class="bi bi-calendar-event text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Relatórios Recentes</h6>
                                <h3 class="mb-0">{{ $recentReports->count() }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Relatórios gerados nos últimos 30 dias.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar-circle bg-warning bg-gradient me-3">
                                <i class="bi bi-bar-chart-line text-white"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Tipos Disponíveis</h6>
                                <h3 class="mb-0">{{ $reportsByType->count() }}</h3>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">
                            Diferentes tipos de relatórios oferecidos.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo Principal -->
        <div class="row g-4">
            <!-- Relatórios Recentes -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>
                            <span class="d-none d-sm-inline">Relatórios Recentes</span>
                            <span class="d-sm-none">Recentes</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($recentReports && $recentReports->isNotEmpty())
                            <!-- Desktop View -->
                            <div class="desktop-view">
                                <div class="table-responsive">
                                    <table class="modern-table table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Período</th>
                                                <th>Gerado em</th>
                                                <th>Status</th>
                                                <th class="text-center">Ações</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($recentReports as $report)
                                                <tr>
                                                    <td>
                                                        <i class="bi bi-file-earmark-pdf text-danger"></i>
                                                        {{ ucfirst($report->type ?? 'N/A') }}
                                                    </td>
                                                    <td>{{ $report->period ?? 'N/A' }}</td>
                                                    <td>
                                                        @if ($report->date)
                                                            {{ \Carbon\Carbon::parse($report->date)->format('d/m/Y H:i') }}
                                                        @else
                                                            {{ $report->generated_at ? \Carbon\Carbon::parse($report->generated_at)->format('d/m/Y H:i') : 'N/A' }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Concluído</span>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            @if (isset($report->download_url))
                                                                <x-button type="link" :href="$report->download_url" variant="primary" outline size="sm" icon="download" title="Download" />
                                                            @endif
                                                            @if (isset($report->view_url))
                                                                <x-button type="link" :href="$report->view_url" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                            @endif
                                                        </div>
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
                                    @foreach ($recentReports as $report)
                                        <div class="list-group-item list-group-item-action py-3">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-file-earmark-pdf text-danger me-3 mt-1"
                                                    style="font-size: 1.2rem;"></i>
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold mb-2">{{ ucfirst($report->type ?? 'N/A') }}
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
                                                        <x-button type="link" :href="$report->download_url" variant="primary" outline size="sm" icon="download" title="Download" />
                                                    @endif
                                                    @if (isset($report->view_url))
                                                        <x-button type="link" :href="$report->view_url" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="p-4">
                                <p class="text-muted mb-0">
                                    Nenhum relatório recente encontrado. Gere seu primeiro relatório para visualizar aqui.
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
                                <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                                Relatórios financeiros ajudam a acompanhar a saúde do negócio.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-people-fill text-success me-2"></i>
                                Análise de clientes revela oportunidades de crescimento.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-box-seam text-info me-2"></i>
                                Controle de produtos otimiza gestão de inventário.
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-pie-chart-fill text-warning me-2"></i>
                                Analytics avançados fornecem insights estratégicos.
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
                        <x-button type="link" :href="route('provider.reports.financial')" variant="primary" size="sm" icon="graph-up" label="Financeiro" />
                        <x-button type="link" :href="route('provider.reports.customers')" variant="info" size="sm" icon="people" label="Clientes" />
                        <x-button type="link" :href="route('provider.reports.products')" variant="success" size="sm" icon="box" label="Produtos" />
                        <x-button type="link" :href="route('provider.reports.index')" variant="secondary" outline size="sm" icon="list-ul" label="Ver Todos" />
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
