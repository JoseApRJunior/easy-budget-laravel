@extends('emails.layouts.base')

@section('title', 'Sua Fatura ' . $invoiceData['code'])

@section('content')
    <div class="content">
        <h1>Nova Fatura Disponível</h1>

        <p>Olá, {{ $invoiceData['customer_name'] }}.</p>

        @if($customMessage)
            <div class="panel" style="border-left: 4px solid {{ $statusColor ?? '#0d6efd' }};">
                <p><strong>Mensagem do Profissional:</strong></p>
                <p>{{ $customMessage }}</p>
            </div>
        @else
            <p>Você recebeu uma nova fatura referente aos serviços prestados.</p>
        @endif

        <div class="panel">
            <h3>Detalhes da Fatura</h3>
            <p><strong>Número da Fatura:</strong> {{ $invoiceData['code'] }}</p>
            <p><strong>Valor Total:</strong> R$ {{ $invoiceData['total'] }}</p>
            @if($invoiceData['subtotal'] !== $invoiceData['total'])
                <p><strong>Subtotal:</strong> R$ {{ $invoiceData['subtotal'] }}</p>
            @endif
            @if($invoiceData['discount'] !== '0,00')
                <p><strong>Desconto:</strong> R$ {{ $invoiceData['discount'] }}</p>
            @endif
            @if($invoiceData['due_date'])
                <p><strong>Vencimento:</strong> {{ $invoiceData['due_date'] }}</p>
            @endif
            @if($invoiceData['payment_method'])
                <p><strong>Forma de Pagamento:</strong> {{ $invoiceData['payment_method'] }}</p>
            @endif

            @if($invoiceData['notes'] && $invoiceData['notes'] !== 'Fatura sem observações')
                <p><strong>Observações:</strong><br>{{ $invoiceData['notes'] }}</p>
            @endif
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ $publicLink }}" class="btn">Visualizar Fatura Completa</a>
        </div>

        <p>Se o botão acima não funcionar, copie e cole o seguinte URL em seu navegador:</p>
        <p class="subcopy">{{ $publicLink }}</p>
@endsection
