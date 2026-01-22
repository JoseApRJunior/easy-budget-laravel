@extends('emails.layouts.base')

@section('title', $emailData['entity_type'] . ' #' . $emailData['service_code'])

@section('content')
<div class="content">
    <h1>{{ $emailData['entity_type'] }} #{{ $emailData['service_code'] }}</h1>

    <p>Olá, <strong>{{ $emailData['first_name'] }}</strong>.</p>

    <p>Houve uma atualização no status do seu {{ strtolower($emailData['entity_type']) }}:</p>

    <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; background: #f8fafc; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 16px;">
            Status atual: <strong style="color: {{ $statusColor ?? '#0d6efd' }};">{{ $emailData['service_status_name'] }}</strong>
        </p>
        @if(!empty($emailData['service_status_description']))
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                {{ $emailData['service_status_description'] }}
            </p>
        @endif
    </div>

    <div class="panel">
        <p><strong>Descrição:</strong><br>{{ $emailData['service_description'] }}</p>
        @if(!empty($emailData['service_total']) && $emailData['service_total'] !== '0,00')
        <p><strong>Valor Total:</strong> R$ {{ $emailData['service_total'] }}</p>
        @endif
        <p><strong>Atualizado em:</strong> {{ $emailData['status_changed_at'] }}</p>
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $emailData['link'] }}" class="btn">Visualizar Detalhes</a>
    </div>

    <p style="font-size: 14px; color: #6b7280; margin-top: 20px;">
        Se você não conseguir clicar no botão, copie e cole o link abaixo no seu navegador:
        <br>
        <span class="subcopy" style="margin-top: 10px;">{{ $emailData['link'] }}</span>
    </p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 25px 0;">

    <p style="font-size: 14px; color: #6b7280;">
        Se tiver alguma dúvida, entre em contato com o prestador responsável.
    </p>
</div>
@endsection
