@extends('layout_pdf_base')
@section('title')Fatura - {{ $invoice->code }}@endsection

@section('content')
<div class="report-container">
  <div class="report-header">
    <table class="header-content" style="width: 100%;">
      @php
        $company = auth()->user() ?: (isset($authenticated) ? $authenticated : null);
      @endphp
      <tr>
        <td style="width: 50%; vertical-align: top;">
          <div class="company-info">
            <h2 style="font-size: 24px; margin-bottom: 5px; color: #2c3e50;">
              {{ $company->company_name }}
            </h2>
            <div class="company-details">
              <div style="margin-bottom: 5px; font-size: 11px;"><span>➤</span> <span>{{ $company->address }},
                  {{$company->address_number}}</span></div>
              <div style="margin-bottom: 5px; font-size: 11px;"><span>⚑</span> <span>@if ($company->cnpj)CNPJ: {{
                  $company->cnpj }}@else()CPF: {{ $company->cpf }}@endif</span></div>
              <div style="margin-bottom: 5px; font-size: 11px;"><span>☎</span> <span>@if ($company->phone_business){{
                  $company->phone_business }}@else(){{ $company->phone }}@endif</span></div>
              <div style="margin-bottom: 5px; font-size: 11px;"><span>✉</span> <span>@if ($company->email_business){{
                  $company->email_business }}@else(){{ $company->email }}@endif</span></div>
            </div>
          </div>
        </td>
        <td style="width: 50%; vertical-align: top; text-align: right;">
          <div class="report-title">
            <h1 style="font-size: 24px; margin-bottom: 5px; color: #2c3e50;">
              Fatura #{{ $invoice->code }}
            </h1>
            <div style="color: #666;">
              <div style="margin-bottom: 5px; font-size: 11px;"><span style="font-weight: 600;">Data de Emissão:</span>
                <span>{{ \Carbon\Carbon::parse($invoice->created_at)->format('d/m/Y') }}</span>
              </div>
              <div style="margin-bottom: 5px; font-size: 11px;"><span style="font-weight: 600;">Data de
                  Vencimento:</span> <span>{{ \Carbon\Carbon::parse($invoice->due_date)->format('d/m/Y') }}</span></div>
              <div style="margin-bottom: 5px; font-size: 11px;"><span style="font-weight: 600;">Status:</span> <span
                  style="color: {{ $invoice->status_color }} !important; font-weight: bold;">{{ $invoice->status_name
                  }}</span></div>
            </div>
          </div>
        </td>
      </tr>
    </table>
  </div>
  <hr style="border: 0; border-top: 1px solid #eee;">

  <div class="section">
    <h3
      style="font-size: 16px; color: #2c3e50; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
      Faturado para</h3>
    <div style="font-size: 12px; line-height: 1.4;">
      <p style="margin: 0;"><strong>{{ $invoice->customer_name }}</strong></p>
      <p style="margin: 0;">@if ($invoice->customer_cnpj)CNPJ: {{ $invoice->customer_cnpj }}@elseif($invoice->customer_cpf)CPF: {{ $invoice->customer_cpf
        }}@endif</p>
      <p style="margin: 0;">Email: {{ $invoice->customer_email_business ?: $invoice->customer_email }} | Telefone: {{
        $invoice->customer_phone_business
        ?: $invoice->customer_phone }}</p>
    </div>
  </div>
  <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

  <div class="section">
    <h3 style="font-size: 16px; color: #2c3e50; margin-bottom: 10px;">Itens da Fatura</h3>
    <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
      <thead>
        <tr>
          <th
            style="text-align: left; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">
            Descrição</th>
          <th
            style="text-align: right; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">
            Valor</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">Referente ao serviço: {{
            $invoice->service_code }} - {{ $invoice->service_description }}</td>
          <td style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">R$ {{
            number_format($invoice->subtotal, 2, ',', '.') }}</td>
        </tr>
      </tbody>
    </table>
  </div>

  <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">

  <div class="section">
    <h3 style="font-size: 16px; color: #2c3e50; margin-bottom: 10px;">Opções de Pagamento</h3>
    <div style="font-size: 12px; line-height: 1.5;">
      <p>Olá, {{ $invoice->customer_name }}! Para sua conveniência, oferecemos as seguintes opções para pagamento:</p>

      <div style="margin-bottom: 15px;">
        <h4 style="font-size: 13px; font-weight: bold; margin-bottom: 5px;">1. Pagar com Pix (Confirmação Imediata)</h4>
        <p style="margin: 0;">Use o QR Code abaixo ou a nossa chave para pagar de forma rápida e segura.</p>
        <p style="margin: 0;"><strong>Chave Pix (CNPJ):</strong> {{ $company->cnpj }}</p>
        <div style="margin-top: 10px;">
          <img src="{{ $qrCodePix }}" alt="QR Code Pix" style="width: 120px; height: 120px;">
        </div>
      </div>

      <div style="margin-bottom: 15px;">
        <h4 style="font-size: 13px; font-weight: bold; margin-bottom: 5px;">2. Pagar com Cartão de Crédito, Boleto ou
          Debito Mercado Pago (via Link de
          Pagamento)</h4>
        <p style="margin: 0;">Clique no link abaixo para pagar com seu cartão de crédito. Aceitamos parcelamento.</p>
        <p style="margin: 0;"><strong>Link para pagamento:</strong> <a
            href="{{ url('/invoices/view/' . $invoice->public_hash) }}">Link de
            Pagamento</a>.</p>
      </div>
    </div>
  </div>

  <div style="margin-top: 20px; float: right; width: 45%;">
    <table style="width: 100%; font-size: 12px;">
      <tr>
        <td style="padding: 5px;">Subtotal:</td>
        <td style="text-align: right; padding: 5px;">R$ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
      </tr>
      <tr>
        <td style="padding: 5px;">Desconto:</td>
        <td style="text-align: right; padding: 5px; color: #dc3545;">- R$ {{ number_format($invoice->discount, 2, ',', '.')
          }}</td>
      </tr>
      <tr style="font-weight: bold; border-top: 1px solid #ccc; font-size: 14px;">
        <td style="padding: 8px 5px;">Total a Pagar:</td>
        <td style="text-align: right; padding: 8px 5px; color: #28a745;">R$ {{ number_format($invoice->total, 2, ',', '.')
          }}</td>
      </tr>
    </table>
  </div>
</div>
@endsection
