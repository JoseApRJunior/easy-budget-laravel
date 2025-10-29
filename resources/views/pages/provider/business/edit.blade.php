@extends( 'layouts.app' )

@section( 'title', 'Dados Empresariais' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-building me-2"></i>Dados Empresariais
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'settings.index' ) }}">Configurações</a></li>
                    <li class="breadcrumb-item active">Dados Empresariais</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route( 'provider.business.update' ) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method( 'PUT' )

            <div class="row g-4">
                <!-- Dados Pessoais -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>Dados Pessoais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="first_name" class="form-label">Nome</label>
                                    <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                           id="first_name" name="first_name"
                                           value="{{ old('first_name', $provider->commonData?->first_name ?? '') }}">
                                    @error('first_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="last_name" class="form-label">Sobrenome</label>
                                    <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                           id="last_name" name="last_name"
                                           value="{{ old('last_name', $provider->commonData?->last_name ?? '') }}">
                                    @error('last_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="birth_date" class="form-label">Data de Nascimento</label>
                                    <input type="date" class="form-control @error('birth_date') is-invalid @enderror"
                                           id="birth_date" name="birth_date"
                                           value="{{ old('birth_date', $provider->commonData?->birth_date ?? '') }}"
                                           max="{{ \Carbon\Carbon::now()->subYears(18)->format('Y-m-d') }}">
                                    @error('birth_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="email_personal" class="form-label">Email Pessoal</label>
                                    <input type="email" class="form-control @error('email_personal') is-invalid @enderror"
                                           id="email_personal" name="email_personal"
                                           value="{{ old('email_personal', $provider->contact?->email ?? '') }}">
                                    @error('email_personal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="phone_personal" class="form-label">Telefone Pessoal</label>
                                    <input type="tel" class="form-control @error('phone_personal') is-invalid @enderror"
                                           id="phone_personal" name="phone_personal"
                                           value="{{ old('phone_personal', $provider->contact?->phone ?? '') }}">
                                    @error('phone_personal')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Profissionais -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-briefcase me-2"></i>Dados Profissionais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="company_name" class="form-label">Nome da Empresa</label>
                                    <input type="text" class="form-control @error( 'company_name' ) is-invalid @enderror"
                                           id="company_name" name="company_name"
                                           value="{{ old( 'company_name', $provider->commonData?->company_name ?? '' ) }}">
                                    @error( 'company_name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="cnpj" class="form-label">CNPJ</label>
                                    <input type="text" class="form-control @error( 'cnpj' ) is-invalid @enderror"
                                           id="cnpj" name="cnpj"
                                           value="{{ old( 'cnpj', $provider->commonData?->cnpj ?? '' ) }}">
                                    @error( 'cnpj' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror"
                                           id="cpf" name="cpf"
                                           value="{{ old( 'cpf', $provider->commonData?->cpf ?? '' ) }}">
                                    @error( 'cpf' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
                                    <select name="area_of_activity_id" class="form-select @error( 'area_of_activity_id' ) is-invalid @enderror"
                                            id="area_of_activity" required>
                                        <option value="">Selecione uma área</option>
                                        @foreach ( $areas_of_activity as $area )
                                            <option value="{{ $area->id }}"
                                                {{ old( 'area_of_activity_id', $provider->commonData?->area_of_activity_id ?? '' ) == $area->id ? 'selected' : '' }}>
                                                {{ $area->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error( 'area_of_activity_id' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="profession_id" class="form-label">Profissão</label>
                                    <select name="profession_id" class="form-select @error( 'profession_id' ) is-invalid @enderror"
                                            id="profession" required>
                                        <option value="">Selecione uma profissão</option>
                                        @foreach ( $professions as $prof )
                                            <option value="{{ $prof->id }}"
                                                {{ old( 'profession_id', $provider->commonData?->profession_id ?? '' ) == $prof->id ? 'selected' : '' }}>
                                                {{ $prof->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error( 'profession_id' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="description" class="form-label">Descrição Profissional</label>
                                    <textarea class="form-control @error( 'description' ) is-invalid @enderror"
                                              id="description" name="description" rows="4" maxlength="250"
                                              placeholder="Descreva sua experiência profissional...">{{ old( 'description', $provider->commonData?->description ?? '' ) }}</textarea>
                                    @error( 'description' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contato e Endereço -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-envelope me-2"></i>Contato e Endereço
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="email_business" class="form-label">Email Empresarial</label>
                                    <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror"
                                           id="email_business" name="email_business"
                                           value="{{ old( 'email_business', $provider->contact?->email_business ?? '' ) }}">
                                    @error( 'email_business' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="phone_business" class="form-label">Telefone Empresarial</label>
                                    <input type="tel" class="form-control @error( 'phone_business' ) is-invalid @enderror"
                                           id="phone_business" name="phone_business"
                                           value="{{ old( 'phone_business', $provider->contact?->phone_business ?? '' ) }}">
                                    @error( 'phone_business' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control @error( 'website' ) is-invalid @enderror"
                                           id="website" name="website"
                                           value="{{ old( 'website', $provider->contact?->website ?? '' ) }}">
                                    @error( 'website' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control @error( 'cep' ) is-invalid @enderror"
                                           id="cep" name="cep"
                                           value="{{ old( 'cep', $provider->address?->cep ?? '' ) }}" required>
                                    @error( 'cep' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">Endereço</label>
                                    <input type="text" class="form-control @error( 'address' ) is-invalid @enderror"
                                           id="address" name="address"
                                           value="{{ old( 'address', $provider->address?->address ?? '' ) }}" required>
                                    @error( 'address' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="address_number" class="form-label">Número</label>
                                    <input type="text" class="form-control @error( 'address_number' ) is-invalid @enderror"
                                           id="address_number" name="address_number"
                                           value="{{ old( 'address_number', $provider->address?->address_number ?? '' ) }}">
                                    @error( 'address_number' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="neighborhood" class="form-label">Bairro</label>
                                    <input type="text" class="form-control @error( 'neighborhood' ) is-invalid @enderror"
                                           id="neighborhood" name="neighborhood"
                                           value="{{ old( 'neighborhood', $provider->address?->neighborhood ?? '' ) }}" required>
                                    @error( 'neighborhood' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="city" class="form-label">Cidade</label>
                                    <input type="text" class="form-control @error( 'city' ) is-invalid @enderror"
                                           id="city" name="city"
                                           value="{{ old( 'city', $provider->address?->city ?? '' ) }}" required>
                                    @error( 'city' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="state" class="form-label">Estado</label>
                                    <input type="text" class="form-control @error( 'state' ) is-invalid @enderror"
                                           id="state" name="state"
                                           value="{{ old( 'state', $provider->address?->state ?? '' ) }}" required>
                                    @error( 'state' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo da Empresa -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-image me-2"></i>Logo da Empresa
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center g-4">
                                <div class="col-md-3 text-center">
                                    <div class="logo-upload-container">
                                        <div class="logo-preview-wrapper mb-3 p-3 bg-light rounded-3">
                                            <img id="logo-preview"
                                                 src="{{ $provider->user->logo ? asset( 'storage/' . $provider->user->logo ) : asset( 'assets/img/img_not_found.png' ) }}"
                                                 alt="Logo da empresa" class="logo-image img-fluid rounded-3 shadow-sm"
                                                 width="150" height="150">
                                        </div>
                                        <div class="upload-controls">
                                            <label for="logo" class="btn btn-outline-primary upload-btn rounded-pill px-4">
                                                <i class="bi bi-camera-fill me-2"></i>Alterar Logo
                                            </label>
                                            <input type="file" class="visually-hidden" id="logo" name="logo"
                                                   accept="image/png,image/jpeg,image/jpg" aria-label="Selecionar nova logo">
                                        </div>
                                        <div class="upload-feedback mt-2">
                                            @error( 'logo' )
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-9">
                                    <p class="text-muted mb-0">
                                        A logo da empresa será exibida em orçamentos, faturas e relatórios.
                                        Recomendamos usar uma imagem de alta qualidade com fundo transparente.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Atualizar Dados Empresariais
                    </button>
                    <a href="{{ route( 'settings.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route( 'settings.profile.edit' ) }}" class="btn btn-outline-info">
                        <i class="bi bi-person me-2"></i>Perfil Pessoal
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/masks/index.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/cep-service.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/image-preview.js' ) }}" type="module"></script>
    <script>
        $( document ).ready( function () {
            $( '#cnpj' ).mask( '00.000.000/0000-00' );
            $( '#cpf' ).mask( '000.000.000-00' );
            $( '#cep' ).mask( '00000-000' );
            $( '#phone_personal' ).mask( '(00) 00000-0000' );
            $( '#birth_date' ).mask( '00/00/0000' );
        } );

        document.getElementById( 'logo' )?.addEventListener( 'change', function ( e ) {
            const file = e.target.files[0];
            const maxSize = 5242880; // 5MB

            if ( file ) {
                if ( file.size > maxSize ) {
                    alert( 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.' );
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function ( e ) {
                    document.getElementById( 'logo-preview' ).src = e.target.result;
                }
                reader.readAsDataURL( file );
            }
        } );
    </script>
@endpush
