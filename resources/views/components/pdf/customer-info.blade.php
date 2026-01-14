@props([
    'customer' => null,
])

@if($customer)
    <div style="margin-bottom: 20px;">
        <x-pdf.section-header title="DADOS DO CLIENTE" />
        <div style="padding-left: 0;">
            <p style="font-weight: bold; margin: 0 0 4px 0; color: {{ $pdfColors['dark'] }}; font-size: 12px;">
                {{ $customer->company_name ?: $customer->full_name }}
            </p>
            <div style="color: {{ $pdfColors['secondary'] }}; line-height: 1.3; font-size: 10px;">
                <p style="margin: 0;">
                    @if($customer->cnpj)
                        CNPJ: {{ \App\Helpers\DocumentHelper::formatCnpj($customer->cnpj) }}
                    @elseif($customer->cpf)
                        CPF: {{ \App\Helpers\DocumentHelper::formatCpf($customer->cpf) }}
                    @endif

                    @if($customer->phone)
                        | Tel: {{ $customer->phone }}
                    @endif

                    @if($customer->email)
                        | Email: {{ $customer->email }}
                    @endif
                </p>

                @if($customer->address)
                    <p style="margin: 0;">
                        {{ $customer->address->address }}, {{ $customer->address->address_number }}
                        | {{ $customer->address->neighborhood }}
                    </p>
                    <p style="margin: 0;">
                        {{ $customer->address->city }}/{{ $customer->address->state }}
                        @if($customer->address->cep)
                            - CEP: {{ \App\Helpers\DocumentHelper::formatCep($customer->address->cep) }}
                        @endif
                    </p>
                @endif
            </div>
        </div>
    </div>
@endif
