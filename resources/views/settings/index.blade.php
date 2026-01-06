@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        @php($activeTab = $activeTab ?? 'profile')

        <x-page-header
            title="Configurações do Sistema"
            icon="gear"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => '#'
            ]">
            <p class="text-muted mb-0 small">Gerencie as preferências e configurações da sua conta e empresa</p>
        </x-page-header>

        <div class="row g-4">
            <!-- Menu Lateral -->
            <div class="col-12 col-md-3">
                @include( 'partials.settings.sidebar' )
            </div>

            <!-- Conteúdo -->
            <div class="col-12 col-md-9">
                <div class="tab-content">
                    @include( 'partials.settings.tabs.profile' )
                    @include( 'partials.settings.tabs.general' )
                    @include( 'partials.settings.tabs.notifications' )
                    @include( 'partials.settings.tabs.security' )
                    @include( 'partials.settings.tabs.integration' )
                    @include( 'partials.settings.tabs.customization' )
                    @include( 'partials.settings.tabs.provider' )
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    @parent
    <script src="{{ asset( 'assets/js/settings.js' ) }}"></script>
@endsection
