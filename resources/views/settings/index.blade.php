@extends( 'layouts.app' )

@section( 'title', 'Configurações' )

@section( 'content' )
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto" x-data="settingsManager()">
            <!-- Cabeçalho das Configurações -->
            <div class="mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Configurações</h1>
                        <p class="text-gray-600">Gerencie as configurações do seu sistema e personalize sua experiência</p>
                    </div>
                    <div class="flex space-x-3">
                        <button @click="createBackup()" class="btn btn-secondary">
                            <i class="bi bi-archive mr-2"></i>Backup
                        </button>
                        <button @click="restoreDefaults()" class="btn btn-outline">
                            <i class="bi bi-arrow-counterclockwise mr-2"></i>Padrões
                        </button>
                    </div>
                </div>
            </div>

            <!-- Layout Desktop com Sidebar -->
            <div class="bg-white rounded-lg shadow-md hidden lg:flex">
                <!-- Sidebar das Abas -->
                <div class="w-1/4 border-r border-gray-200">
                    <nav class="p-6">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <button @click="switchTab(tab.key)"
                                :class="activeTab === tab.key ? 'bg-blue-50 border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50'"
                                class="w-full text-left py-3 px-4 border-l-4 font-medium text-sm mb-1 rounded-r-md transition-colors duration-200 flex items-center">
                                <i :class="tab.icon" class="mr-3"></i>
                                <span x-text="tab.label"></span>
                            </button>
                        </template>
                    </nav>
                </div>

                <!-- Conteúdo das Abas -->
                <div class="w-3/4 p-6">
                    <!-- Loading State -->
                    <div x-show="loading" class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        <span class="ml-3 text-gray-600">Carregando...</span>
                    </div>

                    <!-- Aba Geral -->
                    <div x-show="activeTab === 'general' && !loading" x-transition>
                        @include( 'settings.tabs.general' )
                    </div>

                    <!-- Aba Perfil -->
                    <div x-show="activeTab === 'profile' && !loading" x-transition>
                        @include( 'settings.tabs.profile' )
                    </div>

                    <!-- Aba Segurança -->
                    <div x-show="activeTab === 'security' && !loading" x-transition>
                        @include( 'settings.tabs.security' )
                    </div>

                    <!-- Aba Notificações -->
                    <div x-show="activeTab === 'notifications' && !loading" x-transition>
                        @include( 'settings.tabs.notifications' )
                    </div>

                    <!-- Aba Integrações -->
                    <div x-show="activeTab === 'integrations' && !loading" x-transition>
                        @include( 'settings.tabs.integrations' )
                    </div>

                    <!-- Aba Personalização -->
                    <div x-show="activeTab === 'customization' && !loading" x-transition>
                        @include( 'settings.tabs.customization' )
                    </div>
                </div>
            </div>

            <!-- Layout Mobile com Dropdown -->
            <div class="bg-white rounded-lg shadow-md lg:hidden">
                <!-- Seletor de Aba Mobile -->
                <div class="border-b border-gray-200 p-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Seção</label>
                    <select x-model="activeTab" @change="switchTab(activeTab)" class="form-select w-full">
                        <template x-for="(tab, index) in tabs" :key="index">
                            <option :value="tab.key" x-text="tab.label"></option>
                        </template>
                    </select>
                </div>

                <!-- Conteúdo das Abas Mobile -->
                <div class="p-4">
                    <!-- Aba Geral Mobile -->
                    <div x-show="activeTab === 'general'" x-transition>
                        @include( 'settings.tabs.general-mobile' )
                    </div>

                    <!-- Aba Perfil Mobile -->
                    <div x-show="activeTab === 'profile'" x-transition>
                        @include( 'settings.tabs.profile-mobile' )
                    </div>

                    <!-- Aba Segurança Mobile -->
                    <div x-show="activeTab === 'security'" x-transition>
                        @include( 'settings.tabs.security-mobile' )
                    </div>

                    <!-- Aba Notificações Mobile -->
                    <div x-show="activeTab === 'notifications'" x-transition>
                        @include( 'settings.tabs.notifications-mobile' )
                    </div>

                    <!-- Aba Integrações Mobile -->
                    <div x-show="activeTab === 'integrations'" x-transition>
                        @include( 'settings.tabs.integrations-mobile' )
                    </div>

                    <!-- Aba Personalização Mobile -->
                    <div x-show="activeTab === 'customization'" x-transition>
                        @include( 'settings.tabs.customization-mobile' )
                    </div>
                </div>
            </div>

            <!-- Modal de Backup -->
            <div x-show="showBackupModal" x-cloak
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Criar Backup</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4">
                                Escolha o tipo de backup que deseja criar:
                            </p>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" x-model="backupType" value="full" class="form-radio">
                                    <span class="ml-2">Backup Completo (Usuário + Sistema)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" x-model="backupType" value="user" class="form-radio">
                                    <span class="ml-2">Apenas Configurações do Usuário</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" x-model="backupType" value="system" class="form-radio">
                                    <span class="ml-2">Apenas Configurações do Sistema</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6 space-x-3">
                            <button @click="showBackupModal = false" class="btn btn-secondary">
                                Cancelar
                            </button>
                            <button @click="confirmBackup()" :disabled="creatingBackup" class="btn btn-primary">
                                <span x-show="!creatingBackup">Criar Backup</span>
                                <span x-show="creatingBackup" class="flex items-center">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                    Criando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Confirmação de Restauração -->
            <div x-show="showRestoreModal" x-cloak
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Restaurar Padrões</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 mb-4">
                                Esta ação irá restaurar todas as configurações para os valores padrão. Esta ação não pode
                                ser desfeita.
                            </p>
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="radio" x-model="restoreType" value="user" class="form-radio">
                                    <span class="ml-2">Apenas Configurações do Usuário</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" x-model="restoreType" value="system" class="form-radio">
                                    <span class="ml-2">Apenas Configurações do Sistema</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end mt-6 space-x-3">
                            <button @click="showRestoreModal = false" class="btn btn-secondary">
                                Cancelar
                            </button>
                            <button @click="confirmRestore()" :disabled="restoring" class="btn btn-danger">
                                <span x-show="!restoring">Restaurar Padrões</span>
                                <span x-show="restoring" class="flex items-center">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                    Restaurando...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        function settingsManager() {
            return {
                activeTab: 'general',
                loading: false,
                showBackupModal: false,
                showRestoreModal: false,
                backupType: 'full',
                restoreType: 'user',
                creatingBackup: false,
                restoring: false,

                tabs: [
                    { key: 'general', label: 'Geral', icon: 'bi bi-building text-blue-600' },
                    { key: 'profile', label: 'Perfil', icon: 'bi bi-person text-green-600' },
                    { key: 'security', label: 'Segurança', icon: 'bi bi-shield-check text-red-600' },
                    { key: 'notifications', label: 'Notificações', icon: 'bi bi-bell text-yellow-600' },
                    { key: 'integrations', label: 'Integrações', icon: 'bi bi-link text-purple-600' },
                    { key: 'customization', label: 'Personalização', icon: 'bi bi-palette text-indigo-600' }
                ],

                switchTab( tabName ) {
                    this.activeTab = tabName;
                    this.saveTabPreference( tabName );

                    // Atualiza URL sem recarregar página
                    const url = new URL( window.location );
                    url.searchParams.set( 'tab', tabName );
                    window.history.pushState( {}, '', url );
                },

                saveTabPreference( tabName ) {
                    localStorage.setItem( 'settings_active_tab', tabName );
                },

                loadTabPreference() {
                    const savedTab = localStorage.getItem( 'settings_active_tab' );
                    if ( savedTab && this.tabs.find( tab => tab.key === savedTab ) ) {
                        this.activeTab = savedTab;
                    }
                },

                createBackup() {
                    this.showBackupModal = true;
                },

                confirmBackup() {
                    if ( !this.backupType ) return;

                    this.creatingBackup = true;

                    fetch( '/api/settings/backup', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                        },
                        body: JSON.stringify( {
                            type: this.backupType
                        } )
                    } )
                        .then( response => response.json() )
                        .then( data => {
                            this.creatingBackup = false;
                            this.showBackupModal = false;

                            if ( data.success ) {
                                this.showSuccess( 'Backup criado com sucesso!' );
                                setTimeout( () => location.reload(), 1500 );
                            } else {
                                this.showError( data.message || 'Erro ao criar backup' );
                            }
                        } )
                        .catch( error => {
                            this.creatingBackup = false;
                            this.showError( 'Erro interno do servidor' );
                        } );
                },

                restoreDefaults() {
                    this.showRestoreModal = true;
                },

                confirmRestore() {
                    if ( !this.restoreType ) return;

                    this.restoring = true;

                    fetch( '/api/settings/restore-defaults', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                        },
                        body: JSON.stringify( {
                            type: this.restoreType
                        } )
                    } )
                        .then( response => response.json() )
                        .then( data => {
                            this.restoring = false;
                            this.showRestoreModal = false;

                            if ( data.success ) {
                                this.showSuccess( 'Configurações padrão restauradas com sucesso!' );
                                setTimeout( () => location.reload(), 1500 );
                            } else {
                                this.showError( data.message || 'Erro ao restaurar configurações' );
                            }
                        } )
                        .catch( error => {
                            this.restoring = false;
                            this.showError( 'Erro interno do servidor' );
                        } );
                },

                showSuccess( message ) {
                    // Implementar sistema de notificações visuais
                    console.log( 'Success:', message );
                },

                showError( message ) {
                    // Implementar sistema de notificações visuais
                    console.error( 'Error:', message );
                },

                init() {
                    this.loadTabPreference();
                }
            }
        }
    </script>
@endpush
