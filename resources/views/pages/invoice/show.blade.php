@extends('layouts.app')

@section('title', 'Detalhes da Fatura')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Detalhes da Fatura"
        icon="receipt"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Faturas' => route('provider.invoices.dashboard'),
            $invoice->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize e gerencie todas as informações desta fatura</p>
    </x-layout.page-header>

    {{-- Informações da Fatura (Padrão Budget) --}}
    <x-resource.resource-header-card>
        {{-- Primeira Linha: Informações Principais --}}
        <x-layout.grid-col size="col-md-4">
            <x-resource.resource-info
                title="Código da Fatura"
                :subtitle="'#' . $invoice->code"
                icon="hash" />
        </x-layout.grid-col>

        <x-layout.grid-col size="col-md-4">
            <x-resource.resource-info
                title="Status Atual"
                icon="info-circle">
                <x-slot:subtitle>
                    <x-ui.status-description :item="$invoice" statusField="status" :useColor="false" class="text-dark fw-medium" />
                </x-slot:subtitle>
            </x-resource.resource-info>
        </x-layout.grid-col>

        <x-layout.grid-col size="col-md-4">
            <x-resource.resource-info
                title="Valor Total"
                :subtitle="\App\Helpers\CurrencyHelper::format($invoice->total, 2, true)"
                icon="cash-stack"
                class="text-success fw-bold" />
        </x-layout.grid-col>

        <x-resource.resource-header-divider />

        {{-- Segunda Linha: Dados do Cliente --}}
        <x-resource.resource-header-section title="Dados do Cliente" icon="person">
            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="Nome do Cliente"
                    :subtitle="$invoice->customer->name ?? 'N/A'"
                    icon="person-badge"
                    class="small" />
            </x-layout.grid-col>

            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="E-mail"
                    :subtitle="$invoice->customer->email ?? '-'"
                    icon="envelope"
                    class="small" />
            </x-layout.grid-col>

            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="Telefone"
                    :subtitle="\App\Helpers\MaskHelper::formatPhone($invoice->customer_phone_business ?? $invoice->customer->phone ?? '') ?: '-'"
                    icon="telephone"
                    class="small" />
            </x-layout.grid-col>
        </x-resource.resource-header-section>

        <x-resource.resource-header-divider />

        {{-- Terceira Linha: Dados do Serviço e Datas --}}
        <x-resource.resource-header-section title="Serviço e Prazos" icon="gear">
            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="Serviço Relacionado"
                    :subtitle="$invoice->service->code ?? 'N/A'"
                    icon="tools"
                    :href="$invoice->service ? route('provider.services.show', $invoice->service->code) : null"
                    class="small fw-bold" />
            </x-layout.grid-col>

            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="Data de Emissão"
                    :subtitle="$invoice->issue_date?->format('d/m/Y') ?? $invoice->created_at->format('d/m/Y')"
                    icon="calendar-check"
                    class="small" />
            </x-layout.grid-col>

            <x-layout.grid-col size="col-md-4">
                <x-resource.resource-info
                    title="Data de Vencimento"
                    :subtitle="$invoice->due_date?->format('d/m/Y') ?? 'N/A'"
                    icon="calendar-x"
                    class="small {{ $invoice->due_date && $invoice->due_date->isPast() && $invoice->status->value !== 'paid' ? 'text-danger fw-bold' : '' }}" />
            </x-layout.grid-col>
        </x-resource.resource-header-section>

        <x-resource.resource-header-divider />

        {{-- Quarta Linha: Resumo Financeiro --}}
        <x-resource.resource-header-section title="Resumo Financeiro" icon="calculator">
            <x-layout.grid-col size="col-md-8">
                <div class="row g-3">
                    <x-layout.grid-col size="col-md-6">
                        <x-resource.resource-info
                            title="Criada em"
                            :subtitle="$invoice->created_at->format('d/m/Y H:i')"
                            icon="calendar-plus"
                            class="small" />
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-6">
                        <x-resource.resource-info
                            title="Última Atualização"
                            :subtitle="$invoice->updated_at?->format('d/m/Y H:i')"
                            icon="clock-history"
                            class="small" />
                    </x-layout.grid-col>
                </div>
            </x-layout.grid-col>

            <x-layout.grid-col size="col-md-4">
                <x-ui.box background="#f8fafc" border="border border-light-subtle">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small fw-medium">Subtotal:</span>
                        <span class="text-dark fw-semibold small">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal, 2, true) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small fw-medium">Desconto:</span>
                        <span class="text-danger fw-semibold small">- {{ \App\Helpers\CurrencyHelper::format($invoice->discount, 2, true) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top border-light-subtle">
                        <span class="fw-bold text-dark">Total:</span>
                        <span class="fw-bold text-success fs-5">{{ \App\Helpers\CurrencyHelper::format($invoice->total, 2, true) }}</span>
                    </div>
                </x-ui.box>
            </x-layout.grid-col>
        </x-resource.resource-header-section>
    </x-resource.resource-header-card>

    <x-layout.grid-row>
        <x-layout.grid-col size="col-lg-8">
            {{-- Itens da Fatura --}}
            @if ($invoice->invoiceItems && $invoice->invoiceItems->count() > 0)
                <x-resource.resource-list-card
                    title="Itens da Fatura"
                    icon="list-ul"
                    :total="$invoice->invoiceItems->count()"
                    class="mb-4">

                    <x-slot:desktop>
                        <x-resource.resource-table>
                            <x-slot:thead>
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Produto/Serviço</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-center">Qtd</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-end">Valor Unitário</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-end">Total</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:thead>
                            <x-slot:tbody>
                                @foreach ($invoice->invoiceItems as $item)
                                    <x-resource.table-row>
                                        <x-resource.table-cell>
                                            <x-resource.resource-info
                                                :title="$item->product?->name ?? 'N/A'"
                                                :subtitle="$item->product?->description ?? ''"
                                                icon="box-seam"
                                                titleClass="fw-bold"
                                                subtitleClass="text-muted small" />
                                        </x-resource.table-cell>
                                        <x-resource.table-cell class="text-center">{{ $item->quantity }}</x-resource.table-cell>
                                        <x-resource.table-cell class="text-end">{{ \App\Helpers\CurrencyHelper::format($item->unit_value, 2, true) }}</x-resource.table-cell>
                                        <x-resource.table-cell class="text-end fw-bold">{{ \App\Helpers\CurrencyHelper::format($item->total, 2, true) }}</x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforeach
                            </x-slot:tbody>
                            <x-slot:tfoot>
                                <x-resource.table-row class="table-light">
                                    <x-resource.table-cell colspan="3" class="text-end fw-bold">Subtotal:</x-resource.table-cell>
                                    <x-resource.table-cell class="text-end fw-bold text-success">{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal, 2, true) }}</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot:tfoot>
                        </x-resource.resource-table>
                    </x-slot:desktop>

                    <x-slot:mobile>
                        @foreach ($invoice->invoiceItems as $item)
                            <x-resource.resource-mobile-item>
                                <x-resource.resource-mobile-header
                                    :title="$item->product?->name ?? 'N/A'" />
                                <x-slot:description>
                                    <div class="row g-2">
                                        <x-resource.resource-mobile-field
                                            col="col-6"
                                            label="Qtd"
                                            :value="$item->quantity" />
                                        <x-resource.resource-mobile-field
                                            col="col-6"
                                            label="Unitário"
                                            :value="\App\Helpers\CurrencyHelper::format($item->unit_value, 2, true)" />
                                        <x-resource.resource-mobile-field
                                            col="col-12"
                                            label="Total"
                                            :value="\App\Helpers\CurrencyHelper::format($item->total, 2, true)"
                                            valueClass="text-success fw-bold" />
                                    </div>
                                </x-slot:description>
                            </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot:mobile>
                </x-resource.resource-list-card>
            @else
                <x-ui.box class="text-center py-5">
                    <i class="bi bi-inbox text-muted display-4 mb-3"></i>
                    <p class="text-muted">Nenhum item encontrado nesta fatura</p>
                </x-ui.box>
            @endif
        </x-layout.grid-col>

        <x-layout.grid-col size="col-lg-4">
            {{-- Status e Ações --}}
            <x-resource.resource-list-card title="Status e Ações" icon="info-circle" class="mb-4">
                <div class="p-3">
                    @if ($invoice->due_date)
                        @if ($invoice->status->value === 'pending' || $invoice->status->value === 'overdue')
                            @if ($invoice->due_date < now() || $invoice->status->value === 'overdue')
                                <div class="alert alert-danger py-2 mb-4 d-flex align-items-center">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <span class="small">Vencida há {{ $invoice->due_date->diffInDays(now()) }} dias</span>
                                </div>
                            @else
                                <div class="alert alert-warning py-2 mb-4 d-flex align-items-center">
                                    <i class="bi bi-clock me-2"></i>
                                    <span class="small">Vence em {{ $invoice->due_date->diffInDays(now()) }} dias</span>
                                </div>
                            @endif
                        @elseif($invoice->status->value === 'paid')
                            <div class="alert alert-success py-2 mb-4 d-flex align-items-center">
                                <i class="bi bi-check-circle me-2"></i>
                                <span class="small">Paga em {{ $invoice->updated_at->format('d/m/Y') }}</span>
                            </div>
                        @elseif($invoice->status->value === 'cancelled')
                            <div class="alert alert-info py-2 mb-4 d-flex align-items-center">
                                <i class="bi bi-x-circle me-2"></i>
                                <span class="small">Fatura Cancelada</span>
                            </div>
                        @endif
                    @endif

                    <div class="d-grid gap-2">
                        @if ($invoice->status->value === 'pending')
                            <x-ui.button type="button" variant="success" onclick="changeStatus('paid')" feature="invoices">
                                <i class="bi bi-check-circle me-2"></i>Marcar como Paga
                            </x-ui.button>
                            <x-ui.button type="button" variant="outline-danger" onclick="changeStatus('cancelled')" feature="invoices">
                                <i class="bi bi-x-circle me-2"></i>Cancelar Fatura
                            </x-ui.button>
                        @endif
                    </div>

                    @if ($invoice->status->value !== 'paid' && $invoice->status->value !== 'cancelled')
                        <x-resource.resource-header-divider class="my-4" />
                        <div class="bg-light p-3 rounded">
                            <h6 class="small fw-bold mb-2">
                                <i class="bi bi-link-45deg"></i> Link de Pagamento
                            </h6>
                            <p class="small text-muted mb-3">
                                Envie este link para seu cliente visualizar a fatura.
                            </p>
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" id="sidebarPublicLink" value="{{ $invoice->getPublicUrl() }}" readonly>
                                <button class="btn btn-outline-secondary" type="button" onclick="copySidebarLink()">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </x-resource.resource-list-card>
        </x-layout.grid-col>
    </x-layout.grid-row>

    {{-- Botões de Ação (Footer) --}}
    <x-layout.actions-bar alignment="between" class="mt-4" mb="0">
        <x-ui.back-button index-route="provider.invoices.index" class="w-100 w-md-auto px-md-4" feature="invoices" />

        <x-ui.button-group gap="2" class="w-100 w-md-auto">
            @if ($invoice->status->value === 'pending')
                <x-ui.button
                    type="link"
                    href="{{ route('provider.invoices.edit', $invoice->code) }}"
                    variant="primary"
                    icon="pencil-fill"
                    label="Editar"
                    feature="invoices" />
            @endif

            @if ($invoice->status->value !== 'paid')
                <x-ui.button
                    type="button"
                    variant="outline-danger"
                    onclick="deleteInvoice()"
                    icon="trash-fill"
                    label="Excluir"
                    feature="invoices" />
            @endif

            <x-ui.button
                type="link"
                href="{{ route('provider.invoices.print', $invoice) }}"
                target="_blank"
                variant="outline-secondary"
                icon="printer"
                label="Imprimir"
                feature="invoices" />
        </x-ui.button-group>
    </x-layout.actions-bar>
