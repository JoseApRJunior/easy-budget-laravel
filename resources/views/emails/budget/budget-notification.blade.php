@extends('emails.layouts.base')

@php
$notificationType = $notificationType ?? ($emailData['new_status'] ?? 'updated');
$customMessage = $customMessage ?? ($emailData['service_status_description'] ?? null);
$statusColor = $statusColor ?? ($emailData['status_color'] ?? '#0d6efd');
$primaryColor = config('theme.colors.primary', '#093172');
$textColor = config('theme.colors.text', '#1e293b');
@endphp

@section('title', $notificationType === 'created' ? 'Novo Orçamento Criado' : ($notificationType === 'updated' ? 'Orçamento Atualizado' : ($notificationType === 'approved' ? 'Orçamento Aprovado' : ($notificationType === 'rejected' ? 'Orçamento Rejeitado' : ($notificationType === 'cancelled' ? 'Orçamento Cancelado' : 'Notificação de Orçamento')))))

@section('content')
<div class="content">
    <h1 style="font-size: 22px; font-weight: 700; color: {{ $primaryColor }}; margin-bottom: 20px; margin-top: 0;">
        @if($notificationType === 'created')
        🎉 Um novo orçamento foi criado para você!
        @elseif($notificationType === 'updated')
        📝 Seu orçamento foi atualizado com novas informações.
        @elseif($notificationType === 'approved')
        ✅ Seu orçamento foi aprovado!
        @elseif($notificationType === 'sent')
        📧 Aqui está o seu orçamento!
        @elseif($notificationType === 'rejected')
        ❌ Seu orçamento foi rejeitado.
        @elseif($notificationType === 'cancelled')
        🚫 Seu orçamento foi cancelado.
        @else
        📋 Você recebeu uma notificação sobre seu orçamento.
        @endif
    </h1>

    <p style="margin: 0 0 16px;">Olá, {{ $budgetData['customer_name'] ?? ($emailData['first_name'] ?? 'Cliente') }}.</p>

    <div class="panel" style="background-color: #f8fafc; border-radius: 8px; padding: 20px; margin-top: 24px; border: 1px solid #e2e8f0;">
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Código:</strong> {{ $budgetData['code'] ?? ($emailData['service_code'] ?? 'N/A') }}</p>
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Valor Total:</strong> R$ {{ $budgetData['total'] ?? ($emailData['service_total'] ?? '0,00') }}</p>
        @if(isset($budgetData['discount']) && $budgetData['discount'] !== '0,00')
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Desconto:</strong> R$ {{ $budgetData['discount'] }}</p>
        @endif
        @if(isset($budgetData['due_date']) && $budgetData['due_date'])
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Validade:</strong> {{ $budgetData['due_date'] }}</p>
        @endif
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Status:</strong> {{ $budgetData['status'] ?? ($emailData['service_status_name'] ?? 'N/A') }}</p>

        @if(isset($budgetData['description']) && $budgetData['description'])
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Descrição:</strong><br>{{ $budgetData['description'] }}</p>
        @elseif(isset($emailData['service_description']) && $emailData['service_description'])
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">Descrição:</strong><br>{{ $emailData['service_description'] }}</p>
        @endif
    </div>

    @if($customMessage)
    @php
    $messageLabel = 'Mensagem do Profissional';

    if ($notificationType === 'rejected') {
    $messageLabel = 'Motivo da Rejeição';
    } elseif ($notificationType === 'cancelled') {
    $messageLabel = 'Motivo do Cancelamento';
    } elseif ($notificationType === 'approved') {
    $messageLabel = 'Observação do Cliente';
    }
    @endphp
    <div class="panel" style="background-color: #f8fafc; border-radius: 8px; padding: 20px; margin-top: 24px; border: 1px solid #e2e8f0; border-left: 4px solid {{ $statusColor ?? '#0d6efd' }};">
        <p style="margin: 8px 0; font-size: 15px; color: #475569;"><strong style="color: {{ $textColor }};">{{ $messageLabel }}:</strong></p>
        <p style="margin: 8px 0; font-size: 15px; color: #475569;">{!! nl2br(e($customMessage)) !!}</p>
    </div>
    @endif

    @if(in_array($notificationType, ['rejected', 'cancelled']))
    <div style="text-align: center; margin: 30px 0;">
        <p style="margin: 0; color: #475569;">Este orçamento foi marcado como {{ $notificationType === 'rejected' ? 'rejeitado' : 'cancelado' }}.</p>
    </div>
    @else
    @php
    $budgetUrl = $budgetUrl ?? ($emailData['link'] ?? '#');
    @endphp
    <div style="text-align: center; margin: 30px 0;">
        <a href="{{ $budgetUrl }}" class="btn" style="display: inline-block; background-color: {{ $statusColor ?? '#0d6efd' }}; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; font-size: 16px;">Ver Orçamento Completo</a>
    </div>

    @if($budgetUrl !== '#')
    <div style="margin-top: 20px;">
        <p style="margin-bottom: 10px; font-size: 14px; color: #475569;">Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy" style="word-break: break-all; font-family: Consolas, monospace; background-color: #f1f5f9; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; display: block; font-size: 12px; color: #94a3b8; margin-top: 12px;">{{ $budgetUrl }}</p>
    </div>
    @endif
    @endif
</div>
@endsection
