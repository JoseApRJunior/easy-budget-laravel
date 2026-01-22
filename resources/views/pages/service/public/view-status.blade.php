@extends('layouts.app')

@section('content')
<x-layout.page-container>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-ui.card class="mb-4">
                <x-slot name="header">
                    <x-layout.h-stack justify="between" align="center">
                        <div>
                            <h5 class="mb-0 text-muted">Status do Serviço</h5>
                            <small class="text-muted">Código: {{ $service->code }}</small>
                        </div>
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
                        <x-ui.card class="bg-light border-0" no-padding>
                            <div class="p-3">
                                <h6 class="card-title text-muted mb-3 small text-uppercase fw-bold">
                                    <i class="bi bi-person-circle me-2"></i>
                                    Cliente
                                </h6>
                                <h5 class="mb-1">{{ $service->customer?->common_data?->first_name }}
                                    {{ $service->customer?->common_data?->last_name }}
                                </h5>
                                <p class="text-muted mb-0 small">{{ $service->customer?->contact?->email }}</p>
                                @if ($service->customer?->contact?->phone)
                                <p class="text-muted mb-0 small">{{ $service->customer?->contact?->phone }}</p>
                                @endif
                            </div>
                        </x-ui.card>
                    </x-layout.grid-col>

                    <x-layout.grid-col md="6">
                        <x-ui.card class="bg-light border-0" no-padding>
                            <div class="p-3">
                                <h6 class="card-title text-muted mb-3 small text-uppercase fw-bold">
                                    <i class="bi bi-receipt me-2"></i>
                                    Orçamento
                                </h6>
                                <h5 class="mb-1">{{ $service->budget?->code }}</h5>
                                <p class="text-muted mb-0 small">{{ $service->budget?->description }}</p>
                                <p class="mb-0 mt-2">
                                    <strong>Total:
                                        {{ \App\Helpers\CurrencyHelper::format($service->budget?->total) }}</strong>
                                </p>
                            </div>
                        </x-ui.card>
                    </x-layout.grid-col>
                </x-layout.grid-row>

                <div class="mb-4">
                    <h6 class="mb-3 small text-uppercase fw-bold text-muted">
                        <i class="bi bi-tools me-2"></i>
                        Detalhes do Serviço
                    </h6>

                    @if ($service->description)
                    <p class="mb-3">{{ $service->description }}</p>
                    @endif

                    <x-layout.grid-row class="g-3">
                        <x-layout.grid-col md="4">
                            <div class="p-2 bg-light rounded">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Categoria</small>
                                <span>{{ $service->category?->name }}</span>
                            </div>
                        </x-layout.grid-col>
                        <x-layout.grid-col md="4">
                            <div class="p-2 bg-light rounded">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Valor</small>
                                <span>{{ \App\Helpers\CurrencyHelper::format($service->total) }}</span>
                            </div>
                        </x-layout.grid-col>
                        <x-layout.grid-col md="4">
                            <div class="p-2 bg-light rounded">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Desconto</small>
                                <span>{{ \App\Helpers\CurrencyHelper::format($service->discount) }}</span>
                            </div>
                        </x-layout.grid-col>

                        @if ($service->due_date)
                        <x-layout.grid-col md="4">
                            <div class="p-2 bg-light rounded">
                                <small class="text-muted d-block text-uppercase fw-bold" style="font-size: 0.7rem;">Prazo</small>
                                <span>{{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}</span>
                            </div>
                        </x-layout.grid-col>
                        @endif
                    </x-layout.grid-row>
                </div>

                @if ($service->status->value === 'pending')
                <x-ui.card class="border-warning mb-4 shadow-none" style="background-color: rgba(255, 193, 7, 0.05);">
                    <h6 class="card-title text-warning mb-3 fw-bold">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Ação Necessária
                    </h6>
                    <p class="mb-3 small">Por favor, informe o status atual deste serviço:</p>

                    <form method="POST" action="{{ route('services.public.choose-status') }}">
                        @csrf
                        <input type="hidden" name="service_code" value="{{ $service->code }}">
                        <input type="hidden" name="token" value="{{ $token }}">

                        <x-layout.grid-row align="end">
                            <x-layout.grid-col md="8">
                                <x-ui.form.select
                                    name="service_status_id"
                                    label="Status do Serviço"
                                    required>
                                    <option value="">Selecione o status...</option>
                                    @foreach ([\App\Enums\ServiceStatus::APPROVED, \App\Enums\ServiceStatus::REJECTED, \App\Enums\ServiceStatus::CANCELLED] as $status)
                                    <option value="{{ $status->value }}"
                                        {{ old('service_status_id') == $status->value ? 'selected' : '' }}>
                                        {{ $status->getDescription() }}
                                    </option>
                                    @endforeach
                                </x-ui.form.select>
                            </x-layout.grid-col>
                            <x-layout.grid-col md="4">
                                <x-ui.button type="submit" variant="primary" icon="check-circle" label="Atualizar" class="w-100" />
                            </x-layout.grid-col>
                        </x-layout.grid-row>
                    </form>
                </x-ui.card>
                @endif

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
        </div>
    </div>
</x-layout.page-container>
@endsection
