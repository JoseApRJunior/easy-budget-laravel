@props([
    'provider' => null,
    'tenant' => null,
    'title' => '',
    'code' => '',
    'date' => null,
    'dueDate' => null,
    'status' => null,
])

@php
    $colors = config('pdf_theme.colors');
@endphp

<div style="margin-bottom: 20px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 60%; vertical-align: top; padding: 0;">
                @php
                    $displayProvider = $provider;
                    $displayTenant = $tenant;
                @endphp

                @if($displayProvider && $displayProvider->commonData)
                    <h5 style="font-size: 14px; font-weight: bold; color: {{ $colors['dark'] }}; margin: 0 0 4px 0;">
                        {{ $displayProvider->commonData->company_name ?: ($displayProvider->commonData->first_name . ' ' . $displayProvider->commonData->last_name) }}
                    </h5>
                    <div style="color: {{ $colors['secondary'] }}; line-height: 1.3; font-size: 10px;">
                        @if($displayProvider->address)
                            <p style="margin: 0;">
                                {{ $displayProvider->address->address }}, {{ $displayProvider->address->address_number }}
                                | {{ $displayProvider->address->neighborhood }}
                            </p>
                            <p style="margin: 0;">
                                {{ $displayProvider->address->city }}/{{ $displayProvider->address->state }} - CEP: {{ $displayProvider->address->cep }}
                            </p>
                        @endif
                        <p style="margin: 0;">
                            @if($displayProvider->commonData->cnpj)
                                CNPJ: {{ \App\Helpers\DocumentHelper::formatCnpj($displayProvider->commonData->cnpj) }}
                            @elseif($displayProvider->commonData->cpf)
                                CPF: {{ \App\Helpers\DocumentHelper::formatCpf($displayProvider->commonData->cpf) }}
                            @endif
                        </p>
                        @if($displayProvider->contact)
                            <p style="margin: 0;">
                                Tel: {{ $displayProvider->contact->phone_personal ?: $displayProvider->contact->phone_business }}
                                | Email: {{ $displayProvider->contact->email_personal ?: $displayProvider->contact->email_business }}
                            </p>
                        @endif
                    </div>
                @else
                    <h5 style="font-size: 14px; font-weight: bold; color: {{ $colors['dark'] }}; margin: 0 0 4px 0;">{{ $displayTenant->name ?? 'Empresa não identificada' }}</h5>
                    <div style="color: {{ $colors['secondary'] }}; line-height: 1.3; font-size: 10px;">
                        <p style="margin: 0;">Documento: {{ $displayTenant->document ?? '---' }}</p>
                        <p style="margin: 0;">Email: {{ $displayTenant->email ?? '---' }}</p>
                    </div>
                @endif
            </td>
            <td style="width: 40%; vertical-align: top; padding: 0;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="text-align: right; padding: 0;">
                            @if($title)
                                <h5 style="font-size: 14px; font-weight: bold; color: {{ $colors['primary'] }}; margin: 0 0 4px 0; text-transform: uppercase;">
                                    {{ $title }}{{ $code ? ': #' . $code : '' }}
                                </h5>
                            @endif
                            <div style="color: {{ $colors['secondary'] }}; font-size: 10px; line-height: 1.3;">
                                @if($date)
                                    <p style="margin: 0;">Emissão: {{ $date instanceof \DateTime ? $date->format('d/m/Y') : $date }}</p>
                                @endif
                                @if($dueDate)
                                    <p style="margin: 0;">Validade: {{ $dueDate instanceof \DateTime ? $dueDate->format('d/m/Y') : $dueDate }}</p>
                                @endif
                                @if($status)
                                    <p style="margin: 8px 0 0 0; font-weight: bold; text-transform: uppercase; color: {{ $colors['text'] }};">
                                        Status: <span style="color: {{ $colors['primary'] }}">{{ $status }}</span>
                                    </p>
                                @endif
                                {{ $slot }}
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>
