@extends('emails.layouts.base')

@section('title', 'Atualização de Status - ' . $emailData['service_code'])

@section('content')
    <div class="content">
        <h1>{{ $emailData['entity_type'] }} #{{ $emailData['service_code'] }}</h1>

        <p>Olá, {{ $emailData['first_name'] }}.</p>

        <p>Houve uma atualização no status do seu {{ strtolower($emailData['entity_type']) }}:</p>
        
        <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; background: #f8fafc; padding: 15px;">
            <p style="margin: 0; font-size: 16px;">
                Status atual: <strong style="color: {{ $statusColor ?? '#0d6efd' }};">{{ $emailData['service_status_name'] }}</strong>
            </p>
        </div>

        <div class="panel">
            <p><strong>Descrição:</strong> {{ $emailData['service_description'] }}</p>
            @if(!empty($emailData['service_total']) && $emailData['service_total'] !== '0,00')
                <p><strong>Valor Total:</strong> R$ {{ $emailData['service_total'] }}</p>
            @endif
            <p><strong>Atualizado em:</strong> {{ $emailData['status_changed_at'] }}</p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $emailData['link'] }}" class="btn">Visualizar Detalhes</a>
        </div>

        <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy">{{ $emailData['link'] }}</p>

        <p style="margin-top: 20px;">Se tiver alguma dúvida, entre em contato com o prestador.</p>
    </div>
@endsection
