<div class="space-y-8">
    <!-- Informações Básicas -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-person mr-2 text-green-600"></i>
            Informações Básicas
        </h3>

        <form class="space-y-6" action="{{ route( 'settings.profile.update' ) }}" method="POST">
            @csrf
            @method( 'PUT' )

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Nome Completo -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nome Completo
                    </label>
                    <input type="text" id="full_name" name="full_name"
                        value="{{ $userSettings[ 'settings' ]->full_name ?? old( 'full_name' ) }}" class="form-input w-full"
                        placeholder="Digite seu nome completo">
                </div>

                <!-- Data de Nascimento -->
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Data de Nascimento
                    </label>
                    <input type="date" id="birth_date" name="birth_date"
                        value="{{ $userSettings[ 'settings' ]->birth_date ?? old( 'birth_date' ) }}"
                        class="form-input w-full">
                </div>

                <!-- Telefone Pessoal -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        Telefone Pessoal
                    </label>
                    <input type="tel" id="phone" name="phone"
                        value="{{ $userSettings[ 'settings' ]->phone ?? old( 'phone' ) }}" class="form-input w-full"
                        placeholder="(11) 99999-9999">
                </div>

                <!-- Bio -->
                <div class="md:col-span-2">
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                        Bio
                    </label>
                    <textarea id="bio" name="bio" rows="3" class="form-textarea w-full"
                        placeholder="Conte um pouco sobre você...">{{ $userSettings[ 'settings' ]->bio ?? old( 'bio' ) }}</textarea>
                    <p class="mt-1 text-sm text-gray-500">
                        Máximo 1000 caracteres
                    </p>
                </div>
            </div>
        </form>
    </div>

    <!-- Redes Sociais -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-share mr-2 text-blue-600"></i>
            Redes Sociais
        </h3>

        <form class="space-y-6" action="{{ route( 'settings.profile.update' ) }}" method="POST">
            @csrf
            @method( 'PUT' )

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Facebook -->
                <div>
                    <label for="social_facebook" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-facebook mr-1"></i>
                        Facebook
                    </label>
                    <input type="url" id="social_facebook" name="social_facebook"
                        value="{{ $userSettings[ 'settings' ]->social_facebook ?? old( 'social_facebook' ) }}"
                        class="form-input w-full" placeholder="https://facebook.com/seu-perfil">
                </div>

                <!-- Twitter -->
                <div>
                    <label for="social_twitter" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-twitter mr-1"></i>
                        Twitter
                    </label>
                    <input type="url" id="social_twitter" name="social_twitter"
                        value="{{ $userSettings[ 'settings' ]->social_twitter ?? old( 'social_twitter' ) }}"
                        class="form-input w-full" placeholder="https://twitter.com/seu-usuario">
                </div>

                <!-- LinkedIn -->
                <div>
                    <label for="social_linkedin" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-linkedin mr-1"></i>
                        LinkedIn
                    </label>
                    <input type="url" id="social_linkedin" name="social_linkedin"
                        value="{{ $userSettings[ 'settings' ]->social_linkedin ?? old( 'social_linkedin' ) }}"
                        class="form-input w-full" placeholder="https://linkedin.com/in/seu-perfil">
                </div>

                <!-- Instagram -->
                <div>
                    <label for="social_instagram" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="bi bi-instagram mr-1"></i>
                        Instagram
                    </label>
                    <input type="url" id="social_instagram" name="social_instagram"
                        value="{{ $userSettings[ 'settings' ]->social_instagram ?? old( 'social_instagram' ) }}"
                        class="form-input w-full" placeholder="https://instagram.com/seu-usuario">
                </div>
            </div>
        </form>
    </div>

    <!-- Avatar -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-camera mr-2 text-purple-600"></i>
            Avatar
        </h3>

        <form class="space-y-6" action="{{ route( 'settings.avatar.update' ) }}" method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    @if( $userSettings[ 'settings' ]->avatar_url )
                        <img src="{{ $userSettings[ 'settings' ]->avatar_url }}" alt="Avatar"
                            class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-lg">
                    @else
                        <div
                            class="h-20 w-20 rounded-full bg-gray-300 border-4 border-white shadow-lg flex items-center justify-center">
                            <i class="bi bi-person text-gray-600 text-3xl"></i>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-4">
                        <input type="file" name="avatar" accept="image/*" class="form-input flex-1">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload mr-2"></i>
                            Upload
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-gray-500">
                        Formatos aceitos: JPEG, PNG, GIF, WebP. Tamanho máximo: 2MB. Dimensões mínimas: 100x100px
                    </p>
                </div>
            </div>

            @if( $userSettings[ 'settings' ]->avatar_url )
                <div class="flex justify-end">
                    <form action="{{ route( 'settings.avatar.remove' ) }}" method="POST" class="inline">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-outline text-red-600 hover:bg-red-50">
                            <i class="bi bi-trash mr-2"></i>
                            Remover Avatar
                        </button>
                    </form>
                </div>
            @endif
        </form>
    </div>

    <!-- Botões de Ação -->
    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
        <button type="button" class="btn btn-secondary" onclick="restoreDefaults()">
            <i class="bi bi-arrow-counterclockwise mr-2"></i>
            Restaurar Padrões
        </button>
        <button type="submit" form="profile-form" class="btn btn-primary">
            <i class="bi bi-check-lg mr-2"></i>
            Salvar Alterações
        </button>
    </div>
</div>

<script>
    // Máscara para telefone
    document.getElementById( 'phone' )?.addEventListener( 'input', function ( e ) {
        let value = e.target.value.replace( /\D/g, '' );
        if ( value.length <= 11 ) {
            if ( value.length <= 10 ) {
                value = value.replace( /(\d{2})(\d{4})(\d{4})/, '($1) $2-$3' );
            } else {
                value = value.replace( /(\d{2})(\d{5})(\d{4})/, '($1) $2-$3' );
            }
            e.target.value = value;
        }
    } );

    // Contador de caracteres para bio
    document.getElementById( 'bio' )?.addEventListener( 'input', function ( e ) {
        const counter = document.getElementById( 'bio-counter' );
        const remaining = 1000 - e.target.value.length;
        counter.textContent = remaining + ' caracteres restantes';
    } );
</script>
