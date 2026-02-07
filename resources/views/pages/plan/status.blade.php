@extends('layouts.app')

@section('title', 'Status do Pagamento')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Status do Pagamento"
            icon="credit-card"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Planos' => route('provider.plans.index'),
                'Status do Pagamento' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('provider.plans.index')" variant="secondary" outline icon="arrow-left" label="Voltar para Planos" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row class="justify-content-center">
            <div class="col-md-8 col-lg-6">
                <x-ui.card class="text-center">
                    <x-slot:header>
                        <h4 class="mb-0 fw-bold text-primary">
                            <i class="bi bi-wallet2 me-2"></i>Status do Pagamento
                        </h4>
                    </x-slot:header>
                    
                    <div class="p-2">
                        @php
                            $final_statuses   = ['approved', 'rejected', 'cancelled', 'refunded', 'charged_back', 'recovered'];
                            $pending_statuses = ['pending', 'in_process', 'authorized', 'in_mediation'];
                        @endphp

                        {{-- --- Bloco para Status Finais --- --}}
                        @if (in_array($payment->status, $final_statuses))
                            <div class="mt-2">
                                @if ($payment->status == 'approved' || $payment->status == 'recovered')
                                    <div class="alert alert-success border-0 bg-success bg-opacity-10" role="alert">
                                        <div class="display-1 text-success mb-3"><i class="bi bi-check-circle-fill"></i></div>
                                        <h4 class="alert-heading fw-bold text-success">Pagamento Aprovado!</h4>
                                        <p class="mb-0">Sua assinatura para o plano <strong>{{ $subscription->name }}</strong> foi ativada com sucesso.</p>
                                        <hr>
                                        <x-ui.button href="/provider" variant="success" size="lg" icon="speedometer2" label="Ir para o Dashboard" />
                                    </div>
                                @else
                                    <div class="alert alert-danger border-0 bg-danger bg-opacity-10" role="alert">
                                        <div class="display-1 text-danger mb-3"><i class="bi bi-x-circle-fill"></i></div>
                                        <h4 class="alert-heading fw-bold text-danger">Pagamento não Concluído</h4>
                                        <p>O status do seu pagamento é <strong>{{ ucfirst($payment->status) }}</strong>.</p>
                                        <p class="mb-0">Parece que houve um problema ou a transação foi cancelada.</p>
                                        <hr>
                                        <p class="mb-3">Você pode tentar pagar novamente ou escolher outro plano.</p>
                                        
                                        <form action="/plans/pay" method="post" class="d-grid mb-2">
                                            @csrf
                                            <input type="hidden" id="planSlug" name="planSlug" value="{{ $subscription->slug }}" required>
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="bi bi-arrow-clockwise me-2"></i>Tentar Pagar Novamente
                                            </button>
                                        </form>
                                        <a href="/plans" class="btn btn-outline-secondary">Escolher Outro Plano</a>
                                    </div>
                                @endif
                            </div>

                        {{-- --- Bloco para Status Pendentes --- --}}
                        @elseif (in_array($payment->status, $pending_statuses))
                            <div class="text-center mb-4">
                                <span class="badge bg-warning text-dark px-3 py-2 fs-6 mb-3">
                                    <i class="bi bi-hourglass-split me-2"></i>Aguardando Pagamento
                                </span>
                                <p class="lead mb-0">
                                    Plano <strong>{{ $subscription->name }}</strong> - 
                                    <span class="fw-bold text-primary">R$ {{ number_format($subscription->transaction_amount, 2, ',', '.') }}</span>
                                </p>
                            </div>

                            {{-- --- Caso 1: PIX --- --}}
                            @if ($payment->payment_method_id == 'pix')
                                <div class="card bg-light border-0 p-3 mb-4">
                                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-qr-code me-2"></i>Pague com PIX</h5>
                                    
                                    <div class="bg-white p-3 rounded shadow-sm d-inline-block mx-auto mb-3">
                                        <img src="data:image/png;base64,{{ $payment->point_of_interaction->transaction_data->qr_code_base64 }}"
                                            alt="PIX QR Code" class="img-fluid" style="max-width: 200px;">
                                    </div>
                                    
                                    <p class="text-muted small mb-3">
                                        Este código expira em: 
                                        <strong>{{ \Carbon\Carbon::parse($payment->date_of_expiration)->format("d/m/Y H:i") }}</strong>
                                    </p>
                                    
                                    <label class="form-label small fw-bold text-uppercase text-muted">Copia e Cola</label>
                                    <div class="input-group">
                                        <input type="text" id="pixCode" class="form-control font-monospace bg-white"
                                            value="{{ $payment->point_of_interaction->transaction_data->qr_code }}" readonly>
                                        <button class="btn btn-primary" type="button"
                                            onclick="copyToClipboard('pixCode', this)">
                                            <i class="bi bi-clipboard me-1"></i> Copiar
                                        </button>
                                    </div>
                                </div>

                            {{-- --- Caso 2: Boleto --- --}}
                            @elseif ($payment->payment_method_id == 'bolbradesco' || $payment->payment_method_id == 'boleto')
                                <div class="card bg-light border-0 p-3 mb-4">
                                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-upc me-2"></i>Boleto Bancário</h5>
                                    
                                    <div class="mb-4">
                                        <a href="{{ $payment->transaction_details->external_resource_url }}" target="_blank"
                                            class="btn btn-primary btn-lg shadow-sm">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Visualizar Boleto
                                        </a>
                                    </div>
                                    
                                    <label class="form-label small fw-bold text-uppercase text-muted">Linha Digitável</label>
                                    <div class="input-group mb-2">
                                        <input type="text" id="boletoCode" class="form-control font-monospace bg-white"
                                            value="{{ $payment->transaction_details->digitable_line }}" readonly>
                                        <button class="btn btn-primary" type="button"
                                            onclick="copyToClipboard('boletoCode', this)">
                                            <i class="bi bi-clipboard me-1"></i> Copiar
                                        </button>
                                    </div>
                                    
                                    <p class="text-muted small mt-2">
                                        Vencimento: <strong>{{ \Carbon\Carbon::parse($payment->date_of_expiration)->format("d/m/Y") }}</strong>
                                    </p>
                                </div>

                            {{-- --- Caso 3: Cartão de Crédito em Análise --- --}}
                            @elseif ($payment->status == 'in_process')
                                <div class="py-4">
                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <h5 class="fw-bold">Pagamento em Análise</h5>
                                    <p class="text-muted">
                                        A operadora do seu cartão está processando o pagamento. Isso pode levar alguns minutos.
                                    </p>
                                </div>

                            {{-- --- Outros casos pendentes --- --}}
                            @else
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Aguardando a confirmação do pagamento. Você será notificado assim que o status for atualizado.
                                </div>
                            @endif

                            <div class="border-top pt-3">
                                <p class="text-muted small mb-3">Deseja usar outro método de pagamento?</p>
                                <form action="/plans/cancel-pending" method="post" class="d-grid">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i>Cancelar e Escolher Outro Plano
                                    </button>
                                </form>
                            </div>

                        {{-- --- Fallback para status não iniciado --- --}}
                        @elseif ($payment->status == 'not_started')
                            <div class="alert alert-warning border-0 bg-warning bg-opacity-10" role="alert">
                                <h4 class="alert-heading text-dark fw-bold"><i class="bi bi-exclamation-circle me-2"></i>Pagamento não Iniciado</h4>
                                <p class="mb-3 text-dark">Você pode iniciar o pagamento clicando no botão abaixo.</p>
                                
                                <form action="/plans/pay" method="post" class="d-grid mb-3">
                                    @csrf
                                    <input type="hidden" id="planSlug" name="planSlug" value="{{ $subscription->slug }}" required>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-credit-card me-2"></i>Iniciar Pagamento
                                    </button>
                                </form>
                                <a href="/plans" class="btn btn-outline-secondary d-grid">Escolher Outro Plano</a>
                            </div>

                        @else
                            <div class="alert alert-info" role="alert">
                                <h4 class="alert-heading fw-bold">Status: {{ ucfirst($payment->status) }}</h4>
                                <p class="mb-0">Se precisar de ajuda, por favor, entre em contato com nosso suporte.</p>
                            </div>
                        @endif
                    </div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection

@push('scripts')
<script>
    function copyToClipboard(inputId, buttonElement) {
        const inputElement = document.getElementById(inputId);
        if (!inputElement) return;

        inputElement.select();
        inputElement.setSelectionRange(0, 99999); // For mobile devices

        try {
            navigator.clipboard.writeText(inputElement.value).then(function() {
                const originalHtml = buttonElement.innerHTML;
                buttonElement.innerHTML = '<i class="bi bi-check-lg"></i> Copiado!';
                buttonElement.classList.remove('btn-primary', 'btn-outline-secondary');
                buttonElement.classList.add('btn-success');
                
                setTimeout(() => {
                    buttonElement.innerHTML = originalHtml;
                    buttonElement.classList.remove('btn-success');
                    buttonElement.classList.add('btn-primary');
                }, 2000);
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
                alert('Não foi possível copiar automaticamente. Por favor, copie manualmente.');
            });
        } catch (err) {
            console.error('Erro ao copiar: ', err);
            alert('Não foi possível copiar automaticamente. Por favor, copie manualmente.');
        }
    }
</script>
@endpush
