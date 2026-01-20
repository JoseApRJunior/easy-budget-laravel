@extends('layouts.app')

@section('title', 'Dashboard de Compartilhamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Dashboard de Compartilhamentos"
            icon="share-fill"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Orçamentos' => route('provider.budgets.index'),
                'Compartilhamentos' => route('provider.budgets.shares.index'),
                'Dashboard' => '#'
            ]"
        />

        @php
            $totalShares = $stats['total_shares'] ?? 0;
            $activeShares = $stats['active_shares'] ?? 0;
            $expiredShares = $stats['expired_shares'] ?? 0;
            $totalAccesses = $stats['access_count'] ?? 0;
            $recentShares = $stats['recent_shares'] ?? collect();
            $mostSharedBudgets = $stats['most_shared_budgets'] ?? collect();

            $activeRate = $totalShares > 0 ? number_format(($activeShares / $totalShares) * 100, 1, ',', '.') : 0;
        @endphp

        <!-- Cards de Métricas -->
        <x-layout.grid-row>
            <x-dashboard.stat-card
                title="Total de Compartilhamentos"
                :value="$totalShares"
                description="Quantidade total de links criados."
                icon="share"
                variant="primary"
            />

            <x-dashboard.stat-card
                title="Compartilhamentos Ativos"
                :value="$activeShares"
                description="Links disponíveis para acesso."
                icon="check-circle-fill"
                variant="success"
            />

            <x-dashboard.stat-card
                title="Taxa de Atividade"
                :value="$activeRate . '%'"
                description="Percentual de links ativos."
                icon="clock-fill"
                variant="warning"
            />

            <x-dashboard.stat-card
                title="Total de Acessos"
                :value="$totalAccesses"
                description="Visualizações totais dos orçamentos."
                icon="eye"
                variant="info"
            />
        </x-layout.grid-row>

        <!-- Conteúdo Principal -->
        <x-layout.grid-row>
            <!-- Compartilhamentos Recentes -->
            <x-layout.grid-col size="col-lg-8">
                <x-resource.resource-list-card
                    title="Compartilhamentos Recentes"
                    icon="clock-history"
                >
                    <x-slot:actions>
                        <x-ui.button
                            href="{{ route('provider.budgets.shares.index') }}"
                            variant="primary"
                            size="sm"
                            label="Ver todos"
                        />
                    </x-slot:actions>

                    @if ($recentShares && $recentShares->isNotEmpty())
                        <x-slot:desktop>
                            <x-resource.resource-table>
                                <x-slot:thead>
                                    <x-resource.table-row>
                                        <x-resource.table-cell header>Orçamento</x-resource.table-cell>
                                        <x-resource.table-cell header>Destinatário</x-resource.table-cell>
                                        <x-resource.table-cell header>Status</x-resource.table-cell>
                                        <x-resource.table-cell header>Acessos</x-resource.table-cell>
                                        <x-resource.table-cell header align="center">Ações</x-resource.table-cell>
                                    </x-resource.table-row>
                                </x-slot:thead>

                                @foreach ($recentShares as $share)
                                    @php
                                        $budget = $share->budget;
                                        $customer = $budget->customer->commonData ?? null;
                                        $customerName = $customer?->company_name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Cliente não informado';

                                        $isExpired = !$share->is_active || ($share->expires_at && $share->expires_at <= now());
                                    @endphp
                                    <x-resource.table-row>
                                        <x-resource.table-cell>
                                            <x-resource.resource-info
                                                :title="$budget->code"
                                                :subtitle="Str::limit($customerName, 25)"
                                                icon="file-earmark-text"
                                            />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-resource.resource-info
                                                :title="$share->recipient_name"
                                                :subtitle="$share->recipient_email"
                                                icon="person"
                                            />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.badge
                                                :variant="$isExpired ? 'danger' : 'success'"
                                                :label="$isExpired ? 'Expirado' : 'Ativo'"
                                            />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell>
                                            <x-ui.badge
                                                variant="light"
                                                :label="$share->access_count"
                                                icon="eye"
                                            />
                                        </x-resource.table-cell>
                                        <x-resource.table-actions>
                                            <x-ui.button type="link" :href="route('provider.budgets.show', $share->budget->code)" variant="outline-info" size="sm" icon="eye" title="Visualizar Orçamento" />
                                        </x-resource.table-actions>
                                    </x-resource.table-row>
                                @endforeach
                            </x-resource.resource-table>
                        </x-slot:desktop>

                        <x-slot:mobile>
                            @foreach ($recentShares as $share)
                                @php
                                    $budget = $share->budget;
                                    $customer = $budget->customer->commonData ?? null;
                                    $customerName = $customer?->company_name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Cliente não informado';
                                    $isExpired = !$share->is_active || ($share->expires_at && $share->expires_at <= now());
                                @endphp
                                <x-resource.resource-mobile-item :href="route('provider.budgets.show', $share->budget->code)">
                                    <x-resource.resource-mobile-header
                                        :title="$budget->code"
                                        :subtitle="$customerName"
                                    />
                                    <x-resource.resource-mobile-field label="Destinatário" :value="$share->recipient_name" />
                                    <x-resource.resource-mobile-field label="Status">
                                        <x-ui.badge :variant="$isExpired ? 'danger' : 'success'" :label="$isExpired ? 'Expirado' : 'Ativo'" />
                                    </x-resource.resource-mobile-field>
                                    <x-resource.resource-mobile-field label="Acessos">
                                        <x-ui.badge variant="light" :label="$share->access_count" icon="eye" />
                                    </x-resource.resource-mobile-field>
                                </x-resource.resource-mobile-item>
                            @endforeach
                        </x-slot:mobile>
                    @else
                        <x-resource.empty-state
                            title="Nenhum compartilhamento recente"
                            description="Crie novos compartilhamentos para visualizar aqui."
                            :icon="null"
                        />
                    @endif
                </x-resource.resource-list-card>
            </x-layout.grid-col>

            <!-- Sidebar -->
            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack gap="4">
                    <!-- Orçamentos Mais Compartilhados -->
                    <x-resource.resource-list-card
                        title="Destaques"
                        icon="trophy"
                        padding="p-3"
                    >
                        @if ($mostSharedBudgets && $mostSharedBudgets->isNotEmpty())
                            <x-layout.v-stack gap="2">
                                @foreach ($mostSharedBudgets as $budget)
                                    @php
                                        $customer = $budget->customer->commonData ?? null;
                                        $customerName = $customer?->company_name ?? trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')) ?: 'Cliente não informado';
                                    @endphp
                                    <x-ui.list-item>
                                        <x-resource.resource-info
                                            :title="$budget->code"
                                            :subtitle="Str::limit($customerName, 20)"
                                        />
                                        <x-ui.badge variant="primary" :label="$budget->shares_count" pill />
                                    </x-ui.list-item>
                                @endforeach
                            </x-layout.v-stack>
                        @else
                            <x-resource.empty-state title="Sem destaques" :icon="null" />
                        @endif
                    </x-resource.resource-list-card>

                    <!-- Dicas -->
                    <x-resource.resource-list-card
                        title="Dicas de Uso"
                        icon="lightbulb"
                        padding="p-3"
                    >
                        <x-layout.v-stack gap="3">
                            <x-dashboard.insight-item
                                icon="share-fill"
                                variant="primary"
                                description="Compartilhe orçamentos aprovados para aumentar a conversão."
                            />
                            <x-dashboard.insight-item
                                icon="clock"
                                variant="warning"
                                description="Defina prazos de expiração para criar urgência no cliente."
                            />
                            <x-dashboard.insight-item
                                icon="eye"
                                variant="info"
                                description="Monitore os acessos para entender o interesse do cliente."
                            />
                        </x-layout.v-stack>
                    </x-resource.resource-list-card>

                    <!-- Atalhos -->
                    <x-resource.quick-actions title="Atalhos" icon="lightning-charge">
                        <x-ui.button type="link" :href="route('provider.budgets.shares.create')" variant="outline-success" icon="plus-circle" label="Novo Link" />
                        <x-ui.button type="link" :href="route('provider.budgets.shares.index')" variant="outline-primary" icon="list" label="Gerenciar" />
                        <x-ui.button type="link" :href="route('provider.budgets.index')" variant="outline-secondary" icon="file-earmark-text" label="Orçamentos" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
