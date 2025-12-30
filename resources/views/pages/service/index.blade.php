@extends('layouts.app')

@section('title', 'Gestão de Serviços')

@section('content')
    <div class="container-fluid py-1">
        <x-page-header 
            title="Serviços" 
            icon="gear" 
            :breadcrumb-items="[
                'Serviços' => '#'
            ]"
        >
            <p class="text-muted mb-0">Lista de todos os serviços registrados no sistema</p>
        </x-page-header>

        <div class="row">
            <div class="col-12">
                <!-- Filtros de Busca -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
                    </div>
                    <div class="card-body">
                        <form id="filtersFormServices" method="GET" action="{{ route('provider.services.index') }}">
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="search">Buscar</label>
                                        <input type="text" class="form-control" id="search" name="search"
                                            value="{{ request('search') }}"
                                            placeholder="Código ou Descrição">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="category_id">Categoria</label>
                                        <select class="form-select tom-select" id="category_id" name="category_id">
                                            <option value="">Todas</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ request('category_id') == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="status">Status</label>
                                        <select class="form-select tom-select" id="status" name="status">
                                            <option value="">Todos</option>
                                            @foreach ($statuses as $value => $label)
                                                <option value="{{ $value }}"
                                                    {{ request('status') == $value ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="start_date">Início</label>
                                        <input type="text" class="form-control" id="start_date" name="start_date"
                                            value="{{ request('start_date') }}" data-mask="00/00/0000" placeholder="DD/MM/AAAA">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label for="end_date">Fim</label>
                                        <input type="text" class="form-control" id="end_date" name="end_date"
                                            value="{{ request('end_date') }}" data-mask="00/00/0000" placeholder="DD/MM/AAAA">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label class="d-block">&nbsp;</label>
                                        <div class="d-flex gap-2">
                                            <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterServices" />
                                            <x-button type="link" :href="route('provider.services.index')" variant="outline-secondary" icon="x" title="Limpar" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Lista de Serviços
                                    <span class="text-muted small ms-2">({{ $services->count() }})</span>
                                </h5>
                            </div>
                            <div class="col-12 col-lg-4 text-lg-end mt-2 mt-lg-0">
                                <x-button type="link" :href="route('provider.services.create')" variant="primary" icon="plus" label="Novo Serviço" />
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th>Código</th>
                                        <th>Cliente</th>
                                        <th>Categoria</th>
                                        <th>Data/Hora</th>
                                        <th>Status</th>
                                        <th class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($services as $service)
                                        <tr>
                                            <td><span class="text-code">{{ $service->code }}</span></td>
                                            <td>{{ $service->budget->customer->name ?? 'N/A' }}</td>
                                            <td>{{ $service->category->name ?? 'N/A' }}</td>
                                            <td>{{ $service->scheduled_at ? $service->scheduled_at->format('d/m/Y H:i') : 'N/A' }}</td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $service->status->getColor() }}">
                                                    <i class="bi bi-{{ $service->status->getIcon() }} me-1"></i>
                                                    {{ $service->status->getDescription() }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-btn-group">
                                                    <x-button type="link" :href="route('provider.services.show', $service->code)" variant="info" icon="eye" title="Visualizar" />
                                                    <x-button type="link" :href="route('provider.services.edit', $service->code)" variant="primary" icon="pencil-square" title="Editar" />
                                                    <x-button variant="danger" icon="trash" 
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-delete-url="{{ route('provider.services.destroy', $service->code) }}"
                                                        data-item-name="{{ $service->code }}"
                                                        title="Excluir" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                Nenhum serviço encontrado.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Exclusão --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    Tem certeza de que deseja excluir o serviço <strong id="deleteItemName"></strong>?
                    <br><small class="text-muted">Esta ação não pode ser desfeita.</small>
                </div>
                <div class="modal-footer">
                    <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                    <form id="deleteForm" action="#" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" label="Excluir" />
                    </form>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormServices');

            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;
                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    const message = 'A data inicial não pode ser maior que a data final.';
                    if (window.easyAlert) {
                        window.easyAlert.warning(message);
                    } else {
                        alert(message);
                    }
                    return false;
                }
                return true;
            };

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateDates()) {
                        e.preventDefault();
                        return;
                    }

                    if (startDate.value && !endDate.value) {
                        e.preventDefault();
                        const message = 'Para filtrar por período, informe as datas inicial e final.';
                        if (window.easyAlert) {
                            window.easyAlert.error(message);
                        } else {
                            alert(message);
                        }
                        endDate.focus();
                    } else if (!startDate.value && endDate.value) {
                        e.preventDefault();
                        const message = 'Para filtrar por período, informe as datas inicial e final.';
                        if (window.easyAlert) {
                            window.easyAlert.error(message);
                        } else {
                            alert(message);
                        }
                        startDate.focus();
                    }
                });
            }

            // Modal de exclusão
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const deleteUrl = button.getAttribute('data-delete-url');
                    const itemName = button.getAttribute('data-item-name');

                    const deleteItemNameDisplay = deleteModal.querySelector('#deleteItemName');
                    const deleteForm = deleteModal.querySelector('#deleteForm');

                    deleteItemNameDisplay.textContent = itemName;
                    deleteForm.action = deleteUrl;
                });
            }
        });
    </script>
@endpush
@endsection
