@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
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
                                <span class="badge bg-{{ $service->serviceStatus->color ?? 'secondary' }} fs-6 px-3 py-2">
                                    <i class="bi bi-{{ $service->serviceStatus->icon ?? 'circle' }} me-1"></i>
                                    {{ $service->serviceStatus->name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        @if( session( 'success' ) )
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ session( 'success' ) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if( session( 'error' ) )
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                {{ session( 'error' ) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
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
                                        @if( $service->customer?->contact?->phone )
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
                                            <strong>Total: R$
                                                {{ number_format( $service->budget?->total, 2, ',', '.' ) }}</strong>
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

                                        @if( $service->description )
                                            <p class="mb-3">{{ $service->description }}</p>
                                        @endif

                                        <div class="row">
                                            <div class="col-md-4">
                                                <strong>Categoria:</strong><br>
                                                {{ $service->category?->name }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Valor:</strong><br>
                                                R$ {{ number_format( $service->total, 2, ',', '.' ) }}
                                            </div>
                                            <div class="col-md-4">
                                                <strong>Desconto:</strong><br>
                                                R$ {{ number_format( $service->discount, 2, ',', '.' ) }}
                                            </div>
                                        </div>

                                        @if( $service->due_date )
                                            <div class="row mt-2">
                                                <div class="col-md-4">
                                                    <strong>Prazo:</strong><br>
                                                    {{ \Carbon\Carbon::parse( $service->due_date )->format( 'd/m/Y' ) }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if( $service->serviceStatus->slug === 'enviado' )
                            <div class="row">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <h6 class="card-title text-warning mb-3">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                Ação Necessária
                                            </h6>
                                            <p class="mb-3">Por favor, informe o status atual deste serviço:</p>

                                            <form method="POST" action="{{ route( 'services.public.choose-status' ) }}">
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
                                                            @foreach( [ \App\Enums\ServiceStatusEnum::APPROVED, \App\Enums\ServiceStatusEnum::REJECTED, \App\Enums\ServiceStatusEnum::CANCELLED ] as $status )
                                                                <option value="{{ $status->value }}" {{ old( 'service_status_id' ) == $status->value ? 'selected' : '' }}>
                                                                    {{ $status->getName() }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        @error( 'service_status_id' )
                                                            <div class="text-danger small">{{ $message }}</div>
                                                        @enderror
                                                    </div>
                                                    <div class="col-md-6 d-flex align-items-end">
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="bi bi-check-circle me-2"></i>
                                                            Atualizar Status
                                                        </button>
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
                                    <a href="{{ route( 'services.public.print', [ 'code' => $service->code, 'token' => $token ] ) }}"
                                        class="btn btn-outline-secondary" target="_blank">
                                        <i class="bi bi-printer me-2"></i>
                                        Imprimir
                                    </a>

                                    <div class="text-muted small">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Link válido até:
                                        {{ \Carbon\Carbon::parse( $service->userConfirmationToken?->expires_at )->format( 'd/m/Y H:i' ) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
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
