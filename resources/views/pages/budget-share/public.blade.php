@extends('layouts.public')

@section('title', 'Orçamento Compartilhado')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0">
                                <i class="bi bi-file-text me-2"></i>Orçamento Compartilhado
                            </h3>
                            <small>Acesso via link compartilhado</small>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark fs-6">
                                {{ $budget->code }}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Informações do Cliente -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Informações do Cliente</h5>
                            <div class="card border">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Nome:</strong>
                                        <span class="text-muted">{{ $budget->customer->name }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Email:</strong>
                                        <span class="text-muted text-break">{{ $budget->customer->email ?? 'Não informado' }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Telefone:</strong>
                                        <span class="text-muted">{{ $budget->customer->phone ? \App\Helpers\MaskHelper::formatPhone($budget->customer->phone) : 'Não informado' }}</span>
                                    </div>
                                    @if($budget->customer->address)
                                    <div class="mb-2">
                                        <strong>Endereço:</strong>
                                        <span class="text-muted">{{ $budget->customer->address }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">Detalhes do Orçamento</h5>
                            <div class="card border">
                                <div class="card-body">
                                    <div class="mb-2">
                                        <strong>Data:</strong>
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($budget->budget_date)->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Validade:</strong>
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($budget->validity_date)->format('d/m/Y') }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Status:</strong>
                                        @php
                                            $statusColor = $budget->status->getColor();
                                            $statusLabel = $budget->status->label();
                                        @endphp
                                        <span class="badge" style="background-color: {{ $statusColor }}20; color: {{ $statusColor }}; border: 1px solid {{ $statusColor }}40;">
                                            {{ $statusLabel }}
                                        </span>
                                    </div>
                                    <div class="mb-2">
                                        <strong>Compartilhado por:</strong>
                                        <span class="text-muted">{{ $share->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Serviços e Itens do Orçamento -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Serviços e Itens</h5>
                            @forelse($budget->services as $service)
                                <div class="card mb-3">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">{{ $service->category?->name ?? 'Serviço' }}</h6>
                                        <span class="badge" style="background-color: {{ $service->status?->getColor() ?? '#6c757d' }}">
                                            {{ $service->status?->getDescription() ?? 'Pendente' }}
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        @if($service->description)
                                            <p class="text-muted small mb-3">{{ $service->description }}</p>
                                        @endif
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Produto/Serviço</th>
                                                        <th class="text-center">Qtd</th>
                                                        <th class="text-end">Valor Unit.</th>
                                                        <th class="text-end">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($service->serviceItems as $item)
                                                        <tr>
                                                            <td>
                                                                <strong>{{ $item->product?->name ?? 'Item' }}</strong>
                                                                @if($item->product?->description)
                                                                    <br><small class="text-muted">{{ $item->product->description }}</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">{{ $item->quantity }}</td>
                                                            <td class="text-end">R$ {{ number_format($item->unit_value, 2, ',', '.') }}</td>
                                                            <td class="text-end">R$ {{ number_format($item->quantity * $item->unit_value, 2, ',', '.') }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">Nenhum item neste serviço</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <th colspan="3" class="text-end">Total do Serviço:</th>
                                                        <th class="text-end">R$ {{ number_format($service->total, 2, ',', '.') }}</th>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="alert alert-info text-center">Nenhum serviço encontrado neste orçamento</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Totais -->
                    <div class="row mb-4">
                        <div class="col-md-6 offset-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <strong>R$ {{ number_format($budget->subtotal, 2, ',', '.') }}</strong>
                                    </div>
                                    @if($budget->discount > 0)
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>Desconto:</span>
                                        <strong>- R$ {{ number_format($budget->discount, 2, ',', '.') }}</strong>
                                    </div>
                                    @endif
                                    <hr>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><strong>Total:</strong></span>
                                        <strong class="text-primary fs-5">R$ {{ number_format($budget->total_value, 2, ',', '.') }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Observações -->
                    @if($budget->notes)
                    <div class="row mb-4">
                        <div class="col-12">
                            <h5 class="text-primary mb-3">Observações</h5>
                            <div class="card border">
                                <div class="card-body">
                                    <p class="mb-0">{{ $budget->notes }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Ações -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex gap-2">
                                    @if($permissions['can_print'] ?? true)
                                    <x-button type="button" variant="primary" icon="printer" label="Imprimir" onclick="printBudget()" />
                                    <x-button type="button" variant="secondary" icon="download" label="Download PDF" onclick="downloadPDF()" />
                                    @endif
                                </div>
                                
                                <div class="d-flex gap-2">
                                    @if($permissions['can_approve'] ?? false && $budget->status === 'pending')
                                    <x-button type="button" variant="success" icon="check-circle" label="Aprovar Orçamento" onclick="approveBudget()" />
                                    @endif
                                    
                                    @if($permissions['can_comment'] ?? false)
                                    <x-button 
                                        type="button" 
                                        variant="info" 
                                        onclick="showCommentModal()"
                                        icon="chat-left-text"
                                        label="Adicionar Comentário" />
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer text-muted text-center">
                    <small>
                        <i class="bi bi-shield-check me-2"></i>
                        Acesso seguro via link compartilhado • 
                        <span id="accessTime"></span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

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

@section('scripts')
<script>
// Atualizar hora de acesso
document.getElementById('accessTime').textContent = new Date().toLocaleString('pt-BR');

function showItemDetails(item) {
    const content = `
        <div class="row">
            <div class="col-md-6">
                <h6><strong>Serviço:</strong></h6>
                <p>${item.service_name}</p>
                
                <h6><strong>Descrição:</strong></h6>
                <p>${item.description || 'Sem descrição'}</p>
                
                <h6><strong>Quantidade:</strong></h6>
                <p>${item.quantity}</p>
            </div>
            <div class="col-md-6">
                <h6><strong>Valor Unitário:</strong></h6>
                <p>R$ ${parseFloat(item.unit_value).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                
                <h6><strong>Subtotal:</strong></h6>
                <p class="text-primary fw-bold">R$ ${parseFloat(item.subtotal).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>
                
                <h6><strong>Observações:</strong></h6>
                <p>${item.notes || 'Sem observações'}</p>
            </div>
        </div>
    `;
    
    document.getElementById('itemDetailsContent').innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('itemDetailsModal'));
    modal.show();
}

function printBudget() {
    window.print();
}

function downloadPDF() {
    const token = '{{ $share->token }}';
    window.location.href = `/budget-share/${token}/download`;
}

function approveBudget() {
    if (confirm('Tem certeza que deseja aprovar este orçamento? Esta ação não pode ser desfeita.')) {
        const token = '{{ $share->token }}';
        fetch(`/budget-share/${token}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (window.easyAlert) {
                    window.easyAlert.success('Orçamento aprovado com sucesso!');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Orçamento aprovado com sucesso!');
                    location.reload();
                }
            } else {
                if (window.easyAlert) {
                    window.easyAlert.error('Erro ao aprovar orçamento: ' + data.message);
                } else {
                    alert('Erro ao aprovar orçamento: ' + data.message);
                }
            }
        })
        .catch(error => {
            if (window.easyAlert) {
                window.easyAlert.error('Erro ao processar solicitação');
            } else {
                alert('Erro ao processar solicitação');
            }
        });
    }
}

function showCommentModal() {
    const modal = new bootstrap.Modal(document.getElementById('commentModal'));
    modal.show();
}

document.getElementById('commentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const token = '{{ $share->token }}';
    
    fetch(`/budget-share/${token}/comment`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            comment: formData.get('comment'),
            name: formData.get('name'),
            email: formData.get('email')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.easyAlert) {
                window.easyAlert.success('Comentário enviado com sucesso!');
            } else {
                alert('Comentário enviado com sucesso!');
            }
            bootstrap.Modal.getInstance(document.getElementById('commentModal')).hide();
            this.reset();
        } else {
            if (window.easyAlert) {
                window.easyAlert.error('Erro ao enviar comentário: ' + data.message);
            } else {
                alert('Erro ao enviar comentário: ' + data.message);
            }
        }
    })
    .catch(error => {
        if (window.easyAlert) {
            window.easyAlert.error('Erro ao processar solicitação');
        } else {
            alert('Erro ao processar solicitação');
        }
    });
});

// Adicionar estilo de impressão
const printStyles = `
    @media print {
        .card-header, .card-footer, .btn, .modal {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        body {
            background: white !important;
        }
    }
`;
const styleSheet = document.createElement('style');
styleSheet.textContent = printStyles;
document.head.appendChild(styleSheet);
</script>
@endsection

@section('styles')
<style>
.public-layout {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 2rem 0;
}

.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    border-radius: 15px 15px 0 0 !important;
    padding: 1.5rem;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.05);
}

.text-success {
    color: #28a745 !important;
}

.text-primary {
    color: #007bff !important;
}

.btn {
    border-radius: 8px;
    padding: 0.5rem 1rem;
}

.btn-outline-primary {
    border-color: #007bff;
    color: #007bff;
}

.btn-outline-primary:hover {
    background-color: #007bff;
    color: white;
}

.badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

@media (max-width: 768px) {
    .container {
        padding: 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
}
</style>
@endsection