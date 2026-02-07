@extends('layouts.guest')

@section('title', 'Orçamento: ' . $budget->code)

@section('content')
<x-layout.page-container :fluid="false" padding="py-4">
    @if(session('success'))
        <x-ui.alert variant="success" dismissible>
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" dismissible>
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <x-layout.grid-row class="mb-4 align-items-center">
        <x-layout.grid-col lg="5" md="12">
            <x-layout.h-stack gap="3" class="align-items-start mb-3 mb-lg-0">
                <i class="bi bi-file-earmark-text text-muted fs-3 mt-1"></i>
                <x-layout.v-stack gap="0">
                    <h3 class="mb-1 fw-bold text-dark text-nowrap">Orçamento Compartilhado</h3>
                    <x-layout.h-stack gap="2" class="flex-wrap align-items-center">
                        <x-ui.badge variant="light" class="border">
                            <i class="bi bi-hash me-1"></i>{{ $budget->code }}
                        </x-ui.badge>
                        <x-ui.status-description :item="$budget" />
                    </x-layout.h-stack>
                </x-layout.v-stack>
            </x-layout.h-stack>
        </x-layout.grid-col>
        <x-layout.grid-col lg="7" md="12">
            <x-layout.h-stack gap="2" class="d-grid d-md-flex flex-wrap align-items-center justify-content-lg-end">
                @if($permissions['can_print'] ?? true)
                    <x-ui.button
                        type="link"
                        :href="route('budgets.public.shared.download-pdf', ['token' => $budgetShare->share_token])"
                        variant="danger"
                        outline
                        size="sm"
                        icon="file-pdf"
                        label="Baixar Orçamento (PDF)"
                        target="_blank"
                        class="shadow-sm"
                    />
                @endif

                @if(($permissions['can_approve'] ?? false) && $budget->status->value === 'pending')
                    <x-ui.button
                        variant="secondary"
                        outline
                        size="sm"
                        icon="slash-circle"
                        label="CANCELAR"
                        onclick="cancelBudget()"
                        class="shadow-sm"
                    />
                    <x-ui.button
                        variant="danger"
                        outline
                        size="sm"
                        icon="x-circle"
                        label="REJEITAR"
                        onclick="rejectBudget()"
                        class="shadow-sm"
                    />
                    <x-ui.button
                        variant="success"
                        size="sm"
                        icon="check-all"
                        label="APROVAR"
                        onclick="approveBudget()"
                        class="shadow-sm fw-bold"
                    />
                @elseif($budget->status->value === 'approved')
                    <x-ui.button
                        variant="danger"
                        outline
                        size="sm"
                        icon="slash-circle"
                        label="CANCELAR ORÇAMENTO"
                        onclick="cancelApprovedBudget()"
                        class="shadow-sm"
                    />
                @endif
            </x-layout.h-stack>
        </x-layout.grid-col>
    </x-layout.grid-row>

    <x-ui.card class="mb-4">
        <x-layout.grid-row class="g-0">
            {{-- Informações do Cliente --}}
            <x-layout.grid-col md="7" class="p-4">
                <x-layout.v-stack gap="3">
                    <h6 class="text-uppercase text-muted fw-semibold small mb-0" style="letter-spacing: 1px;">Dados do Cliente</h6>
                    <x-layout.grid-row class="g-3 mb-0">
                        <x-layout.grid-col cols="12">
                            <x-resource.resource-info
                                :title="$budget->customer?->company_name ?? $budget->customer?->name ?? 'Cliente não identificado'"
                                :subtitle="$budget->customer?->email ?? 'E-mail não informado'"
                                icon="person-circle"
                                titleClass="fw-semibold fs-5 text-dark" />
                        </x-layout.grid-col>
                        <x-layout.grid-col md="6">
                            <x-resource.resource-info
                                :title="$budget->customer?->phone ? \App\Helpers\MaskHelper::formatPhone($budget->customer->phone) : 'Não informado'"
                                subtitle="Telefone de Contato"
                                icon="telephone" />
                        </x-layout.grid-col>
                        @if($budget->customer?->address)
                        <x-layout.grid-col cols="12">
                            <x-resource.resource-info
                                :title="\App\Helpers\AddressHelper::format($budget->customer->address)"
                                subtitle="Endereço de Entrega/Serviço"
                                icon="geo-alt" />
                        </x-layout.grid-col>
                        @endif
                    </x-layout.grid-row>
                </x-layout.v-stack>
            </x-layout.grid-col>

            {{-- Detalhes do Orçamento --}}
            <x-layout.grid-col md="5" class="p-4 border-start-md">
                <x-layout.v-stack gap="3">
                    <h6 class="text-uppercase text-muted fw-semibold small mb-0" style="letter-spacing: 1px;">Datas e Prazos</h6>
                    <x-layout.grid-row class="g-3 mb-0">
                        <x-layout.grid-col cols="6">
                            <x-resource.resource-info
                                :title="\Carbon\Carbon::parse($budget->budget_date)->format('d/m/Y')"
                                subtitle="Data de Emissão"
                                icon="calendar-check" />
                        </x-layout.grid-col>
                        <x-layout.grid-col cols="6">
                            <x-resource.resource-info
                                :title="\Carbon\Carbon::parse($budget->validity_date)->format('d/m/Y')"
                                subtitle="Validade"
                                icon="calendar-x"
                                titleClass="text-danger" />
                        </x-layout.grid-col>
                        @if($budgetShare->created_at)
                        <x-layout.grid-col cols="12">
                            <x-resource.resource-info
                                :title="$budgetShare->created_at->diffForHumans()"
                                subtitle="Compartilhado em"
                                icon="share" />
                        </x-layout.grid-col>
                        @endif
                    </x-layout.grid-row>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-ui.card>

    {{-- Listagem de Serviços --}}
    <x-layout.grid-row class="mb-4">
        <x-layout.grid-col>
            <x-resource.resource-list-card
                title="Serviços e Itens"
                mobileTitle="Serviços"
                icon="tools"
                :total="$budget->services?->count() ?? 0">
                @forelse($budget->services as $service)
                    <x-layout.v-stack gap="0" class="border-bottom p-4 {{ $loop->last ? 'border-bottom-0' : '' }}">
                        <x-layout.h-stack gap="2" class="flex-column flex-md-row justify-content-md-between align-items-start align-items-md-center mb-3">
                            <x-layout.v-stack gap="1" class="flex-grow-1">
                                <h5 class="fw-semibold mb-1 text-dark">{{ $service->category?->name ?? 'Serviço' }}</h5>
                                @if(!empty($service->description))
                                    <x-layout.v-stack gap="0" class="mt-2 text-muted small leading-relaxed">
                                        {!! nl2br(e($service->description)) !!}
                                    </x-layout.v-stack>
                                @endif
                            </x-layout.v-stack>
                            <x-layout.h-stack gap="2" class="flex-wrap align-items-center justify-content-start justify-content-md-end gap-md-1">
                                <span class="text-muted small fw-semibold">#{{ $service->code }}</span>
                                <x-ui.status-description :item="$service" />
                            </x-layout.h-stack>
                        </x-layout.h-stack>

                        {{-- Tabela de Itens --}}
                        <x-resource.resource-table class="rounded-3 border overflow-hidden">
                            <x-slot name="thead">
                                <x-resource.table-row class="bg-primary border-bottom">
                                    <x-resource.table-cell header class="ps-3 py-3 text-white small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Item / Descrição</x-resource.table-cell>
                                    <x-resource.table-cell header align="center" class="py-3 text-white small text-uppercase fw-bold d-none d-md-table-cell" style="letter-spacing: 0.5px;">Qtd</x-resource.table-cell>
                                    <x-resource.table-cell header align="right" class="py-3 text-white small text-uppercase fw-bold d-none d-md-table-cell" style="letter-spacing: 0.5px;">Valor Unit.</x-resource.table-cell>
                                    <x-resource.table-cell header align="right" class="pe-3 py-3 text-white small text-uppercase fw-bold" style="letter-spacing: 0.5px;">Subtotal</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>

                            <x-slot name="tbody">
                                @forelse($service->serviceItems as $item)
                                    <x-resource.table-row>
                                        <x-resource.table-cell class="ps-3">
                                            <x-layout.v-stack gap="0">
                                                <span class="fw-semibold text-dark">{{ $item->product?->name ?? 'Item' }}</span>
                                                @if($item->product?->description)
                                                    <span class="small text-muted d-none d-md-block">{{ Str::limit($item->product->description, 100) }}</span>
                                                @endif
                                                <x-layout.v-stack gap="0" class="d-md-none small text-muted">
                                                    {{ $item->quantity }}x R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}
                                                </x-layout.v-stack>
                                            </x-layout.v-stack>
                                        </x-resource.table-cell>
                                        <x-resource.table-cell align="center" class="fw-medium d-none d-md-table-cell">{{ $item->quantity }}</x-resource.table-cell>
                                        <x-resource.table-cell align="right" class="text-muted d-none d-md-table-cell">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</x-resource.table-cell>
                                        <x-resource.table-cell align="right" class="fw-semibold text-dark pe-3">R$ {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->unit_value) }}</x-resource.table-cell>
                                    </x-resource.table-row>
                                @empty
                                    <x-resource.table-row>
                                        <x-resource.table-cell colspan="4" align="center" class="py-4 text-muted italic">Nenhum item neste serviço</x-resource.table-cell>
                                    </x-resource.table-row>
                                @endforelse
                            </x-slot>

                            <x-slot name="tfoot">
                                <x-resource.table-row class="bg-light fw-semibold border-top">
                                    <x-resource.table-cell colspan="3" align="right" class="ps-3 py-3 d-none d-md-table-cell">Total do Serviço:</x-resource.table-cell>
                                    <x-resource.table-cell align="left" class="ps-3 py-3 d-md-none text-muted small">Total:</x-resource.table-cell>
                                    <x-resource.table-cell align="right" class="pe-3 py-3 text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>
                        </x-resource.resource-table>
                    </x-layout.v-stack>
                @empty
                    <x-layout.v-stack align="center" class="py-5">
                        <x-resource.empty-state
                            resource="serviços"
                            icon="tools"
                            message="Nenhum serviço encontrado neste orçamento" />
                    </x-layout.v-stack>
                @endforelse
            </x-resource.resource-list-card>
        </x-layout.grid-col>
    </x-layout.grid-row>

    <x-layout.grid-row class="g-4 mb-5">
        {{-- Observações --}}
        <x-layout.grid-col md="7">
            @if($budget->notes)
            <x-ui.card class="h-100">
                <x-layout.v-stack gap="3">
                    <h6 class="text-uppercase text-muted fw-semibold small mb-0" style="letter-spacing: 1px;">Observações</h6>
                    <x-layout.v-stack class="bg-light p-3 rounded-3 border">
                        <p class="mb-0 text-dark small leading-relaxed">{{ $budget->notes }}</p>
                    </x-layout.v-stack>
                </x-layout.v-stack>
            </x-ui.card>
            @endif
        </x-layout.grid-col>

        {{-- Resumo Financeiro --}}
        <x-layout.grid-col md="5">
            <x-ui.card class="h-100 d-flex flex-column justify-content-center">
                <x-layout.v-stack gap="4">
                    <h6 class="text-uppercase text-muted fw-semibold small mb-0" style="letter-spacing: 1px;">Resumo Financeiro</h6>

                    <x-layout.v-stack gap="2">
                        <x-layout.h-stack justify="between">
                            <span class="text-muted">Subtotal:</span>
                            <span class="fw-medium text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($budget->services?->sum('total') ?? 0) }}</span>
                        </x-layout.h-stack>

                        @if($budget->discount > 0)
                        <x-layout.h-stack justify="between" class="text-danger">
                            <span>Desconto:</span>
                            <span class="fw-medium">- {{ \App\Helpers\CurrencyHelper::format($budget->discount) }}</span>
                        </x-layout.h-stack>
                        @endif
                    </x-layout.v-stack>

                    <hr class="my-0 opacity-25">

                    <x-layout.h-stack justify="between" align="center">
                        <span class="h5 mb-0 fw-semibold text-dark">VALOR TOTAL:</span>
                        <span class="h3 mb-0 fw-semibold text-primary">{{ \App\Helpers\CurrencyHelper::format($budget->total) }}</span>
                    </x-layout.h-stack>
                </x-layout.v-stack>
            </x-ui.card>
        </x-layout.grid-col>
    </x-layout.grid-row>

    <x-layout.h-stack gap="2" justify="center" class="mt-4">
        <span class="status-indicator bg-success"></span>
        <small class="text-muted fw-medium">
            Conexão Segura • <span id="accessTime"></span>
        </small>
    </x-layout.h-stack>
