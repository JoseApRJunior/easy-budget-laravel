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
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'settings.index' ) }}">Configurações</a></li>
                    <li class="breadcrumb-item active">Atualizar</li>
                </ol>
            </nav>
        </div>

        <!-- Mensagem informativa sobre nova estrutura -->
        <div class="alert alert-info border-0 shadow-sm" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                <div>
                    <h5 class="alert-heading mb-1">Nova estrutura de perfil implementada!</h5>
                    <p class="mb-0">
                        Agora você pode gerenciar seus dados de forma mais organizada.
                        Escolha qual seção deseja atualizar:
                    </p>
                </div>
            </div>
        </div>

        <!-- Botões para as novas telas -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-person-circle text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Perfil Pessoal</h5>
                        <p class="card-text text-muted">
                            Atualize seus dados pessoais, foto de perfil e redes sociais.
                        </p>
                        <a href="{{ route( 'settings.profile.edit' ) }}" class="btn btn-primary">
                            <i class="bi bi-person me-2"></i>Editar Perfil Pessoal
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="bi bi-building text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="card-title">Dados Empresariais</h5>
                        <p class="card-text text-muted">
                            Gerencie informações da empresa, endereço, contato e logo.
                        </p>
                        <a href="{{ route( 'provider.business.edit' ) }}" class="btn btn-success">
                            <i class="bi bi-building me-2"></i>Editar Dados Empresariais
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão para voltar -->
        <div class="text-center mt-4">
            <a href="{{ route( 'settings.index' ) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Voltar para Configurações
            </a>
        </div>

        <!-- Formulário legacy (oculto) para compatibilidade -->
        <form id="updateForm" action="{{ route( 'provider.update' ) }}" method="POST" enctype="multipart/form-data"
            style="display: none;">
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
    <script>
        // Scripts específicos para a página de transição
        document.addEventListener( 'DOMContentLoaded', function () {
            // Adicionar feedback visual aos botões
            const buttons = document.querySelectorAll( '.btn' );
            buttons.forEach( button => {
                button.addEventListener( 'click', function () {
                    this.style.transform = 'scale(0.95)';
                    setTimeout( () => {
                        this.style.transform = 'scale(1)';
                    }, 150 );
                } );
            } );
        } );
    </script>
@endpush
