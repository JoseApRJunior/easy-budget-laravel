@extends('layouts.app')

@section('title', 'Integração Mercado Pago')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Integração Mercado Pago"
            icon="credit-card-2-front-fill"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => url('/settings'),
                'Mercado Pago' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="url('/settings')" variant="secondary" outline icon="arrow-left" label="Voltar" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 text-primary fw-bold">
                            <i class="bi bi-link-45deg me-2"></i>Status da Conexão
                        </h5>
                    </x-slot:header>
                    
                    <div class="p-2">
                        @if ($isConnected)
                            <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                                <div>
                                    <strong class="d-block">Conta Mercado Pago conectada</strong>
                                    <span class="small">Agora você pode gerar faturas e receber pagamentos diretamente na sua conta.</span>
                                </div>
                            </div>

                            <div class="bg-light p-3 rounded mb-4">
                                @if (!empty($public_key))
                                    <div class="mb-2">
                                        <span class="text-muted small text-uppercase fw-bold">Chave Pública:</span>
                                        <code class="bg-white px-2 py-1 rounded border ms-2">{{ $public_key }}</code>
                                    </div>
                                @endif
                                @if ($expires_readable)
                                    <div>
                                        <span class="text-muted small text-uppercase fw-bold">Expiração do Token:</span>
                                        <span class="ms-2">{{ $expires_readable }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="d-flex gap-2">
                                <form action="{{ route('integrations.mercadopago.disconnect') }}" method="POST" class="d-inline">
                                    @csrf
                                    <x-ui.button type="submit" variant="danger" icon="x-lg" label="Desconectar Conta" />
                                </form>
                                <form action="{{ route('integrations.mercadopago.refresh') }}" method="POST" class="d-inline">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary" icon="arrow-repeat" label="Renovar Tokens" :disabled="!$can_refresh" />
                                </form>
                            </div>
                        @else
                            <div class="text-center py-4">
                                <div class="mb-4">
                                    <i class="bi bi-exclamation-triangle-fill text-warning display-4"></i>
                                </div>
                                <h4 class="fw-bold">Não conectado</h4>
                                <p class="text-muted mb-4">
                                    Vincule sua conta Mercado Pago para começar a receber pagamentos.<br>
                                    Ao conectar sua conta, você autoriza o Easy Budget a criar cobranças em seu nome.<br>
                                    <small class="fst-italic">Nós não temos acesso à sua senha ou outros dados financeiros.</small>
                                </p>
                                
                                <x-ui.button :href="$authorization_url" variant="primary" size="lg" icon="link-45deg" label="Conectar com Mercado Pago" />
                            </div>
                        @endif
                    </div>

                    <x-slot:footer>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            A integração permite que seus clientes paguem faturas diretamente para sua conta Mercado Pago. Uma taxa de serviço da plataforma pode ser aplicada.
                        </small>
                    </x-slot:footer>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
