@extends('layouts.app')
@section('title', 'Detalhes do Serviço')
@section('content')
<x-page-container>
    <x-page-header
        title="Detalhes do Serviço"
        icon="tools"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Serviços' => route('provider.services.dashboard'),
            $service->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize todas as informações do serviço {{ $service->code }}</p>
    </x-page-header>

    {{-- Alerta de Faturas Existentes --}}
    @if ($service->invoices && $service->invoices->count() > 0)
        <x-alert type="message" :message="'Este serviço já possui ' . $service->invoices->count() . ' fatura(s). <a href=\"' . route('provider.invoices.index', ['search' => $service->code]) . '\" class=\"alert-link\">Ver faturas</a>'" />
    @endif

        <div class="row">
            <div class="col-lg-8">
                {{-- Informações Básicas do Serviço --}}
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">Serviço {{ $service->code }}</h5>
                                <small class="text-muted">Criado em {{ $service->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            <div class="text-end">
                                <x-status-description :item="$service" statusField="status" />
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-4 mb-4">
                            <div class="col-12 col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-tag me-2"></i>
                                    Informações Gerais
                                </h6>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Categoria</small>
                                    <span class="text-dark fw-bold">{{ $service->category?->name ?? 'Não definida' }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Orçamento</small>
                                    <a href="{{ route('provider.budgets.show', $service->budget?->code) }}"
                                        class="text-decoration-none fw-bold">
                                        {{ $service->budget?->code ?? 'N/A' }}
                                    </a>
                                </div>
                                @if ($service->due_date)
                                    <div class="mb-3">
                                        <small class="small fw-bold text-muted text-uppercase d-block mb-1">Prazo</small>
                                        <span class="text-dark fw-bold">
                                            {{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="col-12 col-md-6">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-currency-dollar me-2"></i>
                                    Valores
                                </h6>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Total</small>
                                    <span class="text-success fw-bold fs-5">
                                        {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Desconto</small>
                                    <span class="text-danger fw-bold">
                                        {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                                </div>
                                <div class="mb-3">
                                    <small class="small fw-bold text-muted text-uppercase d-block mb-1">Subtotal</small>
                                    <span class="text-dark fw-bold">
                                        {{ \App\Helpers\CurrencyHelper::format($service->total + $service->discount) }}</span>
                                </div>
                            </div>
                        </div>

                        @if ($service->description)
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">
                                    <i class="bi bi-card-text me-2"></i>
                                    Descrição
                                </h6>
                                <p class="text-muted">{{ $service->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Itens do Serviço --}}
                @if ($service->serviceItems && $service->serviceItems->count() > 0)
                    <x-resource-list-card
                        title="Itens do Serviço"
                        icon="list-ul"
                        :total="$service->serviceItems->count()">

                        <x-slot name="desktop">
                            <x-resource-table>
                                <x-slot name="thead">
                                    <tr>
                                        <th>Produto</th>
                                        <th>Quantidade</th>
                                        <th>Valor Unitário</th>
                                        <th>Total</th>
                                    </tr>
                                </x-slot>

                                @foreach ($service->serviceItems as $item)
                                    <tr>
                                        <td>
                                            <x-resource-info
                                                :title="$item->product?->name ?? 'Produto não encontrado'"
                                                :subtitle="$item->product?->description ?? ''"
                                                icon="box-seam"
                                                titleClass="fw-bold"
                                                subtitleClass="text-muted small"
                                            />
                                        </td>
                                        <td>{{ \App\Helpers\CurrencyHelper::format($item->quantity, false) }}</td>
                                        <td>{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                                        <td><strong>{{ \App\Helpers\CurrencyHelper::format($item->total) }}</strong></td>
                                    </tr>
                                @endforeach

                                <x-slot name="tfoot">
                                    <tr class="table-secondary">
                                        <th colspan="3">Total dos Itens:</th>
                                        <th>{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</th>
                                    </tr>
                                </x-slot>
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach ($service->serviceItems as $item)
                                <x-resource-mobile-item icon="box-seam">
                                    <div class="fw-semibold mb-2">{{ $item->product?->name ?? 'Produto não encontrado' }}</div>
                                    <div class="small text-muted mb-2">
                                        <span class="me-3"><strong>Qtd:</strong> {{ $item->quantity }}</span>
                                        <span><strong>Unit:</strong> {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</span>
                                    </div>
                                    <div class="text-success fw-semibold">Total: {{ \App\Helpers\CurrencyHelper::format($item->total) }}</div>

                                    <x-slot name="footer">
                                        Total: {{ \App\Helpers\CurrencyHelper::format($item->total) }}
                                    </x-slot>
                                </x-resource-mobile-item>
                            @endforeach

                            <div class="list-group-item bg-body-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Total dos Itens:</strong>
                                    <strong class="text-success">{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</strong>
                                </div>
                            </div>
                        </x-slot>
                    </x-resource-list-card>
                @endif

                {{-- Agendamentos --}}
                @if ($service->schedules && $service->schedules->count() > 0)
                    <x-resource-list-card
                        title="Agendamentos"
                        icon="calendar"
                        :total="$service->schedules->count()"
                        class="mb-4">

                        <x-slot name="desktop">
                            <x-resource-table>
                                <x-slot name="thead">
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Localização</th>
                                    </tr>
                                </x-slot>

                                @foreach ($service->schedules as $schedule)
                                    <tr>
                                        <td>
                                            <div class="fw-bold">
                                                {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y') }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') }}
                                                -
                                                {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if ($schedule->location)
                                                <i class="bi bi-geo-alt me-1 text-muted"></i>
                                                {{ $schedule->location }}
                                            @else
                                                <span class="text-muted small">Não informada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </x-resource-table>
                        </x-slot>

                        <x-slot name="mobile">
                            @foreach ($service->schedules as $schedule)
                                <x-resource-mobile-item icon="calendar-event">
                                    <div class="fw-bold mb-1">
                                        {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y') }}
                                    </div>
                                    <div class="text-muted small mb-2">
                                        {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') }}
                                        -
                                        {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                    </div>
                                    @if ($schedule->location)
                                        <div class="small">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            {{ $schedule->location }}
                                        </div>
                                    @endif
                                </x-resource-mobile-item>
                            @endforeach
                        </x-slot>
                    </x-resource-list-card>
                @endif
            </div>

            <div class="col-lg-4">
                {{-- Informações do Cliente --}}
                <div class="card border-0 shadow-sm hover-card mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-person-circle me-2"></i>
                            Cliente
                        </h6>
                    </div>
                    <div class="card-body">
                        @if ($service->budget?->customer)
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-3" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                    <i class="bi bi-person fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">
                                        <a href="{{ route('provider.customers.show', $service->budget->customer) }}" class="text-dark text-decoration-none">
                                            {{ $service->budget->customer->commonData?->first_name }}
                                            {{ $service->budget->customer->commonData?->last_name }}
                                        </a>
                                    </h6>
                                    @if ($service->budget->customer->commonData?->company_name)
                                        <small class="text-muted">{{ $service->budget->customer->commonData->company_name }}</small>
                                    @endif
                                </div>
                            </div>

                            <div class="vstack gap-3">
                                @if ($service->budget->customer->contact?->email)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="bi bi-envelope text-muted"></i>
                                        </div>
                                        <a href="mailto:{{ $service->budget->customer->contact->email }}" class="text-decoration-none text-muted small">
                                            {{ $service->budget->customer->contact->email }}
                                        </a>
                                    </div>
                                @endif

                                @if ($service->budget->customer->contact?->phone)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="bi bi-telephone text-muted"></i>
                                        </div>
                                        <a href="tel:{{ $service->budget->customer->contact->phone }}" class="text-decoration-none text-muted small">
                                            {{ $service->budget->customer->contact->phone }}
                                        </a>
                                    </div>
                                @endif

                                @if ($service->budget->customer->contact?->phone_business)
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle p-2 me-3">
                                            <i class="bi bi-whatsapp text-success"></i>
                                        </div>
                                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $service->budget->customer->contact->phone_business) }}" target="_blank" class="text-decoration-none text-muted small">
                                            {{ $service->budget->customer->contact->phone_business }}
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <hr class="my-4">

                            <div class="d-grid">
                                <x-button type="link" href="{{ route('provider.customers.show', $service->budget->customer) }}" variant="outline-primary" size="sm" icon="eye" label="Ver Perfil Completo" />
                            </div>
                        @else
                            <x-empty-state title="Cliente não vinculado" description="Este serviço não possui um cliente vinculado via orçamento." icon="person-x" />
                        @endif
                    </div>
                </div>

                @php
                    $statusValue = $service->status->value;
                    $budgetStatus = $service->budget?->status->value;
                    $isApproved = $budgetStatus === 'approved';
                @endphp

                <x-quick-actions title="Ações do Serviço" icon="lightning-charge" variant="secondary">
                    @if ($isApproved)
                        {{-- Status PENDING --}}
                        @if ($statusValue === 'pending')
                            <x-button type="button" variant="info" icon="calendar-check" label="Agendar"
                                data-bs-toggle="modal" data-bs-target="#scheduleModal" />

                            <x-button type="button" variant="warning" icon="tools" label="Preparar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="preparing"
                                data-title="Preparar Serviço"
                                data-message="Deseja marcar o serviço {{ $service->code }} como em preparação?" />

                            <x-button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                                data-title="Colocar em Espera"
                                data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />
                        @endif

                        {{-- Status SCHEDULED --}}
                        @if ($statusValue === 'scheduled')
                            <x-button type="button" variant="warning" icon="tools" label="Preparar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="preparing"
                                data-title="Preparar Serviço"
                                data-message="Deseja preparar o serviço {{ $service->code }}?" />

                            <x-button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                                data-title="Colocar em Espera"
                                data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />

                            <x-button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                                data-title="Não Realizado"
                                data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Status PREPARING --}}
                        @if ($statusValue === 'preparing')
                            <x-button type="button" variant="success" icon="play-circle" label="Iniciar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="in_progress"
                                data-title="Iniciar Serviço"
                                data-message="Deseja iniciar a execução do serviço {{ $service->code }}?" />

                            <x-button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                                data-title="Colocar em Espera"
                                data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />

                            <x-button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                                data-title="Não Realizado"
                                data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Status IN_PROGRESS --}}
                        @if ($statusValue === 'in_progress')
                            <x-button type="button" variant="success" icon="check-circle" label="Concluir"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="completed"
                                data-title="Concluir Serviço"
                                data-message="Deseja concluir o serviço {{ $service->code }}?" />

                            <x-button type="button" variant="success" icon="check-circle" label="Parcial"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="partial"
                                data-title="Concluir Parcialmente"
                                data-message="Deseja concluir o serviço {{ $service->code }} parcialmente?" />

                            <x-button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                                data-title="Colocar em Espera"
                                data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />
                        @endif

                        {{-- Status SCHEDULING ou ON_HOLD --}}
                        @if ($statusValue === 'scheduling' || $statusValue === 'on_hold')
                            <x-button type="button" variant="info" icon="calendar-check" label="Agendar"
                                data-bs-toggle="modal" data-bs-target="#scheduleModal" />

                            <x-button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                                data-title="Não Realizado"
                                data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Botão Cancelar - Sempre disponível para status não finais --}}
                        @if (!in_array($statusValue, ['completed', 'partial', 'cancelled', 'not_performed', 'expired']))
                            <x-button type="button" variant="outline-danger" icon="x-circle" label="Cancelar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="cancelled"
                                data-title="Cancelar Serviço"
                                data-message="Deseja cancelar definitivamente o serviço {{ $service->code }}?" />
                        @endif
                    @else
                        <x-alert type="warning" message="Aguardando aprovação do orçamento para prosseguir com o serviço." icon="exclamation-triangle" />
                    @endif

                    <hr class="my-3">

                    @if ($service->canBeEdited())
                        <x-button type="link" href="{{ route('provider.services.edit', $service->code) }}"
                            variant="outline-primary" icon="pencil" label="Editar Serviço" />
                    @endif

                    @if ($service->budget)
                        <x-button type="link" href="{{ route('provider.budgets.show', $service->budget->code) }}"
                            variant="outline-info" icon="receipt" label="Ver Orçamento" />
                    @endif

                    {{-- Botões de Fatura --}}
                    @if ($service->status->isFinished() || $statusValue === 'completed')
                        <x-button type="link" href="{{ route('provider.invoices.create.from-service', $service->code) }}"
                            variant="outline-success" icon="receipt" label="Criar Fatura" />
                    @else
                        @if($service->serviceItems && $service->serviceItems->count() > 0)
                            <x-button type="link" href="{{ route('provider.invoices.create.partial-from-service', $service->code) }}"
                                variant="outline-warning" icon="receipt" label="Criar Fatura Parcial" />
                        @endif
                    @endif

                    <x-button type="button" variant="outline-secondary" onclick="window.print()" icon="printer" label="Imprimir" />
                </x-quick-actions>
            </div>
        </div>

        {{-- Botões de Ação (Footer) --}}
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex gap-2">
                <x-button
                    href="{{ url()->previous(route('provider.services.index')) }}"
                    variant="outline-secondary"
                    icon="bi bi-arrow-left">
                    Voltar
                </x-button>
            </div>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $service->updated_at?->format('d/m/Y H:i') }}
            </small>
            <div class="d-flex gap-2">
                @if ($service->canBeEdited())
                    <x-button
                        href="{{ route('provider.services.edit', $service->code) }}"
                        variant="primary"
                        icon="bi bi-pencil-fill">
                        Editar
                    </x-button>
                @endif
                <x-button
                    type="button"
                    variant="outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteModal"
                    icon="bi bi-trash-fill">
                    Excluir
                </x-button>
            </div>
        </div>

        {{-- Modal de Confirmação de Exclusão --}}
        <x-modal id="deleteModal" title="Confirmar Exclusão">
            Tem certeza de que deseja excluir o serviço <strong>{{ $service->code }}</strong>?
            <br><small class="text-muted">Esta ação não pode ser desfeita.</small>

            <x-slot name="footer">
                <x-button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form action="{{ route('provider.services.destroy', $service->code) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" label="Excluir" />
                </form>
            </x-slot>
        </x-modal>

        {{-- Modal Reutilizável para Ações de Status --}}
        <x-modal id="actionModal" title="Ação de Status">
            <form id="actionForm" action="{{ route('provider.services.change-status', $service->code) }}" method="POST">
                @csrf
                <input type="hidden" name="status" id="actionStatusInput">
                <p id="actionModalMessage"></p>
                <p class="text-danger mt-2" id="actionModalWarning" style="display: none">
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita.
                </p>
            </form>
            <x-slot name="footer">
                <x-button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-button type="submit" form="actionForm" variant="primary" id="actionConfirmButton" label="Confirmar" />
            </x-slot>
        </x-modal>

        {{-- Modal para Agendamento --}}
        <x-modal id="scheduleModal" title="Agendar Serviço">
            <form id="scheduleForm" action="{{ route('provider.schedules.store', $service->code) }}" method="POST">
                @csrf
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="provider_id" value="{{ auth()->id() }}">
                <input type="hidden" name="customer_id" value="{{ $service->budget?->customer_id }}">
                <input type="hidden" name="service_type" value="{{ $service->category?->name ?? 'Serviço' }}">

                <div class="mb-3">
                    <label class="form-label">Data do Serviço</label>
                    <input type="date" name="service_date" class="form-control" required
                        value="{{ $service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('Y-m-d') : date('Y-m-d') }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Horário</label>
                    <input type="time" name="service_time" class="form-control" required value="08:00">
                </div>
                <div class="mb-3">
                    <label class="form-label">Duração (minutos)</label>
                    <input type="number" name="service_duration" class="form-control" required value="60" min="30" max="480">
                </div>
                <div class="mb-3">
                    <label class="form-label">Localização</label>
                    <input type="text" name="location" class="form-control" placeholder="Opcional">
                </div>
                <div class="mb-3">
                    <label class="form-label">Observações</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </form>
            <x-slot name="footer">
                <x-button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-button type="submit" form="scheduleForm" variant="info" label="Agendar" />
            </x-slot>
        </x-modal>
    </x-page-container>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const actionModal = document.getElementById('actionModal');
        if (actionModal) {
            actionModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                if (!button) return;

                const status = button.getAttribute('data-status');
                const title = button.getAttribute('data-title');
                const message = button.getAttribute('data-message');

                const modalTitle = actionModal.querySelector('.modal-title');
                const modalMessage = document.getElementById('actionModalMessage');
                const modalWarning = document.getElementById('actionModalWarning');
                const statusInput = document.getElementById('actionStatusInput');
                const confirmButton = document.getElementById('actionConfirmButton');

                if (modalTitle) modalTitle.textContent = title || 'Ação de Status';
                if (modalMessage) modalMessage.textContent = message || '';
                if (statusInput) statusInput.value = status || '';

                // Mostrar aviso para ações críticas
                if (modalWarning) {
                    if (status === 'cancelled' || status === 'not_performed') {
                        modalWarning.style.display = 'block';
                    } else {
                        modalWarning.style.display = 'none';
                    }
                }

                // Ajustar cor do botão de confirmação baseada no status
                if (confirmButton) {
                    // Resetar classes mantendo a base
                    confirmButton.className = 'btn';

                    if (status === 'completed' || status === 'in_progress' || status === 'partial') {
                        confirmButton.classList.add('btn-success');
                    } else if (status === 'cancelled' || status === 'not_performed') {
                        confirmButton.classList.add('btn-danger');
                    } else if (status === 'preparing' || status === 'on_hold') {
                        confirmButton.classList.add('btn-warning');
                    } else {
                        confirmButton.classList.add('btn-primary');
                    }
                }
            });
        }
    });
</script>
@endpush

@push('styles')
    <style>
        .card {
            border-radius: 12px;
        }

        .badge {
            border-radius: 20px;
        }

        .btn {
            border-radius: 8px;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
        }

        .breadcrumb {
            background: none;
            padding: 0;
        }

        .breadcrumb-item+.breadcrumb-item::before {
            content: "›";
            color: #6c757d;
        }
    </style>
@endpush
