@extends('layouts.guest')

@section('content')
<x-layout.page-container>
    <x-ui.card class="mb-4">
        <x-slot name="header">
            <x-layout.h-stack justify="between" align="center">
                <x-layout.v-stack spacing="0">
                    <h5 class="mb-0 text-muted">Status do Serviço</h5>
                    <small class="text-muted">Código: {{ $service->code }}</small>
                </x-layout.v-stack>
                <x-ui.status-badge :item="$service" />
            </x-layout.h-stack>
        </x-slot>

        @if (session('success'))
        <x-ui.alert type="success" :message="session('success')" class="mb-4" />
        @endif

        @if (session('error'))
        <x-ui.alert type="error" :message="session('error')" class="mb-4" />
        @endif

        <x-layout.grid-row class="mb-4">
            <x-layout.grid-col md="6">
                <x-ui.card class="bg-light dark:bg-gray-800 border-0 h-100">
                    <x-layout.v-stack spacing="2">
                        <h6 class="card-title text-muted dark:text-gray-400 mb-2 small text-uppercase fw-bold">
                            <i class="bi bi-person-circle me-2"></i>
                            Cliente
                        </h6>
                        @if($service->budget?->customer?->commonData)
                        <h5 class="mb-1 text-gray-900 dark:text-white">
                            {{ $service->budget->customer->commonData->first_name }}
                            {{ $service->budget->customer->commonData->last_name }}
                        </h5>
                        @else
                        <h5 class="mb-1 text-muted">Cliente não identificado</h5>
                        @endif

                        @if($service->budget?->customer?->contact)
                        <p class="text-muted dark:text-gray-400 mb-0 small">{{ $service->budget->customer->contact->email_personal ?? $service->budget->customer->contact->email_business }}</p>
                        @if ($service->budget->customer->contact->phone_personal || $service->budget->customer->contact->phone_business)
                        <p class="text-muted dark:text-gray-400 mb-0 small">
                            {{ $service->budget->customer->contact->phone_personal ?? $service->budget->customer->contact->phone_business }}
                        </p>
                        @endif
                        @endif
                    </x-layout.v-stack>
                </x-ui.card>
            </x-layout.grid-col>

            <x-layout.grid-col md="6">
                <x-ui.card class="bg-light dark:bg-gray-800 border-0 h-100">
                    <x-layout.v-stack spacing="2">
                        <h6 class="card-title text-muted dark:text-gray-400 mb-2 small text-uppercase fw-bold">
                            <i class="bi bi-receipt me-2"></i>
                            Orçamento
                        </h6>
                        <h5 class="mb-1 text-gray-900 dark:text-white">{{ $service->budget?->code }}</h5>
                        <p class="text-muted dark:text-gray-400 mb-0 small">{{ $service->budget?->description }}</p>
                        <p class="mb-0 mt-2 text-gray-900 dark:text-white">
                            <strong>Total: {{ \App\Helpers\CurrencyHelper::format($service->budget?->total) }}</strong>
                        </p>
                    </x-layout.v-stack>
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        <x-layout.v-stack spacing="4" class="mb-4">
            <div>
                <h6 class="mb-3 small text-uppercase fw-bold text-muted dark:text-gray-400">
                    <i class="bi bi-tools me-2"></i>
                    Detalhes do Serviço
                </h6>

                @if ($service->description)
                <p class="mb-3 text-gray-700 dark:text-gray-300">{{ $service->description }}</p>
                @endif

                <x-layout.grid-row class="g-3">
                    @php
                    $pendingSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::PENDING->value)->first();
                    $confirmedSchedule = $service->schedules()->where('status', \App\Enums\ScheduleStatus::CONFIRMED->value)->first();
                    $activeSchedule = $confirmedSchedule ?? $pendingSchedule;
                    @endphp

                    @if ($activeSchedule)
                    <x-layout.grid-col md="12" class="mb-2">
                        <div class="p-3 bg-{{ $confirmedSchedule ? 'success' : 'warning' }} bg-opacity-10 border border-{{ $confirmedSchedule ? 'success' : 'warning' }} border-opacity-25 rounded d-flex align-items-center">
                            <i class="bi bi-calendar-check fs-4 text-{{ $confirmedSchedule ? 'success' : 'warning' }} me-3"></i>
                            <x-layout.v-stack spacing="1">
                                <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold" style="font-size: 0.7rem;">
                                    {{ $confirmedSchedule ? 'Agendamento Confirmado' : 'Proposta de Agendamento' }}
                                </small>
                                <span class="text-gray-900 dark:text-white fw-bold">
                                    {{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->format('d/m/Y') }}
                                    às {{ \Carbon\Carbon::parse($activeSchedule->start_date_time)->format('H:i') }}
                                    até {{ \Carbon\Carbon::parse($activeSchedule->end_date_time)->format('H:i') }}
                                </span>

                                @if ($activeSchedule->notes)
                                <div class="mt-2 small text-muted border-top pt-2">
                                    <strong>Observações:</strong><br>
                                    {{ $activeSchedule->notes }}
                                </div>
                                @endif
                            </x-layout.v-stack>
                        </div>
                    </x-layout.grid-col>
                    @endif

                    <x-layout.grid-col md="4">
                        <div class="p-2 bg-light dark:bg-gray-800 rounded">
                            <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Categoria</small>
                            <span class="text-gray-900 dark:text-white">{{ $service->category?->name }}</span>
                        </div>
                    </x-layout.grid-col>
                    <x-layout.grid-col md="4">
                        <div class="p-2 bg-light dark:bg-gray-800 rounded">
                            <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Valor</small>
                            <span class="text-gray-900 dark:text-white">{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                        </div>
                    </x-layout.grid-col>
                    <x-layout.grid-col md="4">
                        <div class="p-2 bg-light dark:bg-gray-800 rounded">
                            <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Desconto</small>
                            <span class="text-gray-900 dark:text-white">{{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                        </div>
                    </x-layout.grid-col>

                    @if ($service->due_date)
                    <x-layout.grid-col md="4">
                        <div class="p-2 bg-light dark:bg-gray-800 rounded">
                            <small class="text-muted dark:text-gray-400 d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Prazo</small>
                            <span class="text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}</span>
                        </div>
                    </x-layout.grid-col>
                    @endif
                </x-layout.grid-row>
            </div>

            @if (in_array($service->status->value, ['pending', 'scheduling', 'scheduled']))
            <x-ui.card class="border-warning shadow-none" style="background-color: rgba(255, 193, 7, 0.05);">
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

            <div class="mb-0">
                <h6 class="mb-3 small text-uppercase fw-bold text-muted dark:text-gray-400">
                    <i class="bi bi-list-check me-2"></i>
                    Itens do Serviço
                </h6>
                <x-resource.resource-table>
                    <x-slot name="thead">
                        <x-resource.table-row>
                            <x-resource.table-cell header>Item</x-resource.table-cell>
                            <x-resource.table-cell header class="text-center">Qtd</x-resource.table-cell>
                            <x-resource.table-cell header class="text-end">V. Unitário</x-resource.table-cell>
                            <x-resource.table-cell header class="text-end">Total</x-resource.table-cell>
                        </x-resource.table-row>
                    </x-slot>

                    @foreach ($service->serviceItems as $item)
                    <x-resource.table-row>
                        <x-resource.table-cell>
                            <div class="fw-bold text-gray-900 dark:text-white">{{ $item->product?->name }}</div>
                            @if ($item->notes)
                            <small class="text-muted">{{ $item->notes }}</small>
                            @endif
                        </x-resource.table-cell>
                        <x-resource.table-cell class="text-center">{{ $item->quantity }}</x-resource.table-cell>
                        <x-resource.table-cell class="text-end">{{ \App\Helpers\CurrencyHelper::format((float) $item->unit_value) }}</x-resource.table-cell>
                        <x-resource.table-cell class="text-end fw-bold">{{ \App\Helpers\CurrencyHelper::format((float) $item->total_value) }}</x-resource.table-cell>
                    </x-resource.table-row>
                    @endforeach

                    <x-slot name="tfoot">
                        <x-resource.table-row class="table-light dark:bg-gray-800">
                            <x-resource.table-cell colspan="3" class="text-end fw-bold">Total dos Itens:</x-resource.table-cell>
                            <x-resource.table-cell class="text-end fw-bold">{{ \App\Helpers\CurrencyHelper::format((float) $service->serviceItems->sum('total_value')) }}</x-resource.table-cell>
                        </x-resource.table-row>
                    </x-slot>
                </x-resource.resource-table>
            </div>
        </x-layout.v-stack>

        <x-slot name="footer">
            <x-layout.h-stack justify="between" align="center">
                <x-ui.button type="link" :href="route('services.public.print', ['code' => $service->code, 'token' => $token])" variant="outline-secondary" icon="printer" label="Imprimir" target="_blank" size="sm" />

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
