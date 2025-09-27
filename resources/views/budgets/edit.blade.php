@extends( 'layouts.app' )

@section( 'title', 'Editar Orçamento - Easy Budget' )

@section( 'content' )
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex align-items-center mb-4">
                <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary me-3">
                    <i class="bi bi-arrow-left me-1"></i>
                    Voltar
                </a>
                <h1 class="h3 mb-0">
                    <i class="bi bi-pencil text-primary me-2"></i>
                    Editar Orçamento: {{ $budget->code ?? 'ORC-' . $budget->id }}
                </h1>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route( 'budgets.update', $budget ) }}">
                        @csrf
                        @method( 'PUT' )

                        <div class="row">
                            <!-- Código -->
                            <div class="col-md-6 mb-3">
                                <label for="code" class="form-label">
                                    Código do Orçamento
                                </label>
                                <input type="text" class="form-control @error( 'code' ) is-invalid @enderror" id="code"
                                    name="code" value="{{ old( 'code', $budget->code ) }}" placeholder="ORC-001">
                                @error( 'code' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_name" class="form-label">
                                    Nome do Cliente <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error( 'client_name' ) is-invalid @enderror"
                                    id="client_name" name="client_name"
                                    value="{{ old( 'client_name', $budget->client_name ) }}" required
                                    placeholder="Nome completo do cliente">
                                @error( 'client_name' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Email do Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_email" class="form-label">
                                    Email do Cliente
                                </label>
                                <input type="email" class="form-control @error( 'client_email' ) is-invalid @enderror"
                                    id="client_email" name="client_email"
                                    value="{{ old( 'client_email', $budget->client_email ) }}"
                                    placeholder="cliente@exemplo.com">
                                @error( 'client_email' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Telefone do Cliente -->
                            <div class="col-md-6 mb-3">
                                <label for="client_phone" class="form-label">
                                    Telefone do Cliente
                                </label>
                                <input type="tel" class="form-control @error( 'client_phone' ) is-invalid @enderror"
                                    id="client_phone" name="client_phone"
                                    value="{{ old( 'client_phone', $budget->client_phone ) }}" placeholder="(11) 99999-9999">
                                @error( 'client_phone' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Valor -->
                            <div class="col-md-6 mb-3">
                                <label for="amount" class="form-label">
                                    Valor <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" min="0"
                                        class="form-control @error( 'amount' ) is-invalid @enderror" id="amount" name="amount"
                                        value="{{ old( 'amount', $budget->amount ) }}" required placeholder="1500.00">
                                </div>
                                @error( 'amount' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Usuário Responsável -->
                            <div class="col-md-6 mb-3">
                                <label for="user_id" class="form-label">
                                    Usuário Responsável
                                </label>
                                <select class="form-select @error( 'user_id' ) is-invalid @enderror" id="user_id"
                                    name="user_id">
                                    <option value="">Selecionar usuário...</option>
                                    @foreach( \App\Models\User::where( 'status', 'active' )->get() as $userOption )
                                        <option value="{{ $userOption->id }}" {{ old( 'user_id', $budget->user_id ) == $userOption->id ? 'selected' : '' }}>
                                            {{ $userOption->name }} - {{ $userOption->email }}
                                        </option>
                                    @endforeach
                                </select>
                                @error( 'user_id' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select @error( 'status' ) is-invalid @enderror" id="status" name="status">
                                <option value="draft" {{ old( 'status', $budget->status ) === 'draft' ? 'selected' : '' }}>
                                    Rascunho</option>
                                <option value="pending" {{ old( 'status', $budget->status ) === 'pending' ? 'selected' : '' }}>
                                    Pendente</option>
                                <option value="approved" {{ old( 'status', $budget->status ) === 'approved' ? 'selected' : '' }}>Aprovado</option>
                                <option value="rejected" {{ old( 'status', $budget->status ) === 'rejected' ? 'selected' : '' }}>Rejeitado</option>
                            </select>
                            @error( 'status' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Observações -->
                        <div class="mb-4">
                            <label for="notes" class="form-label">
                                Observações
                            </label>
                            <textarea class="form-control @error( 'notes' ) is-invalid @enderror" id="notes" name="notes"
                                rows="4"
                                placeholder="Observações sobre o orçamento...">{{ old( 'notes', $budget->notes ) }}</textarea>
                            @error( 'notes' )
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Informações Adicionais -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2">
                                        <i class="bi bi-info-circle text-primary me-1"></i>
                                        Informações
                                    </h6>
                                    <small class="text-muted">
                                        <strong>Criado em:</strong> {{ $budget->created_at->format( 'd/m/Y H:i' ) }}<br>
                                        <strong>Última atualização:</strong>
                                        {{ $budget->updated_at->format( 'd/m/Y H:i' ) }}<br>
                                        <strong>Usuário:</strong> {{ $budget->user ? $budget->user->name : 'N/A' }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded">
                                    <h6 class="mb-2">
                                        <i class="bi bi-bar-chart text-primary me-1"></i>
                                        Estatísticas
                                    </h6>
                                    <small class="text-muted">
                                        <strong>Status anterior:</strong> {{ $budget->getOriginal( 'status' ) }}<br>
                                        <strong>Valor anterior:</strong> R$
                                        {{ number_format( $budget->getOriginal( 'amount' ), 2, ',', '.' ) }}
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="{{ route( 'budgets.index' ) }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar
                            </a>
                            <div>
                                <a href="{{ route( 'budgets.show', $budget ) }}" class="btn btn-outline-info me-2">
                                    <i class="bi bi-eye me-2"></i>
                                    Visualizar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Atualizar Orçamento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    <script>
        // Máscaras para telefone
        document.getElementById( 'client_phone' ).addEventListener( 'input', function ( e ) {
            let value = e.target.value.replace( /\D/g, '' );
            if ( value.length <= 11 ) {
                if ( value.length <= 10 ) {
                    value = value.replace( /(\d{2})(\d{4})(\d{4})/, '($1) $2-$3' );
                } else {
                    value = value.replace( /(\d{2})(\d{5})(\d{4})/, '($1) $2-$3' );
                }
            }
            e.target.value = value;
        } );

        // Formatação do valor
        document.getElementById( 'amount' ).addEventListener( 'blur', function () {
            let value = parseFloat( this.value );
            if ( !isNaN( value ) ) {
                this.value = value.toFixed( 2 );
            }
        } );
    </script>
@endsection
