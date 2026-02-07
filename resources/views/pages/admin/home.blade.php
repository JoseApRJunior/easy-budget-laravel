<x-app-layout title="Dashboard Administrativo">
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Dashboard Administrativo"
            icon="shield-lock"
            :breadcrumb-items="[
                'Admin' => '#'
            ]">
        </x-layout.page-header>

        <!-- Cards de Ação -->
        <div class="row g-4">
            <div class="col-md-4">
                <x-ui.card class="h-100 shadow-sm border-0 hover-shadow">
                    <div class="d-flex flex-column h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-people-fill fs-4 text-success"></i>
                            </div>
                            <h5 class="card-title mb-0">Gerenciar Usuários</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Visualize e gerencie os usuários e provedores do sistema.
                        </p>
                        <x-ui.button href="/admin/user" variant="success" label="Acessar Usuários" />
                    </div>
                </x-ui.card>
            </div>

            <div class="col-md-4">
                <x-ui.card class="h-100 shadow-sm border-0 hover-shadow">
                    <div class="d-flex flex-column h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-card-checklist fs-4 text-primary"></i>
                            </div>
                            <h5 class="card-title mb-0">Gerenciar Assinaturas</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Acompanhe, cancele ou estorne assinaturas de planos dos usuários.
                        </p>
                        <x-ui.button href="/admin/plans/subscriptions" variant="primary" label="Gerenciar Assinaturas" />
                    </div>
                </x-ui.card>
            </div>
            <div class="col-md-4">
                <x-ui.card class="h-100 shadow-sm border-0 hover-shadow">
                    <div class="d-flex flex-column h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-bug-fill fs-4 text-warning"></i>
                            </div>
                            <h5 class="card-title mb-0">Logs do Sistema</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Monitore os logs de erro e eventos importantes da aplicação.
                        </p>
                        <x-ui.button href="/admin/logs" variant="warning" label="Acessar Logs" />
                    </div>
                </x-ui.card>
            </div>

            {{-- Adicione mais cards aqui para outras funcionalidades administrativas --}}

        </div>
    </div>
</x-app-layout>
