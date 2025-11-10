@extends( 'layouts.app' )

@section( 'title', 'Novo Cliente - Pessoa Física' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">
          <i class="bi bi-person-plus-fill text-primary me-2"></i>
          Novo Cliente - Pessoa Física
        </h1>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route( 'provider.customers.index' ) }}">Clientes</a></li>
            <li class="breadcrumb-item active">Novo Cliente PF</li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Formulário -->
    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent">
            <h5 class="mb-0">
              <i class="bi bi-person-fill me-2"></i>
              Dados Pessoais
            </h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route( 'provider.customers.store-pessoa-fisica' ) }}" id="pessoaFisicaForm"
              novalidate>
              @csrf

              <div class="row g-3">
                <!-- Dados Pessoais -->
                <div class="col-md-6">
                  <label for="first_name" class="form-label">Nome *</label>
                  <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
                    name="first_name" value="{{ old( 'first_name' ) }}" required maxlength="100">
                  @error( 'first_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="last_name" class="form-label">Sobrenome *</label>
                  <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name"
                    name="last_name" value="{{ old( 'last_name' ) }}" required maxlength="100">
                  @error( 'last_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="cpf" class="form-label">CPF *</label>
                  <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror" id="cpf" name="cpf"
                    value="{{ old( 'cpf' ) }}" placeholder="000.000.000-00" maxlength="14" required>
                  <div class="invalid-feedback" id="cpf-error"></div>
                  @error( 'cpf' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="birth_date" class="form-label">Data de Nascimento</label>
                  <input type="date" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date"
                    name="birth_date" value="{{ old( 'birth_date' ) }}" max="{{ date( 'Y-m-d' ) }}">
                  @error( 'birth_date' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Contato -->
                <div class="col-md-6">
                  <label for="email" class="form-label">E-mail *</label>
                  <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
                    value="{{ old( 'email' ) }}" required>
                  <div class="invalid-feedback" id="email-error"></div>
                  @error( 'email' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="phone" class="form-label">Telefone</label>
                  <input type="text" class="form-control @error( 'phone' ) is-invalid @enderror" id="phone" name="phone"
                    value="{{ old( 'phone' ) }}" placeholder="(00) 00000-0000" maxlength="15">
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
                      <option value="{{ $area->id }}" {{ old( 'area_of_activity_id' ) == $area->id ? 'selected' : '' }}>
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
                      <option value="{{ $profession->id }}" {{ old( 'profession_id' ) == $profession->id ? 'selected' : '' }}>
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
                    value="{{ old( 'cep' ) }}" placeholder="00000-000" maxlength="9" required>
                  @error( 'cep' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-7">
                  <label for="address" class="form-label">Endereço *</label>
                  <input type="text" class="form-control @error( 'address' ) is-invalid @enderror" id="address"
                    name="address" value="{{ old( 'address' ) }}" required>
                  @error( 'address' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-2">
                  <label for="address_number" class="form-label">Número</label>
                  <input type="text" class="form-control @error( 'address_number' ) is-invalid @enderror"
                    id="address_number" name="address_number" value="{{ old( 'address_number' ) }}">
                  @error( 'address_number' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-4">
                  <label for="neighborhood" class="form-label">Bairro *</label>
                  <input type="text" class="form-control @error( 'neighborhood' ) is-invalid @enderror" id="neighborhood"
                    name="neighborhood" value="{{ old( 'neighborhood' ) }}" required>
                  @error( 'neighborhood' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-4">
                  <label for="city" class="form-label">Cidade *</label>
                  <input type="text" class="form-control @error( 'city' ) is-invalid @enderror" id="city" name="city"
                    value="{{ old( 'city' ) }}" required>
                  @error( 'city' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-2">
                  <label for="state" class="form-label">UF *</label>
                  <input type="text" class="form-control @error( 'state' ) is-invalid @enderror" id="state" name="state"
                    value="{{ old( 'state' ) }}" maxlength="2" required>
                  @error( 'state' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Observações -->
                <div class="col-12">
                  <label for="description" class="form-label">Observações</label>
                  <textarea class="form-control @error( 'description' ) is-invalid @enderror" id="description"
                    name="description" rows="3"
                    placeholder="Informações adicionais sobre o cliente">{{ old( 'description' ) }}</textarea>
                  @error( 'description' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Botões -->
                <div class="col-12">
                  <div class="d-flex justify-content-between align-items-center pt-4">
                    <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                      <i class="bi bi-check-circle me-2"></i>Criar Cliente
                    </button>
                  </div>
                </div>
              </div>
            </form>
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
      validateCPF();
      validateEmail();

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
      $( '#pessoaFisicaForm' ).on( 'submit', function ( e ) {
        if ( !validateCPF() || !validateEmail() ) {
          e.preventDefault();
          return false;
        }

        $( '#submitBtn' ).prop( 'disabled', true ).html( '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...' );
      } );
    } );
  </script>
@endpush
