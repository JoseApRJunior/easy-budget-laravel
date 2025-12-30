@extends('layouts.pdf_base')

@section('title')Relatório de Clientes @endsection

@section('content')
<!-- Botão de Impressão -->
<div class="print-button-container" style="text-align: right; margin-bottom: 20px;">
    <button onclick="window.print()" class="btn-print" style="background: #007bff; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px;">
        🖨️ Imprimir
    </button>
</div>

<div class="report-container">
    {{-- Cabeçalho do Relatório --}}
    <div class="report-header">
        <table class="header-content" style="width: 100%;">
            <tr>
                <!-- Coluna da Esquerda - Informações da Empresa -->
                <td style="width: 50%; vertical-align: top;">
                    <div class="company-info">
                        @if($provider && $provider->commonData)
                            <h2 style="font-size: 24px; margin-bottom: 15px; color: #2c3e50;">
                                {{ $provider->commonData->company_name ?: ($provider->commonData->first_name . ' ' . $provider->commonData->last_name) }}
                            </h2>
                            <div class="company-details">
                                @if($provider->address)
                                    <div style="margin-bottom: 5px; font-size: 11px;">
                                        <span style="margin-right: 8px;">➤</span>
                                        <span>
                                            {{ $provider->address->address }}, {{ $provider->address->address_number }}
                                            @if($provider->address->complement) - {{ $provider->address->complement }} @endif
                                            <br>
                                            {{ $provider->address->neighborhood }} - {{ $provider->address->city }}/{{ $provider->address->state }}
                                        </span>
                                    </div>
                                @endif
                                <div style="margin-bottom: 5px; font-size: 11px;">
                                    <span style="margin-right: 8px;">⚑</span>
                                    <span>
                                        @if($provider->commonData->cnpj)
                                            CNPJ: {{ \App\Helpers\DocumentHelper::formatCnpj($provider->commonData->cnpj) }}
                                        @elseif($provider->commonData->cpf)
                                            CPF: {{ \App\Helpers\DocumentHelper::formatCpf($provider->commonData->cpf) }}
                                        @endif
                                    </span>
                                </div>
                                @if($provider->contact)
                                    <div style="margin-bottom: 5px; font-size: 11px;">
                                        <span style="margin-right: 8px;">☎</span>
                                        <span>{{ $provider->contact->phone_personal ?: $provider->contact->phone_business }}</span>
                                    </div>
                                    <div style="margin-bottom: 5px; font-size: 11px;">
                                        <span style="margin-right: 8px;">✉</span>
                                        <span>{{ $provider->contact->email_personal ?: $provider->contact->email_business }}</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <h2 style="font-size: 24px; margin-bottom: 15px; color: #2c3e50;">
                                {{ auth()->user()->name }}
                            </h2>
                            <div class="company-details">
                                <div style="margin-bottom: 5px; font-size: 11px;">
                                    <span style="margin-right: 8px;">✉</span>
                                    <span>{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </td>
                <!-- Coluna da Direita - Informações do Relatório -->
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <div class="report-title">
                        <h1 style="font-size: 28px; margin-bottom: 15px; color: #2c3e50;">
                            Relatório de Clientes
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
                                <span>{{ count($customers) }}</span>
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
                    <th style="width: 25%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Nome</th>
                    <th style="width: 25%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Email</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Telefone</th>
                    <th style="width: 20%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        CPF/CNPJ</th>
                    <th style="width: 15%; text-align: left; background-color: #f8f9fa !important; padding: 8px; font-size: 12px; color: #333; border-bottom: 2px solid #dee2e6; font-weight: 600;">
                        Data</th>
                </tr>
            </thead>

            <tbody>
                @foreach($customers as $customer)
                @php
                    $commonData = $customer->commonData;
                    $contact = $customer->contact;
                @endphp
                <tr>
                    <td style="width: 25%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ $commonData?->company_name ?: ($commonData?->first_name . ' ' . $commonData?->last_name) ?: 'Nome não informado' }}
                    </td>
                    <td style="width: 25%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ $contact?->email_personal ?: $contact?->email_business ?: 'Não informado' }}
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6; white-space: nowrap;">
                        {{ format_phone($contact?->phone_personal ?: $contact?->phone_business) ?: 'Não informado' }}
                    </td>
                    <td style="width: 20%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6; white-space: nowrap;">
                        @if($commonData?->cpf)
                            {{ format_cpf($commonData->cpf) }}
                        @elseif($commonData?->cnpj)
                            {{ format_cnpj($commonData->cnpj) }}
                        @else
                            Não informado
                        @endif
                    </td>
                    <td style="width: 15%; text-align: left; padding: 8px; font-size: 11px; border-bottom: 1px solid #dee2e6;">
                        {{ $customer->created_at->format('d/m/Y') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <hr>

    {{-- Resumo Estatístico --}}
    <div class="statistics" style="margin-top: 30px; background-color: #f8f9fa !important; padding: 15px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 100%; text-align: center;">
                    <span style="display: block; font-size: 11px; color: #666; margin-bottom: 5px;">Total de Clientes:</span>
                    <span style="font-size: 14px; font-weight: bold; color: #333;">{{ count($customers) }}</span>
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

    .btn-print:hover {
        background: #0056b3 !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    /* Otimizações para Impressão */
    @media print {
        .print-button-container {
            display: none !important;
        }

        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>
@endsection
