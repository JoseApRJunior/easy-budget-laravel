@extends('layouts.app')

@section('title', 'Gerenciar Usuários')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Gerenciar Usuários"
            icon="people"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Usuários' => '#'
            ]">
            <x-slot:actions>
                {{-- <x-ui.button :href="route('admin.users.create')" variant="primary" icon="plus-lg" label="Novo Usuário" /> --}}
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Usuários"
                    icon="list-ul"
                    :total="$users->total() ?? 0"
                >
                    <x-resource.resource-table :headers="['ID', 'Nome', 'Email', 'Criado em', 'Ações']">
                        @forelse($users as $user)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $user->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="d-flex align-items-center">
                                        <x-ui.user-avatar :name="$user->name" class="me-2" />
                                        {{ $user->name }}
                                    </div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>{{ $user->email }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ $user->created_at->format('d/m/Y H:i') }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        {{-- 
                                        <x-ui.button :href="route('admin.users.show', $user)" variant="info" outline size="sm" icon="eye" title="Visualizar" />
                                        <x-ui.button :href="route('admin.users.edit', $user)" variant="primary" outline size="sm" icon="pencil-square" title="Editar" />
                                        --}}
                                        <x-ui.button 
                                            type="button" 
                                            variant="danger" 
                                            outline 
                                            size="sm" 
                                            icon="trash" 
                                            title="Excluir"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal" 
                                            data-delete-url="{{ route('admin.users.destroy', $user) }}"
                                            data-item-name="{{ $user->name }}"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="bi bi-people display-4 d-block mb-3"></i>
                                    Nenhum usuário encontrado
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>

                    @if(method_exists($users, 'links'))
                        <div class="mt-4">
                            {{ $users->links() }}
                        </div>
                    @endif
                </x-resource.resource-list-card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir o usuário <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="usuário"
    />
@endsection
