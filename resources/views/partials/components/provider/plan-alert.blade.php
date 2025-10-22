{{-- Alerta de Trial Expirado --}}
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <h4 class="alert-heading">
        <i class="bi bi-exclamation-triangle me-2"></i>
        Período de Trial Expirado
    </h4>
    <p class="mb-3">
        Seu período de teste gratuito chegou ao fim. Para continuar utilizando todas as funcionalidades
        do Easy Budget, escolha um plano que se adapte às suas necessidades.
    </p>
    <hr>
    <div class="d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Você será redirecionado automaticamente para a página de planos...
        </small>
        <a href="{{ route( 'plans.index' ) }}" class="btn btn-primary">
            <i class="bi bi-arrow-right-circle me-2"></i>
            Escolher Plano
        </a>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
</div>

<script>
    setTimeout( () => window.location.href = "{{ route( 'plans.index' ) }}", 5000 );
</script>
