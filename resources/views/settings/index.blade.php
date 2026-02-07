<x-app-layout title="Configurações do Sistema">
    <x-layout.page-container>
        @php($activeTab = $activeTab ?? 'profile')

        <x-layout.page-header
            title="Configurações do Sistema"
            icon="gear"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => '#'
            ]">
            <p class="text-muted mb-0 small">Gerencie as preferências e configurações da sua conta e empresa</p>
        </x-layout.page-header>

        <x-layout.grid-row>
            <!-- Menu Lateral -->
            <x-layout.grid-col size="col-12 col-md-3">
                <x-settings.sidebar :active-tab="$activeTab" />
            </x-layout.grid-col>

            <!-- Conteúdo -->
            <x-layout.grid-col size="col-12 col-md-9">
                <div class="tab-content">
                    <x-settings.tabs.profile :active-tab="$activeTab" />
                    <x-settings.tabs.general :active-tab="$activeTab" />
                    <x-settings.tabs.notifications :active-tab="$activeTab" :tabs="$tabs" />
                    <x-settings.tabs.security :active-tab="$activeTab" :tabs="$tabs" />
                    <x-settings.tabs.integration :active-tab="$activeTab" :tabs="$tabs" />
                    <x-settings.tabs.customization :active-tab="$activeTab" :tabs="$tabs" />
                    <x-settings.tabs.provider :active-tab="$activeTab" :tabs="$tabs" />
                </div>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>
</x-app-layout>

@section( 'scripts' )
    @parent
    <script src="{{ asset( 'assets/js/settings.js' ) }}"></script>
@endsection
