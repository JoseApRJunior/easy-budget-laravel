@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Unidades"
            icon="rulers"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Unidades' => '#'
            ]">
            <x-ui.button :href="route('admin.units.create')" variant="primary" icon="plus-circle" label="Nova Unidade" />
        </x-layout.page-header>

        <!-- Tabela de Unidades -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" class="ps-4">ID</th>
                                <th scope="col">Nome</th>
                                <th scope="col">Abreviação</th>
                                <th scope="col">Slug</th>
                                <th scope="col">Criado em</th>
                                <th scope="col" class="text-end pe-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($units as $unit)
                                <tr>
                                    <td class="ps-4">{{ $unit->id }}</td>
                                    <td>{{ $unit->name }}</td>
                                    <td><span class="badge bg-secondary">{{ $unit->abbreviation }}</span></td>
                                    <td><span class="text-code">{{ $unit->slug }}</span></td>
                                    <td>{{ $unit->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="{{ route('admin.units.edit', $unit) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-fill"></i>
                                            </a>
                                            <form action="{{ route('admin.units.destroy', $unit) }}" method="POST"
                                                onsubmit="return confirm('Tem certeza que deseja excluir esta unidade?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-1">Nenhuma unidade encontrada.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
