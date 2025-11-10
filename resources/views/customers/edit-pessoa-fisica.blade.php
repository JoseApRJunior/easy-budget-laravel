@extends( 'layouts.app' )

@section( 'title', 'Editar Cliente - Pessoa Física' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">
          <i class="bi bi-person-gear text-warning me-2"></i>
          Editar Cliente - Pessoa Física
        </h1>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route( 'provider.customers.index' ) }}">Clientes</a></li>
            <li class="breadcrumb-item"><a
                href="{{ route( 'provider.customers.show', $customer ) }}">{{ $customer->commonData?->first_name }}
                {{ $customer->commonData?->last_name }}</a></li>
            <li class="breadcrumb-item active">Editar</li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Formulário -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="mb-0">
                <i class="bi bi-person-fill me-2"></i>
                Dados Pessoais
              </h5>
              <div>
                <span
                  class="badge bg-{{ $customer->status === 'active' ? 'success' : ( $customer->status === 'prospect' ? 'warning' : 'secondary' ) }} me-2">
                  {{ ucfirst( $customer->status ) }}
                </span>
                <a href="{{ route( 'provider.customers.show', $customer ) }}" class="btn btn-sm btn-outline-info">
                  <i class="bi bi-eye me-1"></i>Visualizar
                </a>
              </div>
            </div>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route( 'provider.customers.update-pessoa-fisica', $customer ) }}"
              id="pessoaFisicaEditForm" novalidate>
              @csrf
              @method( 'PUT' )

              <div class="row g-3">
                <!-- Status -->
                <div class="col-12">
                  <label for="status" class="form-label">Status</label>
                  <select class="form-select @error( 'status' ) is-invalid @enderror" id="status" name="status">
                    <option value="active" {{ $customer->status === 'active' ? 'selected' : '' }}>
                      Ativo
                    </option>
                    <option value="inactive" {{ $customer->status === 'inactive' ? 'selected' : '' }}>
                      Inativo
                    </option>
                    <option value="deleted" {{ $customer->status === 'deleted' ? 'selected' : '' }}>
                      Excluído
                    </option>
                  </select>
                  @error( 'status' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Dados Pessoais -->
                <div class="col-md-6">
                  <label for="first_name" class="form-label">Nome *</label>
                  <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
                    name="first_name" value="{{ old( 'first_name', $customer->commonData?->first_name ) }}" required
                    maxlength="100">
                  @error( 'first_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="last_name" class="form-label">Sobrenome *</label>
                  <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name"
                    name="last_name" value="{{ old( 'last_name', $customer->commonData?->last_name ) }}" required
                    maxlength="100">
                  @error( 'last_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="cpf" class="form-label">CPF *</label>
                  <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror" id="cpf" name="cpf"
                    value="{{ old( 'cpf', $customer->commonData?->cpf ) }}" placeholder="000.000.000-00" maxlength="14"
                    required>
                  <div class="invalid-feedback" id="cpf-error"></div>
                  @error( 'cpf' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="birth_date" class="form-label">Data de Nascimento</label>
                  <input type="date" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date"
                    name="birth_date" value="{{ old( 'birth_date', $customer->commonData?->birth_date ) }}"
                    max="{{ date( 'Y-m-d' ) }}">
                  @error( 'birth_date' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Contato -->
                <div class="col-md-6">
                  <label for="email" class="form-label">E-mail *</label>
                  <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
                    value="{{ old( 'email', $customer->contact?->email_personal ) }}" required>
                  <div class="invalid-feedback" id="email-error"></div>
                  @error( 'email' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="phone" class="form-label">Telefone</label>
                  <input type="text" class="form-control @error( 'phone' ) is-invalid @enderror" id="phone" name="phone"
                    value="{{ old( 'phone', $customer->contact?->phone_personal ) }}" placeholder="(00) 00000-0000"
                    maxlength="15">
                  @error( 'phone' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Profissional -->
                <div class="col-md-6">
                  <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
                  <select class="form-select @error( 'area_of_activity_id' ) is-invalid @enderror" id="area_of_activity_id"
                    name="area_of_activity_id">
                    <option value="">Selecione uma área</option>
                    @foreach( $areas ?? [] as $area )
                      <option value="{{ $area->id }}" {{ old( 'area_of_activity_id', $customer->commonData?->area_of_activity_id ) == $area->id ? 'selected' : '' }}>
                        {{ $area->name }}
                      </option>
                    @endforeach
                  </select>
                  @error( 'area_of_activity_id' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="profession_id" class="form-label">Profissão</label>
                  <select class="form-select @error( 'profession_id' ) is-invalid @enderror" id="profession_id"
                    name="profession_id">
                    <option value="">Selecione uma profissão</option>
                    @foreach( $professions ?? [] as $profession )
                      <option value="{{ $profession->id }}" {{ old( 'profession_id', $customer->commonData?->profession_id ) == $profession->id ? 'selected' : '' }}>
                        {{ $profession->name }}
                      </option>
                    @endforeach
                  </select>
                  @error( 'profession_id' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Endereço -->
                <div class="col-12">
                  <h6 class="mb-3">
                    <i class="bi bi-geo-alt-fill me-2"></i>
                    Endereço
                  </h6>
                </div>

                <div class="col-md-3">
                  <label for="cep" class="form-label">CEP *</label>
                  <input type="text" class="form-control @error( 'cep' ) is-invalid @enderror" id="cep" name="cep"
                    value="{{ old( 'cep', $customer->address?->cep ) }}" placeholder="00000-000" maxlength="9" required>
                  @error( 'cep' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-7">
                  <label for="address" class="form-label">Endereço *</label>
                  <input type="text" class="form-control @error( 'address' ) is-invalid @enderror" id="address"
                    name="address" value="{{ old( 'address', $customer->address?->address ) }}" required>
                  @error( 'address' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-2">
                  <label for="address_number" class="form-label">Número</label>
                  <input type="text" class="form-control @error( 'address_number' ) is-invalid @enderror"
                    id="address_number" name="address_number"
                    value="{{ old( 'address_number', $customer->address?->address_number ) }}">
                  @error( 'address_number' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-4">
                  <label for="neighborhood" class="form-label">Bairro *</label>
                  <input type="text" class="form-control @error( 'neighborhood' ) is-invalid @enderror" id="neighborhood"
                    name="neighborhood" value="{{ old( 'neighborhood', $customer->address?->neighborhood ) }}" required>
                  @error( 'neighborhood' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-4">
                  <label for="city" class="form-label">Cidade *</label>
                  <input type="text" class="form-control @error( 'city' ) is-invalid @enderror" id="city" name="city"
                    value="{{ old( 'city', $customer->address?->city ) }}" required>
                  @error( 'city' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-2">
                  <label for="state" class="form-label">UF *</label>
                  <input type="text" class="form-control @error( 'state' ) is-invalid @enderror" id="state" name="state"
                    value="{{ old( 'state', $customer->address?->state ) }}" maxlength="2" required>
                  @error( 'state' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Observações -->
                <div class="col-12">
                  <label for="description" class="form-label">Observações</label>
                  <textarea class="form-control @error( 'description' ) is-invalid @enderror" id="description"
                    name="description" rows="3"
                    placeholder="Informações adicionais sobre o cliente">{{ old( 'description', $customer->commonData?->description ) }}</textarea>
                  @error( 'description' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Botões -->
                <div class="col-12">
                  <div class="d-flex justify-content-between align-items-center pt-4">
                    <div class="d-flex gap-2">
                      <a href="{{ route( 'provider.customers.show', $customer ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-eye me-2"></i>Visualizar
                      </a>
                      <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar
                      </a>
                    </div>
                    <div class="d-flex gap-2">
                      <button type="submit" class="btn btn-warning" id="submitBtn">
                        <i class="bi bi-check-circle me-2"></i>Atualizar Cliente
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Informações de Auditoria -->
    <div class="row mt-4">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent">
            <h6 class="mb-0">
              <i class="bi bi-clock-history me-2"></i>
              Histórico
            </h6>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <div class="col-md-6">
                <small class="text-muted">Criado em:</small>
                <p class="mb-0">{{ \Carbon\Carbon::parse( $customer->created_at )->format( 'd/m/Y H:i' ) }}</p>
              </div>
              <div class="col-md-6">
                <small class="text-muted">Última atualização:</small>
                <p class="mb-0">{{ \Carbon\Carbon::parse( $customer->updated_at )->format( 'd/m/Y H:i' ) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push( 'scripts' )
  <script src="{{ asset( 'assets/js/modules/customer-validations.js' ) }}"></script>
  <script>
    $( document ).ready( function () {
      // Inicializar máscaras
      applyMasks();

      // Validação em tempo real
      validateCPF({{ $customer->id }} );
      validateEmail({{ $customer->id }} );

      // Busca de endereço por CEP
      $( '#cep' ).on( 'blur', function () {
        const cep = $( this ).val().replace( /\D/g, '' );
        if ( cep.length === 8 ) {
          fetchAddressByCEP( cep );
        }
      } );

      // Formatar automaticamente enquanto digita
      $( '#cpf' ).on( 'input', function () {
        let cpf = $( this ).val().replace( /\D/g, '' );
        cpf = cpf.replace( /(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4' );
        $( this ).val( cpf );
      } );

      $( '#phone' ).on( 'input', function () {
        let phone = $( this ).val().replace( /\D/g, '' );
        if ( phone.length <= 11 ) {
          phone = phone.replace( /(\d{2})(\d{4,5})(\d{4})/, '($1) $2-$3' );
          $( this ).val( phone );
        }
      } );

      $( '#cep' ).on( 'input', function () {
        let cep = $( this ).val().replace( /\D/g, '' );
        if ( cep.length <= 8 ) {
          cep = cep.replace( /(\d{5})(\d{3})/, '$1-$2' );
          $( this ).val( cep );
        }
      } );

      // Envio do formulário
      $( '#pessoaFisicaEditForm' ).on( 'submit', function ( e ) {
        if ( !validateCPF({{ $customer->id }} ) || !validateEmail({{ $customer->id }} ) ) {
          e.preventDefault();
          return false;
        }

        $( '#submitBtn' ).prop( 'disabled', true ).html( '<span class="spinner-border spinner-border-sm me-2"></span>Atualizando...' );
      } );
    } );
  </script>
@endpush
