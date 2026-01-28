@extends('emails.layouts.base')

@php
$notificationType = $notificationType ?? ($emailData['new_status'] ?? 'updated');
$customMessage = $customMessage ?? ($emailData['service_status_description'] ?? null);
$statusColor = $statusColor ?? ($emailData['status_color'] ?? '#0d6efd');
@endphp

@section('title', $notificationType === 'created' ? 'Novo Orçamento Criado' : ($notificationType === 'updated' ? 'Orçamento Atualizado' : ($notificationType === 'approved' ? 'Orçamento Aprovado' : ($notificationType === 'rejected' ? 'Orçamento Rejeitado' : ($notificationType === 'cancelled' ? 'Orçamento Cancelado' : 'Notificação de Orçamento'))))))

@section('content')
<div class="content">
    <h1>
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

    <p>Olá, {{ $budgetData['customer_name'] ?? ($emailData['first_name'] ?? 'Cliente') }}.</p>

    <div class="panel">
        <p><strong>Código:</strong> {{ $budgetData['code'] ?? ($emailData['service_code'] ?? 'N/A') }}</p>
        <p><strong>Valor Total:</strong> R$ {{ $budgetData['total'] ?? ($emailData['service_total'] ?? '0,00') }}</p>
        @if(isset($budgetData['discount']) && $budgetData['discount'] !== '0,00')
        <p><strong>Desconto:</strong> R$ {{ $budgetData['discount'] }}</p>
        @endif
        @if(isset($budgetData['due_date']) && $budgetData['due_date'])
        <p><strong>Validade:</strong> {{ $budgetData['due_date'] }}</p>
        @endif
        <p><strong>Status:</strong> {{ $budgetData['status'] ?? ($emailData['service_status_name'] ?? 'N/A') }}</p>

        @if(isset($budgetData['description']) && $budgetData['description'])
        <p><strong>Descrição:</strong><br>{{ $budgetData['description'] }}</p>
        @elseif(isset($emailData['service_description']) && $emailData['service_description'])
        <p><strong>Descrição:</strong><br>{{ $emailData['service_description'] }}</p>
        @endif
    </div>

    @if($customMessage)
    @php
    $isCustomerAction = in_array($notificationType, ['approved', 'rejected', 'cancelled']);
    $messageLabel = 'Mensagem do Profissional';

    if ($notificationType === 'rejected') {
    $messageLabel = 'Motivo da Rejeição';
    } elseif ($notificationType === 'cancelled') {
    $messageLabel = 'Motivo do Cancelamento';
    } elseif ($notificationType === 'approved') {
    $messageLabel = 'Observação do Cliente';
    }
    @endphp
    <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }};">
        <p><strong>{{ $messageLabel }}:</strong></p>
        <p>{!! nl2br(e($customMessage)) !!}</p>
    </div>
    @endif

    @if(in_array($notificationType, ['rejected', 'cancelled']))
    <div style="text-align: center; margin: 30px 0;">
        <p>Este orçamento foi marcado como {{ $notificationType === 'rejected' ? 'rejeitado' : 'cancelado' }}.</p>
    </div>
    @else
    @php
    $budgetUrl = $budgetUrl ?? ($emailData['link'] ?? '#');
    @endphp
    <div style="text-align: center; margin: 30px 0;">
        <!-- Adicionado text-decoration: none inline para garantir compatibilidade -->
        <a href="{{ $budgetUrl }}" class="btn" style="text-decoration: none;">Ver Orçamento Completo</a>
    </div>

    @if($budgetUrl !== '#')
    <div style="margin-top: 20px;">
        <p style="margin-bottom: 10px;">Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy">{{ $budgetUrl }}</p>
    </div>
    @endif
    @endif
</div>
@endsection
