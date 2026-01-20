@extends('layouts.app')
@section('title', 'Detalhes do Serviço')
@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Detalhes do Serviço"
        icon="tools"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Serviços' => route('provider.services.dashboard'),
            $service->code => '#'
        ]">
        <p class="text-muted mb-0">Visualize todas as informações do serviço {{ $service->code }}</p>
    </x-layout.page-header>

    {{-- Alerta de Faturas Existentes --}}
    @if ($service->invoices && $service->invoices->count() > 0)
    <x-ui.alert type="message" :message="'Este serviço já possui ' . $service->invoices->count() . ' fatura(s). <a href=\"' . route(' provider.invoices.index', ['search'=> $service->code]) . '\" class=\"alert-link\">Ver faturas</a>'" />
        @endif

        <x-layout.grid-row>
            <x-layout.grid-col size="col-lg-8">
                {{-- Informações Básicas do Serviço --}}
                <x-resource.resource-list-card
                    title="Serviço {{ $service->code }}"
                    icon="tools"
                    padding="p-4">
                    <x-slot name="headerActions">
                        <small class="text-muted">Criado em {{ $service->created_at->format('d/m/Y H:i') }}</small>
                        <div class="ms-2">
                            <x-ui.status-description :item="$service" statusField="status" />
                        </div>
                    </x-slot>

                    <x-layout.grid-row class="mb-4">
                        <x-layout.grid-col size="col-12 col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-tag me-2"></i>
                                Informações Gerais
                            </h6>
                            <x-resource.resource-mobile-field label="Categoria" :value="$service->category?->name ?? 'Não definida'" />
                            <x-resource.resource-mobile-field label="Orçamento">
                                <a href="{{ route('provider.budgets.show', $service->budget?->code) }}"
                                    class="text-decoration-none fw-bold">
                                    {{ $service->budget?->code ?? 'N/A' }}
                                </a>
                            </x-resource.resource-mobile-field>
                            @if ($service->due_date)
                            <x-resource.resource-mobile-field label="Prazo" :value="\Carbon\Carbon::parse($service->due_date)->format('d/m/Y')" />
                            @endif
                        </x-layout.grid-col>
                        <x-layout.grid-col size="col-12 col-md-6">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-currency-dollar me-2"></i>
                                Valores
                            </h6>
                            <x-resource.resource-mobile-field label="Total">
                                <span class="text-success fw-bold fs-5">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                            </x-resource.resource-mobile-field>
                            <x-resource.resource-mobile-field label="Desconto">
                                <span class="text-danger fw-bold">{{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                            </x-resource.resource-mobile-field>
                            <x-resource.resource-mobile-field label="Subtotal" :value="\App\Helpers\CurrencyHelper::format($service->total + $service->discount)" />
                        </x-layout.grid-col>
                    </x-layout.grid-row>

                    @if ($service->description)
                    <div class="mb-0">
                        <h6 class="text-muted mb-3">
                            <i class="bi bi-card-text me-2"></i>
                            Descrição
                        </h6>
                        <p class="text-muted mb-0">{{ $service->description }}</p>
                    </div>
                    @endif
                </x-resource.resource-list-card>

                {{-- Itens do Serviço --}}
                @if ($service->serviceItems && $service->serviceItems->count() > 0)
                <x-resource.resource-list-card
                    title="Itens do Serviço"
                    icon="list-ul"
                    :total="$service->serviceItems->count()">

                    <x-slot name="desktop">
                        <x-resource.resource-table>
                            <x-slot name="thead">
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Produto</x-resource.table-cell>
                                    <x-resource.table-cell header>Quantidade</x-resource.table-cell>
                                    <x-resource.table-cell header>Valor Unitário</x-resource.table-cell>
                                    <x-resource.table-cell header>Total</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>

                            @foreach ($service->serviceItems as $item)
                            <x-resource.table-row>
                                <x-resource.table-cell>
                                    <x-resource.resource-info
                                        :title="$item->product?->name ?? 'Produto não encontrado'"
                                        :subtitle="$item->product?->description ?? ''"
                                        icon="box-seam"
                                        titleClass="fw-bold"
                                        subtitleClass="text-muted small" />
                                </x-resource.table-cell>
                                <x-resource.table-cell>{{ \App\Helpers\CurrencyHelper::format($item->quantity, false) }}</x-resource.table-cell>
                                <x-resource.table-cell>{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</x-resource.table-cell>
                                <x-resource.table-cell><strong>{{ \App\Helpers\CurrencyHelper::format($item->total) }}</strong></x-resource.table-cell>
                            </x-resource.table-row>
                            @endforeach

                            <x-slot name="tfoot">
                                <x-resource.table-row class="table-secondary">
                                    <x-resource.table-cell colspan="3" header>Total dos Itens:</x-resource.table-cell>
                                    <x-resource.table-cell header>{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>
                        </x-resource.resource-table>
                    </x-slot>

                    <x-slot name="mobile">
                        @foreach ($service->serviceItems as $item)
                        <x-resource.resource-mobile-item icon="box-seam">
                            <x-resource.resource-mobile-header
                                :title="$item->product?->name ?? 'Produto não encontrado'" />

                            <x-layout.grid-row g="2" class="mb-2">
                                <x-resource.resource-mobile-field
                                    label="Qtd"
                                    :value="\App\Helpers\CurrencyHelper::format($item->quantity, false)"
                                    col="col-6" />
                                <x-resource.resource-mobile-field
                                    label="Unit"
                                    :value="\App\Helpers\CurrencyHelper::format($item->unit_value)"
                                    col="col-6"
                                    align="end" />
                            </x-layout.grid-row>

                            <x-resource.resource-mobile-field
                                label="Total"
                                col="col-12">
                                <span class="text-success fw-bold">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</span>
                            </x-resource.resource-mobile-field>
                        </x-resource.resource-mobile-item>
                        @endforeach

                        <x-resource.resource-mobile-field label="Total dos Itens" class="bg-body-secondary p-3">
                            <strong class="text-success">{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</strong>
                        </x-resource.resource-mobile-field>
                    </x-slot>
                </x-resource.resource-list-card>
                @endif

                {{-- Agendamentos --}}
                @if ($service->schedules && $service->schedules->count() > 0)
                <x-resource.resource-list-card
                    title="Agendamentos"
                    icon="calendar"
                    :total="$service->schedules->count()"
                    class="mb-4">

                    <x-slot name="desktop">
                        <x-resource.resource-table>
                            <x-slot name="thead">
                                <x-resource.table-row>
                                    <x-resource.table-cell header>Data</x-resource.table-cell>
                                    <x-resource.table-cell header>Horário</x-resource.table-cell>
                                    <x-resource.table-cell header>Localização</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>

                            @foreach ($service->schedules as $schedule)
                            <x-resource.table-row>
                                <x-resource.table-cell class="fw-bold">
                                    {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell class="text-muted">
                                    {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') }}
                                    -
                                    {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                </x-resource.table-cell>
                                <x-resource.table-cell>
                                    @if ($schedule->location)
                                    <i class="bi bi-geo-alt me-1 text-muted"></i>
                                    {{ $schedule->location }}
                                    @else
                                    <span class="text-muted small">Não informada</span>
                                    @endif
                                </x-resource.table-cell>
                            </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot>

                    <x-slot name="mobile">
                        @foreach ($service->schedules as $schedule)
                        <x-resource.resource-mobile-item icon="calendar-event">
                            <x-resource.resource-mobile-header
                                :title="\Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y')"
                                :subtitle="\Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i')" />
                            @if ($schedule->location)
                            <x-resource.resource-mobile-field
                                label="Local"
                                :value="$schedule->location"
                                icon="geo-alt" />
                            @endif
                        </x-resource.resource-mobile-item>
                        @endforeach
                    </x-slot>
                </x-resource.resource-list-card>
                @endif
            </x-layout.grid-col>

            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack>
                    {{-- Informações do Cliente --}}
                    <x-resource.resource-list-card
                        title="Cliente"
                        icon="person-circle"
                        padding="p-4">
                        @if ($service->budget?->customer)
                        <div class="mb-4">
                            <x-resource.resource-info
                                :title="$service->budget->customer->commonData?->full_name"
                                :subtitle="$service->budget->customer->commonData?->company_name"
                                icon="person"
                                titleClass="fw-bold text-dark"
                                subtitleClass="text-muted small"
                                :href="route('provider.customers.show', $service->budget->customer)" />
                        </div>

                        <x-layout.v-stack gap="3" class="mb-4">
                            @if ($service->budget->customer->contact?->email)
                            <x-resource.resource-mobile-field
                                label="E-mail"
                                :value="$service->budget->customer->contact->email"
                                icon="envelope" />
                            @endif

                            @if ($service->budget->customer->contact?->phone)
                            <x-resource.resource-mobile-field
                                label="Telefone"
                                :value="$service->budget->customer->contact->phone"
                                icon="telephone" />
                            @endif

                            @if ($service->budget->customer->contact?->phone_business)
                            <x-resource.resource-mobile-field
                                label="WhatsApp"
                                icon="whatsapp">
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $service->budget->customer->contact->phone_business) }}" target="_blank" class="text-decoration-none fw-bold">
                                    {{ $service->budget->customer->contact->phone_business }}
                                </a>
                            </x-resource.resource-mobile-field>
                            @endif
                        </x-layout.v-stack>

                        <div class="d-grid">
                            <x-ui.button type="link" href="{{ route('provider.customers.show', $service->budget->customer) }}" variant="outline-primary" size="sm" icon="eye" label="Ver Perfil Completo" />
                        </div>
                        @else
                        <x-resource.empty-state title="Cliente não vinculado" description="Este serviço não possui um cliente vinculado via orçamento." icon="person-x" />
                        @endif
                    </x-resource.resource-list-card>

                    @php
                    $statusValue = $service->status->value;
                    $budgetStatus = $service->budget?->status->value;
                    $isApproved = $budgetStatus === 'approved';
                    @endphp

                    <x-resource.quick-actions title="Ações do Serviço" icon="lightning-charge" variant="secondary">
                        @if ($isApproved)
                        {{-- Status PENDING --}}
                        @if ($statusValue === 'pending')
                        <x-ui.button type="button" variant="info" icon="calendar-check" label="Agendar"
                            data-bs-toggle="modal" data-bs-target="#scheduleModal" />

                        <x-ui.button type="button" variant="warning" icon="tools" label="Preparar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="preparing"
                            data-title="Preparar Serviço"
                            data-message="Deseja marcar o serviço {{ $service->code }} como em preparação?" />

                        <x-ui.button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                            data-title="Colocar em Espera"
                            data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />
                        @endif

                        {{-- Status SCHEDULED --}}
                        @if ($statusValue === 'scheduled')
                        <x-ui.button type="button" variant="warning" icon="tools" label="Preparar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="preparing"
                            data-title="Preparar Serviço"
                            data-message="Deseja preparar o serviço {{ $service->code }}?" />

                        <x-ui.button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                            data-title="Colocar em Espera"
                            data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />

                        <x-ui.button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                            data-title="Não Realizado"
                            data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Status PREPARING --}}
                        @if ($statusValue === 'preparing')
                        <x-ui.button type="button" variant="success" icon="play-circle" label="Iniciar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="in_progress"
                            data-title="Iniciar Serviço"
                            data-message="Deseja iniciar a execução do serviço {{ $service->code }}?" />

                        <x-ui.button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                            data-title="Colocar em Espera"
                            data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />

                        <x-ui.button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                            data-title="Não Realizado"
                            data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Status IN_PROGRESS --}}
                        @if ($statusValue === 'in_progress')
                        <x-ui.button type="button" variant="success" icon="check-circle" label="Concluir"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="completed"
                            data-title="Concluir Serviço"
                            data-message="Deseja concluir o serviço {{ $service->code }}?" />

                        <x-ui.button type="button" variant="success" icon="check-circle" label="Parcial"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="partial"
                            data-title="Concluir Parcialmente"
                            data-message="Deseja concluir o serviço {{ $service->code }} parcialmente?" />

                        <x-ui.button type="button" variant="warning" icon="pause-circle" label="Em Espera"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="on_hold"
                            data-title="Colocar em Espera"
                            data-message="Deseja colocar o serviço {{ $service->code }} em espera?" />
                        @endif

                        {{-- Status SCHEDULING ou ON_HOLD --}}
                        @if ($statusValue === 'scheduling' || $statusValue === 'on_hold')
                        <x-ui.button type="button" variant="info" icon="calendar-check" label="Agendar"
                            data-bs-toggle="modal" data-bs-target="#scheduleModal" />

                        <x-ui.button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                            data-title="Não Realizado"
                            data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Botão Cancelar - Sempre disponível para status não finais --}}
                        @if (!in_array($statusValue, ['completed', 'partial', 'cancelled', 'not_performed', 'expired']))
                        <x-ui.button type="button" variant="outline-danger" icon="x-circle" label="Cancelar"
                            data-bs-toggle="modal" data-bs-target="#actionModal" data-status="cancelled"
                            data-title="Cancelar Serviço"
                            data-message="Deseja cancelar definitivamente o serviço {{ $service->code }}?" />
                        @endif
                        @else
                        <x-ui.alert type="warning" message="Aguardando aprovação do orçamento para prosseguir com o serviço." icon="exclamation-triangle" />
                        @endif

                        <hr class="my-3">

                        @if ($service->canBeEdited())
                        <x-ui.button type="link" href="{{ route('provider.services.edit', $service->code) }}"
                            variant="outline-primary" icon="pencil" label="Editar Serviço" />
                        @endif

                        @if ($service->budget)
                        <x-ui.button type="link" href="{{ route('provider.budgets.show', $service->budget->code) }}"
                            variant="outline-info" icon="receipt" label="Ver Orçamento" />
                        @endif

                        {{-- Botões de Fatura --}}
                        @if ($service->status->isFinished() || $statusValue === 'completed')
                        <x-ui.button type="link" href="{{ route('provider.invoices.create.from-service', $service->code) }}"
                            variant="outline-success" icon="receipt" label="Criar Fatura" />
                        @else
                        @if($service->serviceItems && $service->serviceItems->count() > 0)
                        <x-ui.button type="link" href="{{ route('provider.invoices.create.partial-from-service', $service->code) }}"
                            variant="outline-warning" icon="receipt" label="Criar Fatura Parcial" />
                        @endif
                        @endif

                        <x-ui.button type="button" variant="outline-secondary" onclick="window.print()" icon="printer" label="Imprimir" />
                    </x-resource.quick-actions>
                </x-layout.v-stack>
            </x-layout.grid-col>
        </x-layout.grid-row>

        {{-- Botões de Ação (Footer) --}}
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex gap-2">
                <x-ui.button
                    href="{{ url()->previous(route('provider.services.index')) }}"
                    variant="outline-secondary"
                    icon="bi bi-arrow-left">
                    Voltar
                </x-ui.button>
            </div>
            <small class="text-muted d-none d-md-block">
                Última atualização: {{ $service->updated_at?->format('d/m/Y H:i') }}
            </small>
            <div class="d-flex gap-2">
                @if ($service->canBeEdited())
                <x-ui.button
                    href="{{ route('provider.services.edit', $service->code) }}"
                    variant="primary"
                    icon="bi bi-pencil-fill">
                    Editar
                </x-ui.button>
                @endif
                <x-ui.button
                    type="button"
                    variant="outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteModal"
                    icon="bi bi-trash-fill">
                    Excluir
                </x-ui.button>
            </div>
        </div>

        {{-- Modal de Confirmação de Exclusão --}}
        <x-ui.modal id="deleteModal" title="Confirmar Exclusão">
            Tem certeza de que deseja excluir o serviço <strong>{{ $service->code }}</strong>?
            <br><small class="text-muted">Esta ação não pode ser desfeita.</small>

            <x-slot name="footer">
                <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <form action="{{ route('provider.services.destroy', $service->code) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" label="Excluir" />
                </form>
            </x-slot>
        </x-ui.modal>

        {{-- Modal Reutilizável para Ações de Status --}}
        <x-ui.modal id="actionModal" title="Ação de Status">
            <form id="actionForm" action="{{ route('provider.services.change-status', $service->code) }}" method="POST">
                @csrf
                <input type="hidden" name="status" id="actionStatusInput">
                <p id="actionModalMessage"></p>
                <p class="text-danger mt-2" id="actionModalWarning" style="display: none">
                    <strong>Atenção:</strong> Esta ação não pode ser desfeita.
                </p>
            </form>
            <x-slot name="footer">
                <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-ui.button type="submit" form="actionForm" variant="primary" id="actionConfirmButton" label="Confirmar" />
            </x-slot>
        </x-ui.modal>

        {{-- Modal para Agendamento --}}
        <x-ui.modal id="scheduleModal" title="Agendar Serviço">
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
                <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-ui.button type="submit" form="scheduleForm" variant="info" label="Agendar" />
            </x-slot>
        </x-ui.modal>
</x-layout.page-container>
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
