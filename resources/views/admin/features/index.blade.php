<x-app-layout title="Gerenciamento de Módulos (Feature Flags) - EasyBudget">
    <x-layout.page-container>
        <x-layout.page-header
            title="Módulos do Sistema"
            description="Gerencie os módulos e funcionalidades do sistema"
            icon="toggle-on"
            :breadcrumb-items="[
                'Admin' => route('admin.index'),
                'Módulos' => '#'
            ]" />

        <x-layout.grid-row>
            <x-layout.grid-col>
                <x-resource.resource-list-card
                    title="Lista de Módulos"
                    icon="list-ul"
                    :total="$features->count()">

                    <x-slot:headerActions>
                        <x-ui.button
                            variant="primary"
                            data-bs-toggle="modal"
                            data-bs-target="#createFeatureModal"
                            icon="plus-lg"
                            label="Novo Módulo"
                            feature="manage-settings" />
                    </x-slot:headerActions>

                    <x-resource.resource-table>
                        <x-slot:thead>
                            <x-resource.table-row>
                                <x-resource.table-cell header>Nome</x-resource.table-cell>
                                <x-resource.table-cell header>Slug</x-resource.table-cell>
                                <x-resource.table-cell header>Ambiente</x-resource.table-cell>
                                <x-resource.table-cell header>Status</x-resource.table-cell>
                                <x-resource.table-cell header align="right">Ações</x-resource.table-cell>
                            </x-resource.table-row>
                        </x-slot:thead>
                        <x-slot:tbody>
                            @foreach($features as $feature)
                                <x-resource.table-row>
                                    <x-resource.table-cell>
                                        <x-ui.text weight="bold">{{ $feature->name }}</x-ui.text>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        <code>{{ $feature->slug }}</code>
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        @if($feature->in_dev)
                                            <x-ui.badge variant="warning" label="Em Desenvolvimento" icon="cone-striped" solid />
                                        @else
                                            <x-ui.badge variant="primary" label="Produção" icon="rocket-takeoff" solid />
                                        @endif
                                    </x-resource.table-cell>
                                    <x-resource.table-cell>
                                        @if($feature->status === \App\Models\Resource::STATUS_ACTIVE)
                                            <x-ui.badge variant="success" label="Ativo" />
                                        @else
                                            <x-ui.badge variant="secondary" label="Inativo" />
                                        @endif
                                    </x-resource.table-cell>
                                    <x-resource.table-cell align="right">
                                        <x-layout.h-stack justify="end" gap="2">
                                            <x-ui.button
                                                type="button"
                                                variant="primary"
                                                size="sm"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editFeatureModal"
                                                data-id="{{ $feature->id }}"
                                                data-name="{{ $feature->name }}"
                                                data-description="{{ $feature->description }}"
                                                onclick="populateEditModal(this)"
                                                icon="pencil"
                                                title="Editar"
                                                feature="manage-settings"
                                            />

                                            <form action="{{ route('admin.features.toggle-dev', $feature) }}" method="POST">
                                                @csrf
                                                <x-ui.button
                                                    type="submit"
                                                    variant="{{ $feature->in_dev ? 'success' : 'warning' }}"
                                                    size="sm"
                                                    icon="{{ $feature->in_dev ? 'check-circle' : 'cone-striped' }}"
                                                    title="{{ $feature->in_dev ? 'Promover para Produção' : 'Voltar para Desenvolvimento' }}"
                                                    feature="manage-settings"
                                                />
                                            </form>

                                            <form action="{{ route('admin.features.toggle', $feature) }}" method="POST">
                                                @csrf
                                                <x-ui.button
                                                    type="submit"
                                                    variant="{{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'warning' : 'success' }}"
                                                    size="sm"
                                                    icon="{{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'toggle-on' : 'toggle-off' }}"
                                                    title="{{ $feature->status === \App\Models\Resource::STATUS_ACTIVE ? 'Desativar' : 'Ativar' }}"
                                                    feature="manage-settings"
                                                />
                                            </form>

                                            <form action="{{ route('admin.features.destroy', $feature) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja remover este módulo?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button
                                                    type="submit"
                                                    variant="danger"
                                                    size="sm"
                                                    icon="trash"
                                                    title="Excluir"
                                                    feature="manage-settings"
                                                />
                                            </form>
                                        </x-layout.h-stack>
                                    </x-resource.table-cell>
                                </x-resource.table-row>
                            @endforeach
                        </x-slot:tbody>
                    </x-resource.resource-table>
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>

    <!-- Create Feature Modal -->
    <x-ui.modal id="createFeatureModal" title="Cadastrar Novo Módulo">
        <form action="{{ route('admin.features.store') }}" method="POST" id="createFeatureForm">
            @csrf
            <x-ui.form.input
                wrapperClass="mb-3"
                name="name"
                label="Nome do Módulo"
                placeholder="Ex: Módulo de Estoque"
                required />

            <x-ui.form.input
                wrapperClass="mb-3"
                name="slug"
                label="Slug (Identificador único)"
                placeholder="Ex: estoque"
                required
                help="Use apenas letras minúsculas e hifens." />

            <x-ui.form.textarea
                wrapperClass="mb-3"
                name="description"
                label="Descrição"
                rows="3"
                placeholder="Breve descrição da funcionalidade..." />

            <x-ui.form.checkbox
                name="in_dev"
                id="in_dev"
                label="Módulo em Desenvolvimento"
                checked
                switch />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" data-bs-dismiss="modal" label="Cancelar" feature="manage-settings" />
            <x-ui.button type="submit" form="createFeatureForm" variant="primary" label="Salvar Módulo" feature="manage-settings" />
        </x-slot:footer>
    </x-ui.modal>

    <!-- Edit Feature Modal -->
    <x-ui.modal id="editFeatureModal" title="Editar Módulo">
        <form id="editFeatureForm" method="POST">
            @csrf
            @method('PUT')
            <x-ui.form.input
                wrapperClass="mb-3"
                id="edit_name"
                name="name"
                label="Nome do Módulo"
                required />

            <x-ui.form.textarea
                wrapperClass="mb-3"
                id="edit_description"
                name="description"
                label="Descrição"
                rows="3" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" data-bs-dismiss="modal" label="Cancelar" feature="manage-settings" />
            <x-ui.button type="submit" form="editFeatureForm" variant="primary" label="Salvar Alterações" feature="manage-settings" />
        </x-slot:footer>
    </x-ui.modal>

    @push('scripts')
    <script>
        function populateEditModal(button) {
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const description = button.getAttribute('data-description');

            const form = document.getElementById('editFeatureForm');
            const nameInput = document.getElementById('edit_name');
            const descriptionInput = document.getElementById('edit_description');

            // Update form action
            form.action = `/admin/features/${id}`;

            // Populate fields
            nameInput.value = name;
            descriptionInput.value = description || '';
        }
    </script>
    @endpush
</x-app-layout>
