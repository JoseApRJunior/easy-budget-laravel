@extends('layouts.app')

@section('title', 'Dashboard de Clientes')

@section('content')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="flex-grow-1">
                <h1 class="h4 h3-md mb-1">
                    <i class="bi bi-people me-2"></i>
                    <span class="d-none d-sm-inline">Dashboard de Clientes</span>
                    <span class="d-sm-none">Clientes</span>
                </h1>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('provider.dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Dashboard de Clientes
                    </li>
                </ol>
            </nav>
        </div>
        <p class="text-muted mb-0 small">Visão geral dos clientes do seu negócio</p>
    </div>

    @php
    $total = $stats['total_customers'] ?? 0;
    $active = $stats['active_customers'] ?? 0;
    $inactive = $stats['inactive_customers'] ?? 0;
    $recent = $stats['recent_customers'] ?? collect();
    $activeWithStats = $stats['active_with_stats'] ?? collect();
    $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div>
                            <div class="text-uppercase small fw-bold text-muted mb-1">Total de Clientes</div>
                            <h3 class="mb-0 fw-bold text-dark">{{ $total }}</h3>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Total de clientes cadastrados.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-success bg-opacity-10 text-success me-3">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div>
                            <div class="text-uppercase small fw-bold text-muted mb-1">Clientes Ativos</div>
                            <h3 class="mb-0 fw-bold text-dark">{{ $active }}</h3>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Prontos para propostas e serviços.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-secondary bg-opacity-10 text-secondary me-3">
                            <i class="bi bi-pause-circle-fill"></i>
                        </div>
                        <div>
                            <div class="text-uppercase small fw-bold text-muted mb-1">Clientes Inativos</div>
                            <h3 class="mb-0 fw-bold text-dark">{{ $inactive }}</h3>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Marcados para controle interno.
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar-circle bg-info bg-opacity-10 text-info me-3">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <div>
                            <div class="text-uppercase small fw-bold text-muted mb-1">Taxa de Atividade</div>
                            <h3 class="mb-0 fw-bold text-dark">{{ $activityRate }}%</h3>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Engajamento da base cadastrada.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Clientes Recentes -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-clock-history me-2 text-primary"></i>
                        <span class="d-none d-sm-inline">Clientes Recentes</span>
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
                                        <th class="text-uppercase small fw-bold text-muted">Cliente</th>
                                        <th class="text-uppercase small fw-bold text-muted">E-mail</th>
                                        <th class="text-uppercase small fw-bold text-muted">Telefone</th>
                                        <th class="text-uppercase small fw-bold text-muted">Cadastrado em</th>
                                        <th class="text-center text-uppercase small fw-bold text-muted">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recent as $customer)
                                    @php
                                    $common = $customer->commonData ?? ($customer->common_data ?? null);
                                    $contact = $customer->contact ?? null;
                                    $name =
                                    $common?->company_name ??
                                    trim(
                                    ($common->first_name ?? '') .
                                    ' ' .
                                    ($common->last_name ?? ''),
                                    ) ?:
                                    'Cliente';
                                    $email =
                                    $contact->email_personal ?? ($contact->email_business ?? null);
                                    $phone =
                                    $contact->phone_personal ?? ($contact->phone_business ?? null);
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle-sm bg-light text-primary me-2">
                                                    <i class="bi bi-person"></i>
                                                </div>
                                                <span class="fw-bold text-dark">{{ $name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted">{{ $email ?? '—' }}</td>
                                        <td class="text-muted">{{ $phone ?? '—' }}</td>
                                        <td class="text-muted">{{ optional($customer->created_at)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <a href="{{ route('provider.customers.show', $customer) }}"
                                                class="btn btn-sm btn-light border text-primary">
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
                            @foreach ($recent as $customer)
                            @php
                            $common = $customer->commonData ?? ($customer->common_data ?? null);
                            $name =
                            $common?->company_name ??
                            trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?:
                            'Cliente';
                            @endphp
                            <a href="{{ route('provider.customers.show', $customer) }}"
                                class="list-group-item list-group-item-action py-3">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-person text-muted me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold mb-2">{{ $name }}</div>
                                        <small
                                            class="text-muted">{{ optional($customer->created_at)->format('d/m/Y') }}</small>
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
                            Nenhum cliente recente encontrado. Cadastre novos clientes para visualizar aqui.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Indicadores Laterais -->
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
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Mantenha seus clientes ativos com informações completas e atualizadas.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-funnel-fill text-primary me-2"></i>
                            Use filtros na listagem de clientes para segmentar sua base.
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-bar-chart-line-fill text-info me-2"></i>
                            Acompanhe a evolução do cadastro de clientes para entender seu crescimento.
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
                    <a href="{{ route('provider.customers.create') }}" class="btn btn-sm btn-success">
                        <i class="bi bi-person-plus me-2"></i>Novo Cliente
                    </a>
                    <a href="{{ route('provider.customers.index') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-people me-2"></i>Listar Clientes
                    </a>
                    <a href="{{ route('provider.customers.index', ['deleted' => 'only']) }}"
                        class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-archive me-2"></i>Ver Deletados
                    </a>
                </div>
            </div>
        </div>
        <!-- Clientes com Maior Atividade -->
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            Clientes com Maior Atividade
                        </h5>
                        <small class="text-uppercase small fw-bold text-muted">Top 10 Clientes Ativos</small>
                    </div>
                    <div class="card-body p-0">
                        @if ($activeWithStats->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="ps-4 text-uppercase small fw-bold text-muted">Cliente</th>
                                        <th class="text-center text-uppercase small fw-bold text-muted">Orçamentos</th>
                                        <th class="text-center text-uppercase small fw-bold text-muted">Faturas</th>
                                        <th class="text-center text-uppercase small fw-bold text-muted">Engajamento</th>
                                        <th class="text-end pe-4 text-uppercase small fw-bold text-muted">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($activeWithStats as $customer)
                                    @php
                                    $common = $customer->commonData ?? ($customer->common_data ?? null);
                                    $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                                    $budgetsCount = $customer->budgets_count ?? 0;
                                    $invoicesCount = $customer->invoices_count ?? 0;
                                    $totalActivity = $budgetsCount + $invoicesCount;
                                    @endphp
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark">{{ $name }}</div>
                                            <small class="text-muted">ID: #{{ $customer->id }}</small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3">
                                                {{ $budgetsCount }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-3">
                                                {{ $invoicesCount }}
                                            </span>
                                        </td>
                                        <td class="text-center" style="width: 200px;">
                                            <div class="progress bg-light" style="height: 6px;">
                                                @php
                                                $maxActivity = $activeWithStats->max(function($c) { return ($c->budgets_count ?? 0) + ($c->invoices_count ?? 0); }) ?: 1;
                                                $percent = ($totalActivity / $maxActivity) * 100;
                                                @endphp
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $percent }}%"></div>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="{{ route('provider.customers.show', $customer) }}" class="btn btn-sm btn-light border text-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">Nenhuma atividade registrada para clientes ativos.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

    @push('styles')
    @endpush
