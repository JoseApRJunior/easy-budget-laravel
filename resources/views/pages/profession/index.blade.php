@extends('layouts.app')

@section('title', 'Profissões')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Profissões"
            icon="briefcase"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Profissões' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="url('/admin/professions/create')" variant="primary" icon="plus-circle" label="Adicionar Profissão" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Profissões"
                    icon="list-ul"
                    :total="count($professions ?? [])"
                    :actions="[]"
                >
                    <x-resource.resource-table :headers="['#', 'Nome', 'Slug', 'Criado em', 'Ações']">
                        @forelse($professions as $profession)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $profession->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-briefcase text-muted me-2"></i>
                                        <strong>{{ $profession->name }}</strong>
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <code class="bg-light px-2 py-1 rounded">{{ $profession->slug }}</code>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ \Carbon\Carbon::parse($profession->createdAt)->format('d/m/Y H:i') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="url('/admin/professions/' . $profession->id)" variant="info" outline size="sm" icon="eye" title="Visualizar" feature="manage-professions" />
                                        <x-ui.button :href="url('/admin/professions/' . $profession->id . '/edit')" variant="primary" outline size="sm" icon="pencil-square" title="Editar" feature="manage-professions" />
                                        <x-ui.button 
                                            type="button" 
                                            variant="danger" 
                                            outline 
                                            size="sm" 
                                            icon="trash" 
                                            title="Excluir"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-delete-url="{{ url('/admin/professions/' . $profession->id) }}"
                                            data-item-name="{{ $profession->name }}"
                                            feature="manage-professions"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-briefcase display-4 d-block mb-3"></i>
                                    Nenhuma profissão encontrada
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
        message="Tem certeza que deseja excluir a profissão <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="profissão"
    />
</x-app-layout>
