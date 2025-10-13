@extends( 'layouts.app' )

@section( 'content' )
    <div class="page-header">
        <h1 class="page-title">
            Visualizar Status do Serviço
        </h1>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Detalhes do Serviço</h3>
                    <div class="card-options">
                        <a href="{{ route( 'service.show', [ 'id' => $service->id ] ) }}" class="btn btn-primary btn-sm">
                            <i class="fe fe-eye"></i> Visualizar Serviço
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Código do Serviço:</strong> #{{ $service->id }}</p>
                            <p><strong>Cliente:</strong> {{ $service->customer->name }}</p>
                            <p><strong>Data de Criação:</strong> {{ $service->created_at->format( 'd/m/Y H:i' ) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Status Atual:</strong> <span
                                    class="badge badge-{{ $service->status->color }}">{{ $service->status->name }}</span>
                            </p>
                            <p><strong>Última Atualização:</strong> {{ $service->updated_at->format( 'd/m/Y H:i' ) }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Resumo Financeiro</h4>
                            <p><strong>Valor Total dos Itens:</strong> R$
                                {{ number_format( $service->total_items, 2, ',', '.' ) }}</p>
                            <p><strong>Desconto:</strong> R$ {{ number_format( $service->discount, 2, ',', '.' ) }}</p>
                            <p><strong>Valor Total do Serviço:</strong> R$
                                {{ number_format( $service->total_service, 2, ',', '.' ) }}</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-12">
                            <h4>Itens do Serviço</h4>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Produto/Serviço</th>
                                        <th>Quantidade</th>
                                        <th>Valor Unitário</th>
                                        <th>Valor Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach( $service->items as $item )
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->quantity }}</td>
                                            <td>R$ {{ number_format( $item->unit_price, 2, ',', '.' ) }}</td>
                                            <td>R$ {{ number_format( $item->quantity * $item->unit_price, 2, ',', '.' ) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer text-right">
                    <button type="button" class="btn btn-success" data-toggle="modal" data-target="#changeStatusModal"
                        data-status="approved">
                        <i class="fe fe-check-circle"></i> Aprovar
                    </button>
                    <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#changeStatusModal"
                        data-status="pending">
                        <i class="fe fe-alert-circle"></i> Marcar como Pendente
                    </button>
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#changeStatusModal"
                        data-status="canceled">
                        <i class="fe fe-x-circle"></i> Cancelar
                    </button>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#changeStatusModal"
                        data-status="in_progress">
                        <i class="fe fe-play-circle"></i> Iniciar Progresso
                    </button>
                    <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#changeStatusModal"
                        data-status="completed">
                        <i class="fe fe-check"></i> Concluir
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Mudança de Status -->
    <div class="modal fade" id="changeStatusModal" tabindex="-1" role="dialog" aria-labelledby="changeStatusModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeStatusModalLabel">Confirmar Mudança de Status</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Você tem certeza que deseja alterar o status deste serviço?</p>
                    <form id="changeStatusForm" action="{{ route( 'service.updateStatus', [ 'id' => $service->id ] ) }}"
                        method="POST">
                        @csrf
                        <input type="hidden" name="status" id="statusInput">
                        <div class="form-group">
                            <label for="observation">Observação (opcional):</label>
                            <textarea name="observation" id="observation" class="form-control" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="confirmChangeStatus">Confirmar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script>
        $( document ).ready( function () {
            $( '#changeStatusModal' ).on( 'show.bs.modal', function ( event ) {
                var button = $( event.relatedTarget );
                var status = button.data( 'status' );
                var modal = $( this );
                modal.find( '#statusInput' ).val( status );
            } );

            $( '#confirmChangeStatus' ).on( 'click', function () {
                $( '#changeStatusForm' ).submit();
            } );
        } );
    </script>
@endsection
