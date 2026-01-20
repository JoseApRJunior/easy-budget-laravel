@props(['activeTab' => 'profile'])

<div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush rounded-3">
        <a href="#perfil" class="list-group-item list-group-item-action {{ $activeTab === 'profile' ? 'active' : '' }} d-flex align-items-center"
            data-bs-toggle="list">
            <i class="bi bi-person me-3"></i>Perfil
        </a>
        <a href="#geral" class="list-group-item list-group-item-action {{ $activeTab === 'general' ? 'active' : '' }} d-flex align-items-center" data-bs-toggle="list">
            <i class="bi bi-sliders me-3"></i>Geral
        </a>
        <a href="#notificacoes" class="list-group-item list-group-item-action {{ $activeTab === 'notifications' ? 'active' : '' }} d-flex align-items-center"
            data-bs-toggle="list">
            <i class="bi bi-bell me-3"></i>Notificações
        </a>
        <a href="#seguranca" class="list-group-item list-group-item-action {{ $activeTab === 'security' ? 'active' : '' }} d-flex align-items-center"
            data-bs-toggle="list">
            <i class="bi bi-shield-lock me-3"></i>Segurança
        </a>
        <a href="#integracao" class="list-group-item list-group-item-action {{ $activeTab === 'integrations' ? 'active' : '' }} d-flex align-items-center"
            data-bs-toggle="list">
            <i class="bi bi-box-arrow-in-right me-3"></i>Integração
        </a>
    </div>
</div>
