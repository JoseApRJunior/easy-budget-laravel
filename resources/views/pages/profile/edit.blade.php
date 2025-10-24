@extends( 'layouts.app' )

@section( 'title', 'Perfil Pessoal' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person me-2"></i>Perfil Pessoal
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Perfil Pessoal</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route( 'profile.update' ) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method( 'PATCH' )

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
                                    <label for="name" class="form-label">Nome Completo</label>
                                    <input type="text" class="form-control @error( 'name' ) is-invalid @enderror" id="name"
                                        name="name" value="{{ old( 'name', $user->name ?? '' ) }}">
                                    @error( 'name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control @error( 'email' ) is-invalid @enderror"
                                        id="email" name="email" value="{{ old( 'email', $user->email ?? '' ) }}">
                                    @error( 'email' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="avatar" class="form-label">Avatar (Foto de Perfil)</label>
                                    <input type="file" class="form-control @error( 'avatar' ) is-invalid @enderror"
                                        id="avatar" name="avatar" accept="image/png,image/jpeg,image/jpg,gif,webp">
                                    @error( 'avatar' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    @if( $user->avatar ?? false )
                                        <div class="mt-2">
                                            <img src="{{ asset( 'storage/' . $user->avatar ) }}" alt="Avatar atual"
                                                class="img-thumbnail" width="100" height="100">
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Redes Sociais -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-share me-2"></i>Redes Sociais
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="social_facebook" class="form-label">Facebook</label>
                                    <input type="url" class="form-control @error( 'social_facebook' ) is-invalid @enderror"
                                        id="social_facebook" name="social_facebook"
                                        value="{{ old( 'social_facebook', $settings[ 'social_links' ][ 'facebook' ] ?? '' ) }}"
                                        placeholder="https://facebook.com/seuperfil">
                                    @error( 'social_facebook' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="social_twitter" class="form-label">Twitter</label>
                                    <input type="url" class="form-control @error( 'social_twitter' ) is-invalid @enderror"
                                        id="social_twitter" name="social_twitter"
                                        value="{{ old( 'social_twitter', $settings[ 'social_links' ][ 'twitter' ] ?? '' ) }}"
                                        placeholder="https://twitter.com/seuperfil">
                                    @error( 'social_twitter' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="social_linkedin" class="form-label">LinkedIn</label>
                                    <input type="url" class="form-control @error( 'social_linkedin' ) is-invalid @enderror"
                                        id="social_linkedin" name="social_linkedin"
                                        value="{{ old( 'social_linkedin', $settings[ 'social_links' ][ 'linkedin' ] ?? '' ) }}"
                                        placeholder="https://linkedin.com/in/seuperfil">
                                    @error( 'social_linkedin' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="social_instagram" class="form-label">Instagram</label>
                                    <input type="url" class="form-control @error( 'social_instagram' ) is-invalid @enderror"
                                        id="social_instagram" name="social_instagram"
                                        value="{{ old( 'social_instagram', $settings[ 'social_links' ][ 'instagram' ] ?? '' ) }}"
                                        placeholder="https://instagram.com/seuperfil">
                                    @error( 'social_instagram' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
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
                        <i class="bi bi-check-circle me-2"></i>Atualizar Perfil
                    </button>
                    <a href="{{ route( 'settings.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route( 'provider.business.edit' ) }}" class="btn btn-outline-info">
                        <i class="bi bi-building me-2"></i>Dados Empresariais
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <script src="{{ asset( 'assets/js/modules/image-preview.js' ) }}" type="module"></script>
    <script>
        document.getElementById( 'avatar' )?.addEventListener( 'change', function ( e ) {
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
