@extends('layouts.app')

@section('content')
    <x-layout.page-container>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h5 class="mb-0 text-muted">Status do Serviço</h5>
                                <small class="text-muted">Código: {{ $service->code }}</small>
                            </div>
                            <div>
                                <x-ui.status-badge :item="$service" />
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if (session('success'))
                            <x-ui.alert type="success" :message="session('success')" />
                        @endif

                        @if (session('error'))
                            <x-ui.alert type="error" :message="session('error')" />
                        @endif

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">
                                            <i class="bi bi-person-circle me-2"></i>
                                            Cliente
                                        </h6>
                                        <h5 class="mb-1">{{ $service->customer?->common_data?->first_name }}
                                            {{ $service->customer?->common_data?->last_name }}
                                        </h5>
                                        <p class="text-muted mb-0">{{ $service->customer?->contact?->email }}</p>
                                        @if ($service->customer?->contact?->phone)
                                            <p class="text-muted mb-0">{{ $service->customer?->contact?->phone }}</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="card-title text-muted mb-3">
                                            <i class="bi bi-receipt me-2"></i>
                                            Orçamento
                                        </h6>
                                        <h5 class="mb-1">{{ $service->budget?->code }}</h5>
                                        <p class="text-muted mb-0">{{ $service->budget?->description }}</p>
                                        <p class="mb-0">
                                            <strong>Total:
                                                {{ \App\Helpers\CurrencyHelper::format($service->budget?->total) }}</strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3">
                                            <i class="bi bi-tools me-2"></i>
                                            Detalhes do Serviço
                                        </h6>

                                        @if ($service->description)
                                            <p class="mb-3">{{ $service->description }}</p>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Categoria:</strong><br>
                                                {{ $service->category?->name }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Valor:</strong><br>
                                                {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Desconto:</strong><br>
                                                {{ \App\Helpers\CurrencyHelper::format($service->discount) }}
                                            </div>
                                        </div>

                                        @if ($service->due_date)
                                            <div class="row mt-2">
                                                <div class="col-md-4">
                                                    <strong>Prazo:</strong><br>
                                                    {{ \Carbon\Carbon::parse($service->due_date)->format('d/m/Y') }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if ($service->status->value === 'pending')
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <h6 class="card-title text-warning mb-3">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Ação Necessária
                                            </h6>
                                            <p class="mb-3">Por favor, informe o status atual deste serviço:</p>

                                            <form method="POST" action="{{ route('services.public.choose-status') }}">
                                                @csrf
                                                <input type="hidden" name="service_code" value="{{ $service->code }}">
                                                <input type="hidden" name="token" value="{{ $token }}">

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <label for="service_status_id" class="form-label">Status do
                                                            Serviço</label>
                                                        <select name="service_status_id" id="service_status_id"
                                                            class="form-select" required>
                                                            <option value="">Selecione o status...</option>
                                                            @foreach ([\App\Enums\ServiceStatus::APPROVED, \App\Enums\ServiceStatus::REJECTED, \App\Enums\ServiceStatus::CANCELLED] as $status)
                                                                <option value="{{ $status->value }}"
                                                                    {{ old('service_status_id') == $status->value ? 'selected' : '' }}>
                                                                    {{ $status->getDescription() }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error('service_status_id')
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-end">
                                                        <x-ui.button type="submit" variant="primary" icon="check-circle" label="Atualizar Status" />
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <x-ui.button type="link" :href="route('services.public.print', ['code' => $service->code, 'token' => $token])" variant="outline-secondary" icon="printer" label="Imprimir" target="_blank" />

                                    <div class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Link válido até:
                                        {{ \Carbon\Carbon::parse($service->userConfirmationToken?->expires_at)->format('d/m/Y H:i') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-layout.page-container>
@endsection

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
    </style>
@endpush
