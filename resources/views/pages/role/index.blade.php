@extends('layouts.app')

@section('title', 'Funções')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Funções"
            icon="person-badge"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Funções' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="url('/admin/roles/create')" variant="primary" icon="plus-circle" label="Adicionar Função" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Funções"
                    icon="list-ul"
                    :total="count($roles ?? [])"
                    :actions="[]"
                >
                    <x-resource.resource-table :headers="['#', 'Nome', 'Slug', 'Criado em', 'Ações']">
                        @forelse($roles as $role)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $role->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-person-badge text-muted me-2"></i>
                                        <strong>{{ $role->name }}</strong>
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <code class="bg-light px-2 py-1 rounded">{{ $role->slug }}</code>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ \Carbon\Carbon::parse($role->createdAt)->format('d/m/Y H:i') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="url('/admin/roles/' . $role->id)" variant="info" outline size="sm" icon="eye" title="Visualizar" />
                                        <x-ui.button :href="url('/admin/roles/' . $role->id . '/edit')" variant="primary" outline size="sm" icon="pencil-square" title="Editar" />
                                        <x-ui.button 
                                            type="button" 
                                            variant="danger" 
                                            outline 
                                            size="sm" 
                                            icon="trash" 
                                            title="Excluir"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-delete-url="{{ url('/admin/roles/' . $role->id) }}"
                                            data-item-name="{{ $role->name }}"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-person-badge display-4 d-block mb-3"></i>
                                    Nenhuma função encontrada
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
        message="Tem certeza que deseja excluir a função <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="função"
    />
</x-app-layout>
