@extends('layouts.app')

@section('title', 'Detalhes do Orçamento')

@section('content')
<x-page-container>
    <x-page-header
        title="Detalhes do Orçamento"
        icon="file-earmark-text"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Orçamentos' => route('provider.budgets.dashboard'),
            $budget->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize as informações completas do orçamento</p>
    </x-page-header>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- Primeira Linha: Informações Principais --}}
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="item-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Código</label>
                            <h5 class="mb-0 fw-bold">{{ $budget->code }}</h5>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="item-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Status Atual</label>
                            <x-status-badge :item="$budget" statusField="status" />
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="item-icon">
                            <i class="bi bi-cash-stack"></i>
                        </div>
                        <div>
                            <label class="text-muted small d-block mb-1">Total Geral</label>
                            <h5 class="mb-0 fw-bold text-success">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total) }}</h5>
                        </div>
                    </div>
                </div>

                {{-- Divisor --}}
                <div class="col-12 mt-0">
                    <hr class="text-muted opacity-25">
                </div>

                {{-- Segunda Linha: Cliente --}}
                <div class="col-12 mt-2">
                    <h6 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="bi bi-person me-2 text-primary"></i>
                        Dados do Cliente
                    </h6>
                    <div class="row g-3">
                        @if ($budget->customer && $budget->customer->commonData)
                            <div class="col-md-4">
                                <x-resource-info
                                    title="Nome/Razão Social"
                                    :subtitle="$budget->customer->commonData->company_name ?? ($budget->customer->commonData->first_name . ' ' . $budget->customer->commonData->last_name)"
                                    icon="person"
                                    class="small"
                                />
                            </div>
                            <div class="col-md-4">
                                @php
                                    $docLabel = $budget->customer->commonData->cnpj ? 'CNPJ' : 'CPF';
                                    $docValue = $budget->customer->commonData->cnpj
                                        ? \App\Helpers\DocumentHelper::formatCnpj($budget->customer->commonData->cnpj)
                                        : ($budget->customer->commonData->cpf ? \App\Helpers\DocumentHelper::formatCpf($budget->customer->commonData->cpf) : '-');
                                @endphp
                                <x-resource-info
                                    :title="$docLabel"
                                    :subtitle="$docValue"
                                    icon="card-text"
                                    class="small"
                                />
                            </div>
                            <div class="col-md-4">
                                <x-resource-info
                                    title="Contato Principal"
                                    :subtitle="$budget->customer?->contact?->email_personal ?? \App\Helpers\MaskHelper::formatPhone($budget->customer?->contact?->phone_personal ?? '') ?: '-'"
                                    icon="envelope"
                                    class="small"
                                />
                            </div>
                        @else
                            <div class="col-12">
                                <p class="text-muted mb-0 italic">Dados do cliente não vinculados corretamente.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Divisor --}}
                <div class="col-12 mt-0">
                    <hr class="text-muted opacity-25">
                </div>

                {{-- Terceira Linha: Resumo Financeiro e Datas --}}
                <div class="col-md-8 mt-2">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <x-resource-info
                                title="Criado em"
                                :subtitle="$budget->created_at->format('d/m/Y H:i')"
                                icon="calendar-plus"
                                class="small"
                            />
                        </div>
                        @if ($budget->due_date)
                        <div class="col-md-4">
                            <x-resource-info
                                title="Vencimento"
                                :subtitle="$budget->due_date->format('d/m/Y')"
                                icon="calendar-event"
                                class="small"
                            />
                        </div>
                        @endif
                        <div class="col-md-4">
                            <x-resource-info
                                title="Última Atualização"
                                :subtitle="$budget->updated_at?->format('d/m/Y H:i')"
                                icon="clock-history"
                                class="small"
                            />
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mt-2">
                    <div class="bg-light p-3 rounded-3 border border-light-subtle">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Subtotal:</span>
                            <span class="fw-semibold small">R$ {{ \App\Helpers\CurrencyHelper::format($budget->services?->sum('total') ?? 0) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Desconto:</span>
                            <span class="text-warning fw-semibold small">- R$ {{ \App\Helpers\CurrencyHelper::format($budget->discount) }}</span>
                        </div>
                        <div class="d-flex justify-content-between pt-2 border-top border-secondary-subtle">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-success">R$ {{ \App\Helpers\CurrencyHelper::format($budget->total) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Descrição e Observações --}}
                @if ($budget->description || $budget->payment_terms)
                    <div class="col-12 mt-0">
                        <hr class="text-muted opacity-25">
                    </div>
                    @if ($budget->description)
                        <div class="col-md-6 mt-2">
                            <label class="text-muted small d-block mb-1 fw-bold text-uppercase">Descrição</label>
                            <p class="mb-0 text-dark small">{{ $budget->description }}</p>
                        </div>
                    @endif
                    @if ($budget->payment_terms)
                        <div class="col-md-6 mt-2">
                            <label class="text-muted small d-block mb-1 fw-bold text-uppercase">Condições de Pagamento</label>
                            <p class="mb-0 text-dark small">{{ $budget->payment_terms }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Serviços Vinculados --}}
    <div class="mt-4">
        <x-resource-list-card
            title="Serviços Vinculados"
            mobileTitle="Serviços"
            icon="tools"
            :total="$budget->services?->count() ?? 0"
        >
            <x-slot:actions>
                @if ($budget->canBeEdited())
                    <x-button type="link" :href="route('provider.budgets.services.create', $budget->code)"
                        variant="success" size="sm" icon="plus" label="Novo Serviço" />
                @endif
            </x-slot:actions>

            @if ($budget->services && $budget->services->count())
                <x-slot:desktop>
                    <x-resource-table>
                        <x-slot:thead>
                            <tr>
                                <th>Código</th>
                                <th>Descrição</th>
                                <th>Categoria</th>
                                <th class="text-center">Status</th>
                                <th class="text-end">Total</th>
                                <th class="text-center">Ações</th>
                            </tr>
                        </x-slot:thead>
                        <x-slot:tbody>
                            @foreach ($budget->services as $service)
                                <tr>
                                    <td class="fw-bold text-dark">{{ $service->code }}</td>
                                    <td>{{ Str::limit($service->description, 50) }}</td>
                                    <td>{{ $service->category?->name ?? '-' }}</td>
                                    <td class="text-center">
                                        <x-status-badge :item="$service" />
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                                    </td>
                                    <td class="text-center">
                                        <x-action-buttons
                                            :item="$service"
                                            resource="services"
                                            identifier="code"
                                            size="sm"
                                            :showDelete="false"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot:tbody>
                    </x-resource-table>
                </x-slot:desktop>

                <x-slot:mobile>
                    @foreach ($budget->services as $service)
                        <x-resource-mobile-item>
                            <x-resource-info
                                :title="$service->code"
                                :subtitle="$service->description"
                                icon="tools"
                            />
                            <x-slot:description>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <x-status-badge :item="$service" />
                                    <span class="fw-bold text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                </div>
                            </x-slot:description>
                            <x-slot:actions>
                                <x-table-actions mobile>
                                    <x-button type="link" :href="route('provider.services.show', $service->code)" variant="info" icon="eye" size="sm" />
                                </x-table-actions>
                            </x-slot:actions>
                        </x-resource-mobile-item>
                    @endforeach
                </x-slot:mobile>
            @else
                <div class="py-5">
                    <x-empty-state
                        resource="serviços"
                        icon="tools"
                        message="Nenhum serviço vinculado a este orçamento"
                    />
                </div>
            @endif
        </x-resource-list-card>
    </div>

    {{-- Botões de Ação --}}
    <div class="mt-auto pt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto order-2 order-md-1">
                <x-back-button index-route="provider.budgets.index" class="w-100 w-md-auto px-md-3" />
            </div>

            <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                <small class="text-muted">
                    Informações oficiais geradas pelo sistema Easy Budget
                </small>
            </div>

            <div class="col-12 col-md-auto order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    @php
                        $isSent = $budget->actionHistory()->whereIn('action', ['sent_and_reserved', 'sent'])->exists();
                    @endphp

                    <x-button type="button" class="d-flex align-items-center"
                        variant="{{ $isSent ? 'outline-info' : 'info' }}"
                        icon="send-fill"
                        label="{{ $isSent ? 'Reenviar' : 'Enviar' }}"
                        data-bs-toggle="modal" data-bs-target="#sendToCustomerModal" />

                    <x-button type="link" :href="route('provider.budgets.shares.create', ['budget_id' => $budget->id])"
                        variant="outline-secondary" icon="share-fill" label="Links" />

                    @if ($budget->canBeEdited())
                        <x-button type="link" :href="route('provider.budgets.edit', $budget->code)"
                            variant="primary" icon="pencil-fill" label="Editar" />
                    @endif

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center w-100" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-2"></i>
                            <span class="d-none d-md-inline">Exportar</span>
                            <span class="d-md-none">Ações</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('provider.budgets.print', $budget->code) }}" target="_blank">
                                    <i class="bi bi-printer me-2 text-secondary"></i>
                                    Imprimir
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('provider.budgets.print', ['code' => $budget->code, 'pdf' => true, 'download' => true]) }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>
                                    PDF
                                </a>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-page-container>

