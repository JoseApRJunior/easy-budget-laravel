<div class="space-y-6">
    <!-- Informações Básicas -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-person mr-2 text-green-600"></i>
            Perfil
        </h3>

        <form class="space-y-4" action="{{ route( 'settings.profile.update' ) }}" method="POST">
            @csrf
            @method( 'PUT' )

            <!-- Nome Completo -->
            <div>
                <label for="full_name_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Nome Completo
                </label>
                <input type="text" id="full_name_mobile" name="full_name"
                    value="{{ $userSettings[ 'settings' ]->full_name ?? old( 'full_name' ) }}" class="form-input w-full">
            </div>

            <!-- Telefone -->
            <div>
                <label for="phone_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Telefone
                </label>
                <input type="tel" id="phone_mobile" name="phone"
                    value="{{ $userSettings[ 'settings' ]->phone ?? old( 'phone' ) }}" class="form-input w-full"
                    placeholder="(11) 99999-9999">
            </div>

            <!-- Bio -->
            <div>
                <label for="bio_mobile" class="block text-sm font-medium text-gray-700 mb-1">
                    Bio
                </label>
                <textarea id="bio_mobile" name="bio" rows="3" class="form-textarea w-full"
                    placeholder="Conte um pouco sobre você...">{{ $userSettings[ 'settings' ]->bio ?? old( 'bio' ) }}</textarea>
            </div>

            <!-- Botão Salvar -->
            <button type="submit" class="btn btn-primary w-full">
                <i class="bi bi-check-lg mr-2"></i>
                Salvar Perfil
            </button>
        </form>
    </div>

    <!-- Avatar -->
    <div class="bg-white rounded-lg border p-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="bi bi-camera mr-2 text-purple-600"></i>
            Avatar
        </h3>

        <form action="{{ route( 'settings.avatar.update' ) }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    @if( $userSettings[ 'settings' ]->avatar_url )
                        <img src="{{ $userSettings[ 'settings' ]->avatar_url }}" alt="Avatar"
                            class="h-16 w-16 rounded-full object-cover border">
                    @else
                        <div class="h-16 w-16 rounded-full bg-gray-300 border flex items-center justify-center">
                            <i class="bi bi-person text-gray-600 text-xl"></i>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <input type="file" name="avatar" accept="image/*" class="form-input">
                    <p class="mt-1 text-sm text-gray-500">
                        Formatos: JPEG, PNG, WebP. Máx: 2MB
                    </p>
                </div>
            </div>

            <div class="mt-4 flex space-x-2">
                <button type="submit" class="btn btn-primary flex-1">
                    <i class="bi bi-upload mr-2"></i>
                    Upload
                </button>

                @if( $userSettings[ 'settings' ]->avatar_url )
                    <form action="{{ route( 'settings.avatar.remove' ) }}" method="POST" class="inline">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-outline text-red-600">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endif
            </div>
        </form>
    </div>
</div>
