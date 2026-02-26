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
            {{--  --}}
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

            <!-- Status e Ações -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent p-4">
                    <h6 class="mb-0">
                        <i class="bi bi-info-circle me-2"></i>Status e Ações
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if ($invoice->due_date)
                        @if ($invoice->status->value === 'pending' || $invoice->status->value === 'overdue')
                            @if ($invoice->due_date < now() || $invoice->status->value === 'overdue')
                                <div class="alert alert-danger py-2 mb-4">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    Vencida há {{ $invoice->due_date->diffInDays(now()) }} dias
                                </div>
                            @else
                                <div class="alert alert-warning py-2 mb-4">
                                    <i class="bi bi-clock me-2"></i>
                                    Vence em {{ $invoice->due_date->diffInDays(now()) }} dias
                                </div>
                            @endif
                        @elseif($invoice->status->value === 'paid')
                            <div class="alert alert-success py-2 mb-4">
                                <i class="bi bi-check-circle me-2"></i>
                                Paga em {{ $invoice->updated_at->format('d/m/Y') }}
                            </div>
                        @elseif($invoice->status->value === 'cancelled')
                            <div class="alert alert-secondary py-2 mb-4">
                                <i class="bi bi-x-circle me-2"></i>
                                Fatura Cancelada em {{ $invoice->updated_at->format('d/m/Y') }}
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

                        <x-ui.button href="{{ route('provider.invoices.print', $invoice) }}" target="_blank" variant="primary" feature="invoices">
                            <i class="bi bi-printer me-2"></i>Imprimir Fatura
                        </x-ui.button>

                        @if ($invoice->status->value === 'pending')
                            <x-ui.button href="{{ route('provider.invoices.edit', $invoice->code) }}" variant="outline-secondary" feature="invoices">
                                <i class="bi bi-pencil me-2"></i>Editar Fatura
                            </x-ui.button>
                        @endif

                        @if ($invoice->status->value !== 'paid')
                            <x-ui.button type="button" variant="link" class="text-danger text-decoration-none p-0 mt-2 small" onclick="deleteInvoice()" feature="invoices">
                                <i class="bi bi-trash me-1"></i>Excluir Fatura
                            </x-ui.button>
                        @endif
                    </div>

                    @if ($invoice->status->value !== 'paid' && $invoice->status->value !== 'cancelled')
                        <hr class="my-4">
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
                        @if ($invoice->updated_at != $invoice->created_at)
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

    {{-- Botão Voltar (Estilo Legado) --}}
    <div class="mt-4">
        <a href="{{ route('provider.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Voltar para Faturas
        </a>
    </div>
    </div>

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

    <!-- Share Invoice Modal -->
    <div class="modal fade" id="shareInvoiceModal" tabindex="-1" aria-labelledby="shareInvoiceModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shareInvoiceModalLabel">Compartilhar Fatura</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Link Público</label>
                        <div class="input-group">
                            <input type="text" id="publicShareLink" class="form-control" value="{{ $invoice->getPublicUrl() }}" readonly>
                            <button class="btn btn-outline-primary" type="button" onclick="copyShareLink()">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <small class="text-muted">Qualquer pessoa com este link poderá visualizar a fatura.</small>
                    </div>

                    @if($invoice->status === 'pending')
                    <hr>
                    <div class="mb-3">
                        <h6 class="mb-3">Enviar por E-mail</h6>
                        <form action="{{ route('provider.invoices.share', $invoice->code) }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="recipient_email" class="form-label">E-mail do Destinatário</label>
                                <input type="email" name="recipient_email" class="form-control" id="recipient_email" value="{{ $invoice->customer->email ?? '' }}" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Mensagem (Opcional)</label>
                                <textarea name="message" class="form-control" id="message" rows="3">Olá, segue o link para visualização e pagamento da sua fatura.</textarea>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-send me-2"></i>Enviar Link por E-mail
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copySidebarLink() {
            const copyText = document.getElementById("sidebarPublicLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            // Feedback visual
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        }

        function copyShareLink() {
            const copyText = document.getElementById("publicShareLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value);

            // Feedback visual simples
            const btn = event.currentTarget;
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="bi bi-check2"></i>';
            setTimeout(() => {
                btn.innerHTML = originalHtml;
            }, 2000);
        }

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
