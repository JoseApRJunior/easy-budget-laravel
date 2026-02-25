@extends('layouts.app')

@section('title', 'Serviços e Orçamentos')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Serviços e Orçamentos"
            icon="clipboard-data"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Clientes' => route('provider.customers.index'),
                'Serviços e Orçamentos' => '#'
            ]"
        />

        <x-layout.grid-row>
            <!-- Serviços -->
            <div class="col-12 mb-4">
                <x-resource.resource-list-card
                    title="Serviços"
                    icon="tools"
                    :total="$servicos->count()"
                    :actions="[
                        [
                            'label' => 'Novo Serviço',
                            'icon' => 'plus-lg',
                            'route' => route('provider.services.create'),
                            'variant' => 'primary'
                        ]
                    ]"
                >
                    <x-resource.resource-table :headers="['Cliente', 'Serviço', 'Orçamento', 'Data', 'Status', 'Ações']">
                        @forelse($servicos as $servico)
                            <x-resource.table-row>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <x-ui.user-avatar :name="$servico->cliente->nome ?? 'N/A'" class="me-2" />
                                        {{ $servico->cliente->nome ?? 'N/A' }}
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>{{ $servico->nome }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    R$ {{ number_format($servico->orcamento->valor ?? 0, 2, ',', '.') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ \Carbon\Carbon::parse($servico->data)->format('d/m/Y') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    @php
                                        $statusMap = [
                                            'Concluído' => 'success',
                                            'Em andamento' => 'primary',
                                            'Pendente' => 'warning',
                                            'Cancelado' => 'danger'
                                        ];
                                        $statusClass = $statusMap[$servico->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $servico->status }}
                                    </span>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="route('provider.services.show', $servico->code)" variant="info" outline size="sm" icon="eye" title="Visualizar" feature="services" />
                                        <x-ui.button :href="route('provider.services.edit', $servico->code)" variant="primary" outline size="sm" icon="pencil-square" title="Editar" feature="services" />
                                         <x-ui.button
                                            type="button"
                                            variant="danger"
                                            outline
                                            size="sm"
                                            icon="trash"
                                            title="Excluir"
                                            class="btn-delete"
                                            data-id="{{ $servico->id }}"
                                            data-delete-url="{{ route('provider.services.destroy', $servico->code) }}"
                                            data-item-name="{{ $servico->nome }}"
                                            feature="services"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-tools display-4 d-block mb-3"></i>
                                    Nenhum serviço encontrado
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>
                </x-resource.resource-list-card>
            </div>

            <!-- Orçamentos -->
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Orçamentos"
                    icon="receipt"
                    :total="$orcamentos->count()"
                    :actions="[
                        [
                            'label' => 'Novo Orçamento',
                            'icon' => 'plus-lg',
                            'route' => route('provider.budgets.create'),
                            'variant' => 'primary'
                        ]
                    ]"
                >
                    <x-resource.resource-table :headers="['Cliente', 'Orçamento', 'Data', 'Status', 'Ações']">
                        @forelse($orcamentos as $orcamento)
                            <x-resource.table-row>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <x-ui.user-avatar :name="$orcamento->cliente->nome ?? 'N/A'" class="me-2" />
                                        {{ $orcamento->cliente->nome ?? 'N/A' }}
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    R$ {{ number_format($orcamento->valor, 2, ',', '.') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ \Carbon\Carbon::parse($orcamento->data)->format('d/m/Y') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    @php
                                        $statusMap = [
                                            'Aprovado' => 'success',
                                            'Pendente' => 'warning',
                                            'Recusado' => 'danger'
                                        ];
                                        $statusClass = $statusMap[$orcamento->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">
                                        {{ $orcamento->status }}
                                    </span>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="route('provider.budgets.show', $orcamento->code ?? $orcamento->id)" variant="info" outline size="sm" icon="eye" title="Visualizar" feature="budgets" />
                                        <x-ui.button :href="route('provider.budgets.edit', $orcamento->code ?? $orcamento->id)" variant="primary" outline size="sm" icon="pencil-square" title="Editar" feature="budgets" />
                                        <x-ui.button
                                            type="button"
                                            variant="danger"
                                            outline
                                            size="sm"
                                            icon="trash"
                                            title="Excluir"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-delete-url="{{ route('provider.budgets.destroy', $orcamento->id) }}"
                                            data-item-name="{{ $orcamento->code ?? $orcamento->id }}"
                                            feature="budgets"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-receipt display-4 d-block mb-3"></i>
                                    Nenhum orçamento encontrado
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>
                </x-resource.resource-list-card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal
        id="deleteModal"
        title="Confirmar Exclusão"
        message="Tem certeza que deseja excluir <strong id='deleteModalItemName'></strong>?"
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete"
        resource="item"
    />
@endsection
