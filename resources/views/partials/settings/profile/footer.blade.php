<div class="card-footer bg-transparent border-0">
    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route( 'profile.edit' ) }}" class="btn btn-outline-primary">
            <i class="bi bi-person me-2"></i>Editar Perfil Pessoal
        </a>
        <a href="{{ route( 'provider.business.edit' ) }}" class="btn btn-outline-info">
            <i class="bi bi-building me-2"></i>Editar Dados Empresariais
        </a>
        <a href="{{ url( '/provider/change-password' ) }}" class="btn btn-outline-secondary">
            <i class="bi bi-key me-2"></i>Alterar Senha
        </a>
    </div>
</div>
