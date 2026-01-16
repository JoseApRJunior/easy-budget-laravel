@extends('layouts.app')

@section('title', 'Áreas de Atividade')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Áreas de Atividade"
            icon="diagram-3"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Áreas de Atividade' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="url('/admin/area-of-activities/create')" variant="primary" icon="plus-circle" label="Adicionar Área de Atividade" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Áreas de Atividade"
                    icon="list-ul"
                    :total="$areaOfActivities->count()"
                    :actions="[]"
                >
                    <x-resource.resource-table :headers="['#', 'Nome', 'Slug', 'Criado em', 'Ações']">
                        @forelse($areaOfActivities as $areaOfActivity)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $areaOfActivity->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-diagram-3 text-muted me-2"></i>
                                        <strong>{{ $areaOfActivity->name }}</strong>
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <code class="bg-light px-2 py-1 rounded">{{ $areaOfActivity->slug }}</code>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    {{ $areaOfActivity->createdAt->format('d/m/Y H:i') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        <x-ui.button :href="url('/admin/area-of-activities/' . $areaOfActivity->id)" variant="info" outline size="sm" icon="eye" title="Visualizar" />
                                        <x-ui.button :href="url('/admin/area-of-activities/' . $areaOfActivity->id . '/edit')" variant="primary" outline size="sm" icon="pencil-square" title="Editar" />
                                        <x-ui.button 
                                            type="button" 
                                            variant="danger" 
                                            outline 
                                            size="sm" 
                                            icon="trash" 
                                            title="Excluir"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-delete-url="{{ url('/admin/area-of-activities/' . $areaOfActivity->id) }}"
                                            data-item-name="{{ $areaOfActivity->name }}"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-diagram-3 display-4 d-block mb-3"></i>
                                    Nenhuma área de atividade encontrada
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
        message="Tem certeza que deseja excluir a área de atividade <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="área de atividade"
    />
@endsection
