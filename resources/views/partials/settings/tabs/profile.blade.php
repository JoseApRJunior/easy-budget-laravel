<div class="tab-pane fade {{ $activeTab === 'profile' ? 'show active' : '' }}" id="perfil">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Perfil do Usuário</h3>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @include( 'partials.settings.profile.avatar' )
                @include( 'partials.settings.profile.info' )
            </div>
        </div>
        @include( 'partials.settings.profile.footer' )
    </div>
</div>
