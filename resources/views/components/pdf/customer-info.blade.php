@props([
    'customer' => null,
])

@if($customer)
    <div class="mb-3">
        <h6 class="text-secondary fw-bold border-bottom pb-1 mb-2" style="font-size: 10px; letter-spacing: 1px;">DADOS DO CLIENTE</h6>
        <table class="table table-borderless mb-0">
            <tr>
                <td class="p-0">
                    <p class="fw-bold mb-0 text-dark" style="font-size: 12px;">
                        {{ $customer->company_name ?: $customer->full_name }}
                    </p>
                    <div class="text-secondary" style="line-height: 1.2;">
                        <p class="mb-0">
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
                            <p class="mb-0">
                                {{ $customer->address->address }}, {{ $customer->address->address_number }}
                                | {{ $customer->address->neighborhood }}
                            </p>
                            <p class="mb-0">
                                {{ $customer->address->city }}/{{ $customer->address->state }}
                                @if($customer->address->cep)
                                    - CEP: {{ \App\Helpers\DocumentHelper::formatCep($customer->address->cep) }}
                                @endif
                            </p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endif