</x-layout.page-container>

<!-- Modal de Detalhes do Item -->
<x-ui.modal id="itemDetailsModal" title="Detalhes do Item" size="modal-lg">
    <x-layout.v-stack id="itemDetailsContent">
        <!-- Conteúdo será preenchido via JavaScript -->
    </x-layout.v-stack>
    <x-slot name="footer">
        <x-ui.button variant="secondary" label="Fechar" data-bs-dismiss="modal" />
    </x-slot>
</x-ui.modal>

<!-- Modal de Comentário -->
<x-ui.modal id="commentModal" title="Adicionar Comentário">
        <x-layout.v-stack gap="3">
            <x-ui.alert variant="info" class="py-2 small mb-0">
                <i class="bi bi-info-circle me-2"></i>Seu comentário será enviado junto com a sua decisão.
            </x-ui.alert>
            <x-layout.v-stack gap="1">
                <label for="comment" class="form-label small fw-semibold text-muted">Comentário (opcional):</label>
                <textarea class="form-control" id="comment" rows="4" placeholder="Escreva aqui seu comentário..."></textarea>
            </x-layout.v-stack>
        </x-layout.v-stack>
    <x-slot name="footer">
        <x-ui.button variant="secondary" label="Voltar" data-bs-dismiss="modal" />
        <x-ui.button variant="primary" id="confirmActionBtn" label="Confirmar" onclick="submitDecision()" />
    </x-slot>
