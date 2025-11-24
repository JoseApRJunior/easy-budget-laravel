@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Editar Orçamento
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'budget.index' ) }}">Orçamentos</a></li>
                    <li class="breadcrumb-item"><a
                            href="{{ route( 'budget.show', $budget->code ) }}">{{ $budget->code }}</a>
                    </li>
                    <li class="breadcrumb-item active">Editar</li>
                </ol>
            </nav>
        </div>

        <!-- Budget Edit Form -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form id="update-budget-form" action="{{ route( 'budget.update', $budget->id ) }}" method="POST">
                    @csrf
                    @method( 'PUT' )
                    <fieldset {{ !StatusHelper::status_allows_edit( $budget->status->value ) ? 'disabled' : '' }}>
                        <!-- Budget Information -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="code" class="form-label fw-semibold"><i
                                            class="bi bi-hash me-2"></i>Código</label>
                                    <input type="text" class="form-control bg-light" value="{{ $budget->code }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label fw-semibold"><i class="bi bi-person me-2"></i>Cliente</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ $budget->customer->first_name }} {{ $budget->customer->last_name }}"
                                        disabled>
                                    <input type="hidden" name="customer_id" value="{{ $budget->customer_id }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="created_at" class="form-label fw-semibold"><i
                                            class="bi bi-calendar-check me-2"></i>Data de Criação</label>
                                    <input type="text" class="form-control bg-light"
                                        value="{{ DateHelper::formatBR( $budget->created_at, 'd/m/Y H:i' ) }}" disabled>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="due_date" class="form-label fw-semibold"><i
                                            class="bi bi-calendar me-2"></i>Vencimento</label>
                                    <input type="date" id="due_date" name="due_date" class="form-control"
                                        value="{{ old( 'due_date', DateHelper::format( $budget->due_date, 'Y-m-d' ) ) }}"
                                        required>
                                    @error( 'due_date' )
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="row g-4">
                            <!-- Description -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="description" class="form-label fw-semibold"><i
                                            class="bi bi-card-text me-2"></i>Descrição</label>
                                    <textarea id="description" name="description" class="form-control" rows="4"
                                        maxlength="255">{{ old( 'description', $budget->description ) }}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <small id="char-count"
                                            class="text-muted mt-2">{{ 255 - strlen( $budget->description ) }} caracteres
                                            restantes</small>
                                    </div>
                                    @error( 'description' )
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <!-- Payment Terms -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label for="payment_terms" class="form-label fw-semibold"><i
                                            class="bi bi-credit-card me-2"></i>Condições de Pagamento</label>
                                    <textarea id="payment_terms" name="payment_terms" class="form-control" rows="2"
                                        maxlength="255">{{ old( 'payment_terms', $budget->payment_terms ) }}</textarea>
                                    @error( 'payment_terms' )
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </form>

                <!-- Form Actions -->
                <div class="d-flex justify-content-between mt-4 pt-4 border-top">
                    <a href="{{ route( 'budget.show', $budget->code ) }}" class="btn btn-outline-secondary px-4">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                    <div>
                        @if ( StatusHelper::status_allows_edit( $budget->status->value ) )
                            <button type="submit" form="update-budget-form" class="btn btn-primary px-4">
                                <i class="bi bi-check-lg me-2"></i>Salvar Alterações
                            </button>
                        @else
                            <div class="alert alert-info mb-0 py-2 px-3">
                                <i class="bi bi-info-circle-fill me-2"></i>Não Editável ({{ $budget->status->getDescription() }})
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Linked Services -->
        @if( $budget->services->isNotEmpty() )
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-transparent border-0 p-4">
                    <h5 class="card-title mb-0"><i class="bi bi-tools me-2"></i>Serviços Vinculados</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descrição</th>
                                    <th>Status</th>
                                    <th>Valor</th>
                                    <th class="text-end">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ( $budget->services as $service )
                                    <tr>
                                        <td>{{ $service->code }}</td>
                                        <td>{{ Str::limit( $service->description, 50 ) }}</td>
                                        <td>
                                            {!! StatusHelper::status_badge( $service->status ) !!}
                                        </td>
                                        <td>R$ {{ number_format( $service->total, 2, ',', '.' ) }}</td>
                                        <td class="text-end">
                                            <a href="{{ route( 'provider.services.show', $service->code ) }}"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push( 'scripts' )
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const description = document.getElementById( 'description' );
            const charCount = document.getElementById( 'char-count' );
            const maxLength = 255;

            if ( description && charCount ) {
                description.addEventListener( 'input', function () {
                    const remaining = maxLength - this.value.length;
                    charCount.textContent = remaining + ' caracteres restantes';
                } );
            }
        } );
    </script>
@endpush
