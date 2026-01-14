@extends('layouts.app')

@section('title', 'Dashboard de Clientes')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Dashboard de Clientes"
        icon="people"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Clientes' => '#'
        ]"
        description="Visão geral dos clientes do seu negócio com métricas e acompanhamento de performance."
    />

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
    <x-layout.grid-row>
        <x-dashboard.stat-card
            title="Total de Clientes"
            :value="$total"
            description="Base total de clientes cadastrados."
            icon="people"
            variant="primary"
        />

        <x-dashboard.stat-card
            title="Clientes Ativos"
            :value="$active"
            description="Clientes disponíveis para novos orçamentos."
            icon="check-circle"
            variant="success"
        />

        <x-dashboard.stat-card
            title="Clientes Inativos"
            :value="$inactive"
            description="Clientes com cadastro suspenso."
            icon="pause-circle"
            variant="warning"
        />

        <x-dashboard.stat-card
            title="Taxa de Atividade"
            :value="$activityRate . '%'"
            description="Percentual de clientes ativos."
            icon="percent"
            variant="info"
        />
    </x-layout.grid-row>

    <!-- Conteúdo Principal -->
    <x-layout.grid-row>
        <!-- Clientes Recentes (8 colunas) -->
        <x-layout.grid-col size="col-lg-8">
            <x-resource.resource-list-card
                title="Clientes Recentes"
                icon="clock-history"
                :total="$recent->count()"
            >
                @if ($recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Cliente</x-resource.table-cell>
                                    <x-resource.table-cell header>E-mail</x-resource.table-cell>
                                    <x-resource.table-cell header>Telefone</x-resource.table-cell>
                                    <x-resource.table-cell header>Data</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:thead>

                            @foreach ($recent as $customer)
                                @php
                                    $common = $customer->commonData ?? ($customer->common_data ?? null);
                                    $contact = $customer->contact ?? null;
                                    $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                                    $email = $contact->email_personal ?? ($contact->email_business ?? null);
                                    $phone = $contact->phone_personal ?? ($contact->phone_business ?? null);
                                @endphp
                                <x-resource.table-row>
                                    <x-resource.table-cell>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person me-2 text-muted"></i>
                                            <span class="fw-medium text-dark">{{ $name }}</span>
                                        </div>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="text-muted small">
                                        <x-resource.table-cell-truncate :text="$email ?? '—'" />
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="text-muted small">
                                        {{ $phone ? \App\Helpers\MaskHelper::formatPhone($phone) : '—' }}
                                    </x-resource.table-cell>
                                    <x-resource.table-cell class="text-muted small">
                                        {{ optional($customer->created_at)->format('d/m/Y') }}
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center">
                                        <x-resource.action-buttons
                                            :item="$customer"
                                            resource="customers"
                                            :can-delete="false"
                                            size="sm"
                                        />
                                    </x-resource.table-cell>
                                </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($recent as $customer)
                            @php
                                $common = $customer->commonData ?? ($customer->common_data ?? null);
                                $name = $common?->company_name ?? trim(($common->first_name ?? '') . ' ' . ($common->last_name ?? '')) ?: 'Cliente';
                            @endphp
                            <x-resource.resource-mobile-item
                                icon="person"
                                :href="route('provider.customers.show', $customer)"
                            >
                                <x-resource.resource-mobile-header
                                    :title="$name"
                                    :subtitle="optional($customer->created_at)->format('d/m/Y')"
                                />

                                <x-resource.resource-mobile-field
                                    label="E-mail"
                                    :value="$email ?? '—'"
                                />

                                <x-resource.resource-mobile-field
                                    label="Telefone"
                                    :value="$phone ? \App\Helpers\MaskHelper::formatPhone($phone) : '—'"
                                />
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state
                        title="Nenhum cliente recente"
                        description="Comece cadastrando seus clientes para visualizá-los aqui."
                        icon="people"
                    />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>

        <!-- Sidebar (4 colunas) -->
        <x-layout.grid-col size="col-lg-4">
            <x-layout.v-stack gap="4">
                <!-- Insights -->
                <x-resource.resource-list-card
                    title="Insights de Clientes"
                    icon="lightbulb"
                    padding="p-3"
                    gap="3"
                >
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
                        description="Acompanhe a evolução do cadastro para entender seu crescimento."
                    />
                </x-resource.resource-list-card>

                <!-- Atalhos -->
                <x-resource.quick-actions
                    title="Ações de Cliente"
                    icon="lightning-charge"
                >
                    <x-ui.button type="link" :href="route('provider.customers.create')" variant="outline-success" icon="person-plus" label="Novo Cliente" />
                    <x-ui.button type="link" :href="route('provider.budgets.create')" variant="outline-success" icon="plus-lg" label="Novo Orçamento" />
                    <x-ui.button type="link" :href="route('provider.customers.index')" variant="outline-primary" icon="people" label="Listar Clientes" />
                    <x-ui.button type="link" :href="route('provider.customers.index', ['deleted' => 'only'])" variant="outline-secondary" icon="trash" label="Ver Deletados" />
                </x-resource.quick-actions>
            </x-layout.v-stack>
        </x-layout.grid-col>
    </x-layout.grid-row>

    <!-- Clientes com Maior Atividade -->
    <x-layout.grid-row class="mt-4">
        <x-layout.grid-col size="col-12">
            <x-resource.resource-list-card
                title="Clientes com Maior Atividade"
                icon="graph-up"
                :total="$activeWithStats->count()"
            >
                @if ($activeWithStats->isNotEmpty())
                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <x-resource.table-row>
                                    <x-resource.table-cell header class="ps-4">Cliente</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Orçamentos</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Faturas</x-resource.table-cell>
                                    <x-resource.table-cell header align="center">Engajamento</x-resource.table-cell>
                                    <x-resource.table-cell header align="end" class="pe-4">Ações</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:thead>

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
                                <x-resource.table-row>
                                    <x-resource.table-cell class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-light me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 50%;">
                                                <i class="bi bi-person text-muted" style="font-size: 0.8rem;"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $name }}</div>
                                                <small class="text-muted">ID: #{{ $customer->id }}</small>
                                            </div>
                                        </div>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center">
                                        <span class="badge bg-primary bg-opacity-10 text-primary border-0 rounded-pill px-3 py-1">
                                            {{ $budgetsCount }}
                                        </span>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center">
                                        <span class="badge bg-success bg-opacity-10 text-success border-0 rounded-pill px-3 py-1">
                                            {{ $invoicesCount }}
                                        </span>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="center" style="min-width: 150px;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress bg-light flex-grow-1" style="height: 6px;">
                                                <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: {{ $percent }}%"></div>
                                            </div>
                                            <small class="text-muted fw-bold">{{ round($percent) }}%</small>
                                        </div>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="end" class="pe-4">
                                        <x-ui.button type="link" :href="route('provider.customers.show', $customer)"
                                            variant="light" size="sm" icon="eye" class="btn-icon" />
                                    </x-resource.table-cell>
                                </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
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
                            <x-resource.resource-mobile-item
                                icon="graph-up"
                                :href="route('provider.customers.show', $customer)"
                            >
                                <x-resource.resource-mobile-header
                                    :title="$name"
                                    :subtitle="'ID: #' . $customer->id"
                                />

                                <div class="d-flex gap-2 mb-3 mt-2">
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
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                @else
                    <x-resource.empty-state
                        title="Nenhuma atividade registrada"
                        description="Os dados de engajamento aparecerão conforme orçamentos e faturas forem gerados."
                        icon="graph-up"
                    />
                @endif
            </x-resource.resource-list-card>
        </x-layout.grid-col>
    </x-layout.grid-row>
</x-layout.page-container>
@endsection
