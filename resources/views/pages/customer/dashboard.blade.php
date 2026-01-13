@extends('layouts.app')

@section('title', 'Dashboard de Clientes')

@section('content')
<x-layout.page-container>
    <!-- Cabeçalho -->
    <x-layout.page-header
        title="Dashboard de Clientes"
        icon="people"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Clientes' => '#'
        ]">
        <p class="text-muted mb-0">Visão geral dos clientes do seu negócio</p>
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
    <x-layout.grid-row class="mb-4">
        <x-dashboard.stat-card
            title="Total"
            :value="$total"
            description="Ativos e inativos."
            icon="people"
            variant="primary"
            isCustom
        />

        <x-dashboard.stat-card
            title="Ativos"
            :value="$active"
            description="Disponíveis para uso."
            icon="check-circle"
            variant="success"
            isCustom
        />

        <x-dashboard.stat-card
            title="Inativos"
            :value="$inactive"
            description="Suspensos temporariamente."
            icon="pause-circle"
            variant="warning"
            isCustom
        />

        <x-dashboard.stat-card
            title="Deletados"
            :value="$deleted"
            description="Na lixeira."
            icon="trash3"
            variant="danger"
            isCustom
        />

        <x-dashboard.stat-card
            title="Taxa de Atividade"
            :value="$activityRate . '%'"
            description="Percentual de ativos."
            icon="percent"
            variant="info"
            isCustom
        />
    </x-layout.grid-row>

    <!-- Clientes Recentes e Insights -->
    <x-layout.grid-row class="g-4">
        <x-layout.grid-col lg="8">
            <x-resource.resource-list-card
                title="Clientes Recentes"
                icon="clock-history"
                :items="$recent"
                empty-message="Nenhum cliente recente encontrado. Cadastre novos clientes para visualizar aqui."
            >
                <x-slot:table-header>
                    <tr>
                        <th>Cliente</th>
                        <th>E-mail</th>
                        <th>Telefone</th>
                        <th>Cadastrado em</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </x-slot:table-header>

                <x-slot:table-row>
                    @foreach ($recent as $customer)
                        @php
                            $common = $customer->commonData ?? ($customer->common_data ?? null);
                            $contact = $customer->contact ?? null;
                            $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                            $email = $contact->email_personal ?? ($contact->email_business ?? null);
                            $phone = $contact->phone_personal ?? ($contact->phone_business ?? null);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-person me-2 text-muted"></i>
                                    <span class="fw-medium">{{ $name }}</span>
                                </div>
                            </td>
                            <td class="text-muted text-break small">{{ $email ?? '—' }}</td>
                            <td class="text-muted small">{{ $phone ? \App\Helpers\MaskHelper::formatPhone($phone) : '—' }}</td>
                            <td class="text-muted small">{{ optional($customer->created_at)->format('d/m/Y') }}</td>
                            <td class="text-center">
                                <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                    variant="light" size="sm" icon="eye" class="btn-icon" />
                            </td>
                        </tr>
                    @endforeach
                </x-slot:table-row>

                <x-slot:mobile-item>
                    @foreach ($recent as $customer)
                        @php
                            $common = $customer->commonData ?? ($customer->common_data ?? null);
                            $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                        @endphp
                        <div class="list-group-item py-3 border-0 border-bottom px-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-light me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $name }}</div>
                                        <small class="text-muted">{{ optional($customer->created_at)->format('d/m/Y') }}</small>
                                    </div>
                                </div>
                                <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                    variant="light" size="sm" icon="eye" class="btn-icon" />
                            </div>
                        </div>
                    @endforeach
                </x-slot:mobile-item>
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Indicadores Laterais -->
        <x-layout.grid-col lg="4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
                    </h6>
                </div>
                <div class="card-body">
                    <x-layout.v-stack gap="3">
                        <x-dashboard.insight-item
                            icon="check-circle-fill"
                            variant="success"
                            description="Mantenha seus clientes ativos com informações completas e atualizadas."
                        />
                        <x-dashboard.insight-item
                            icon="funnel-fill"
                            variant="primary"
                            description="Use filtros na listagem de clientes para segmentar sua base."
                        />
                        <x-dashboard.insight-item
                            icon="bar-chart-line-fill"
                            variant="info"
                            description="Acompanhe a evolução do cadastro de clientes para entender seu crescimento."
                        />
                    </x-layout.v-stack>
                </div>
            </div>

            <x-resource.quick-actions title="Atalhos">
                <x-ui.button type="link" :href="route('provider.customers.create')" variant="success" icon="person-plus" label="Novo Cliente" class="w-100 justify-content-start" />
                <x-ui.button type="link" :href="route('provider.customers.index')" variant="primary" outline icon="people" label="Listar Clientes" class="w-100 justify-content-start" />
                <x-ui.button type="link" :href="route('provider.customers.index', ['deleted' => 'only'])" variant="secondary" outline icon="archive" label="Ver Deletados" class="w-100 justify-content-start" />
            </x-resource.quick-actions>
        </x-layout.grid-col>
    </x-layout.grid-row>

    <!-- Clientes com Maior Atividade -->
    <x-layout.grid-row class="mt-4">
        <x-layout.grid-col cols="12">
            <x-resource.resource-list-card
                title="Clientes com Maior Atividade"
                icon="graph-up"
                :items="$activeWithStats"
                empty-message="Nenhuma atividade registrada para clientes ativos."
            >
                <x-slot:table-header>
                    <tr>
                        <th class="ps-4">Cliente</th>
                        <th class="text-center">Orçamentos</th>
                        <th class="text-center">Faturas</th>
                        <th class="text-center">Engajamento</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </x-slot:table-header>

                <x-slot:table-row>
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
                        <tr>
                            <td class="ps-4">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle bg-light me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $name }}</div>
                                        <small class="text-muted">ID: #{{ $customer->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3 py-1">
                                    {{ $budgetsCount }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-3 py-1">
                                    {{ $invoicesCount }}
                                </span>
                            </td>
                            <td class="text-center" style="width: 200px;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress bg-light flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <small class="text-muted fw-bold">{{ round($percent) }}%</small>
                                </div>
                            </td>
                            <td class="text-end pe-4">
                                <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                    variant="light" size="sm" icon="eye" class="btn-icon" />
                            </td>
                        </tr>
                    @endforeach
                </x-slot:table-row>

                <x-slot:mobile-item>
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
                        <div class="list-group-item py-3 border-0 border-bottom px-0">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="fw-bold text-dark h6 mb-0">{{ $name }}</div>
                                <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                    variant="light" size="sm" icon="eye" class="btn-icon" />
                            </div>
                            <div class="d-flex gap-2 mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3 py-2 small">
                                    <i class="bi bi-file-earmark-text me-1"></i>{{ $budgetsCount }} Orçamentos
                                </span>
                                <span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-3 py-2 small">
                                    <i class="bi bi-receipt me-1"></i>{{ $invoicesCount }} Faturas
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress bg-light flex-grow-1" style="height: 4px;">
                                    <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $percent }}%"></div>
                                </div>
                                <small class="text-muted x-small fw-bold">{{ round($percent) }}%</small>
                            </div>
                        </div>
                    @endforeach
                </x-slot:mobile-item>
            </x-resource.resource-list-card>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
