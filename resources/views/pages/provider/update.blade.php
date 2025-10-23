@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-gear me-2"></i>Atualizar Perfil
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'settings.index' ) }}">Perfil</a></li>
                    <li class="breadcrumb-item active">Atualizar</li>
                </ol>
            </nav>
        </div>

        <form id="updateForm" action="{{ route( 'provider.update' ) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method( 'PUT' )
            <div class="row g-4">
                <!-- Dados Pessoais -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>Dados Pessoais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @include( 'partials.provider.personal_fields' )
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Profissionais -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-briefcase me-2"></i>Dados Profissionais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @include( 'partials.provider.professional_fields' )
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-geo-alt me-2"></i>Endereço
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                @include( 'partials.provider.address_fields' )
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo e Descrição -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-transparent border-0 py-1">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2 "></i>
                                <span>Informações Adicionais</span>
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row align-items-start g-4">
                                <!-- Coluna da Logo -->
                                <div class="col-md-2 text-center">
                                    <div class="logo-upload-container">
                                        <div class="logo-preview-wrapper mb-3 p-3 bg-light rounded-3 hover-shadow">
                                            <img id="preview"
                                                src="{{ $provider->user->logo ? asset( 'storage/' . $provider->user->logo ) : asset( 'assets/img/img_not_found.png' ) }}"
                                                alt="Logo da empresa" class="logo-image img-fluid rounded-3 shadow-sm"
                                                width="150" height="150">
                                        </div>
                                        <div class="upload-controls">
                                            <label for="logo" class="btn btn-outline-primary upload-btn rounded-pill px-4">
                                                <i class="bi bi-camera-fill me-2" aria-hidden="true"></i>
                                                <span>Alterar Logo</span>
                                            </label>
                                            <input type="file" class="visually-hidden" id="logo" name="logo"
                                                accept="image/png,image/jpeg,image/jpg" aria-label="Selecionar nova logo"
                                                data-max-size="5242880">
                                        </div>
                                        <div class="upload-feedback mt-2">
                                            @error( 'logo' ) <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input @error( 'terms_accepted' ) is-invalid @enderror"
                                    type="checkbox" id="terms_accepted" name="terms_accepted" required>
                                <label class="form-check-label" for="terms_accepted">
                                    Eu li e aceito os
                                    <a href="{{ route( 'terms' ) }}" target="_blank">Termos de Serviço</a>
                                    e a
                                    <a href="{{ route( 'privacy' ) }}" target="_blank">Política de Privacidade</a>.
                                </label>
                                @error( 'terms_accepted' ) <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary" id="updateButton">
                        <i class="bi bi-check-circle me-2"></i>Atualizar Cadastro
                    </button>
                    <a href="{{ route( 'settings.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
                <small class="text-muted">
                    Última atualização: {{ $provider->updated_at->format( 'd/m/Y H:i' ) }}
                </small>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/masks/index.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/form-validation.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/cep-service.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/image-preview.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/modules/character-counter.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/provider_update.js' ) }}" type="module"></script>
    <script>
        $( document ).ready( function () {
            $( '#phone' ).mask( '(00) 00000-0000' );
            $( '#phone_business' ).mask( '(00) 00000-0000' );
            $( '#cnpj' ).mask( '00.000.000/0000-00' );
            $( '#cpf' ).mask( '000.000.000-00' );
            $( '#cep' ).mask( '00000-000' );
        } );
    </script>
    <script>
        document.getElementById( 'logo' ).addEventListener( 'change', function ( e ) {
            const file = e.target.files[0];
            const maxSize = this.dataset.maxSize;

            if ( file ) {
                if ( file.size > maxSize ) {
                    alert( 'O arquivo é muito grande. O tamanho máximo permitido é 5MB.' );
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function ( e ) {
                    document.getElementById( 'preview' ).src = e.target.result;
                }
                reader.readAsDataURL( file );
            }
        } );

        document.getElementById( 'avatar' ).addEventListener( 'change', function ( e ) {
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
                    const img = document.createElement( 'img' );
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.width = 100;
                    img.height = 100;
                    const container = document.querySelector( '#avatar + div' );
                    if ( container ) {
                        container.innerHTML = '';
                        container.appendChild( img );
                    }
                }
                reader.readAsDataURL( file );
            }
        } );
    </script>
@endpush
