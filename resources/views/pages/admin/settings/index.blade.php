<x-app-layout title="Configurações do Sistema">
    <x-layout.page-container>
        <x-layout.page-header
            title="Configurações do Sistema"
            icon="gear"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'Configurações' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button type="submit" form="settingsForm" variant="primary" icon="save" label="Salvar Alterações" />
            </x-slot:actions>
        </x-layout.page-header>

        <form id="settingsForm" method="POST" action="{{ route('admin.settings.store') }}">
            @csrf
            
            <x-layout.grid-row>
                <!-- General Settings -->
                <div class="col-md-6 mb-4">
                    <x-ui.card class="h-100">
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-sliders me-2"></i>Geral
                            </h5>
                        </x-slot:header>
                        
                        <div class="mb-3">
                            <x-ui.form.input 
                                name="app_name" 
                                id="app_name" 
                                label="Nome da Aplicação" 
                                value="{{ old('app_name', config('app.name')) }}" 
                            />
                        </div>

                        <div class="mb-3">
                            <x-ui.form.input 
                                type="email"
                                name="admin_email" 
                                id="admin_email" 
                                label="Email do Administrador" 
                                value="{{ old('admin_email', 'admin@easybudget.com') }}" 
                            />
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Manutenção</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1">
                                <label class="form-check-label" for="maintenance_mode">Ativar Modo de Manutenção</label>
                            </div>
                            <small class="text-muted">Quando ativo, apenas administradores podem acessar o sistema.</small>
                        </div>
                    </x-ui.card>
                </div>

                <!-- Security Settings -->
                <div class="col-md-6 mb-4">
                    <x-ui.card class="h-100">
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-shield-lock me-2"></i>Segurança
                            </h5>
                        </x-slot:header>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Autenticação</label>
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="two_factor" name="two_factor" value="1" checked>
                                <label class="form-check-label" for="two_factor">Forçar Autenticação em Dois Fatores (2FA)</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="social_login" name="social_login" value="1" checked>
                                <label class="form-check-label" for="social_login">Permitir Login Social</label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <x-ui.form.input 
                                type="number"
                                name="session_lifetime" 
                                id="session_lifetime" 
                                label="Tempo de Expiração da Sessão (minutos)" 
                                value="{{ old('session_lifetime', 120) }}" 
                            />
                        </div>
                    </x-ui.card>
                </div>

                <!-- Notification Settings -->
                <div class="col-md-12 mb-4">
                    <x-ui.card>
                        <x-slot:header>
                            <h5 class="mb-0 text-primary fw-bold">
                                <i class="bi bi-bell me-2"></i>Notificações
                            </h5>
                        </x-slot:header>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Email</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_signup" name="notify_signup" value="1" checked>
                                        <label class="form-check-label" for="notify_signup">Novos Cadastros</label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_error" name="notify_error" value="1" checked>
                                        <label class="form-check-label" for="notify_error">Erros Críticos</label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Sistema</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="notify_maintenance" name="notify_maintenance" value="1">
                                        <label class="form-check-label" for="notify_maintenance">Avisos de Manutenção</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </x-layout.grid-row>
        </form>
    </x-layout.page-container>
</x-app-layout>
