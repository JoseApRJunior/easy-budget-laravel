@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Integração Mercado Pago"
            icon="credit-card-2-front-fill"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => url('/settings'),
                'Mercado Pago' => '#'
            ]">
            <x-button :href="url('/settings')" variant="secondary" outline icon="arrow-left" label="Voltar" />
        </x-page-header>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light border-0">
                <h5 class="mb-0">Status da Conexão</h5>
            </div>
            <div class="card-body">

                @if ($isConnected)
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <div>
                            <strong>Conta Mercado Pago conectada</strong>
                        </div>
                    </div>
                    @if (!empty($public_key))
                        <p class="text-muted">Sua chave pública é: <span class="text-code">{{ $public_key }}</span></p>
                    @endif
                    @if ($expires_readable)
                        <p class="text-muted">Token expira em {{ $expires_readable }}.</p>
                    @endif
                    <p>Agora você pode gerar faturas e receber pagamentos diretamente na sua conta.</p>
                    <form action="{{ route('integrations.mercadopago.disconnect') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-lg me-2"></i>Desconectar Conta
                        </button>
                    </form>
                    <form action="{{ route('integrations.mercadopago.refresh') }}" method="POST" class="d-inline ms-2">
                        @csrf
                        <button type="submit" class="btn btn-secondary" @if (!$can_refresh) disabled @endif>
                            <i class="bi bi-arrow-repeat me-2"></i>Renovar Tokens
                        </button>
                    </form>
                @else
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <div>
                            <strong>Não conectado.</strong> Vincule sua conta Mercado Pago para começar a receber
                            pagamentos.
                        </div>
                    </div>
                    <p>Ao conectar sua conta, você autoriza o Easy Budget a criar cobranças em seu nome. Nós não temos
                        acesso à
                        sua
                        senha ou dados financeiros.</p>
                    <a href="{{ $authorization_url }}" class="btn btn-primary btn-lg">
                        <i class="bi bi-link-45deg me-2"></i>Conectar com Mercado Pago
                    </a>
                @endif
            </div>
            <div class="card-footer bg-transparent border-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    A integração permite que seus clientes paguem faturas diretamente para sua conta Mercado Pago. Uma taxa
                    de
                    serviço da plataforma pode ser aplicada.
                </small>
            </div>
        </div>
    </div>
@endsection
