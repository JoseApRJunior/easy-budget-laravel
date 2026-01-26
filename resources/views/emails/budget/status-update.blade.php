@extends('emails.layouts.base')

@section('title', 'Atualização de Orçamento - #' . $emailData['service_code'])

@section('content')
<div class="content">
    <h1>
        @switch($emailData['new_status'])
            @case('approved')
                ✅ Orçamento Aprovado!
                @break
            @case('rejected')
                ❌ Orçamento Rejeitado
                @break
            @case('cancelled')
                🚫 Orçamento Cancelado
                @break
            @case('pending')
                ⏳ Orçamento aguardando aprovação
                @break
            @case('expired')
                ⚠️ Orçamento Expirado
                @break
            @default
                📋 Atualização no seu Orçamento
        @endswitch
    </h1>

    <p>Olá, <strong>{{ $emailData['first_name'] }}</strong>.</p>

    <p>Houve uma atualização no status do seu orçamento <strong>#{{ $emailData['service_code'] }}</strong>:</p>

    <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; background: #f8fafc; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 16px;">
            Status atual: <strong style="color: {{ $statusColor ?? '#0d6efd' }};">{{ $emailData['service_status_name'] }}</strong>
        </p>
        @if(!empty($emailData['service_status_description']))
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280; font-style: italic;">
                "{{ $emailData['service_status_description'] }}"
            </p>
        @endif
    </div>

    <div class="panel">
        <p><strong>Descrição:</strong><br>
            <span style="color: #475569; display: block; margin-top: 4px;">{{ $emailData['service_description'] }}</span>
        </p>

        @if(!empty($emailData['service_total']) && $emailData['service_total'] !== '0,00')
            <p><strong>Valor Total:</strong><br>
                <span style="color: #475569; display: block; margin-top: 4px;">R$ {{ $emailData['service_total'] }}</span>
            </p>
        @endif

        <p><strong>Atualizado em:</strong><br>
            <span style="color: #475569; display: block; margin-top: 4px;">{{ $emailData['status_changed_at'] }}</span>
        </p>
    </div>

    @if($emailData['new_status'] === 'pending')
        <div class="notice" style="background-color: #fff3cd; border-color: #ffeeba; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0;">
            <p style="margin: 0;"><strong>Atenção:</strong> Por favor, revise os detalhes e escolha entre aprovar ou rejeitar a proposta para prosseguirmos.</p>
        </div>
    @endif

    <div style="text-align: center; margin: 40px 0;">
        <a href="{{ $emailData['link'] }}" class="btn">Visualizar Orçamento Completo</a>
    </div>

    <p style="font-size: 13px; color: #94a3b8; text-align: center;">
        Se o botão acima não funcionar, copie e cole o URL abaixo:<br>
        <span class="subcopy">{{ $emailData['link'] }}</span>
    </p>

    <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 32px 0;">

    <p style="font-size: 14px; color: #64748b; text-align: center;">
        Este orçamento foi enviado por <strong>{{ $company['company_name'] ?? config('app.name') }}</strong>.
    </p>
</div>
@endsection
