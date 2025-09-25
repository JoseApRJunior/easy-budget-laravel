@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-file-earmark-text me-2"></i>Detalhes do Orçamento
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="{{ route('budgets.index') }}">Orçamentos</a></li>
        <li class="breadcrumb-item active">{{ $budget->code ?? 'N/A' }}</li>
      </ol>
    </nav>
  </div>

  {{-- Mensagens de erro/sucesso --}}
  @if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  @if(session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  @endif

  <div class="row g-4 mb-4">
    {{-- Detalhes do Orçamento --}}
    <div class="col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          {{-- Informações Básicas --}}
          <div class="row g-4 mb-4">
            <div class="col-md-4">
              <div class="d-flex flex-column">
                <label class="text-muted small mb-1">Código</label>
                <h5 class="mb-0 fw-semibold">{{ $budget->code ?? 'N/A' }}</h5>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex flex-column">
                <label class="text-muted small mb-1">Cliente</label>
                <h5 class="mb-0">{{ $budget->customer->commonData->fullName ?? 'N/A' }}</h5>
              </div>
            </div>
            <div class="col-md-2">
              <div class="d-flex flex-column">
                <label class="text-muted small mb-1">Status</label>
                <span class="badge bg-primary">{{ $budget->budgetStatus->name ?? 'Pendente' }}</span>
              </div>
            </div>
          </div>

          {{-- Descrição --}}
          <div class="mb-4">
            <label class="text-muted small mb-1">Descrição</label>
            <p class="mb-0 lead">{{ $budget->description ?? 'Sem descrição' }}</p>
          </div>

          {{-- Detalhes Adicionais --}}
          <div class="accordion" id="budgetDetails">
            <div class="accordion-item border-0 shadow-sm">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                  data-bs-target="#collapseDetails">
                  <i class="bi bi-info-circle me-2"></i>Detalhes Adicionais
                </button>
              </h2>
              <div id="collapseDetails" class="accordion-collapse collapse">
                <div class="accordion-body">
                  <div class="row g-4">
                    {{-- Detalhes do Cliente --}}
                    <div class="col-md-6">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                          <h6 class="mb-0">
                            <i class="bi bi-person me-2"></i>Detalhes do Cliente
                          </h6>
                        </div>
                        <div class="card-body">
                          <div class="mb-3">
                            <label class="text-muted small">Telefone</label>
                            <p class="mb-0">
                              {{ $budget->customer->contact->phone_business ?? $budget->customer->contact->phone ?? 'N/A' }}
                            </p>
                          </div>
                          <div class="mb-0">
                            <label class="text-muted small">Email</label>
                            <p class="mb-0">
                              {{ $budget->customer->contact->email_business ?? $budget->customer->contact->email ?? 'N/A' }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    {{-- Datas --}}
                    <div class="col-md-6">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                          <h6 class="mb-0">
                            <i class="bi bi-calendar me-2"></i>Datas
                          </h6>
                        </div>
                        <div class="card-body">
                          <div class="mb-3">
                            <label class="text-muted small">Criado em</label>
                            <p class="mb-0">{{ $budget->created_at ? $budget->created_at->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                          </div>
                          <div class="mb-0">
                            <label class="text-muted small">Atualizado em</label>
                            <p class="mb-0">{{ $budget->updated_at ? $budget->updated_at->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    {{-- Condições de Pagamento --}}
                    @if($budget->payment_terms)
                    <div class="col-12">
                      <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                          <h6 class="mb-0">
                            <i class="bi bi-credit-card me-2"></i>Condições de Pagamento
                          </h6>
                        </div>
                        <div class="card-body">
                          <p class="mb-0">{{ $budget->payment_terms }}</p>
                        </div>
                      </div>
                    </div>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Resumo Financeiro --}}
    <div class="col-md-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header bg-transparent">
          <h5 class="card-title mb-0">
            <i class="bi bi-currency-dollar me-2"></i>Resumo Financeiro
          </h5>
        </div>
        <div class="card-body">
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              Total Bruto:
              <span class="fw-semibold">R$ {{ number_format($budget->total ?? 0, 2, ',', '.') }}</span>
            </li>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
              <strong>Total a Pagar:</strong>
              <strong class="text-success h5 mb-0">R$ {{ number_format($budget->total ?? 0, 2, ',', '.') }}</strong>
            </li>
          </ul>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted">Data de Vencimento:</div>
            <div class="fw-semibold {{ $budget->due_date && $budget->due_date < now() ? 'text-danger' : '' }}">
              {{ $budget->due_date ? $budget->due_date->format('d/m/Y') : 'N/A' }}
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted">Quantidade de Serviços:</div>
            <div class="fw-semibold">{{ $services->count() ?? 0 }}</div>
          </div>

          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="text-muted">Progresso do Orçamento:</span>
            </div>
            <div class="progress" style="height: 10px;">
              <div class="progress-bar bg-success" role="progressbar" style="width: {{ $budget->progress ?? 0 }}%;"
                aria-valuenow="{{ $budget->progress ?? 0 }}" aria-valuemin="0" aria-valuemax="100"
                title="{{ $budget->progress ?? 0 }}% Concluído">
              </div>
            </div>
          </div>

          <small class="text-muted mt-1 d-block">
            @php
            $statusSlug = $budget->budgetStatus->slug ?? 'pending';
            @endphp
            @switch($statusSlug)
            @case('draft')
            Orçamento em elaboração.
            @break
            @case('pending')
            Aguardando aprovação do cliente.
            @break
            @case('approved')
            Orçamento aprovado, serviços podem ser iniciados.
            @break
            @case('completed')
            Orçamento concluído com sucesso.
            @break
            @case('rejected')
            Orçamento rejeitado pelo cliente.
            @break
            @case('cancelled')
            Orçamento cancelado.
            @break
            @default
            Status não definido.
            @endswitch
          </small>
        </div>
      </div>
    </div>
  </div>

  {{-- Serviços Vinculados --}}
  <div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 p-4">
      <h2 class="h5 mb-0">
        <i class="bi bi-tools me-2"></i>Serviços Vinculados
      </h2>
    </div>
    <div class="card-body p-4">
      @if($services && $services->count() > 0)
      @foreach($services as $service)
      <div class="card mb-4 border-0 shadow-sm">
        <div class="card-header p-3">
          <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
              <h5 class="mb-0">
                <i class="bi bi-tag me-2"></i>{{ $service->category->name ?? 'Categoria não definida' }}
              </h5>
              <a href="{{ route('services.show', $service->code ?? '') }}" class="btn btn-warning btn-sm ms-3">
                <i class="bi bi-arrow-right-circle me-1"></i> Detalhes
              </a>
            </div>
            <span class="badge bg-primary">{{ $service->serviceStatus->name ?? 'Pendente' }}</span>
          </div>
        </div>
        <div class="card-body p-4">
          <div class="row g-4">
            <div class="col-md-6">
              <div class="d-flex flex-column h-100">
                <div class="mb-3">
                  <label class="text-muted small">Data de Vencimento</label>
                  <h5 class="mb-0">{{ $service->due_date ? $service->due_date->format('d/m/Y') : 'N/A' }}</h5>
                </div>
                <div class="mt-3">
                  <ul class="list-group list-group-flush small">
                    <li class="list-group-item ps-0 d-flex justify-content-between align-items-center">
                      Total:
                      <span class="text-success fw-bold">R$
                        {{ number_format($service->total ?? 0, 2, ',', '.') }}</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="h-100">
                <label class="text-muted small">Descrição</label>
                <p class="mb-0">{{ $service->description ?? 'Sem descrição' }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endforeach
      @else
      <div class="alert alert-info d-flex align-items-center" role="alert">
        <i class="bi bi-info-circle flex-shrink-0 me-2"></i>
        <div class="flex-grow-1">
          Nenhum serviço vinculado encontrado, adicione um serviço para enviar a proposta ao cliente.
        </div>
        <a href="{{ route('budgets.services.create', $budget->code ?? '') }}"
          class="btn btn-sm btn-success ms-2 flex-shrink-0">
          Adicionar Serviço
        </a>
      </div>
      @endif
    </div>
  </div>

  {{-- Botões de ação --}}
  <div class="d-flex justify-content-between mt-4">
    <a href="{{ route('budgets.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left me-2"></i>Voltar
    </a>
    <div class="d-flex gap-2">
      @if(($budget->budgetStatus->slug ?? '') === 'draft')
      <a href="{{ route('budgets.edit', $budget->code ?? '') }}" class="btn btn-primary">
        <i class="bi bi-pencil-fill me-2"></i>Editar Orçamento
      </a>
      @endif

      @if(($budget->budgetStatus->slug ?? '') === 'draft' && ($services->count() ?? 0) > 0)
      <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#actionModal"
        data-action="pending" data-title="Enviar Orçamento para Aprovação"
        data-message="Tem certeza que deseja enviar o orçamento {{ $budget->code ?? '' }} para aprovação do cliente?">
        <i class="bi bi-send me-2"></i>Enviar para Aprovação
      </button>
      @endif

      <a href="{{ route('budgets.print', $budget->code ?? '') }}" class="btn btn-outline-primary" target="_blank">
        <i class="bi bi-printer me-2"></i>Imprimir
      </a>
    </div>
  </div>
</div>

{{-- Modal de Ações --}}
<div class="modal fade" id="actionModal" tabindex="-1" aria-labelledby="actionModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="actionModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="actionModalMessage"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Voltar</button>
        <form id="actionForm" action="{{ route('budgets.change-status') }}" method="POST">
          @csrf
          <input type="hidden" name="budget_id" value="{{ $budget->id ?? '' }}">
          <input type="hidden" name="budget_code" value="{{ $budget->code ?? '' }}">
          <input type="hidden" name="current_status_id" value="{{ $budget->budget_status_id ?? '' }}">
          <input type="hidden" name="current_status_slug" value="{{ $budget->budgetStatus->slug ?? '' }}">
          <input type="hidden" name="action" id="actionInput" value="">
          <button type="submit" class="btn" id="actionConfirmButton">Confirmar</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
const actionModal = document.getElementById('actionModal');
actionModal.addEventListener('show.bs.modal', function(event) {
  const button = event.relatedTarget;
  const action = button.getAttribute('data-action');
  const title = button.getAttribute('data-title');
  const message = button.getAttribute('data-message');

  const modalTitle = actionModal.querySelector('.modal-title');
  const modalMessage = actionModal.querySelector('#actionModalMessage');
  const actionInput = actionModal.querySelector('#actionInput');
  const confirmButton = actionModal.querySelector('#actionConfirmButton');

  modalTitle.textContent = title;
  modalMessage.textContent = message;
  actionInput.value = action;

  // Define a classe do botão de confirmação
  confirmButton.className = 'btn btn-warning';
});
</script>
@endsection