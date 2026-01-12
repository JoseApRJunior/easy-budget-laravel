@extends('layouts.app')

@section('title', 'Detalhes da Fatura')
@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Detalhes da Fatura"
            icon="receipt"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Faturas' => route('provider.invoices.dashboard'),
                $invoice->code => '#'
            ]">
        <div class="d-flex gap-2">
            <x-ui.button type="link" :href="route('provider.invoices.print', $invoice)" variant="outline-secondary" icon="printer" label="Imprimir" target="_blank" />
        </div>
    </x-layout.page-header>

    <div class="row g-4">
        <!-- Informações Principais -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <!-- Cabeçalho da Fatura -->
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h2 class="h4 mb-1">Fatura #{{ $invoice->code }}</h2>
                            <p class="text-muted mb-0">
                                Gerada em {{ $invoice->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>
                        <x-ui.status-description :item="$invoice" />
                    </div>

                    <!-- Dados do Cliente e Empresa -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-muted">Cliente</h5>
                            <div class="bg-light p-3 rounded">
                                <strong>{{ $invoice->customer->name ?? 'N/A' }}</strong><br>
                                @if ($invoice->customer->email)
                                    <i class="bi bi-envelope me-1"></i>{{ $invoice->customer->email }}<br>
                                @endif
                                @if ($invoice->customer->phone)
                                    <i class="bi bi-telephone me-1"></i>{{ $invoice->customer->phone }}
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-muted">Serviço</h5>
                            <div class="bg-light p-3 rounded">
                                <strong>{{ $invoice->service->code ?? 'N/A' }}</strong><br>
                                <small class="text-muted">
                                    {{ Str::limit($invoice->service->description ?? '', 50) }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Datas Importantes -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h6 class="text-muted">Data de Emissão</h6>
                            <p class="mb-0">{{ $invoice->issue_date?->format('d/m/Y') ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Data de Vencimento</h6>
                            <p class="mb-0">
                                {{ $invoice->due_date?->format('d/m/Y') ?? 'N/A' }}
                                @if ($invoice->due_date && $invoice->status === 'pending')
                                    @if ($invoice->due_date < now())
                                        <span class="badge bg-danger ms-2">Vencida</span>
                                    @elseif($invoice->due_date->diffInDays(now()) <= 7)
                                        <span class="badge bg-warning ms-2">
                                            Vence em {{ $invoice->due_date->diffInDays(now()) }} dias
                                        </span>
                                    @endif
                                @endif
                            </p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Valor Total</h6>
                            <p class="mb-0 fs-5 text-success fw-bold">
                                {{ \App\Helpers\CurrencyHelper::format($invoice->total) }}
                            </p>
                        </div>
                    </div>

                    <!-- Itens da Fatura -->
                    @if ($invoice->invoiceItems->count() > 0)
                        <h5 class="mb-3">Itens da Fatura</h5>
                        {{-- Desktop View --}}
                        <div class="desktop-view">
                            <div class="table-responsive">
                                <table class="modern-table table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Produto</th>
                                            <th class="text-center">Qtd</th>
                                            <th class="text-end">Valor Unit.</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($invoice->invoiceItems as $item)
                                            <tr>
                                                <td>
                                                    <strong>{{ $item->product->name ?? 'N/A' }}</strong>
                                                    @if ($item->product->description)
                                                        <br><small
                                                            class="text-muted">{{ $item->product->description }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{ $item->quantity }}</td>
                                                <td class="text-end">
                                                    {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                                                <td class="text-end fw-bold">
                                                    {{ \App\Helpers\CurrencyHelper::format($item->total) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3" class="text-end">Subtotal:</th>
                                            <th class="text-end">
                                                {{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Mobile View --}}
                        <div class="mobile-view">
                            <div class="list-group">
                                @foreach ($invoice->invoiceItems as $item)
                                    <div class="list-group-item py-3">
                                        <div class="d-flex align-items-start">
                                            <i class="bi bi-box-seam text-muted me-2 mt-1"></i>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold mb-2">{{ $item->product->name ?? 'N/A' }}</div>
                                                <div class="small text-muted mb-2">
                                                    <span class="me-3"><strong>Qtd:</strong> {{ $item->quantity }}</span>
                                                    <span><strong>Unit:</strong>
                                                        {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</span>
                                                </div>
                                                <div class="text-success fw-semibold">Total:
                                                    {{ \App\Helpers\CurrencyHelper::format($item->total) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="list-group-item bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>Subtotal:</strong>
                                        <strong class="text-success">
                                            {{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                            <br>Nenhum item encontrado nesta fatura
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar com Informações Extras -->
        <div class="col-md-4">
            <!-- Resumo Financeiro -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-calculator me-2"></i>Resumo Financeiro
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoice->subtotal) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Desconto:</span>
                        <span>{{ \App\Helpers\CurrencyHelper::format($invoice->discount) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span class="text-success">{{ \App\Helpers\CurrencyHelper::format($invoice->total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Status Detalhado -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Status Detalhado
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <x-ui.status-badge :item="$invoice" class="w-100 py-2 fs-6 mb-1" />
                        <x-ui.status-description :item="$invoice" class="mt-1" />
                    </div>

                    @if ($invoice->due_date)
                        @if ($invoice->status === 'pending')
                            @if ($invoice->due_date < now())
                                <div class="alert alert-danger">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Fatura vencida há {{ $invoice->due_date->diffInDays(now()) }} dias
                                </div>
                            @else
                                <div class="alert alert-warning">
                                    <i class="bi bi-clock me-2"></i>
                                    Vence em {{ $invoice->due_date->diffInDays(now()) }} dias
                                </div>
                            @endif
                        @elseif($invoice->status === 'paid')
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle me-2"></i>
                                Fatura paga com sucesso
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Histórico de Ações -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="bi bi-clock-history me-2"></i>Histórico
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <h6 class="timeline-title">Fatura Criada</h6>
                                <p class="timeline-description text-muted">
                                    {{ $invoice->created_at->format('d/m/Y H:i') }}
                                </p>
                            </div>
                        </div>
                        @if ($invoice->updated_at->ne($invoice->created_at))
                            <div class="timeline-item">
                                <div class="timeline-marker bg-info"></div>
                                <div class="timeline-content">
                                    <h6 class="timeline-title">Última Atualização</h6>
                                    <p class="timeline-description text-muted">
                                        {{ $invoice->updated_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Botões de Ação (Footer) --}}
    <div class="d-flex justify-content-between align-items-center mt-4">
        <div class="d-flex gap-2">
            <a href="{{ url()->previous(route('provider.invoices.index')) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Voltar
            </a>
        </div>
        <small class="text-muted d-none d-md-block">
            Última atualização: {{ $invoice->updated_at?->format('d/m/Y H:i') }}
        </small>
        <div class="d-flex gap-2">
            @if ($invoice->status === 'pending')
                <a href="{{ route('provider.invoices.edit', $invoice->code) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-fill me-2"></i>Editar
                </a>
                <button type="button" class="btn btn-success" onclick="changeStatus('paid')">
                    <i class="bi bi-check-circle-fill me-2"></i>Marcar como Paga
                </button>
                <button type="button" class="btn btn-danger" onclick="changeStatus('cancelled')">
                    <i class="bi bi-x-circle-fill me-2"></i>Cancelar
                </button>
            @endif
            <button type="button" class="btn btn-outline-danger" onclick="deleteInvoice()">
                <i class="bi bi-trash-fill me-2"></i>Excluir
            </button>
        </div>
    </div>
    </div>



    <script>
        let newStatus = '';

        // Função para alterar status
        function changeStatus(status) {
            newStatus = status;
            const statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        }

        // Confirmação de mudança de status
        document.getElementById('confirmStatusChange').addEventListener('click', function() {
            if (!newStatus) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('provider.invoices.change_status', $invoice->code) }}';

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'PUT';

            const statusField = document.createElement('input');
            statusField.type = 'hidden';
            statusField.name = 'status';
            statusField.value = newStatus;

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            form.appendChild(statusField);

            document.body.appendChild(form);
            form.submit();
        });

        // Função para excluir fatura
        function deleteInvoice() {
            if (confirm('Tem certeza que deseja excluir esta fatura? Esta ação não pode ser desfeita.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('provider.invoices.destroy', $invoice->code) }}';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';

                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';

                form.appendChild(csrfToken);
                form.appendChild(methodField);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
@endsection
