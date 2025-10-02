@extends('layout_pdf_base')

@section('title')Detalhes do Serviço - {{ $service->code }}@endsection

@section('content')
<div class="report-container">
    <div class="report-header">
        <table class="header-content" style="width: 100%;">
            @php
                $company = auth()->user();
            @endphp
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <div class="company-info">
                        <h2 style="font-size: 24px; margin-bottom: 5px; color: #2c3e50;">
                            {{ $company->company_name }}
                        </h2>
                        <div class="company-details">
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 5px;">➤</span>
                                <span>{{ $company->address }}, {{ $company->address_number }}</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 5px;">⚑</span>
                                <span>@if($company->cnpj)CNPJ: {{ $company->cnpj }}@else CPF: {{ $company->cpf }}@endif</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 5px;">☎</span>
                                <span>@if($company->phone_business){{ $company->phone_business }}@else{{ $company->phone }}@endif</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 5px;">✉</span>
                                <span>@if($company->email_business){{ $company->email_business }}@else{{ $company->email }}@endif</span>
                            </div>
                        </div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <div class="report-title">
                        <h1 style="font-size: 24px; margin-bottom: 5px; color: #2c3e50;">
                            Serviço #{{ $service->code }}
                        </h1>
                        <div style="color: #666;">
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="font-weight: 600; margin-right: 5px;">Cliente:</span>
                                <span>{{ $service->customer_name }}</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="font-weight: 600; margin-right: 5px;">Status:</span>
                                <span style="color: {{ $service->status->color }} !important; font-weight: bold;">{{ $service->status->name }}</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <hr style="border: 0; border-top: 1px solid #eee;">

    <div class="section">
        <h3 style="font-size: 18px; color: #2c3e50; margin-bottom: 5px;">Descrição do Serviço</h3>
        <p style="font-size: 12px; line-height: 1.4;">{{ $service->description }}</p>
    </div>

    <div class="section">
        <h3 style="font-size: 18px; color: #2c3e50; margin-bottom: 5px;">Informações do Serviço</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                    <div class="card-like" style="padding: 10px; margin-bottom: 5px;">
                        <h4 style="font-size: 14px; color: #333; margin-bottom: 5px;"><i class="bi bi-info-circle me-2"></i>Detalhes Gerais</h4>
                        <div style="font-size: 12px; margin-bottom: 3px;">
                            <span style="font-weight: 600;">Categoria:</span> {{ $service->category->name }}
                        </div>
                        <div style="font-size: 12px; margin-bottom: 3px;">
                            <span style="font-weight: 600;">Orçamento:</span> {{ $service->budget_code }}
                        </div>
                        <div style="font-size: 12px;">
                            <span style="font-weight: 600;">Vencimento:</span> {{ $service->due_date->format('d/m/Y') }}
                        </div>
                    </div>
                </td>
                <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                    <div class="card-like" style="padding: 10px; margin-bottom: 5px;">
                        <h4 style="font-size: 14px; color: #333; margin-bottom: 5px;"><i class="bi bi-calendar me-2"></i>Datas</h4>
                        <p style="font-size: 12px; margin-bottom: 3px;">Criado em: {{ $service->created_at->format("d/m/Y H:i:s") }}</p>
                        <p style="font-size: 12px;">Atualizado em: {{ $service->updated_at->format("d/m/Y H:i:s") }}</p>
                    </div>
                </td>
            </tr>
            @if ($latest_schedule)
            <tr>
                <td colspan="2" style="vertical-align: top;">
                    <div class="card-like" style="padding: 10px; margin-bottom: 5px;">
                        <h4 style="font-size: 14px; color: #333; margin-bottom: 5px;"><i class="bi bi-calendar-event me-2"></i>Agendamento</h4>
                        <div style="font-size: 12px; margin-bottom: 3px;">
                            <span style="font-weight: 600;">Data/Hora:</span> {{ \Carbon\Carbon::parse($latest_schedule->start_date_time)->format('d/m/Y H:i') }}
                        </div>
                        <div style="font-size: 12px;">
                            <span style="font-weight: 600;">Local:</span> {{ $latest_schedule->location ?? 'Não informado' }}
                        </div>
                    </div>
                </td>
            </tr>
            @endif
        </table>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 5px 0;">

    <div class="section">
        <h3 style="font-size: 18px; color: #2c3e50; margin-bottom: 5px;">Resumo Financeiro</h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
            <tr>
                <td style="padding: 2px 0;">Total Bruto:</td>
                <td style="text-align: right; padding: 2px 0; font-weight: bold;">
                    @if ($service->status->slug == 'CANCELLED')
                    <s style="color: #dc3545;">R$ {{ number_format($service->total, 2, ',', '.') }}</s>
                    @elseif ($service->status->slug == 'PARTIAL')
                    <span style="color: #dc3545;">R$ {{ number_format($service->total, 2, ',', '.') }}</span>
                    @else
                    <span style="color: #28a745;">R$ {{ number_format($service->total, 2, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
            @if ($service->discount > 0)
            <tr>
                <td style="padding: 2px 0;">Desconto:</td>
                <td style="text-align: right; padding: 2px 0; font-weight: bold;">
                    @if ($service->status->slug == 'CANCELLED')
                    <s style="color: #dc3545;">- R$ {{ number_format($service->discount, 2, ',', '.') }}</s>
                    @else
                    <span style="color: #dc3545;">- R$ {{ number_format($service->discount, 2, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
            <tr style="border-top: 1px solid #eee;">
                <td style="padding: 5px 0; font-weight: 600;">Total Líquido:</td>
                <td style="text-align: right; padding: 5px 0; font-weight: bold;">
                    @php $service_net_total = $service->total - $service->discount; @endphp
                    @if ($service->status->slug == 'CANCELLED')
                    <s style="color: #dc3545;">R$ {{ number_format($service_net_total, 2, ',', '.') }}</s>
                    @else
                    <span style="color: #28a745;">R$ {{ number_format($service_net_total, 2, ',', '.') }}</span>
                    @endif
                </td>
            </tr>
            @endif
            @if ($service->status->slug == 'PARTIAL')
            <tr>
                <td colspan="2" style="padding-top: 10px;">
                    <div style="font-size: 11px; padding: 8px; background-color: #fff3cd; border: 1px solid #ffeeba; color: #856404; border-radius: 4px;">
                        <strong>Atenção:</strong> O valor final a ser faturado será calculado com base no trabalho realizado.
                    </div>
                </td>
            </tr>
            @endif
            <tr style="border-top: 1px solid #eee; margin-top: 5px;">
                <td style="padding: 5px 0;">Quantidade de Itens:</td>
                <td style="text-align: right; padding: 5px 0;">
                    {{ $service->items->count() }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 5px 0; font-size: 12px;">
                    <span style="font-weight: 600;">Status do Serviço:</span>
                    <span style="display: block; margin-top: 3px; font-size: 11px; color: #666;">
                        {{ $service->status->description }}
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <hr style="border: 0; border-top: 1px solid #eee; margin: 5px 0;">

    <div class="section">
        <h3 style="font-size: 18px; color: #2c3e50; margin-bottom: 5px;">Itens do Serviço</h3>
        @if ($service->items->isNotEmpty())
        <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
            <thead>
                <tr>
                    <th style="text-align: left; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">#</th>
                    <th style="text-align: left; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">Código</th>
                    <th style="text-align: left; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">Produto</th>
                    <th style="text-align: right; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">Qtd</th>
                    <th style="text-align: right; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">Valor Unit.</th>
                    <th style="text-align: right; padding: 8px; font-size: 12px; background-color: #f8f9fa !important; border-bottom: 1px solid #ddd;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($service->items as $item)
                <tr>
                    <td style="text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">{{ $loop->iteration }}</td>
                    <td style="text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">{{ $item->product->code }}</td>
                    <td style="text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">{{ $item->product->name }}</td>
                    <td style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">{{ $item->quantity }}</td>
                    <td style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">R$ {{ number_format($item->unit_value, 2, ',', '.') }}</td>
                    <td style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #eee;">R$ {{ number_format($item->unit_value * $item->quantity, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right; padding: 8px; font-size: 12px; font-weight: bold; background-color: #f8f9fa !important; border-top: 2px solid #ddd;">Total Líquido dos Itens de Serviço:</td>
                    <td style="text-align: right; padding: 8px; font-size: 12px; font-weight: bold; background-color: #f8f9fa !important; border-top: 2px solid #ddd;">
                        @php $service_net_total = $service->total - ($service->discount ?? 0); @endphp
                        @if (in_array($service->status->slug, ['CANCELLED', 'PARTIAL']))
                        <s style="color: #dc3545;">R$ {{ number_format($service_net_total, 2, ',', '.') }}</s>
                        @else
                        <span style="color: #28a745;">R$ {{ number_format($service->total, 2, ',', '.') }}</span>
                        @endif
                    </td>
                </tr>
            </tfoot>
        </table>
        @else
        <p style="font-size: 12px; color: #666;">Nenhum item vinculado a este serviço.</p>
        @endif
    </div>
</div>
@endsection

@push('styles')
<style>
  @page {
    size: portrait;
    margin: 5mm;
  }

  body {
    font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
    color: #333;
    line-height: 1.4;
  }

  .report-container {
    width: 100%;
    margin: 0 auto;
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  .header-content td {
    padding: 0;
  }

  .company-info h2,
  .report-title h1 {
    margin-top: 0;
    margin-bottom: 5px;
  }

  .company-details div,
  .report-title div {
    margin-bottom: 3px;
  }

  .company-details span,
  .report-title span {
    display: inline-block;
  }

  hr {
    border: none;
    border-top: 1px solid #eee;
    margin: 5px 0;
  }

  .section {
    margin-bottom: 5px;
  }

  .section h3 {
    border-bottom: 1px solid #eee;
    padding-bottom: 5px;
    margin-bottom: 5px;
  }

  .card-like {
    padding: 10px;
    margin-bottom: 5px;
    background-color: #fdfdfd;
  }

  .card-like h4 {
    margin-top: 0;
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #f0f0f0;
  }

  @media print {
    * {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }

    body {
      -webkit-print-color-adjust: exact;
    }

    .report-container {
      box-shadow: none;
      border: none;
    }
  }
</style>
@endpush
