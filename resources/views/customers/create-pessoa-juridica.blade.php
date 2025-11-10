@extends( 'layouts.app' )

@section( 'title', 'Novo Cliente - Pessoa Jurídica' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-1">
          <i class="bi bi-building-add text-success me-2"></i>
          Novo Cliente - Pessoa Jurídica
        </h1>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route( 'provider.customers.index' ) }}">Clientes</a></li>
            <li class="breadcrumb-item active">Novo Cliente PJ</li>
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
              <i class="bi bi-building me-2"></i>
              Dados da Empresa
            </h5>
          </div>
          <div class="card-body">
            <form method="POST" action="{{ route( 'provider.customers.store-pessoa-juridica' ) }}" id="pessoaJuridicaForm"
              novalidate>
              @csrf

              <div class="row g-3">
                <!-- Dados da Empresa -->
                <div class="col-md-8">
                  <label for="company_name" class="form-label">Razão Social *</label>
                  <input type="text" class="form-control @error( 'company_name' ) is-invalid @enderror" id="company_name"
                    name="company_name" value="{{ old( 'company_name' ) }}" required maxlength="255">
                  @error( 'company_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-4">
                  <label for="cnpj" class="form-label">CNPJ *</label>
                  <input type="text" class="form-control @error( 'cnpj' ) is-invalid @enderror" id="cnpj" name="cnpj"
                    value="{{ old( 'cnpj' ) }}" placeholder="00.000.000/0000-00" maxlength="18" required>
                  <div class="invalid-feedback" id="cnpj-error"></div>
                  @error( 'cnpj' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="fantasy_name" class="form-label">Nome Fantasia</label>
                  <input type="text" class="form-control @error( 'fantasy_name' ) is-invalid @enderror" id="fantasy_name"
                    name="fantasy_name" value="{{ old( 'fantasy_name' ) }}" maxlength="255">
                  @error( 'fantasy_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="founding_date" class="form-label">Data de Fundação</label>
                  <input type="date" class="form-control @error( 'founding_date' ) is-invalid @enderror" id="founding_date"
                    name="founding_date" value="{{ old( 'founding_date' ) }}" max="{{ date( 'Y-m-d' ) }}">
                  @error( 'founding_date' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="state_registration" class="form-label">Inscrição Estadual</label>
                  <input type="text" class="form-control @error( 'state_registration' ) is-invalid @enderror"
                    id="state_registration" name="state_registration" value="{{ old( 'state_registration' ) }}"
                    maxlength="50">
                  @error( 'state_registration' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="municipal_registration" class="form-label">Inscrição Municipal</label>
                  <input type="text" class="form-control @error( 'municipal_registration' ) is-invalid @enderror"
                    id="municipal_registration" name="municipal_registration" value="{{ old( 'municipal_registration' ) }}"
                    maxlength="50">
                  @error( 'municipal_registration' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Contato Empresarial -->
                <div class="col-12">
                  <h6 class="mb-3">
                    <i class="bi bi-telephone me-2"></i>
                    Contato Empresarial
                  </h6>
                </div>

                <div class="col-md-6">
                  <label for="email_business" class="form-label">E-mail Empresarial *</label>
                  <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror"
                    id="email_business" name="email_business" value="{{ old( 'email_business' ) }}" required>
                  <div class="invalid-feedback" id="email-business-error"></div>
                  @error( 'email_business' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="phone_business" class="form-label">Telefone Empresarial</label>
                  <input type="text" class="form-control @error( 'phone_business' ) is-invalid @enderror"
                    id="phone_business" name="phone_business" value="{{ old( 'phone_business' ) }}"
                    placeholder="(00) 00000-0000" maxlength="15">
                  @error( 'phone_business' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="email_personal" class="form-label">E-mail Pessoal (Contato)</label>
                  <input type="email" class="form-control @error( 'email_personal' ) is-invalid @enderror"
                    id="email_personal" name="email_personal" value="{{ old( 'email_personal' ) }}">
                  @error( 'email_personal' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="phone_personal" class="form-label">Telefone Pessoal (Contato)</label>
                  <input type="text" class="form-control @error( 'phone_personal' ) is-invalid @enderror"
                    id="phone_personal" name="phone_personal" value="{{ old( 'phone_personal' ) }}"
                    placeholder="(00) 00000-0000" maxlength="15">
                  @error( 'phone_personal' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-12">
                  <label for="website" class="form-label">Website</label>
                  <input type="url" class="form-control @error( 'website' ) is-invalid @enderror" id="website"
                    name="website" value="{{ old( 'website' ) }}" placeholder="https://www.empresa.com.br">
                  @error( 'website' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Responsável Legal -->
                <div class="col-12">
                  <h6 class="mb-3">
                    <i class="bi bi-person-badge me-2"></i>
                    Responsável Legal
                  </h6>
                </div>

                <div class="col-md-6">
                  <label for="first_name" class="form-label">Nome do Responsável</label>
                  <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
                    name="first_name" value="{{ old( 'first_name' ) }}" maxlength="100">
                  @error( 'first_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="last_name" class="form-label">Sobrenome do Responsável</label>
                  <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name"
                    name="last_name" value="{{ old( 'last_name' ) }}" maxlength="100">
                  @error( 'last_name' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Setor e Porte -->
                <div class="col-md-6">
                  <label for="industry" class="form-label">Setor de Atuação</label>
                  <input type="text" class="form-control @error( 'industry' ) is-invalid @enderror" id="industry"
                    name="industry" value="{{ old( 'industry' ) }}" maxlength="255">
                  @error( 'industry' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="col-md-6">
                  <label for="company_size" class="form-label">Porte da Empresa</label>
                  <select class="form-select @error( 'company_size' ) is-invalid @enderror" id="company_size"
                    name="company_size">
                    <option value="">Selecione o porte</option>
                    <option value="micro" {{ old( 'company_size' ) == 'micro' ? 'selected' : '' }}>
                      Micro Empresa
                    </option>
                    <option value="pequena" {{ old( 'company_size' ) == 'pequena' ? 'selected' : '' }}>
                      Pequena Empresa
                    </option>
                    <option value="media" {{ old( 'company_size' ) == 'media' ? 'selected' : '' }}>
                      Média Empresa
                    </option>
                    <option value="grande" {{ old( 'company_size' ) == 'grande' ? 'selected' : '' }}>
                      Grande Empresa
                    </option>
                  </select>
                  @error( 'company_size' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

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
                  <label for="profession_id" class="form-label">Atividade Principal</label>
                  <select class="form-select @error( 'profession_id' ) is-invalid @enderror" id="profession_id"
                    name="profession_id">
                    <option value="">Selecione uma atividade</option>
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
                  <label for="notes" class="form-label">Observações</label>
                  <textarea class="form-control @error( 'notes' ) is-invalid @enderror" id="notes" name="notes" rows="3"
                    placeholder="Observações sobre a empresa">{{ old( 'notes' ) }}</textarea>
                  @error( 'notes' )
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <!-- Botões -->
                <div class="col-12">
                  <div class="d-flex justify-content-between align-items-center pt-4">
                    <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-success" id="submitBtn">
                      <i class="bi bi-check-circle me-2"></i>Criar Empresa
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
      validateCNPJ();
      validateEmailBusiness();

      // Busca de endereço por CEP
      $( '#cep' ).on( 'blur', function () {
        const cep = $( this ).val().replace( /\D/g, '' );
        if ( cep.length === 8 ) {
          fetchAddressByCEP( cep );
        }
      } );

      // Formatar automaticamente enquanto digita
      $( '#cnpj' ).on( 'input', function () {
        let cnpj = $( this ).val().replace( /\D/g, '' );
        cnpj = cnpj.replace( /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5' );
        $( this ).val( cnpj );
      } );

      $( '#phone_business, #phone_personal' ).on( 'input', function () {
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

      // Auto-preenchimento do campo área de atuação
      $( '#industry' ).on( 'input', function () {
        const industry = $( this ).val().toLowerCase();
        const areaSelect = $( '#area_of_activity_id' );

        if ( industry.includes( 'tecnologia' ) || industry.includes( 'informática' ) ) {
          areaSelect.val( '' ).find( 'option' ).each( function () {
            if ( $( this ).text().toLowerCase().includes( 'tecnologia' ) ) {
              $( this ).prop( 'selected', true );
            }
          } );
        }
      } );

      // Envio do formulário
      $( '#pessoaJuridicaForm' ).on( 'submit', function ( e ) {
        if ( !validateCNPJ() || !validateEmailBusiness() ) {
          e.preventDefault();
          return false;
        }

        $( '#submitBtn' ).prop( 'disabled', true ).html( '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...' );
      } );
    } );
  </script>
@endpush
