@extends('emails.layouts.base')

@section('title', $notificationType === 'created' ? 'Novo Orçamento Criado' : ($notificationType === 'updated' ? 'Orçamento Atualizado' : ($notificationType === 'approved' ? 'Orçamento Aprovado' : ($notificationType === 'rejected' ? 'Orçamento Rejeitado' : 'Notificação de Orçamento'))))

@section('content')
    <div class="content">
        <h1>
            @if($notificationType === 'created')
                🎉 Um novo orçamento foi criado para você!
            @elseif($notificationType === 'updated')
                📝 Seu orçamento foi atualizado com novas informações.
            @elseif($notificationType === 'approved')
                ✅ Seu orçamento foi aprovado!
            @elseif($notificationType === 'rejected')
                ❌ Seu orçamento foi rejeitado.
            @else
                📋 Você recebeu uma notificação sobre seu orçamento.
            @endif
        </h1>

        <p>Olá, {{ $customer->first_name }}.</p>

        <div class="panel">
            <p><strong>Código:</strong> {{ $budgetData['code'] }}</p>
            <p><strong>Valor Total:</strong> R$ {{ $budgetData['total'] }}</p>
            @if($budgetData['discount'] !== '0,00')
                <p><strong>Desconto:</strong> R$ {{ $budgetData['discount'] }}</p>
            @endif
            @if($budgetData['due_date'])
                <p><strong>Validade:</strong> {{ $budgetData['due_date'] }}</p>
            @endif
            <p><strong>Status:</strong> {{ $budgetData['status'] }}</p>

            @if($budgetData['description'])
                <p><strong>Descrição:</strong><br>{{ $budgetData['description'] }}</p>
            @endif
        </div>

        @if($customMessage)
            <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }};">
                <p><strong>Mensagem do Profissional:</strong></p>
                <p>{{ $customMessage }}</p>
            </div>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $budgetUrl }}" class="btn">Ver Orçamento Completo</a>
        </div>

        <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy">{{ $budgetUrl }}</p>
    </div>
@endsection
