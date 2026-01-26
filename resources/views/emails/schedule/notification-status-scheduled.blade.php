@extends('emails.layouts.base')

@section('title', 'Serviço Agendado - #' . $emailData['service_code'])

@section('content')
<div class="notice">
    📅 Atualização no seu agendamento!
</div>

<h1>Agendamento #{{ $emailData['service_code'] }}</h1>

<p>Olá, <strong>{{ $emailData['first_name'] }}</strong>.</p>

<p>Houve uma atualização no status do seu agendamento:</p>

<div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; background: #ffffff;">
    <p style="margin: 0; font-size: 16px;">
        Status do Agendamento: <strong style="color: {{ $statusColor ?? '#0d6efd' }};">{{ $emailData['service_status_name'] }}</strong>
    </p>
    @if(!empty($emailData['service_status_description']))
    <p style="margin: 8px 0 0 0; font-size: 14px; color: #64748b; font-style: italic;">
        "{{ $emailData['service_status_description'] }}"
    </p>
    @endif
</div>

@if(!empty($emailData['related_service_status']))
<div class="panel" style="border-left: 4px solid {{ $emailData['related_service_status_color'] ?? '#6c757d' }}; background: #ffffff;">
    <p style="margin: 0; font-size: 14px;">
        Status do Serviço Relacionado: <strong style="color: {{ $emailData['related_service_status_color'] ?? '#6c757d' }};">{{ $emailData['related_service_status'] }}</strong>
    </p>
</div>
@endif

<div class="panel">
    <p><strong>Descrição do Serviço:</strong><br>
        <span style="color: #475569; display: block; margin-top: 4px;">{{ $emailData['service_description'] }}</span>
    </p>

    @if($entity->start_date_time)
    <p><strong>Data e Horário:</strong><br>
        <span style="color: #475569; display: block; margin-top: 4px;">
            📅 {{ $entity->start_date_time->format('d/m/Y') }}<br>
            ⏰ das {{ $entity->start_date_time->format('H:i') }} às {{ $entity->end_date_time->format('H:i') }}
        </span>
    </p>
    @endif

    @if($entity->location)
    <p><strong>Local:</strong><br>
        <span style="color: #475569; display: block; margin-top: 4px;">📍 {{ $entity->location }}</span>
    </p>
    @endif

    @if($entity->notes)
    <p><strong>Observações:</strong><br>
        <span style="color: #475569; display: block; margin-top: 4px;">📝 {{ $entity->notes }}</span>
    </p>
    @endif
</div>

<div style="text-align: center; margin: 40px 0;">
    <a href="{{ $emailData['link'] }}" class="btn">Visualizar Detalhes do Agendamento</a>
</div>

<p style="font-size: 13px; color: #94a3b8; text-align: center;">
    Se o botão acima não funcionar, copie e cole o URL abaixo:<br>
    <span class="subcopy">{{ $emailData['link'] }}</span>
</p>

<hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 32px 0;">

<p style="font-size: 14px; color: #64748b; text-align: center;">
    Você pode acompanhar todos os detalhes, solicitar alterações ou entrar em contato conosco diretamente pela plataforma.
</p>
@endsection
