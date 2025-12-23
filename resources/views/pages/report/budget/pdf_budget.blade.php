@extends('layouts.pdf_base')

@section('title')Relatório de Orçamentos@endsection

@section('content')
<div class="report-container">
    {{-- Cabeçalho do Relatório --}}
    <div class="report-header">
        <table class="header-content" style="width: 100%;">
            <tr>
                <!-- Coluna da Esquerda - Informações da Empresa -->
                <td style="width: 50%; vertical-align: top;">
                    <div class="company-info">
                        <h2 style="font-size: 24px; margin-bottom: 15px; color: #2c3e50;">
                            {{ auth()->user()->company_name }}
                        </h2>
                        <div class="company-details">
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 8px;">➤</span>
                                <span>{{ auth()->user()->address }}</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 8px;">⚑</span>
                                <span>@if(auth()->user()->cnpj)CNPJ: {{ auth()->user()->cnpj }}@else CPF: {{ auth()->user()->cpf }}@endif</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 8px;">☎</span>
                                <span>@if(auth()->user()->phone_business){{ auth()->user()->phone_business }}@else{{ auth()->user()->phone }}@endif</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="margin-right: 8px;">✉</span>
                                <span>@if(auth()->user()->email_business){{ auth()->user()->email_business }}@else{{ auth()->user()->email }}@endif</span>
                            </div>
                        </div>
                    </div>
                </td>
                <!-- Coluna da Direita - Informações do Relatório -->
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <div class="report-title">
                        <h1 style="font-size: 28px; margin-bottom: 15px; color: #2c3e50;">
                            Relatório de Orçamentos
                        </h1>
                        <div style="color: #666;">
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="font-weight: 600; margin-right: 5px;">Gerado em:</span>
                                <span>{{ now()->format('d/m/Y H:i:s') }}</span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="font-weight: 600; margin-right: 5px;">Período:</span>
                                <span>
                                    @if(isset($filters['start_date']) && isset($filters['end_date']))
                                    {{ \Carbon\Carbon::parse($filters['start_date'])->format('d/m/Y') }} até {{ \Carbon\Carbon::parse($filters['end_date'])->format('d/m/Y') }}
                                    @else
                                    Todos os períodos
                                    @endif
                                </span>
                            </div>
                            <div style="margin-bottom: 5px; font-size: 11px;">
                                <span style="font-weight: 600; margin-right: 5px;">Total de Registros:</span>
                                <span>{{ count($budgets) }}</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <hr>
    {{-- Tabela de Resultados --}}
    <div class="results-table">
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="width: 10%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Nº Orçamento</th>
                    <th style="width: 30%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Cliente</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Data Criação</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Data Vencimento</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Valor Total</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Status</th>
                </tr>
            </thead>

            <tbody>
                @foreach($budgets as $budget)
                <tr>
                    <td style="width: 10%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ $budget->code }}</td>
                    <td style="width: 30%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ $budget->customer_name }}
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ \Carbon\Carbon::parse($budget->created_at)->format('d/m/Y') }}
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ \Carbon\Carbon::parse($budget->due_date)->format('d/m/Y') }}
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">R$
                        {{ number_format($budget->total, 2, ',', '.') }}
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        <span style="color: {{ $budget->color }} !important; border-bottom: 2px solid {{ $budget->color }} !important;">
                            {{ $budget->name }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        <strong>Total:</strong>
                    </td>
                    <td style="text-align: right; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        <strong>R$ {{ number_format($totals['sum'], 2, ',', '.') }}</strong>
                    </td>
                    <td style="text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;"></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <hr>

    {{-- Resumo Estatístico --}}
    <div class="statistics" style="margin-top: 30px; background-color: #f8f9fa !important; padding: 15px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 33.33%; text-align: center;">
                    <span style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">Total de Orçamentos:</span>
                    <span style="font-size: 14px; font-weight: bold; color: #333;">{{ $totals['count'] }}</span>
                </td>
                <td style="width: 33.33%; text-align: center;">
                    <span style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">Valor Total:</span>
                    <span style="font-size: 14px; font-weight: bold; color: #333;">R$ {{ number_format($totals['sum'], 2, ',', '.') }}</span>
                </td>
                <td style="width: 33.33%; text-align: center;">
                    <span style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">Média por Orçamento:</span>
                    <span style="font-size: 14px; font-weight: bold; color: #333;">R$ {{ number_format($totals['avg'], 2, ',', '.') }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection

@section('styles')
<style>
    @page {
        size: portrait;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    .text-right {
        text-align: right !important;
    }

    /* Otimizações para Impressão */
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
@endsection
