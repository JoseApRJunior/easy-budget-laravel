@extends('emails.layouts.base')

@section('title', 'Serviço Agendado - #' . $emailData['service_code'])

@section('content')
<div class="content">
    <div class="notice">
        <span class="icon">📅</span>
        <span>Atualização no seu agendamento!</span>
    </div>

    <h1>Agendamento #{{ $emailData['service_code'] }}</h1>

    <p>Olá, <strong>{{ $emailData['first_name'] }}</strong>.</p>

    <p>Houve uma atualização no status do seu agendamento:</p>

    <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; background: #f8fafc; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 16px;">
            Status do Agendamento: <strong style="color: {{ $statusColor ?? '#0d6efd' }};">{{ $emailData['service_status_name'] }}</strong>
        </p>
        @if(!empty($emailData['service_status_description']))
        <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
            {{ $emailData['service_status_description'] }}
        </p>
        @endif
    </div>

    @if(!empty($emailData['related_service_status']))
    <div class="panel" style="border-left: 4px solid {{ $emailData['related_service_status_color'] ?? '#6c757d' }}; background: #f8fafc; padding: 15px; margin-bottom: 20px;">
        <p style="margin: 0; font-size: 14px;">
            Status do Serviço Relacionado: <strong style="color: {{ $emailData['related_service_status_color'] ?? '#6c757d' }};">{{ $emailData['related_service_status'] }}</strong>
        </p>
    </div>
    @endif

    <div class="panel">
        <p><strong>Descrição do Serviço:</strong><br>{{ $emailData['service_description'] }}</p>
        @if($entity->start_date_time)
        <p><strong>Data e Horário:</strong><br>{{ $entity->start_date_time->format('d/m/Y \à\s H:i') }}</p>
        @endif
        @if($entity->location)
        <p><strong>Local:</strong><br>{{ $entity->location }}</p>
        @endif
    </div>

    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $emailData['link'] }}" class="btn">Visualizar Agendamento</a>
    </div>

    <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
    <p class="subcopy" style="word-break: break-all; color: #6b7280; font-size: 12px; background: #f3f4f6; padding: 10px; border-radius: 4px; margin-top: 10px;">
        {{ $emailData['link'] }}
    </p>

    <p>Você pode acompanhar todos os detalhes, solicitar alterações ou entrar em contato conosco diretamente pela plataforma.</p>

    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 25px 0;">

    <p style="font-size: 14px; color: #6b7280;">
        Se tiver alguma dúvida, não hesite em nos contatar.
    </p>
</div>
@endsection
