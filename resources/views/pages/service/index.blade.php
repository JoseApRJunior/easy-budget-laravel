@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Action cards -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title mb-0">Gerenciamento de Serviços</h5>
                            <small class="text-muted">Visualize, crie e gerencie os serviços prestados.</small>
                        </div>
                        <a href="{{ route( 'provider.services.create' ) }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-2"></i>Novo Serviço
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form id="filter-form" action="{{ route( 'provider.services.index' ) }}" method="GET">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="service_number" class="form-label">Número do Serviço</label>
                            <input type="text" id="service_number" name="service_number" class="form-control"
                                value="{{ request( 'service_number' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Data Inicial</label>
                            <input type="date" id="start_date" name="start_date" class="form-control"
                                value="{{ request( 'start_date' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Data Final</label>
                            <input type="date" id="end_date" name="end_date" class="form-control"
                                value="{{ request( 'end_date' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="client" class="form-label">Cliente</label>
                            <input type="text" id="client" name="client" class="form-control"
                                value="{{ request( 'client' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="min_value" class="form-label">Valor Mínimo</label>
                            <input type="number" id="min_value" name="min_value" class="form-control"
                                value="{{ request( 'min_value' ) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select id="status" name="status" class="form-select">
                                <option value="">Todos</option>
                                @foreach ( StatusHelper::service_status_options() as $status )
                                    <option value="{{ $status->id }}" {{ request( 'status' ) == $status->id ? 'selected' : '' }}>
                                        {{ $status->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                            <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-outline-secondary">Limpar</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="results-table" class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ( $services as $service )
                                <tr>
                                    <td>{{ $service->code }}</td>
                                    <td>{{ $service->customer_name }}</td>
                                    <td>{{ DateHelper::formatBR( $service->created_at ) }}</td>
                                    <td>{{ DateHelper::formatBR( $service->due_date ) }}</td>
                                    <td>R$ {{ number_format( $service->total, 2, ',', '.' ) }}</td>
                                    <td>{!! StatusHelper::status_badge( $service->status ) !!}</td>
                                    <td>
                                        <a href="{{ route( 'provider.services.show', $service->code ) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route( 'provider.services.edit', $service->id ) }}"
                                            class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                            data-bs-target="#deleteModal" data-id="{{ $service->id }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Nenhum serviço encontrado.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @include( 'partials.components.table_paginator', [ 'data' => $services ] )
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este serviço?
                </div>
                <div class="modal-footer">
                    <form id="delete-form" action="" method="POST">
                        @csrf
                        @method( 'DELETE' )
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            var deleteModal = document.getElementById( 'deleteModal' );
            deleteModal.addEventListener( 'show.bs.modal', function ( event ) {
                var button = event.relatedTarget;
                var serviceId = button.getAttribute( 'data-id' );
                var form = document.getElementById( 'delete-form' );
                var action = '{{ route( "provider.services.destroy", [ ":id" ] ) }}';
                action = action.replace( ':id', serviceId );
                form.action = action;
            } );
        } );
    </script>
@endpush
