@extends('layouts.guest')

@section('content')
<x-layout.page-container :fluid="false">
    <x-ui.card class="mb-4 main-container-card">
        <x-slot name="header" class="bg-transparent border-0 p-0 mb-3">
            <div class="header-accent">
                <x-layout.h-stack justify="between" align="center">
                    <x-resource.resource-info
                        title="Status do Serviço"
                        :subtitle="'Código: ' . $service->code"
                        icon="gear"
                        titleClass="text-neutral-strong fw-bold"
                        subtitleClass="text-neutral-soft">
                        @if($service->public_expires_at)
                        <span class="ms-2 text-neutral-soft opacity-50">•</span>
                        <span class="ms-2 text-neutral-soft small" title="Link válido até">
                            <i class="bi bi-clock-history me-1"></i>
                            {{ \Carbon\Carbon::parse($service->public_expires_at)->format('d/m/Y') }}
                        </span>
                        @endif
                    </x-resource.resource-info>
                    <x-ui.status-badge :item="$service" />
                </x-layout.h-stack>
            </div>
        </x-slot>

        <x-layout.v-stack spacing="4">
            @if (session('success'))
            <x-ui.alert type="success" :message="session('success')" />
            @endif

            @if (session('error'))
            <x-ui.alert type="error" :message="session('error')" />
            @endif

            <x-layout.grid-row class="g-4 mb-4">
                {{-- Card do Prestador --}}
                <x-layout.grid-col size="col-md-4">
                    <x-ui.card class="glass-card h-100">
                        <x-layout.v-stack spacing="2">
                            <h6 class="card-title text-neutral-soft mb-2 small text-uppercase fw-bold border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                <i class="bi bi-shop me-2"></i>
                                Prestador de Serviço
                            </h6>
                            @php
                            $provider = $service->tenant?->provider;
                            $providerName = $provider?->businessData?->fantasy_name
                            ?? ($provider?->commonData ? $provider->commonData->first_name . ' ' . $provider->commonData->last_name : $service->tenant?->name);
                            @endphp
                            <h5 class="mb-1 text-neutral-strong fw-bold">
                                {{ $providerName }}
                            </h5>

                            @if($provider?->contact)
                            <p class="text-neutral-soft mb-0 small">
                                <i class="bi bi-envelope me-1"></i>
                                {{ $provider->contact->email_personal ?? $provider->contact->email_business }}
                            </p>
                            @php
                            $providerPhone = $provider->contact->phone_personal ?? $provider->contact->phone_business;
                            @endphp
                            @if ($providerPhone)
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a href="tel:{{ preg_replace('/\D/', '', $providerPhone) }}" class="btn btn-sm btn-neutral-outline rounded-pill px-3">
                                    <i class="bi bi-telephone me-1"></i> Ligar
                                </a>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $providerPhone) }}" target="_blank" class="btn btn-sm btn-success bg-opacity-75 rounded-pill px-3">
                                    <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                </a>
                            </div>
                            @endif
                            @endif

                            @if($provider?->address)
                            <div class="text-neutral-soft mt-3 pt-2 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                <div class="d-flex align-items-start gap-2 small mb-2">
                                    <i class="bi bi-geo-alt mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-neutral-strong">{{ $provider->address->address }}{{ $provider->address->address_number ? ', ' . $provider->address->address_number : '' }}</div>
                                        <div>{{ $provider->address->neighborhood }}</div>
                                        <div>{{ $provider->address->city }} - {{ $provider->address->state }}</div>
                                        <div class="opacity-75">CEP: {{ $provider->address->cep }}</div>
                                    </div>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($provider->address->address . ', ' . $provider->address->address_number . ' - ' . $provider->address->city . ' - ' . $provider->address->state) }}" target="_blank" class="btn btn-sm btn-neutral-outline rounded-pill px-3 w-100">
                                    <i class="bi bi-map me-1"></i> Ver no Mapa
                                </a>
                            </div>
                            @endif
                        </x-layout.v-stack>
                    </x-ui.card>
                </x-layout.grid-col>

                {{-- Card do Cliente --}}
                <x-layout.grid-col size="col-md-4">
                    <x-ui.card class="glass-card h-100">
                        <x-layout.v-stack spacing="2">
                            <h6 class="card-title text-neutral-soft mb-2 small text-uppercase fw-bold border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                <i class="bi bi-person-circle me-2"></i>
                                Cliente
                            </h6>
                            @if($service->budget?->customer?->commonData)
                            <h5 class="mb-1 text-neutral-strong fw-bold">
                                {{ $service->budget->customer->commonData->first_name }}
                                {{ $service->budget->customer->commonData->last_name }}
                            </h5>
                            @else
                            <h5 class="mb-1 text-neutral-soft">Cliente não identificado</h5>
                            @endif

                            @if($service->budget?->customer?->contact)
                            <p class="text-neutral-soft mb-0 small">
                                <i class="bi bi-envelope me-1"></i>
                                {{ $service->budget->customer->contact->email_personal ?? $service->budget->customer->contact->email_business }}
                            </p>
                            @php
                            $phone = $service->budget->customer->contact->phone_personal ?? $service->budget->customer->contact->phone_business;
                            @endphp
                            @if ($phone)
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a href="tel:{{ preg_replace('/\D/', '', $phone) }}" class="btn btn-sm btn-neutral-outline rounded-pill px-3">
                                    <i class="bi bi-telephone me-1"></i> Ligar
                                </a>
                                <a href="https://wa.me/{{ preg_replace('/\D/', '', $phone) }}" target="_blank" class="btn btn-sm btn-success bg-opacity-75 rounded-pill px-3">
                                    <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                </a>
                            </div>
                            @endif
                            @endif

                            @if($service->budget?->customer?->address)
                            <div class="text-neutral-soft mt-3 pt-2 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                <div class="d-flex align-items-start gap-2 small mb-2">
                                    <i class="bi bi-geo-alt mt-1"></i>
                                    <div>
                                        <div class="fw-bold text-neutral-strong">{{ $service->budget->customer->address->address }}{{ $service->budget->customer->address->address_number ? ', ' . $service->budget->customer->address->address_number : '' }}</div>
                                        <div>{{ $service->budget->customer->address->neighborhood }}</div>
                                        <div>{{ $service->budget->customer->address->city }} - {{ $service->budget->customer->address->state }}</div>
                                        <div class="opacity-75">CEP: {{ $service->budget->customer->address->cep }}</div>
                                    </div>
                                </div>
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($service->budget->customer->address->address . ', ' . $service->budget->customer->address->address_number . ' - ' . $service->budget->customer->address->city . ' - ' . $service->budget->customer->address->state) }}" target="_blank" class="btn btn-sm btn-neutral-outline rounded-pill px-3 w-100">
                                    <i class="bi bi-map me-1"></i> Ver no Mapa
                                </a>
                            </div>
                            @endif
                        </x-layout.v-stack>
                    </x-ui.card>
                </x-layout.grid-col>

                {{-- Card do Projeto / Orçamento --}}
                <x-layout.grid-col size="col-md-4">
                    <x-ui.card class="glass-card h-100">
                        <x-layout.v-stack spacing="2">
                            <h6 class="card-title text-neutral-soft mb-2 small text-uppercase fw-bold border-bottom pb-2" style="border-color: rgba(0,0,0,0.05) !important;">
                                <i class="bi bi-briefcase me-2"></i>
                                Resumo do Projeto
                            </h6>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-neutral-soft small">Orçamento:</span>
                                <span class="fw-bold text-neutral-strong">{{ $service->budget?->code }}</span>
                            </div>

                            <div class="mt-2 pt-2 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                <p class="mb-0 text-primary dark:text-primary-400 fs-5">
                                    <span class="text-neutral-soft small fw-normal">Total Geral:</span>
                                    <strong>{{ \App\Helpers\CurrencyHelper::format($service->budget?->total) }}</strong>
                                </p>
                                @if($service->budget?->payment_terms)
                                <p class="text-neutral-soft mb-0 mt-1 small">
                                    <i class="bi bi-credit-card me-1"></i> {{ $service->budget->payment_terms }}
                                </p>
                                @endif
                            </div>

                            @if($service->budget && $service->budget->services->count() > 1)
                            <div class="mt-3">
                                <h6 class="small text-neutral-soft text-uppercase fw-bold mb-2" style="font-size: 0.7rem;">
                                    Outros Serviços no Projeto
                                </h6>
                                <div class="d-flex flex-column gap-2">
                                    @foreach($service->budget->services as $relatedService)
                                    @if($relatedService->id !== $service->id)
                                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-white bg-opacity-30 border border-white border-opacity-40">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <i class="bi bi-gear small opacity-50 text-neutral-strong"></i>
                                            <div class="d-flex flex-column overflow-hidden">
                                                <span class="fw-bold text-neutral-strong" style="font-size: 0.75rem;">
                                                    {{ $relatedService->code }}
                                                </span>
                                                <span class="small text-truncate text-neutral-soft" title="{{ $relatedService->description ?? 'Serviço' }}">
                                                    {{ $relatedService->category?->name ?? 'Serviço' }}
                                                </span>
                                            </div>
                                        </div>
                                        <x-ui.status-badge :item="$relatedService" />
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </x-layout.v-stack>
                    </x-ui.card>
                </x-layout.grid-col>
            </x-layout.grid-row>

            {{-- Destaque para o Agendamento --}}
            @php
            $pendingSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::PENDING->value)->first();
            $confirmedSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::CONFIRMED->value)->first();
            $activeSchedule = $confirmedSchedule ?? $pendingSchedule;
            $isConfirmed = (bool) $confirmedSchedule;
            @endphp

            @if ($activeSchedule)
            <div class="mb-4">
                <div class="glass-card overflow-hidden border-0 shadow-sm">
                    <div class="d-flex flex-column flex-md-row">
                        {{-- Indicador Lateral --}}
                        <div class="bg-{{ $isConfirmed ? 'success' : 'warning' }} opacity-50" style="width: 6px;"></div>

                        <div class="p-4 d-flex flex-column flex-md-row align-items-center flex-grow-1" style="background: var(--contrast-overlay);">
                            <div class="rounded-4 bg-{{ $isConfirmed ? 'success' : 'warning' }} bg-opacity-10 p-3 mb-3 mb-md-0 me-md-4 border border-{{ $isConfirmed ? 'success' : 'warning' }} border-opacity-25 shadow-sm">
                                <i class="bi bi-calendar2-check fs-1 text-{{ $isConfirmed ? 'success' : 'warning' }}"></i>
                            </div>

                            <div class="text-center text-md-start flex-grow-1">
                                <div class="d-flex align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                                    <span class="text-neutral-soft text-uppercase fw-bold ls-wider small" style="font-size: 0.65rem; letter-spacing: 0.05em;">
                                        {{ $isConfirmed ? 'Agendamento Confirmado' : 'Aguardando Sua Confirmação' }}
                                    </span>
                                    @if(!$isConfirmed)
                                    <span class="spinner-grow spinner-grow-sm text-warning opacity-75" role="status"></span>
                                    @endif
                                </div>

                                <h3 class="text-neutral-strong fw-bold mb-0 display-6 d-flex align-items-center justify-content-center justify-content-md-start flex-wrap gap-2" style="font-size: 1.85rem;">
                                    <span>{{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->translatedFormat('d \d\e F') }}</span>
                                    <span class="text-neutral-soft fw-light opacity-50 d-none d-md-inline">|</span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="text-neutral-strong">{{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->format('H:i') }}</span>
                                        <span class="text-neutral-soft fw-light small opacity-75" style="font-size: 1rem;">às</span>
                                        <span class="text-neutral-strong">{{ \Carbon\Carbon::parse($activeSchedule->end_date_time)->format('H:i') }}</span>
                                    </div>
                                </h3>

                                @if ($activeSchedule->notes)
                                <div class="mt-3 p-2 px-3 rounded-3 d-inline-block shadow-sm" style="background: rgba(255, 255, 255, 0.3); border: 1px solid rgba(255, 255, 255, 0.4); backdrop-filter: blur(4px);">
                                    <p class="mb-0 text-neutral-strong small">
                                        <i class="bi bi-info-circle text-neutral-soft me-1"></i>
                                        <strong class="text-neutral-soft fw-bold">Obs:</strong> {{ $activeSchedule->notes }}
                                    </p>
                                </div>
                                @endif
                            </div>

                            <div class="ms-md-auto mt-4 mt-md-0">
                                @if($isConfirmed)
                                <div class="px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center bg-success bg-opacity-10 border border-success border-opacity-20" style="color: var(--text-success); font-size: 0.85rem; font-weight: 700;">
                                    <i class="bi bi-check-circle-fill me-2"></i> CONFIRMADO
                                </div>
                                @else
                                <div class="px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center bg-warning bg-opacity-10 border border-warning border-opacity-20" style="color: var(--text-warning); font-size: 0.85rem; font-weight: 700;">
                                    <i class="bi bi-hourglass-split me-2"></i> PENDENTE
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-4">
                <x-layout.grid-row class="g-3">
                    @if ($service->description)
                    <x-layout.grid-col md="12">
                        <div class="p-3 glass-card rounded-4 mb-1 border-0 shadow-sm">
                            <h6 class="text-neutral-soft text-uppercase fw-bold mb-2" style="font-size: 0.7rem; letter-spacing: 0.05em;">Descrição do Trabalho</h6>
                            <p class="mb-0 text-neutral-strong">{{ $service->description }}</p>
                        </div>
                    </x-layout.grid-col>
                    @endif

                    <x-dashboard.stat-card
                        title="Categoria"
                        :value="$service->category?->name ?? 'N/A'"
                        icon="tag"
                        variant="info" />

                    <x-dashboard.stat-card
                        title="Valor Total"
                        :value="\App\Helpers\CurrencyHelper::format($service->total)"
                        icon="cash-stack"
                        variant="success" />

                    <x-dashboard.stat-card
                        title="Desconto"
                        :value="\App\Helpers\CurrencyHelper::format($service->discount)"
                        icon="percent"
                        variant="warning" />

                    @if ($service->due_date)
                    <x-dashboard.stat-card
                        title="Prazo de Entrega"
                        :value="\Carbon\Carbon::parse($service->due_date)->format('d/m/Y')"
                        icon="calendar-event"
                        variant="secondary" />
                    @endif
                </x-layout.grid-row>
            </div>

            @if (in_array($service->status->value, ['pending', 'scheduling']))
            <div class="mb-4">
                <div class="glass-card border-0 shadow-sm overflow-hidden">
                    <div class="bg-warning opacity-50" style="height: 4px;"></div>
                    <div class="p-4" style="background: var(--contrast-overlay);">
                        <h6 class="text-neutral-strong mb-3 fw-bold d-flex align-items-center">
                            <span class="p-2 bg-warning bg-opacity-10 rounded-circle me-2 border border-warning border-opacity-25">
                                <i class="bi bi-exclamation-circle-fill text-warning"></i>
                            </span>
                            @if($service->status === \App\Enums\ServiceStatus::SCHEDULING)
                            Confirmação de Agendamento
                            @else
                            Aprovação do Orçamento
                            @endif
                        </h6>
                        <p class="mb-4 text-neutral-soft small">
                            @if($service->status === \App\Enums\ServiceStatus::SCHEDULING)
                            O orçamento já foi aprovado! Agora, por favor, <strong>confirme o agendamento</strong> proposto abaixo ou solicite uma alteração:
                            @else
                            Por favor, revise os detalhes abaixo e <strong>aprove o orçamento</strong> para que possamos iniciar o serviço:
                            @endif
                        </p>

                        <form method="POST" action="{{ route('services.public.choose-status') }}">
                            @csrf
                            <input type="hidden" name="service_code" value="{{ $service->code }}">
                            <input type="hidden" name="token" value="{{ $token }}">

                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <x-ui.form.select
                                        name="service_status_id"
                                        label="SUA DECISÃO"
                                        labelClass="text-neutral-soft fw-bold small mb-2"
                                        required>
                                        @php
                                        // Como o orçamento já foi aprovado, focamos apenas no agendamento
                                        $options = [
                                        \App\Enums\ServiceStatus::SCHEDULED,
                                        \App\Enums\ServiceStatus::REJECTED,
                                        \App\Enums\ServiceStatus::CANCELLED
                                        ];
                                        @endphp
                                        @foreach ($options as $status)
                                        <option value="{{ $status->value }}"
                                            {{ old('service_status_id') == $status->value ? 'selected' : '' }}>
                                            @if ($status === \App\Enums\ServiceStatus::SCHEDULED)
                                            Confirmar Agendamento Proposto
                                            @elseif ($status === \App\Enums\ServiceStatus::REJECTED)
                                            Solicitar Alteração de Data/Horário
                                            @else
                                            Cancelar Agendamento / Serviço
                                            @endif
                                        </option>
                                        @endforeach
                                    </x-ui.form.select>
                                </div>
                                <div class="col-md-4">
                                    <x-ui.button type="submit" variant="primary" icon="check-circle" label="Enviar Decisão" class="w-100 py-2 fw-bold shadow-sm" style="height: 42px; background: var(--primary-color); border: none;" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif

            <div class="mt-4">
                <x-resource.resource-list-card title="Itens do Serviço" icon="list-check">
                    <x-resource.resource-table class="mb-0">
                        <x-slot name="thead">
                            <tr>
                                <th class="p-3">Item</th>
                                <th class="text-center p-3">Qtd</th>
                                <th class="text-end p-3">V. Unitário</th>
                                <th class="text-end p-3">Total</th>
                            </tr>
                        </x-slot>

                        @forelse ($service->serviceItems as $item)
                        <tr wire:key="item-{{ $item->id }}">
                            <td class="p-3">
                                <div class="fw-bold text-neutral-strong">{{ $item->product?->name }}</div>
                                @if ($item->notes)
                                <small class="text-neutral-soft opacity-75">{{ $item->notes }}</small>
                                @endif
                            </td>
                            <td class="text-center p-3 text-neutral-strong">{{ (int) $item->quantity }}</td>
                            <td class="text-end p-3 text-neutral-strong">{{ \App\Helpers\CurrencyHelper::format((float) $item->unit_value) }}</td>
                            <td class="text-end fw-bold p-3 text-neutral-strong">{{ \App\Helpers\CurrencyHelper::format((float) ($item->unit_value * $item->quantity)) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-neutral-soft border-0">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Nenhum item registrado para este serviço.
                            </td>
                        </tr>
                        @endforelse

                        @if($service->serviceItems->isNotEmpty())
                        <x-slot name="tfoot">
                            <tr class="bg-light bg-opacity-50">
                                <td colspan="3" class="text-end fw-bold p-3 text-neutral-strong">Total dos Itens:</td>
                                <td class="text-end fw-bold p-3 fs-5 text-primary">
                                    {{ \App\Helpers\CurrencyHelper::format((float) $service->serviceItems->sum(fn($item) => $item->unit_value * $item->quantity)) }}
                                </td>
                            </tr>
                        </x-slot>
                        @endif
                    </x-resource.resource-table>
                </x-resource.resource-list-card>
            </div>
        </x-layout.v-stack>

        <x-slot name="footer" class="bg-transparent border-0 mt-4">
            <x-layout.grid-row class="mt-4 mb-2">
                <x-layout.grid-col md="6" class="text-center text-md-start">
                    <a href="{{ route('services.public.print', ['code' => $service->code, 'token' => $token]) }}" target="_blank" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-printer me-2"></i> Imprimir Orçamento
                    </a>
                </x-layout.grid-col>
                <x-layout.grid-col md="6" class="text-center text-md-end mt-3 mt-md-0">
                    @if($service->public_expires_at && \Carbon\Carbon::parse($service->public_expires_at)->isFuture())
                    <span class="badge bg-success text-white rounded-pill px-3 py-2 shadow-sm">
                        <i class="bi bi-shield-check me-1"></i> Link de Acesso Seguro e Válido
                    </span>
                    @endif
                </x-layout.grid-col>
            </x-layout.grid-row>
        </x-slot>
    </x-ui.card>
</x-layout.page-container>
@endsection
