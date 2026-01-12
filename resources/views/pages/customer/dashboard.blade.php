@extends('layouts.app')

@section('title', 'Dashboard de Clientes')

@section('content')
<div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <x-layout.page-header
        title="Dashboard de Clientes"
        icon="people"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Clientes' => '#'
        ]">
        <p class="text-muted mb-0 small">Visão geral dos clientes do seu negócio</p>
    </x-layout.page-header>

    @php
    $total = $stats['total_customers'] ?? 0;
    $active = $stats['active_customers'] ?? 0;
    $inactive = $stats['inactive_customers'] ?? 0;
    $deleted = $stats['deleted_customers'] ?? 0;
    $recent = $stats['recent_customers'] ?? collect();
    $activeWithStats = $stats['active_with_stats'] ?? collect();
    $activityRate = $total > 0 ? number_format(($active / $total) * 100, 1, ',', '.') : 0;
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100 bg-primary bg-gradient text-white">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-white bg-opacity-25 me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-people-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-white text-opacity-75 mb-0 small fw-bold">TOTAL</h6>
                    </div>
                    <h3 class="mb-1 fw-bold">{{ $total }}</h3>
                    <p class="text-white text-opacity-75 small-text mb-0">Ativos e inativos.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-success bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-check-circle-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">ATIVOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-success">{{ $active }}</h3>
                    <p class="text-muted small-text mb-0">Disponíveis para uso.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-secondary bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-pause-circle-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">INATIVOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-secondary">{{ $inactive }}</h3>
                    <p class="text-muted small-text mb-0">Suspensos temporariamente.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-danger bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-trash3-fill text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">DELETADOS</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-danger">{{ $deleted }}</h3>
                    <p class="text-muted small-text mb-0">Na lixeira.</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-5-custom">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3 d-flex flex-column justify-content-between">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar-circle bg-info bg-gradient me-2" style="width: 35px; height: 35px;">
                            <i class="bi bi-percent text-white" style="font-size: 0.9rem;"></i>
                        </div>
                        <h6 class="text-muted mb-0 small fw-bold">TAXA USO</h6>
                    </div>
                    <h3 class="mb-1 fw-bold text-info">{{ $activityRate }}%</h3>
                    <p class="text-muted small-text mb-0">Percentual de ativos.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Clientes Recentes -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent border-0 py-3">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>
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
                                        <th>Cliente</th>
                                        <th>E-mail</th>
                                        <th>Telefone</th>
                                        <th>Cadastrado em</th>
                                        <th class="text-center">Ações</th>
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
                                                <i class="bi bi-person me-2 text-muted"></i>
                                                <span>{{ $name }}</span>
                                            </div>
                                        </td>
                                        <td class="text-muted text-break">{{ $email ?? '—' }}</td>
                                        <td class="text-muted">{{ $phone ? \App\Helpers\MaskHelper::formatPhone($phone) : '—' }}</td>
                                        <td class="text-muted">{{ optional($customer->created_at)->format('d/m/Y') }}</td>
                                        <td class="text-center">
                                            <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
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
                                @foreach ($recent as $customer)
                                @php
                                $common = $customer->commonData ?? ($customer->common_data ?? null);
                                $name =
                                $common?->company_name ??
                                trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?:
                                'Cliente';
                                @endphp
                                <div class="list-group-item py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person text-muted me-2 mt-1"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">{{ $name }}</div>
                                            <small
                                                class="text-muted">{{ optional($customer->created_at)->format('d/m/Y') }}</small>
                                        </div>
                                        <div class="ms-2">
                                            <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
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
                    <x-ui.button type="link" :href="route('provider.customers.create')" variant="success" size="sm" icon="person-plus" label="Novo Cliente" />
                    <x-ui.button type="link" :href="route('provider.customers.index')" variant="primary" outline size="sm" icon="people" label="Listar Clientes" />
                    <x-ui.button type="link" :href="route('provider.customers.index', ['deleted' => 'only'])" variant="secondary" outline size="sm" icon="archive" label="Ver Deletados" />
                </div>
            </div>
        </div>
        <!-- Clientes com Maior Atividade -->
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up me-2"></i>
                            Clientes com Maior Atividade
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if ($activeWithStats->isNotEmpty())
                        <!-- Desktop View -->
                        <div class="desktop-view d-none d-md-block">
                            <div class="table-responsive">
                                <table class="modern-table table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th class="ps-4">Cliente</th>
                                            <th class="text-center">Orçamentos</th>
                                            <th class="text-center">Faturas</th>
                                            <th class="text-center">Engajamento</th>
                                            <th class="text-end pe-4">Ações</th>
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
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-person me-2 text-muted"></i>
                                                    <div>
                                                        <div class="fw-bold">{{ $name }}</div>
                                                        <small class="text-muted">ID: #{{ $customer->id }}</small>
                                                    </div>
                                                </div>
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
                                                <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                                    variant="info" size="sm" icon="eye" />
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Mobile View -->
                        <div class="mobile-view d-md-none">
                            <div class="list-group list-group-flush">
                                @foreach ($activeWithStats as $customer)
                                @php
                                $common = $customer->commonData ?? ($customer->common_data ?? null);
                                $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                                $budgetsCount = $customer->budgets_count ?? 0;
                                $invoicesCount = $customer->invoices_count ?? 0;
                                $totalActivity = $budgetsCount + $invoicesCount;
                                $maxActivity = $activeWithStats->max(function($c) { return ($c->budgets_count ?? 0) + ($c->invoices_count ?? 0); }) ?: 1;
                                $percent = ($totalActivity / $maxActivity) * 100;
                                @endphp
                                <div class="list-group-item py-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="fw-bold text-dark">{{ $name }}</div>
                                        <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                            variant="info" size="sm" icon="eye" />
                                    </div>
                                    <div class="d-flex gap-2 mb-2">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-2">
                                            {{ $budgetsCount }} Orçamentos
                                        </span>
                                        <span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-2">
                                            {{ $invoicesCount }} Faturas
                                        </span>
                                    </div>
                                    <div class="progress bg-light" style="height: 4px;">
                                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
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
