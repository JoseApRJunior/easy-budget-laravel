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

            @if($budgetData['description'] && $budgetData['description'] !== 'Orçamento sem descrição')
                <p><strong>Descrição:</strong><br>{{ $budgetData['description'] }}</p>
            @endif
        </div>

        @if($customMessage)
            <div class="panel" style="border-left: 4px solid #0d6efd;">
                <p><strong>Mensagem do Profissional:</strong></p>
                <p>{{ $customMessage }}</p>
            </div>
        @endif

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $budgetUrl }}" class="btn">Ver Orçamento Completo</a>
        </div>

        <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy">{{ $budgetUrl }}</p>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e5e7eb;">

        <h3>Informações da Empresa</h3>
        <p>
            <strong>{{ $company['company_name'] ?? config('app.name') }}</strong><br>
            @if($company['email_business'] ?? $company['email'] ?? null)
                Email: {{ $company['email_business'] ?? $company['email'] }}<br>
            @endif
            @if($company['phone_business'] ?? $company['phone'] ?? null)
                Telefone: {{ $company['phone_business'] ?? $company['phone'] }}<br>
            @endif
        </p>

        @if($supportEmail)
            <p style="font-size: 13px; color: #6b7280; margin-top: 20px;">
                Precisa de ajuda? Entre em contato: <a href="mailto:{{ $supportEmail }}">{{ $supportEmail }}</a>
            </p>
        @endif
    </div>
@endsection
