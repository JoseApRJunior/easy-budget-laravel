@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Unidades de Medida"
            icon="rulers"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Unidades' => '#'
            ]">
            <x-ui.button type="link" :href="route('admin.units.create')" variant="primary" icon="plus-circle" label="Nova Unidade" feature="manage-units" />
        </x-layout.page-header>

        <!-- Tabela de Unidades -->
        <x-resource.resource-list-card
            title="Lista de Unidades"
            mobileTitle="Unidades"
            icon="list-ul"
            :total="$units->count()"
            padding="p-0"
        >
            <x-slot:desktop>
                <x-resource.resource-table>
                    <x-slot:thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>Nome</th>
                            <th>Abreviação</th>
                            <th>Slug</th>
                            <th>Criado em</th>
                            <th width="150" class="text-center">Ações</th>
                        </tr>
                    </x-slot:thead>
                    <x-slot:tbody>
                        @forelse ($units as $unit)
                            <tr>
                                <td>{{ $unit->id }}</td>
                                <td><strong>{{ $unit->name }}</strong></td>
                                <td><span class="badge bg-secondary">{{ $unit->abbreviation }}</span></td>
                                <td><span class="text-code">{{ $unit->slug }}</span></td>
                                <td>{{ $unit->created_at->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <x-ui.button type="link" :href="route('admin.units.edit', $unit)" variant="primary" size="sm" icon="pencil" title="Editar" feature="manage-units" />
                                        <form action="{{ route('admin.units.destroy', $unit) }}" method="POST"
                                            onsubmit="return confirm('Tem certeza que deseja excluir esta unidade?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="danger" size="sm" icon="trash" title="Excluir" feature="manage-units" />
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <x-resource.empty-state
                                        title="Nenhuma unidade encontrada"
                                        description="Cadastre sua primeira unidade de medida."
                                        icon="rulers"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </x-slot:tbody>
                </x-resource.resource-table>
            </x-slot:desktop>

            <x-slot:mobile>
                @forelse ($units as $unit)
                    <x-resource.resource-mobile-item icon="rulers">
                        <x-resource.resource-mobile-header
                            :title="$unit->name"
                            :subtitle="$unit->abbreviation"
                        />
                        <x-slot:description>
                            <small class="text-muted">Slug: {{ $unit->slug }}</small>
                        </x-slot:description>
                        <x-slot:actions>
                            <div class="d-flex gap-1">
                                <x-ui.button type="link" :href="route('admin.units.edit', $unit)" variant="primary" size="sm" icon="pencil" feature="manage-units" />
                                <form action="{{ route('admin.units.destroy', $unit) }}" method="POST"
                                    onsubmit="return confirm('Tem certeza que deseja excluir esta unidade?')">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button type="submit" variant="danger" size="sm" icon="trash" feature="manage-units" />
                                </form>
                            </div>
                        </x-slot:actions>
                    </x-resource.resource-mobile-item>
                @empty
                    <div class="py-5">
                        <x-resource.empty-state
                            title="Nenhuma unidade encontrada"
                            description="Cadastre sua primeira unidade de medida."
                            icon="rulers"
                        />
                    </div>
                @endforelse
            </x-slot:mobile>
        </x-resource.resource-list-card>
    </x-layout.page-container>
</x-app-layout>
