@extends('emails.layouts.base')

@section('title', 'Nova Mensagem de Contato - ' . config('app.name'))

@section('content')
    <div class="notice">
        <span class="icon">✉</span>
        <span>Nova mensagem de contato recebida</span>
    </div>

    <p><strong>Olá, equipe de suporte!</strong></p>
    
    <p>Uma nova mensagem de contato foi recebida através do formulário do site. Seguem os detalhes:</p>

    <div class="panel">
        <h3 style="margin-top: 0; color: #374151;">📋 Detalhes do Contato</h3>
        
        <p><strong>Nome:</strong> {{ $contactData['name'] ?? ($contactData['first_name'] ?? '') . ' ' . ($contactData['last_name'] ?? '') }}</p>
        
        <p><strong>E-mail:</strong> 
            <a href="mailto:{{ $contactData['email'] }}" style="color: #0d6efd;">{{ $contactData['email'] }}</a>
        </p>
        
        <p><strong>Assunto:</strong> {{ $contactData['subject'] }}</p>
        
        <p><strong>Data/Hora:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        
        @if($tenant)
            <p><strong>Tenant:</strong> {{ $tenant->name }} (ID: {{ $tenant->id }})</p>
        @endif
    </div>

    <div class="panel">
        <h3 style="margin-top: 0; color: #374151;">💬 Mensagem</h3>
        <div style="background: #ffffff; padding: 12px; border-radius: 4px; border-left: 4px solid #0d6efd;">
            {!! nl2br(e($contactData['message'])) !!}
        </div>
    </div>

    <div style="margin: 24px 0; text-align: center;">
        <a href="mailto:{{ $contactData['email'] }}?subject=Re: {{ urlencode($contactData['subject']) }}" 
           class="btn" 
           style="margin-right: 10px;">
            📧 Responder por E-mail
        </a>
        
        @if(isset($supportUrl))
            <a href="{{ $supportUrl }}" class="btn" style="background: #6b7280;">
                🎫 Acessar Painel de Suporte
            </a>
        @endif
    </div>

    <div class="panel" style="font-size: 12px; color: #6b7280;">
        <p style="margin: 0;"><strong>💡 Dicas para resposta:</strong></p>
        <ul style="margin: 8px 0; padding-left: 20px;">
            <li>Responda em até 24 horas para manter um bom atendimento</li>
            <li>Use um tom profissional e cordial</li>
            <li>Se necessário, solicite informações adicionais</li>
            <li>Considere criar um ticket de suporte se o problema for complexo</li>
        </ul>
    </div>

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 24px 0;">
    
    <p style="font-size: 13px; color: #6b7280; text-align: center;">
        <strong>Informações do Sistema:</strong><br>
        IP do remetente: {{ request()->ip() ?? 'N/A' }}<br>
        User Agent: {{ request()->userAgent() ?? 'N/A' }}<br>
        Timestamp: {{ now()->toISOString() }}
    </p>
@endsection

@section('footerExtra')
    <div style="font-size: 11px; color: #9ca3af; margin-top: 8px;">
        Este e-mail foi gerado automaticamente pelo sistema {{ config('app.name') }}.<br>
        Para responder ao contato, use o botão "Responder por E-mail" acima.
    </div>
@endsection