</x-ui.modal>
@endsection

@push('styles')
<style>
    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 5px rgba(40, 167, 69, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
        }
    }

    .border-start-md {
        border-left: 1px solid #dee2e6;
    }

    @media (max-width: 767.98px) {
        .border-start-md {
            border-left: none;
            border-top: 1px solid #dee2e6;
            padding-top: 1.5rem;
            margin-top: 1rem;
        }
    }

    /* Estilização para SweetAlert2 ficar mais elegante e menos exagerado */
    .swal2-popup {
        padding: 1.25rem !important;
        border-radius: 16px !important;
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }

    .swal2-title {
        font-size: 1.2rem !important;
        font-weight: 600 !important;
        color: #1e293b !important;
        padding-top: 0.5rem !important;
    }

    .swal2-html-container {
        font-size: 0.95rem !important;
        color: #475569 !important;
        margin: 0.5rem 0 1rem !important;
    }

    .swal2-actions {
        margin-top: 1.25rem !important;
        gap: 8px !important;
    }

    .swal2-confirm,
    .swal2-cancel {
        padding: 8px 20px !important;
        font-size: 0.9rem !important;
        font-weight: 500 !important;
        border-radius: 8px !important;
        margin: 0 !important;
    }

    .swal2-textarea {
        font-size: 0.9rem !important;
        border-radius: 10px !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: none !important;
        margin: 1rem auto 0 !important;
        width: 90% !important;
        height: 100px !important;
        /* Altura fixa para não crescer demais */
        resize: none !important;
        /* Evita que o usuário mude o tamanho e quebre o layout */
    }

    .swal2-textarea:focus {
        border-color: #0d6efd !important;
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important;
    }

    .swal2-icon {
        margin: 1rem auto 0.5rem !important;
        transform: scale(0.8);
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.getElementById('accessTime').textContent = now.toLocaleDateString() + ' às ' + now.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
        });


    });

    function approveBudget() {
        Swal.fire({
            title: 'Aprovar Orçamento?',
            text: "Esta ação confirmará seu pedido.",
            icon: 'question',
            width: '400px',
            input: 'textarea',
            inputPlaceholder: 'Observações (opcional)',
            inputAttributes: {
                maxlength: 255
            },
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sim, Aprovar',
            cancelButtonText: 'Voltar',
            reverseButtons: true,
            didOpen: () => {
                const input = Swal.getInput();
                const counter = document.createElement('div');
                counter.className = 'text-end small text-muted mt-1';
                counter.style.paddingRight = '1.5rem';
                counter.textContent = '0/255';
                input.after(counter);

                input.addEventListener('input', () => {
                    const currentLength = input.value.length;
                    counter.textContent = `${currentLength}/255`;
                    if (currentLength >= 255) {
                        counter.classList.remove('text-muted');
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                        counter.classList.add('text-muted');
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processando...',
                    width: '350px',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('budgets.public.shared.accept', ['token' => $budgetShare->share_token]) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comment: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'Orçamento aprovado!',
                                icon: 'success',
                                width: '350px',
                                confirmButtonColor: '#198754'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: result.message || 'Erro ao aprovar.',
                                icon: 'error',
                                width: '350px',
                                confirmButtonColor: '#dc3545'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao processar sua solicitação.',
                            icon: 'error',
                            width: '350px',
                            confirmButtonColor: '#dc3545'
                        });
                    });
            }
        });
    }

    function rejectBudget() {
        Swal.fire({
            title: 'Rejeitar Orçamento?',
            text: "Informe o motivo da rejeição:",
            icon: 'warning',
            width: '400px',
            input: 'textarea',
            inputPlaceholder: 'Motivo da rejeição (obrigatório)',
            inputAttributes: {
                maxlength: 255
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Você precisa informar o motivo!'
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sim, Rejeitar',
            cancelButtonText: 'Voltar',
            reverseButtons: true,
            didOpen: () => {
                const input = Swal.getInput();
                const counter = document.createElement('div');
                counter.className = 'text-end small text-muted mt-1';
                counter.style.paddingRight = '1.5rem';
                counter.textContent = '0/255';
                input.after(counter);

                input.addEventListener('input', () => {
                    const currentLength = input.value.length;
                    counter.textContent = `${currentLength}/255`;
                    if (currentLength >= 255) {
                        counter.classList.remove('text-muted');
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                        counter.classList.add('text-muted');
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processando...',
                    width: '350px',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('budgets.public.shared.reject', ['token' => $budgetShare->share_token]) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comment: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                title: 'Rejeitado',
                                text: 'Orçamento rejeitado.',
                                icon: 'info',
                                width: '350px',
                                confirmButtonColor: '#0d6efd'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: result.message || 'Erro ao rejeitar.',
                                icon: 'error',
                                width: '350px'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao processar.',
                            icon: 'error',
                            width: '350px'
                        });
                    });
            }
        });
    }

    function cancelBudget() {
        Swal.fire({
            title: 'Cancelar Orçamento?',
            text: "Deseja realmente cancelar?",
            icon: 'warning',
            width: '400px',
            input: 'textarea',
            inputPlaceholder: 'Motivo (opcional)',
            inputAttributes: {
                maxlength: 255
            },
            showCancelButton: true,
            confirmButtonColor: '#64748b',
            cancelButtonColor: '#f1f5f9',
            confirmButtonText: 'Sim, Cancelar',
            cancelButtonText: 'Voltar',
            reverseButtons: true,
            customClass: {
                cancelButton: 'text-dark border'
            },
            didOpen: () => {
                const input = Swal.getInput();
                const counter = document.createElement('div');
                counter.id = 'char-counter';
                counter.className = 'text-end small text-muted mt-1';
                counter.style.paddingRight = '1.5rem';
                counter.textContent = '0/255';
                input.after(counter);

                input.addEventListener('input', () => {
                    const currentLength = input.value.length;
                    counter.textContent = `${currentLength}/255`;
                    if (currentLength >= 255) {
                        counter.classList.remove('text-muted');
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                        counter.classList.add('text-muted');
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Cancelando...',
                    width: '350px',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('budgets.public.shared.cancel', ['token' => $budgetShare->share_token]) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comment: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                title: 'Cancelado',
                                text: 'Orçamento cancelado com sucesso.',
                                icon: 'success',
                                width: '350px'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: result.message || 'Erro ao cancelar.',
                                icon: 'error',
                                width: '350px'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao processar.',
                            icon: 'error',
                            width: '350px'
                        });
                    });
            }
        });
    }

    function cancelApprovedBudget() {
        Swal.fire({
            title: 'Cancelar Aprovado?',
            text: "Deseja realmente cancelar este orçamento aprovado?",
            icon: 'warning',
            width: '400px',
            input: 'textarea',
            inputPlaceholder: 'Motivo do cancelamento (obrigatório)',
            inputAttributes: {
                maxlength: 255
            },
            inputValidator: (value) => {
                if (!value) {
                    return 'Informe o motivo!'
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Sim, Cancelar',
            cancelButtonText: 'Voltar',
            reverseButtons: true,
            didOpen: () => {
                const input = Swal.getInput();
                const container = Swal.getHtmlContainer();
                const counter = document.createElement('div');
                counter.id = 'char-counter';
                counter.className = 'text-end small text-muted mt-1';
                counter.style.paddingRight = '1.5rem';
                counter.textContent = '0/255';
                input.after(counter);

                input.addEventListener('input', () => {
                    const currentLength = input.value.length;
                    counter.textContent = `${currentLength}/255`;
                    if (currentLength >= 255) {
                        counter.classList.remove('text-muted');
                        counter.classList.add('text-danger');
                    } else {
                        counter.classList.remove('text-danger');
                        counter.classList.add('text-muted');
                    }
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Processando...',
                    width: '350px',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('budgets.public.shared.cancel', ['token' => $budgetShare->share_token]) }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            comment: result.value
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            Swal.fire({
                                title: 'Sucesso!',
                                text: 'Orçamento cancelado.',
                                icon: 'success',
                                width: '350px'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Erro!',
                                text: result.message || 'Erro ao cancelar.',
                                icon: 'error',
                                width: '350px'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Erro!',
                            text: 'Erro ao processar.',
                            icon: 'error',
                            width: '350px'
                        });
                    });
            }
        });
    }

    function showCommentModal() {
        const modal = new bootstrap.Modal(document.getElementById('commentModal'));
        modal.show();
    }
</script>
@endpush
