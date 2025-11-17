<div class="tab-pane fade" id="integracao">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Integrações</h3>
            <p class="text-muted small mb-0 mt-1">Gerencie suas integrações com gateways de pagamento</p>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @foreach($tabs['integrations']['data']['integrations'] as $key => $integration)
                    <div class="col-12 col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="mb-0">{{ $integration['name'] }}</h5>
                                    @if($integration['status'] === 'connected')
                                        <span class="badge bg-success">Conectado</span>
                                    @elseif($integration['status'] === 'expired')
                                        <span class="badge bg-warning">Token Expirado</span>
                                    @else
                                        <span class="badge bg-secondary">Desconectado</span>
                                    @endif
                                </div>
                                
                                @if($integration['last_sync'])
                                    <p class="text-muted small mb-3">
                                        Última sincronização: {{ \Carbon\Carbon::parse($integration['last_sync'])->diffForHumans() }}
                                    </p>
                                @endif

                                <div class="d-flex gap-2">
                                    @if($integration['status'] === 'connected')
                                        <form method="POST" action="{{ route('integrations.mercadopago.disconnect') }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Tem certeza que deseja desconectar esta integração?')">
                                                <i class="bi bi-plug me-1"></i>Desconectar
                                            </button>
                                        </form>
                                        @if($integration['status'] === 'expired')
                                            <form method="POST" action="{{ route('integrations.mercadopago.refresh') }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    <i class="bi bi-arrow-clockwise me-1"></i>Renovar Token
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <a href="{{ route('integrations.mercadopago.index') }}" 
                                           class="btn btn-primary btn-sm">
                                            <i class="bi bi-plug me-1"></i>Conectar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(empty($tabs['integrations']['data']['integrations']))
                <div class="text-center py-5">
                    <i class="bi bi-box-seam text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">Nenhuma integração disponível no momento.</p>
                </div>
            @endif
        </div>
    </div>
</div>