</x-layout.page-container>

{{-- Modais e Scripts (Mantidos) --}}
<!-- Status Confirmation Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Confirmar Mudança de Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Tem certeza que deseja alterar o status desta fatura?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirmar</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentStatus = '';

    function changeStatus(status) {
        currentStatus = status;
        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }

    document.getElementById('confirmStatusChange').addEventListener('click', function() {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = "{{ route('provider.invoices.change_status', $invoice->code) }}";

        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = "{{ csrf_token() }}";
        form.appendChild(csrfToken);

        const methodField = document.createElement('input');
        methodField.type = 'hidden';
        methodField.name = '_method';
        methodField.value = 'POST';
        form.appendChild(methodField);

        const statusField = document.createElement('input');
        statusField.type = 'hidden';
        statusField.name = 'status';
        statusField.value = currentStatus;
        form.appendChild(statusField);

        document.body.appendChild(form);
        form.submit();
    });

    function deleteInvoice() {
        if (confirm('Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('provider.invoices.destroy', $invoice->code) }}";

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = "{{ csrf_token() }}";
            form.appendChild(csrfToken);

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';
            form.appendChild(methodField);

            document.body.appendChild(form);
            form.submit();
        }
    }

    function copySidebarLink() {
        const copyText = document.getElementById("sidebarPublicLink");
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(copyText.value);

        const btn = event.currentTarget;
        const icon = btn.querySelector('i');
        icon.classList.replace('bi-clipboard', 'bi-check2');
        btn.classList.replace('btn-outline-secondary', 'btn-success');

        setTimeout(() => {
            icon.classList.replace('bi-check2', 'bi-clipboard');
            btn.classList.replace('btn-success', 'btn-outline-secondary');
        }, 2000);
    }
</script>
@endpush
@endsection
