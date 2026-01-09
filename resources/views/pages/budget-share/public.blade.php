@extends('layouts.app')

@section('title', 'Orçamento: ' . $budget->code)

@section('content')
<x-page-container :fluid="false" padding="py-4">
    {{-- Cabeçalho da Visualização Pública --}}
    <div class="row align-items-center mb-4 g-3">
        <div class="col-12 col-md">
            <div class="d-flex flex-column">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-text  fs-4"></i>
                    <h4 class="mb-0 fw-semibold text-dark">Orçamento Compartilhado</h4>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="text-muted small ms-md-2  ps-md-3">
                        <i class="bi bi-shield-check me-1 text-success"></i>
                        Acesso seguro via link oficial •  {{ $budget->code }}
                    </span>
                    <x-status-description :item="$budget" :useColor="false" class="ms-md-2" />
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <div class="d-flex flex-wrap gap-2 justify-content-center align-items-center">
                @if($permissions['can_print'] ?? true)
                    <a href="{{ route('budgets.public.shared.download-pdf', ['token' => $budgetShare->share_token]) }}"
                       class="btn btn-sm btn-outline-danger shadow-sm">
                        <i class="bi bi-file-pdf me-1"></i>Baixar Orçamento (PDF)
                    </a>
                @endif

                @if($permissions['can_comment'] ?? false)
                    <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="showCommentModal()">
                        <i class="bi bi-chat-left-text me-1"></i>Comentar
                    </button>
                @endif

                @if(($permissions['can_approve'] ?? false) && $budget->status->value === 'pending')
                    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm px-3" onclick="cancelBudget()">
                        <i class="bi bi-slash-circle me-1"></i>CANCELAR
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm px-3" onclick="rejectBudget()">
                        <i class="bi bi-x-circle me-1"></i>REJEITAR
                    </button>
                    <button type="button" class="btn btn-sm btn-success fw-semibold px-3 shadow-sm" onclick="approveBudget()">
                        <i class="bi bi-check-all me-1"></i>APROVAR
                    </button>
                @elseif($budget->status->value === 'approved')
                    <button type="button" class="btn btn-sm btn-outline-danger shadow-sm px-3" onclick="cancelApprovedBudget()">
                        <i class="bi bi-slash-circle me-1"></i>CANCELAR ORÇAMENTO APROVADO
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Card Principal de Informações --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="row g-4">
                {{-- Informações do Cliente --}}
                <div class="col-md-7">
                    <h6 class="text-uppercase text-muted fw-semibold mb-3 small" style="letter-spacing: 1px;">Dados do Cliente</h6>
                    <div class="row g-3">
                        <div class="col-12">
                            <x-resource-info
                                :title="$budget->customer?->company_name ?? $budget->customer?->name ?? 'Cliente não identificado'"
                                :subtitle="$budget->customer?->email ?? 'E-mail não informado'"
                                icon="person-circle"
                                titleClass="fw-semibold fs-5 text-dark"
                            />
                        </div>
                        <div class="col-sm-6">
                            <x-resource-info
                                :title="$budget->customer?->phone ? \App\Helpers\MaskHelper::formatPhone($budget->customer->phone) : 'Não informado'"
                                subtitle="Telefone de Contato"
                                icon="telephone"
                            />
                        </div>
                        @if($budget->customer?->address)
                            <div class="col-12">
                                <x-resource-info
                                    :title="\App\Helpers\AddressHelper::format($budget->customer->address)"
                                    subtitle="Endereço de Entrega/Serviço"
                                    icon="geo-alt"
                                />
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Detalhes do Orçamento --}}
                <div class="col-md-5 border-start-md ps-md-4">
                    <h6 class="text-uppercase text-muted fw-semibold mb-3 small" style="letter-spacing: 1px;">Datas e Prazos</h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <x-resource-info
                                :title="\Carbon\Carbon::parse($budget->budget_date)->format('d/m/Y')"
                                subtitle="Data de Emissão"
                                icon="calendar-check"
                            />
                        </div>
                        <div class="col-6">
                            <x-resource-info
                                :title="\Carbon\Carbon::parse($budget->validity_date)->format('d/m/Y')"
                                subtitle="Validade"
                                icon="calendar-x"
                                titleClass="text-danger"
                            />
                        </div>
                        @if($budgetShare->created_at)
                            <div class="col-12">
                                <x-resource-info
                                    :title="$budgetShare->created_at->diffForHumans()"
                                    subtitle="Compartilhado em"
                                    icon="share"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Listagem de Serviços --}}
    <div class="mb-4">
        <x-resource-list-card
            title="Serviços e Itens"
            mobileTitle="Serviços"
            icon="tools"
            :total="$budget->services?->count() ?? 0"
        >
            @forelse($budget->services as $service)
                <div class="border-bottom p-4 {{ $loop->last ? 'border-bottom-0' : '' }}">
                    <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-start align-items-md-center mb-3 gap-2">
                        <div class="flex-grow-1">
                            <h5 class="fw-semibold mb-1 text-dark">{{ $service->category?->name ?? 'Serviço' }}</h5>
                            @if(!empty($service->description))
                                <div class="text-muted small mb-0">{!! nl2br(e($service->description)) !!}</div>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap align-items-center justify-content-start justify-content-md-end gap-2 gap-md-1">
                            <span class="text-muted small fw-semibold">#{{ $service->code }}</span>
                            <x-status-description :item="$service" />
                        </div>
                    </div>

                    {{-- Desktop Table --}}
                    <div class="table-responsive rounded-3 border d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="border-bottom">
                                    <th class="ps-3 py-3 text-muted small text-uppercase" style="letter-spacing: 0.5px;">Item / Descrição</th>
                                    <th class="text-center py-3 text-muted small text-uppercase" style="letter-spacing: 0.5px;">Qtd</th>
                                    <th class="text-end py-3 text-muted small text-uppercase" style="letter-spacing: 0.5px;">Valor Unit.</th>
                                    <th class="text-end pe-3 py-3 text-muted small text-uppercase" style="letter-spacing: 0.5px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($service->serviceItems as $item)
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-semibold text-dark">{{ $item->product?->name ?? 'Item' }}</div>
                                            @if($item->product?->description)
                                                <div class="small text-muted">{{ Str::limit($item->product->description, 60) }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center fw-medium">{{ $item->quantity }}</td>
                                        <td class="text-end text-muted">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                                        <td class="text-end fw-semibold text-dark pe-3">R$ {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->unit_value) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted italic">Nenhum item neste serviço</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="fw-semibold border-top">
                                <tr>
                                    <td colspan="3" class="text-end ps-3 py-3">Total do Serviço:</td>
                                    <td class="text-end pe-3 py-3 text-primary">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Mobile List View --}}
                    <div class="d-md-none">
                        @forelse($service->serviceItems as $item)
                            <div class="py-3 border-bottom">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <div class="fw-semibold text-dark">{{ $item->product?->name ?? 'Item' }}</div>
                                    <span class="text-muted small fw-semibold">x{{ $item->quantity }}</span>
                                </div>

                                @if($item->product?->description)
                                    <div class="small text-muted mb-2 lh-sm">{{ Str::limit($item->product->description, 80) }}</div>
                                @endif

                                <div class="d-flex justify-content-between align-items-center pt-1">
                                    <span class="small text-muted">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }} /un</span>
                                    <span class="fw-semibold text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->unit_value) }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-3 text-muted italic small">Nenhum item cadastrado</div>
                        @endforelse

                        <div class="d-flex justify-content-between align-items-center mt-3 p-3 rounded-3">
                            <span class="fw-semibold small text-muted text-uppercase">Total do Serviço</span>
                            <span class="fw-semibold text-primary fs-5">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="py-5 text-center">
                    <x-empty-state
                        resource="serviços"
                        icon="tools"
                        message="Nenhum serviço encontrado neste orçamento"
                    />
                </div>
            @endforelse
        </x-resource-list-card>
    </div>

    <div class="row g-4 mb-5">
        {{-- Observações --}}
        <div class="col-lg-7">
            @if($budget->notes)
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase text-muted fw-semibold mb-3 small" style="letter-spacing: 1px;">Observações</h6>
                        <p class="mb-0 text-dark small leading-relaxed">{{ $budget->notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Resumo Financeiro --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4 d-flex flex-column justify-content-center">
                    <h6 class="text-uppercase text-muted fw-semibold mb-4 small" style="letter-spacing: 1px;">Resumo Financeiro</h6>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-medium text-dark">R$ {{ \App\Helpers\CurrencyHelper::format($budget->services?->sum('total') ?? 0) }}</span>
                    </div>

                    @if($budget->discount > 0)
                        <div class="d-flex justify-content-between mb-2 text-danger">
                            <span>Desconto:</span>
                            <span class="fw-medium">- {{ \App\Helpers\CurrencyHelper::format($budget->discount) }}</span>
                        </div>
                    @endif

                    <hr class="my-3 opacity-25">

                    <div class="d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0 fw-semibold text-dark">VALOR TOTAL:</span>
                        <span class="h3 mb-0 fw-semibold text-primary">{{ \App\Helpers\CurrencyHelper::format($budget->total) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 text-center">
        <div class="d-flex align-items-center justify-content-center gap-2">
            <div class="status-indicator bg-success"></div>
            <small class="text-muted fw-medium">
                Conexão Segura • <span id="accessTime"></span>
            </small>
        </div>
    </div>
</x-page-container>

<!-- Modal de Detalhes do Item -->
<div class="modal fade" id="itemDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="itemDetailsContent">
                <!-- Conteúdo será preenchido via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Comentário -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adicionar Comentário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="commentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="comment" class="form-label">Seu comentário</label>
                        <textarea class="form-control" id="comment" name="comment" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Seu nome</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Seu email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar Comentário</button>
                </div>
            </form>
        </div>
    </div>
</div>
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
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 5px rgba(40, 167, 69, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
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
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        document.getElementById('accessTime').textContent = now.toLocaleDateString() + ' às ' + now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

        // Lógica do formulário de comentário
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());

                fetch("{{ route('budgets.public.shared.comment', ['token' => $budgetShare->share_token]) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('Comentário enviado com sucesso!');
                        bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
                        commentForm.reset();
                    } else {
                        alert('Erro ao enviar comentário: ' + (result.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Erro ao processar solicitação');
                });
            });
        }
    });

    function approveBudget() {
        if (confirm('Deseja realmente aprovar este orçamento? Esta ação não pode ser desfeita.')) {
            fetch("{{ route('budgets.public.shared.accept', ['token' => $budgetShare->share_token]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Orçamento aprovado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao aprovar orçamento: ' + (result.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar solicitação');
            });
        }
    }

    function rejectBudget() {
        if (confirm('Deseja realmente rejeitar este orçamento? Esta ação não pode ser desfeita.')) {
            fetch("{{ route('budgets.public.shared.reject', ['token' => $budgetShare->share_token]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Orçamento rejeitado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao rejeitar orçamento: ' + (result.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar solicitação');
            });
        }
    }

    function cancelBudget() {
        if (confirm('Deseja realmente cancelar este orçamento? Esta ação não pode ser desfeita.')) {
            fetch("{{ route('budgets.public.shared.cancel', ['token' => $budgetShare->share_token]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Orçamento cancelado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao cancelar orçamento: ' + (result.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar solicitação');
            });
        }
    }

    function cancelApprovedBudget() {
        if (confirm('Atenção: Este orçamento já foi aprovado. Cancelá-lo agora pode incorrer em custos pelo trabalho já realizado. Serviços em andamento serão faturados como \'Parcialmente Concluídos\'. Deseja continuar?')) {
            fetch("{{ route('budgets.public.shared.cancel', ['token' => $budgetShare->share_token]) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Orçamento aprovado foi cancelado com sucesso!');
                    location.reload();
                } else {
                    alert('Erro ao cancelar orçamento aprovado: ' + (result.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Erro ao processar solicitação');
            });
        }
    }

    function showCommentModal() {
        const modal = new bootstrap.Modal(document.getElementById('commentModal'));
        modal.show();
    }
</script>
@endpush
