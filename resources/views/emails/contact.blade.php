@extends('emails.layouts.base')

@section('content')
<div style="margin-bottom: 25px;">
    <h2 style="color: {{ $statusColor ?? '#0d6efd' }}; margin-bottom: 15px;">Nova Mensagem de Contato</h2>
    <p>Você recebeu uma nova mensagem através do formulário de contato.</p>
</div>

<div style="background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
    <h3 style="color: #495057; font-size: 16px; margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">
        Informações do Remetente
    </h3>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 5px 0; color: #6c757d; width: 100px;"><strong>Nome:</strong></td>
            <td style="padding: 5px 0; color: #212529;">{{ $contactData['name'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="padding: 5px 0; color: #6c757d;"><strong>E-mail:</strong></td>
            <td style="padding: 5px 0; color: #212529;">
                <a href="mailto:{{ $contactData['email'] ?? '' }}" style="color: {{ $statusColor ?? '#0d6efd' }}; text-decoration: none;">
                    {{ $contactData['email'] ?? 'N/A' }}
                </a>
            </td>
        </tr>
        @if(isset($contactData['phone']) && !empty($contactData['phone']))
        <tr>
            <td style="padding: 5px 0; color: #6c757d;"><strong>Telefone:</strong></td>
            <td style="padding: 5px 0; color: #212529;">{{ $contactData['phone'] }}</td>
        </tr>
        @endif
        @if(isset($contactData['subject']) && !empty($contactData['subject']))
        <tr>
            <td style="padding: 5px 0; color: #6c757d;"><strong>Assunto:</strong></td>
            <td style="padding: 5px 0; color: #212529;">{{ $contactData['subject'] }}</td>
        </tr>
        @endif
    </table>
</div>

<div style="background-color: #ffffff; border-left: 4px solid {{ $statusColor ?? '#0d6efd' }}; padding: 15px 20px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
    <h3 style="color: #495057; font-size: 16px; margin-top: 0; margin-bottom: 10px;">Mensagem:</h3>
    <p style="white-space: pre-wrap; color: #212529; font-style: italic; line-height: 1.6;">{{ $contactData['message'] ?? 'Sem mensagem.' }}</p>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="mailto:{{ $contactData['email'] ?? '' }}" style="display: inline-block; padding: 12px 24px; background-color: {{ $statusColor ?? '#0d6efd' }}; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Responder Cliente
    </a>
</div>
@endsection
