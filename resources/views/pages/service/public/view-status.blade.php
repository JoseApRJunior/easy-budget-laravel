@extends('layouts.guest')

@section('content')
<style>
    body {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        background-attachment: fixed;
        min-height: 100vh;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        box-shadow: 0 4px 20px 0 rgba(0, 0, 0, 0.03) !important;
        border-radius: 1.25rem !important;
    }

    .dark .glass-card {
        background: rgba(30, 41, 59, 0.7) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
    }

    /* Cores de texto mais neutras */
    .text-neutral-strong {
        color: #1e293b !important;
    }

    .text-neutral-soft {
        color: #64748b !important;
    }

    .dark .text-neutral-strong {
        color: #f1f5f9 !important;
    }

    .dark .text-neutral-soft {
        color: #94a3b8 !important;
    }

    .btn-neutral-outline {
        border-color: #e2e8f0;
        color: #475569;
        background: transparent;
    }

    .btn-neutral-outline:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #1e293b;
    }

    .main-container-card {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    .dark .main-container-card {
        background: transparent !important;
    }

    .section-glass {
        background: rgba(255, 255, 255, 0.25) !important;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
</style>
<x-layout.page-container :fluid="false">
    <x-ui.card class="mb-4 main-container-card">
        <x-slot name="header" class="bg-transparent border-0">
            <x-layout.h-stack justify="between" align="center">
                <x-resource.resource-info
                    title="Status do Serviço"
                    :subtitle="'Código: ' . $service->code"
                    icon="gear"
                    titleClass="text-muted fw-normal">
                    @if($service->public_expires_at)
                    <span class="ms-2 text-muted opacity-50">•</span>
                    <span class="ms-2 text-muted" title="Link válido até">
                        <i class="bi bi-clock-history me-1"></i>
                        {{ \Carbon\Carbon::parse($service->public_expires_at)->format('d/m/Y') }}
                    </span>
                    @endif
                </x-resource.resource-info>
                <x-ui.status-badge :item="$service" />
            </x-layout.h-stack>
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
            @endphp

            @if ($activeSchedule)
            <div class="mb-4">
                <div class="p-4 glass-card border-{{ $confirmedSchedule ? 'success' : 'warning' }} border-opacity-50 d-flex flex-column flex-md-row align-items-center" style="background: rgba({{ $confirmedSchedule ? '25, 135, 84' : '255, 193, 7' }}, 0.1) !important;">
                    <div class="rounded-circle bg-{{ $confirmedSchedule ? 'success' : 'warning' }} bg-opacity-25 p-3 mb-3 mb-md-0 me-md-4">
                        <i class="bi bi-calendar-check fs-2 text-{{ $confirmedSchedule ? 'success' : 'warning' }}"></i>
                    </div>
                    <div class="text-center text-md-start flex-grow-1">
                        <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold ls-wider mb-1" style="font-size: 0.75rem;">
                            {{ $confirmedSchedule ? 'Agendamento Confirmado' : 'Aguardando Confirmação de Agendamento' }}
                        </small>
                        <h3 class="text-gray-900 dark:text-white fw-bold mb-1">
                            {{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->format('d/m/Y') }}
                            <span class="text-muted fw-normal mx-2">|</span>
                            {{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->format('H:i') }} às {{ \Carbon\Carbon::parse($activeSchedule->end_date_time)->format('H:i') }}
                        </h3>
                        @if ($activeSchedule->notes)
                        <p class="mb-0 text-muted small mt-2">
                            <strong>Obs:</strong> {{ $activeSchedule->notes }}
                        </p>
                        @endif
                    </div>
                    @if($confirmedSchedule)
                    <div class="ms-md-auto mt-3 mt-md-0">
                        <span class="badge bg-success px-3 py-2 rounded-pill">
                            <i class="bi bi-check-circle-fill me-1"></i> Confirmado
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <x-resource.resource-header-section title="Detalhes do Serviço" icon="tools" class="border-0 shadow-none bg-transparent">
                @if ($service->description)
                <x-layout.grid-col md="12">
                    <div class="p-3 glass-card rounded mb-3">
                        <h6 class="small text-muted text-uppercase fw-bold mb-2">Descrição do Trabalho</h6>
                        <p class="mb-0 text-gray-700 dark:text-gray-300">{{ $service->description }}</p>
                    </div>
                </x-layout.grid-col>
                @endif

                <x-resource.resource-header-item
                    label="Categoria"
                    :value="$service->category?->name"
                    icon="tag"
                    iconVariant="info" />

                <x-resource.resource-header-item
                    label="Valor Total"
                    :value="\App\Helpers\CurrencyHelper::format($service->total)"
                    icon="cash-stack"
                    iconVariant="success" />

                <x-resource.resource-header-item
                    label="Desconto Aplicado"
                    :value="\App\Helpers\CurrencyHelper::format($service->discount)"
                    icon="percent"
                    iconVariant="warning" />

                @if ($service->due_date)
                <x-resource.resource-header-item
                    label="Prazo de Entrega"
                    :value="\Carbon\Carbon::parse($service->due_date)->format('d/m/Y')"
                    icon="calendar-event"
                    iconVariant="secondary" />
                @endif
            </x-resource.resource-header-section>

            @if (in_array($service->status->value, ['pending', 'scheduling', 'scheduled']))
            <x-ui.card class="glass-card border-warning shadow-none" style="background-color: rgba(255, 193, 7, 0.08) !important;">
                <h6 class="card-title text-warning mb-3 fw-bold">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Ação Necessária
                </h6>
                <p class="mb-3 small text-gray-700 dark:text-gray-300">Por favor, confirme ou informe o status deste serviço/agendamento:</p>

                <form method="POST" action="{{ route('services.public.choose-status') }}">
                    @csrf
                    <input type="hidden" name="service_code" value="{{ $service->code }}">
                    <input type="hidden" name="token" value="{{ $token }}">

                    <x-layout.grid-row align="end">
                        <x-layout.grid-col md="8">
                            <x-ui.form.select
                                name="service_status_id"
                                label="Sua Decisão"
                                required>
                                @php
                                $options = $service->status === \App\Enums\ServiceStatus::SCHEDULING
                                ? [\App\Enums\ServiceStatus::SCHEDULED, \App\Enums\ServiceStatus::REJECTED, \App\Enums\ServiceStatus::CANCELLED]
                                : [\App\Enums\ServiceStatus::APPROVED, \App\Enums\ServiceStatus::REJECTED, \App\Enums\ServiceStatus::CANCELLED];
                                @endphp
                                @foreach ($options as $status)
                                <option value="{{ $status->value }}"
                                    {{ old('service_status_id') == $status->value ? 'selected' : '' }}>
                                    @if ($status === \App\Enums\ServiceStatus::APPROVED)
                                    Aprovar Serviço
                                    @elseif ($status === \App\Enums\ServiceStatus::SCHEDULED)
                                    Confirmar Agendamento
                                    @else
                                    {{ $status->getDescription() }}
                                    @endif
                                </option>
                                @endforeach
                            </x-ui.form.select>
                        </x-layout.grid-col>
                        <x-layout.grid-col md="4">
                            <x-ui.button type="submit" variant="primary" icon="check-circle" label="Enviar Decisão" class="w-100" />
                        </x-layout.grid-col>
                    </x-layout.grid-row>
                </form>
            </x-ui.card>
            @endif

            <x-resource.resource-header-section title="Itens do Serviço" icon="list-check" class="border-0 shadow-none bg-transparent">
                <x-layout.grid-col md="12">
                    <div class="glass-card rounded-4 overflow-hidden p-0 border-0">
                        <x-resource.resource-table class="mb-0">
                            <x-slot name="thead">
                                <x-resource.table-row class="bg-transparent">
                                    <x-resource.table-cell header class="border-0 bg-transparent opacity-75">Item</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-center border-0 bg-transparent opacity-75">Qtd</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-end border-0 bg-transparent opacity-75">V. Unitário</x-resource.table-cell>
                                    <x-resource.table-cell header class="text-end border-0 bg-transparent opacity-75">Total</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>

                            @foreach ($service->serviceItems as $item)
                            <x-resource.table-row class="bg-transparent border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                <x-resource.table-cell class="border-0">
                                    <div class="fw-bold text-gray-900 dark:text-white">{{ $item->product?->name }}</div>
                                    @if ($item->notes)
                                    <small class="text-muted">{{ $item->notes }}</small>
                                    @endif
                                </x-resource.table-cell>
                                <x-resource.table-cell class="text-center border-0">{{ $item->quantity }}</x-resource.table-cell>
                                <x-resource.table-cell class="text-end border-0">{{ \App\Helpers\CurrencyHelper::format((float) $item->unit_value) }}</x-resource.table-cell>
                                <x-resource.table-cell class="text-end fw-bold border-0">{{ \App\Helpers\CurrencyHelper::format((float) $item->total_value) }}</x-resource.table-cell>
                            </x-resource.table-row>
                            @endforeach

                            <x-slot name="tfoot">
                                <x-resource.table-row class="bg-transparent border-top" style="border-color: rgba(0,0,0,0.1) !important;">
                                    <x-resource.table-cell colspan="3" class="text-end fw-bold border-0">Total dos Itens:</x-resource.table-cell>
                                    <x-resource.table-cell class="text-end fw-bold border-0 fs-5 text-primary">{{ \App\Helpers\CurrencyHelper::format((float) $service->serviceItems->sum('total_value')) }}</x-resource.table-cell>
                                </x-resource.table-row>
                            </x-slot>
                        </x-resource.resource-table>
                    </div>
                </x-layout.grid-col>
            </x-resource.resource-header-section>
        </x-layout.v-stack>

        <x-slot name="footer" class="bg-transparent border-0 mt-4">
            <x-layout.h-stack justify="between" align="center">
                <x-ui.button type="link" :href="route('services.public.print', ['code' => $service->code, 'token' => $token])" variant="outline-secondary" icon="printer" label="Imprimir" target="_blank" size="sm" class="glass-card" />

                <div class="text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    Link válido até:
                    <span class="fw-bold">{{ $service->public_expires_at ? \Carbon\Carbon::parse($service->public_expires_at)->format('d/m/Y H:i') : 'Indeterminado' }}</span>
                </div>
            </x-layout.h-stack>
        </x-slot>
    </x-ui.card>

    <x-layout.h-stack justify="center" class="mt-4 mb-5">
        <p class="text-muted small mb-0">&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
    </x-layout.h-stack>
</x-layout.page-container>
@endsection
