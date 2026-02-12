<x-app-layout title="Gerenciar Tenants">
    <x-layout.page-container>
        <x-layout.page-header
            title="Gerenciar Tenants"
            icon="building-gear"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Tenants' => '#'
            ]">
            <x-slot:actions>
                {{-- <x-ui.button :href="route('admin.tenants.create')" variant="primary" icon="plus-lg" label="Novo Tenant" feature="manage-tenants" /> --}}
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-resource.resource-list-card
                    title="Lista de Tenants"
                    icon="list-ul"
                    :total="$tenants->total() ?? 0"
                >
                    <x-resource.resource-table :headers="['ID', 'Nome', 'Domínio', 'Plano', 'Status', 'Ações']">
                        @forelse($tenants as $tenant)
                            <x-resource.table-row>
                                <x-resource.table-cell>{{ $tenant->id }}</x-resource.table-cell>
                                <x-resource.table-cell>
                                    <div class="fw-bold">{{ $tenant->name }}</div>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    @if($tenant->domain)
                                        <a href="http://{{ $tenant->domain }}" target="_blank" class="text-decoration-none">
                                            {{ $tenant->domain }} <i class="bi bi-box-arrow-up-right small ms-1"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <span class="badge bg-info text-dark">
                                        {{ $tenant->plan->name ?? 'N/A' }}
                                    </span>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <span class="badge bg-{{ $tenant->status == 'active' ? 'success' : 'secondary' }}">
                                        {{ $tenant->status == 'active' ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    <x-resource.action-buttons>
                                        {{-- 
                                        <x-ui.button :href="route('admin.tenants.show', $tenant)" variant="info" outline size="sm" icon="eye" title="Visualizar" feature="manage-tenants" />
                                        <x-ui.button :href="route('admin.tenants.edit', $tenant)" variant="primary" outline size="sm" icon="pencil-square" title="Editar" feature="manage-tenants" />
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
                                            data-delete-url="{{ route('admin.tenants.destroy', $tenant) }}"
                                            data-item-name="{{ $tenant->name }}"
                                            feature="manage-tenants"
                                        />
                                    </x-resource.action-buttons>
                                </x-resource.table-cell>
                            </x-resource.table-row>
                        @empty
                            <x-resource.table-row>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="bi bi-building display-4 d-block mb-3"></i>
                                    Nenhum tenant encontrado
                                </td>
                            </x-resource.table-row>
                        @endforelse
                    </x-resource.resource-table>

                    @if(method_exists($tenants, 'links'))
                        <div class="mt-4">
                            {{ $tenants->links() }}
                        </div>
                    @endif
                </x-resource.resource-list-card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>

    <x-ui.confirm-modal 
        id="deleteModal" 
        title="Confirmar Exclusão" 
        message="Tem certeza que deseja excluir o tenant <strong id='deleteModalItemName'></strong>?" 
        submessage="Esta ação não pode ser desfeita e excluirá todos os dados associados."
        confirmLabel="Excluir"
        variant="danger"
        type="delete" 
        resource="tenant"
    />
</x-app-layout>
