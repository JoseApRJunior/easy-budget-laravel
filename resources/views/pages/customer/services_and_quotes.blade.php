@extends( 'layouts.app' )


@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-clipboard-data me-2"></i>Serviços e Orçamentos
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Serviços e Orçamentos</li>
                </ol>
            </nav>
        </div>

        <!-- Serviços -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-tools me-2"></i>Serviços
                    </h5>
                    <a href="{{ route( 'provider.services.create' ) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-2"></i>Novo Serviço
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Cliente</th>
                                <th>Serviço</th>
                                <th>Orçamento</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th class="text-end px-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse( $servicos as $servico )
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <span class="bg-opacity-10 text-primary rounded px-2 py-1 me-2 small">
                                                {{ substr( $servico->cliente->nome, 0, 1 ) }}
                                            </span>
                                            {{ $servico->cliente->nome }}
                                        </div>
                                    </td>
                                    <td>{{ $servico->nome }}</td>
                                    <td>R$ {{ number_format( $servico->orcamento->valor, 2, ',', '.' ) }}</td>
                                    <td>{{ \Carbon\Carbon::parse( $servico->data )->format( 'd/m/Y' ) }}</td>
                                    <td>
                                        @php
                                            $statusClass = 'secondary';
                                            if ( $servico->status == 'Concluído' ) {
                                                $statusClass = 'success';
                                            } elseif ( $servico->status == 'Em andamento' ) {
                                                $statusClass = 'primary';
                                            } elseif ( $servico->status == 'Pendente' ) {
                                                $statusClass = 'warning';
                                            }
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $servico->status }}
                                        </span>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <x-button type="link" :href="route( 'provider.services.show', $servico->id )" variant="info" size="sm" icon="eye" title="Visualizar" />
                                            <x-button type="link" :href="route( 'provider.services.edit', $servico->id )" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                            <x-button variant="danger" size="sm" icon="trash" title="Excluir"
                                                onclick="handleGenericDelete(this)" 
                                                data-type="servico" 
                                                data-id="{{ $servico->id }}" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-1">
                                        <div class="text-muted">
                                            <i class="bi bi-tools display-6 d-block mb-2"></i>
                                            <p class="mb-0">Nenhum serviço encontrado</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Orçamentos -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-1">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-receipt me-2"></i>Orçamentos
                    </h5>
                    <a href="{{ route( 'provider.budgets.create' ) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg me-2"></i>Novo Orçamento
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Cliente</th>
                                <th>Orçamento</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th class="text-end px-4">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ( $orcamentos as $orcamento )
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <span class="bg-opacity-10 text-primary rounded px-2 py-1 me-2 small">
                                                {{ substr( $orcamento->cliente->nome, 0, 1 ) }}
                                            </span>
                                            {{ $orcamento->cliente->nome }}
                                        </div>
                                    </td>
                                    <td>R$ {{ number_format( $orcamento->valor, 2, ',', '.' ) }}</td>
                                    <td>{{ \Carbon\Carbon::parse( $orcamento->data )->format( 'd/m/Y' ) }}</td>
                                    <td>
                                        @php
                                            $statusClass = 'secondary';
                                            if ( $orcamento->status == 'Aprovado' ) {
                                                $statusClass = 'success';
                                            } elseif ( $orcamento->status == 'Pendente' ) {
                                                $statusClass = 'warning';
                                            } elseif ( $orcamento->status == 'Recusado' ) {
                                                $statusClass = 'danger';
                                            }
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $orcamento->status }}
                                        </span>
                                    </td>
                                    <td class="text-end px-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <x-button type="link" :href="route( 'provider.budgets.show', $orcamento->code )" variant="info" size="sm" icon="eye" title="Visualizar" />
                                            <x-button type="link" :href="route( 'provider.budgets.edit', $orcamento->code )" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                            <x-button variant="danger" size="sm" icon="trash" title="Excluir"
                                                onclick="handleGenericDelete(this)"
                                                data-type="orcamento" 
                                                data-id="{{ $orcamento->id }}" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-1">
                                        <div class="text-muted">
                                            <i class="bi bi-receipt display-6 d-block mb-2"></i>
                                            <p class="mb-0">Nenhum orçamento encontrado</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir este item?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST" action="#">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            // Inicializa tooltips
            const tooltipTriggerList = [].slice.call( document.querySelectorAll( '[data-bs-toggle="tooltip"]' ) );
            tooltipTriggerList.map( function ( tooltipTriggerEl ) {
                return new bootstrap.Tooltip( tooltipTriggerEl );
            } );

            // Listener para botões de exclusão
            document.querySelectorAll('.btn-delete-item').forEach(button => {
                button.addEventListener('click', function() {
                    const type = this.getAttribute('data-type');
                    const id = this.getAttribute('data-id');
                    confirmDelete(type, id);
                });
            });
        } );

        function confirmDelete( type, id ) {
            const modal = new bootstrap.Modal( document.getElementById( 'deleteModal' ) );
            const form = document.getElementById( 'deleteForm' );
            form.action = `{{ route( 'provider.dashboard' ) }}/${type}/${id}`;
            modal.show();
        }
    </script>
@endsection