<!-- Modal Enviar para Cliente -->
<div class="modal fade" id="sendToCustomerModal" tabindex="-1" aria-labelledby="sendToCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('provider.budgets.send-to-customer', $budget->code) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white border-0">
                    <h5 class="modal-title" id="sendToCustomerModalLabel">
                        <i class="bi bi-send-fill me-2"></i>{{ $isSent ? 'Reenviar Orçamento' : 'Enviar Orçamento' }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted">O orçamento será enviado para o e-mail: <strong>{{ $budget->customer->contact->email_personal ?? 'E-mail não cadastrado' }}</strong></p>

                    @if(!($budget->customer->contact->email_personal))
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            O cliente não possui e-mail pessoal cadastrado. Por favor, atualize o cadastro do cliente antes de enviar.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="message" class="form-label fw-bold">Mensagem Personalizada (Opcional)</label>
                        <textarea class="form-control" id="message" name="message" rows="4" placeholder="Olá, segue o orçamento solicitado..."></textarea>
                    </div>

                    <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-2"></i>
                        @if($isSent)
                            Os produtos deste orçamento já foram reservados. O reenvio atualizará o PDF e gerará um novo link de acesso, mas <strong>não duplicará a reserva de estoque</strong>.
                        @else
                            O PDF do orçamento será gerado, o link de visualização pública será criado e <strong>os produtos serão reservados no estoque automaticamente</strong>.
                        @endif
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info px-4 text-white" {{ !($budget->customer->contact->email_personal) ? 'disabled' : '' }}>
                        <i class="bi bi-send me-2"></i>{{ $isSent ? 'Reenviar Agora' : 'Enviar E-mail' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Inicialização manual caso o data-bs-toggle falhe por causa do defer
    document.addEventListener('DOMContentLoaded', function() {
        const modalBtn = document.querySelector('[data-bs-target="#sendToCustomerModal"]');
        if (modalBtn) {
            modalBtn.addEventListener('click', function() {
                const modalEl = document.getElementById('sendToCustomerModal');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.show();
                }
            });
        }
    });
</script>
@endpush
