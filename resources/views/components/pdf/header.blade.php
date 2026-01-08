@props([
    'provider' => null,
    'tenant' => null,
    'title' => '',
    'code' => '',
    'date' => null,
    'dueDate' => null,
    'status' => null,
])

<div class="mb-3">
    <table class="table table-borderless mb-0" style="width: 100%;">
        <tr>
            <td width="60%" class="p-0">
                @php
                    $displayProvider = $provider;
                    $displayTenant = $tenant;
                @endphp

                @if($displayProvider && $displayProvider->commonData)
                    <h5 class="text-dark fw-bold mb-1" style="font-size: 14px;">
                        {{ $displayProvider->commonData->company_name ?: ($displayProvider->commonData->first_name . ' ' . $displayProvider->commonData->last_name) }}
                    </h5>
                    <div class="text-secondary" style="line-height: 1.2;">
                        @if($displayProvider->address)
                            <p class="mb-0">
                                {{ $displayProvider->address->address }}, {{ $displayProvider->address->address_number }}
                                | {{ $displayProvider->address->neighborhood }}
                            </p>
                            <p class="mb-0">
                                {{ $displayProvider->address->city }}/{{ $displayProvider->address->state }} - CEP: {{ $displayProvider->address->cep }}
                            </p>
                        @endif
                        <p class="mb-0">
                            @if($displayProvider->commonData->cnpj)
                                CNPJ: {{ \App\Helpers\DocumentHelper::formatCnpj($displayProvider->commonData->cnpj) }}
                            @elseif($displayProvider->commonData->cpf)
                                CPF: {{ \App\Helpers\DocumentHelper::formatCpf($displayProvider->commonData->cpf) }}
                            @endif
                        </p>
                        @if($displayProvider->contact)
                            <p class="mb-0">
                                Tel: {{ $displayProvider->contact->phone_personal ?: $displayProvider->contact->phone_business }}
                                | Email: {{ $displayProvider->contact->email_personal ?: $displayProvider->contact->email_business }}
                            </p>
                        @endif
                    </div>
                @else
                    <h5 class="text-dark fw-bold mb-1" style="font-size: 14px;">{{ $displayTenant->name ?? 'Empresa não identificada' }}</h5>
                    <div class="text-secondary">
                        <p class="mb-0">Documento: {{ $displayTenant->document ?? '---' }}</p>
                        <p class="mb-0">Email: {{ $displayTenant->email ?? '---' }}</p>
                    </div>
                @endif
            </td>
            <td width="40%" class="p-0 align-top">
                <table align="right" style="width: auto; border-collapse: collapse;">
                    <tr>
                        <td class="p-0" style="text-align: left;">
                            @if($title)
                                <h5 class="text-dark fw-bold mb-1" style="font-size: 14px;">
                                    {{ $title }}{{ $code ? ': #' . $code : '' }}
                                </h5>
                            @endif
                            <div class="text-secondary small">
                                @if($date)
                                    <p class="mb-0">Emissão: {{ $date instanceof \DateTime ? $date->format('d/m/Y') : $date }}</p>
                                @endif
                                @if($dueDate)
                                    <p class="mb-0">Validade: {{ $dueDate instanceof \DateTime ? $dueDate->format('d/m/Y') : $dueDate }}</p>
                                @endif
                                @if($status)
                                    <p class="mb-0 fw-bold text-uppercase mt-1" style="color: #000;">Status: {{ $status }}</p>
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
