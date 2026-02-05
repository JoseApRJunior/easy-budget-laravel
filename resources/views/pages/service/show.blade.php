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

        @php
        $statusValue = $service->status->value;
        $budgetStatus = $service->budget?->status->value;
        $isApproved = $budgetStatus === 'approved';
        $pendingSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::PENDING->value)->first();
        @endphp

        {{-- Informações do Serviço (Padrão Budget) --}}
        <x-resource.resource-header-card>
            {{-- Primeira Linha: Informações Principais --}}
            <x-resource.resource-header-item
                label="Código do Serviço"
                :value="$service->code" />

            <x-resource.resource-header-item
                label="Status Atual">
                <x-ui.status-description :item="$service" statusField="status" :useColor="false" class="text-dark fw-medium" />
            </x-resource.resource-header-item>

            <x-resource.resource-header-item
                label="Total do Serviço"
                :value="'R$ ' . \App\Helpers\CurrencyHelper::format($service->total)" />

            <x-resource.resource-header-divider />

            {{-- Segunda Linha: Dados do Cliente --}}
            <x-resource.resource-header-section title="Dados do Cliente" icon="people">
                @if ($service->customer)
                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Nome/Razão Social"
                        :subtitle="$service->customer->name"
                        icon="person-badge"
                        :href="route('provider.customers.show', $service->customer->id)"
                        class="small" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    @php
                    $docLabel = $service->customer->commonData->cnpj ? 'CNPJ' : 'CPF';
                    $docValue = $service->customer->commonData->cnpj
                    ? \App\Helpers\DocumentHelper::formatCnpj($service->customer->commonData->cnpj)
                    : ($service->customer->commonData->cpf ? \App\Helpers\DocumentHelper::formatCpf($service->customer->commonData->cpf) : '-');
                    @endphp
                    <x-resource.resource-info
                        :title="$docLabel"
                        :subtitle="$docValue"
                        icon="card-text"
                        class="small" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    <x-resource.resource-info
                        title="Contato Principal"
                        :subtitle="$service->customer?->contact?->email_personal ?? \App\Helpers\MaskHelper::formatPhone($service->customer?->contact?->phone_personal ?? '') ?: '-'"
                        icon="envelope"
                        class="small" />
                </x-layout.grid-col>

                <x-layout.grid-col size="col-md-3">
                    @php
                    $address = $service->customer?->address;
                    $addressText = $address
                    ? "{$address->address}, {$address->address_number} - {$address->neighborhood}, {$address->city}/{$address->state}"
                    : 'Não informado';
                    @endphp
                    <x-resource.resource-info
                        title="Endereço"
                        :subtitle="$addressText"
                        icon="geo-alt"
                        class="small" />
                </x-layout.grid-col>
                @else
                <x-layout.grid-col size="col-12">
                    <p class="text-muted mb-0 italic">Dados do cliente não vinculados a este serviço.</p>
                </x-layout.grid-col>
                @endif
            </x-resource.resource-header-section>

            <x-resource.resource-header-divider />

            {{-- Terceira Linha: Vínculos e Detalhes --}}
            <x-resource.resource-header-section title="Vínculos e Detalhes" icon="link-45deg">
                <div class="col-md-4">
                    <x-resource.resource-info
                        title="Categoria"
                        :subtitle="$service->category?->name ?? 'Não definida'"
                        icon="tag"
                        class="small" />
                </div>
                <div class="col-md-4">
                    <x-resource.resource-info
                        title="Orçamento Vinculado"
                        :subtitle="$service->budget?->code ?? 'N/A'"
                        icon="file-earmark-text"
                        :href="route('provider.budgets.show', $service->budget?->code)"
                        class="small fw-bold" />
                </div>
                @if ($service->due_date)
                <div class="col-md-4">
                    <x-resource.resource-info
                        title="Prazo de Entrega"
                        :subtitle="\Carbon\Carbon::parse($service->due_date)->format('d/m/Y')"
                        icon="calendar-event"
                        class="small" />
                </div>
                @endif
            </x-resource.resource-header-section>

            <x-resource.resource-header-divider />

            {{-- Terceira Linha: Resumo Financeiro e Datas --}}
            <div class="col-md-8 mt-2">
                <div class="row g-3">
                    <div class="col-md-4">
                        <x-resource.resource-info
                            title="Criado em"
                            :subtitle="$service->created_at->format('d/m/Y H:i')"
                            icon="calendar-plus"
                            class="small" />
                    </div>
                    <div class="col-md-4">
                        <x-resource.resource-info
                            title="Última Atualização"
                            :subtitle="$service->updated_at?->format('d/m/Y H:i')"
                            icon="clock-history"
                            class="small" />
                    </div>
                </div>
            </div>

            <div class="col-md-4 mt-2">
                <div class="bg-light p-3 rounded-3 border border-light-subtle h-100 d-flex flex-column justify-content-center">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted small">Desconto:</span>
                        <span class="fw-semibold small text-danger">R$ {{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top border-secondary-subtle">
                        <span class="fw-bold">Total Final:</span>
                        <span class="fw-bold text-success">R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                    </div>
                </div>
            </div>

            {{-- Descrição e Observações --}}
            @if ($service->description)
            <x-resource.resource-header-divider />
            <div class="col-12 mt-2">
                <label class="text-muted small d-block mb-1 fw-bold text-uppercase">Descrição do Serviço</label>
                <p class="mb-0 text-dark small" style="white-space: pre-wrap;">{{ $service->description }}</p>
            </div>
            @endif
        </x-resource.resource-header-card>

        <x-layout.grid-row>
            <x-layout.grid-col size="col-lg-8">
                {{-- Itens do Serviço --}}
                @if ($service->serviceItems && $service->serviceItems->count() > 0)
                <x-resource.resource-list-card
                    title="Itens do Serviço"
                    icon="list-ul"
                    :total="$service->serviceItems->count()"
                    class="mb-4">

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
                        <div class="p-3">
                            @foreach ($service->serviceItems as $item)
                            <div class="mb-3 pb-3 {{ !$loop->last ? 'border-bottom border-light-subtle' : '' }}">
                                <div class="fw-bold text-dark mb-1">{{ $item->product?->name ?? 'Produto não encontrado' }}</div>
                                <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
                                    <span>Qtd: <span class="text-dark fw-semibold">{{ \App\Helpers\CurrencyHelper::format($item->quantity, false) }}</span></span>
                                    <span>Unit: <span class="text-dark fw-semibold">{{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</span></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="small text-muted">Subtotal:</span>
                                    <span class="text-success fw-bold h6 mb-0">{{ \App\Helpers\CurrencyHelper::format($item->total) }}</span>
                                </div>
                            </div>
                            @endforeach

                            <div class="bg-light p-3 rounded-3 mt-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-muted small">TOTAL DOS ITENS</span>
                                    <span class="h5 mb-0 text-success fw-bold">{{ \App\Helpers\CurrencyHelper::format($service->serviceItems->sum('total')) }}</span>
                                </div>
                            </div>
                        </div>
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
                                    <x-resource.table-cell header class="text-center">Status</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-end">Ações</x-resource.table-cell>
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
                                <x-resource.table-cell class="text-center">
                                    <x-ui.status-badge :item="$schedule" statusField="status" />
                                </x-resource.table-cell>
                                <x-resource.table-cell class="text-end">
                                    <x-ui.button type="link" :href="route('provider.schedules.show', $schedule->id)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                </x-resource.table-cell>
                            </x-resource.table-row>
                            @endforeach
                        </x-resource.resource-table>
                    </x-slot>

                    <x-slot name="mobile">
                        <div class="p-3">
                            @foreach ($service->schedules as $schedule)
                            <div class="mb-4 {{ !$loop->last ? 'border-bottom border-light-subtle pb-4' : '' }}">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-bold text-dark h6 mb-0">{{ \Carbon\Carbon::parse($schedule->start_date_time)->format('d/m/Y') }}</div>
                                            <div class="small text-muted">
                                                {{ \Carbon\Carbon::parse($schedule->start_date_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($schedule->end_date_time)->format('H:i') }}
                                            </div>
                                        </div>
                                        <x-ui.status-badge :item="$schedule" statusField="status" />
                                    </div>

                                    @if ($schedule->location)
                                    <div class="bg-light p-2 rounded small mb-2">
                                        <i class="bi bi-geo-alt text-primary me-1"></i>
                                        <span class="text-dark">{{ $schedule->location }}</span>
                                    </div>
                                    @endif

                                    <div class="d-flex justify-content-end">
                                        <x-ui.button type="link" :href="route('provider.schedules.show', $schedule->id)" variant="light" size="sm" icon="eye" label="Ver Detalhes" />
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </x-slot>
                </x-resource.resource-list-card>
                @endif
            </x-layout.grid-col>

            <x-layout.grid-col size="col-lg-4">
                <x-layout.v-stack>
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
                            @php
                                $hasConfirmedSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::CONFIRMED->value)->exists();
                            @endphp

                            @if ($pendingSchedule)
                                <x-ui.button type="button" variant="warning" icon="hourglass-split" label="Aguardando Aprovação"
                                    data-bs-toggle="modal" data-bs-target="#pendingScheduleModal" />
                            @elseif (!$hasConfirmedSchedule)
                                <x-ui.button type="button" variant="info" icon="calendar-check" label="Agendar"
                                    data-bs-toggle="modal" data-bs-target="#scheduleModal" />
                            @endif

                            <x-ui.button type="button" variant="danger" icon="x-circle" label="Não Realizar"
                                data-bs-toggle="modal" data-bs-target="#actionModal" data-status="not_performed"
                                data-title="Não Realizado"
                                data-message="Deseja marcar o serviço {{ $service->code }} como não realizado?" />
                        @endif

                        {{-- Botão Cancelar - Sempre disponível para status não finais --}}
                        @if (!in_array($statusValue, ['completed', 'partial', 'cancelled', 'not_performed', 'expired']))
                        <x-ui.button type="button" variant="danger" icon="x-circle" label="Cancelar"
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
                            variant="primary" icon="pencil" label="Editar Serviço" />
                        @endif

                        @if ($service->budget)
                        <x-ui.button type="link" href="{{ route('provider.budgets.show', $service->budget->code) }}"
                            variant="info" icon="receipt" label="Ver Orçamento" />
                        @endif

                        {{-- Botões de Fatura --}}
                        @if ($service->status->isFinished() || $statusValue === 'completed')
                        <x-ui.button type="link" href="{{ route('provider.invoices.create.from-service', $service->code) }}"
                            variant="success" icon="receipt" label="Criar Fatura" />
                        @else
                        @if($service->serviceItems && $service->serviceItems->count() > 0)
                        <x-ui.button type="link" href="{{ route('provider.invoices.create.partial-from-service', $service->code) }}"
                            variant="warning" icon="receipt" label="Criar Fatura Parcial" />
                        @endif
                        @endif

                        <x-ui.button type="button" variant="secondary" onclick="window.print()" icon="printer" label="Imprimir" />
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
        <x-ui.modal id="deleteModal" title="Confirmar Exclusão" icon="trash-fill">
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
        <x-ui.modal id="actionModal" title="Ação de Status" icon="gear-fill">
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
        <x-ui.modal id="scheduleModal" title="Agendar Serviço" icon="calendar-check">
            <x-ui.form id="scheduleForm" action="{{ route('provider.schedules.store', $service->code) }}" method="POST">
                <input type="hidden" name="service_id" value="{{ $service->id }}">
                <input type="hidden" name="provider_id" value="{{ auth()->id() }}">
                <input type="hidden" name="customer_id" value="{{ $service->budget?->customer_id }}">
                <input type="hidden" name="service_type" value="{{ $service->category?->name ?? 'Serviço' }}">

                <x-layout.grid-row g="3">
                    <x-layout.grid-col size="col-md-5">
                        <x-ui.form.input
                            type="date"
                            name="service_date"
                            label="Data do Serviço"
                            required
                            :value="$service->due_date ? \Carbon\Carbon::parse($service->due_date)->format('Y-m-d') : date('Y-m-d')" />
                    </x-layout.grid-col>

                    <x-layout.grid-col size="col-md-3">
                        <x-ui.form.input
                            type="time"
                            name="service_time"
                            label="Horário"
                            required
                            value="08:00" />
                    </x-layout.grid-col>

                    <x-layout.grid-col size="col-md-4">
                        <x-ui.form.input
                            type="number"
                            name="service_duration"
                            label="Duração (min)"
                            required
                            value="60"
                            min="30"
                            max="480" />
                    </x-layout.grid-col>
                </x-layout.grid-row>

                @php
                $customerAddress = $service->budget?->customer?->address;
                @endphp

                <hr class="my-4">
                <h6 class="fw-bold mb-3 text-uppercase small text-muted">Local de Execução</h6>

                <x-layout.grid-row g="3">
                    <x-layout.grid-col size="col-md-6">
                        <x-ui.form.input
                            id="cep"
                            name="cep"
                            label="CEP"
                            data-cep-lookup
                            data-mask="00000-000"
                            placeholder="00000-000"
                            :value="$customerAddress?->cep" />
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-6">
                        <x-ui.form.input
                            id="address_number"
                            name="address_number"
                            label="Número"
                            placeholder="Nº"
                            :value="$customerAddress?->address_number" />
                    </x-layout.grid-col>
                </x-layout.grid-row>

                <div class="mt-3">
                    <x-ui.form.input
                        id="address"
                        name="address"
                        label="Endereço"
                        placeholder="Rua, Av, etc."
                        :value="$customerAddress?->address" />
                </div>

                <x-layout.grid-row g="3" class="mt-1">
                    <x-layout.grid-col size="col-md-5">
                        <x-ui.form.input
                            id="neighborhood"
                            name="neighborhood"
                            label="Bairro"
                            placeholder="Bairro"
                            :value="$customerAddress?->neighborhood" />
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-5">
                        <x-ui.form.input
                            id="city"
                            name="city"
                            label="Cidade"
                            placeholder="Cidade"
                            :value="$customerAddress?->city" />
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-2">
                        <x-ui.form.input
                            id="state"
                            name="state"
                            label="UF"
                            maxlength="2"
                            placeholder="UF"
                            :value="$customerAddress?->state" />
                    </x-layout.grid-col>
                </x-layout.grid-row>

                <input type="hidden" name="location" id="schedule_location_hidden">

                <div class="mt-3">
                    <x-ui.form.textarea
                        name="notes"
                        label="Observações"
                        placeholder="Observações adicionais para este agendamento..."
                        rows="3" />
                </div>
            </x-ui.form>
            <x-slot name="footer">
                <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                <x-ui.button type="submit" form="scheduleForm" variant="info" label="Agendar Serviço" />
            </x-slot>
        </x-ui.modal>

        @if ($pendingSchedule)
        {{-- Modal para Visualizar Agendamento Pendente --}}
        <x-ui.modal id="pendingScheduleModal" title="Agendamento Pendente" icon="hourglass-split">
            <x-ui.alert type="warning" class="mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-hourglass-split fs-4 me-3"></i>
                    <div>
                        <strong class="d-block">Aguardando Confirmação do Cliente</strong>
                        <span class="small">O cliente recebeu a proposta abaixo por e-mail e precisa aprovar para confirmar.</span>
                    </div>
                </div>
            </x-ui.alert>

            <x-ui.card class="bg-light border-0 shadow-none mb-4">
                <x-layout.v-stack gap="3">
                    <x-resource.resource-mobile-field
                        label="Data Proposta"
                        :value="\Carbon\Carbon::parse($pendingSchedule->start_date_time)->format('d/m/Y')"
                        icon="calendar-event" />

                    <x-resource.resource-mobile-field
                        label="Horário"
                        :value="\Carbon\Carbon::parse($pendingSchedule->start_date_time)->format('H:i') . ' - ' . \Carbon\Carbon::parse($pendingSchedule->end_date_time)->format('H:i')"
                        icon="clock" />

                    @if($pendingSchedule->location)
                    <x-resource.resource-mobile-field
                        label="Localização"
                        :value="$pendingSchedule->location"
                        icon="geo-alt" />
                    @endif
                </x-layout.v-stack>
            </x-ui.card>

            <p class="text-muted small text-center px-3">
                Deseja propor uma nova data? Cancele o agendamento atual primeiro.
            </p>

            <x-slot name="footer">
                <x-ui.button type="button" variant="secondary" data-bs-dismiss="modal" label="Fechar" />
                <x-ui.form action="{{ route('provider.schedules.cancel', $pendingSchedule->id) }}" method="POST" class="d-inline">
                    <x-ui.button type="submit" variant="outline-danger" icon="x-circle" label="Cancelar Agendamento" />
                </x-ui.form>
            </x-slot>
        </x-ui.modal>
        @endif
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
        // Máscaras para o modal de agendamento
        if (typeof VanillaMask !== 'undefined') {
            const cepInput = document.getElementById('cep');
            if (cepInput) {
                new VanillaMask('cep', 'cep');
            }
        }

        // Concatenação de endereço no modal de agendamento
        const scheduleForm = document.getElementById('scheduleForm');
        if (scheduleForm) {
            scheduleForm.addEventListener('submit', function(e) {
                const cep = document.getElementById('cep')?.value || '';
                const address = document.getElementById('address')?.value || '';
                const number = document.getElementById('address_number')?.value || '';
                const neighborhood = document.getElementById('neighborhood')?.value || '';
                const city = document.getElementById('city')?.value || '';
                const state = document.getElementById('state')?.value || '';

                let fullLocation = '';
                if (address) fullLocation += address;
                if (number) fullLocation += `, ${number}`;
                if (neighborhood) fullLocation += ` - ${neighborhood}`;
                if (city) fullLocation += ` - ${city}`;
                if (state) fullLocation += `/${state}`;
                if (cep) fullLocation += ` (CEP: ${cep})`;

                document.getElementById('schedule_location_hidden').value = fullLocation.trim();
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
