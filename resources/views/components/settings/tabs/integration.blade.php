@props(['activeTab', 'tabs'])

<div class="tab-pane fade {{ $activeTab === 'integrations' ? 'show active' : '' }}" id="integracao">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Integrações</h3>
            <p class="text-muted small mb-0 mt-1">Conecte sua conta com meios de pagamento</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach( $tabs[ 'integrations' ][ 'data' ][ 'integrations' ] as $key => $integration )
                    <div class="col-12">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                                            <i class="bi bi-{{ $integration['icon'] }} text-primary fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="mb-0">{{ $integration[ 'name' ] }}</h5>
                                            <p class="text-muted small mb-0">{{ $integration['description'] ?? '' }}</p>
                                        </div>
                                    </div>
                                    @if( $integration[ 'status' ] === 'connected' )
                                        <span class="badge bg-success">Conectado</span>
                                    @elseif( $integration[ 'status' ] === 'expired' )
                                        <span class="badge bg-warning">Token Expirado</span>
                                    @else
                                        <span class="badge bg-secondary">Desconectado</span>
                                    @endif
                                </div>

                                @if( !empty($integration[ 'last_sync' ]) )
                                    <p class="text-muted small mb-3">
                                        Última atualização:
                                        {{ \Carbon\Carbon::parse( $integration[ 'last_sync' ] )->diffForHumans() }}
                                    </p>
                                @endif

                                <div class="d-flex gap-2">
                                    <a href="{{ route( 'integrations.mercadopago.index' ) }}" class="btn btn-primary btn-sm">
                                        <i class="bi bi-gear me-1"></i>Gerenciar Integração
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if( empty( $tabs[ 'integrations' ][ 'data' ][ 'integrations' ] ) )
                <div class="text-center py-5">
                    <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Nenhuma integração disponível no momento.</p>
                </div>
            @endif
        </div>
    </div>
</div>
