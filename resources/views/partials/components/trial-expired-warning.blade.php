{{-- Trial Expired Warning Banner --}}
@if ( session()->has( 'trial_expired_warning' ) && session( 'trial_expired_warning' ) )
    <div class="alert alert-warning alert-dismissible fade show" role="alert" id="trialExpiredWarning">
        <div class="d-flex align-items-start">
            <div class="flex-shrink-0 me-3">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-2">
                    <i class="bi bi-clock-history me-2"></i>Período de Trial Expirado
                </h5>
                <p class="mb-2">
                    Seu período de trial expirou. Para continuar usando todas as funcionalidades do sistema,
                    escolha um plano de assinatura.
                </p>
                <div class="d-flex gap-2">
                    <a href="{{ route( 'provider.plans.index' ) }}" class="btn btn-sm btn-warning">
                        <i class="bi bi-credit-card me-1"></i>Ver Planos
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-warning" data-bs-dismiss="alert">
                        Descartar
                    </button>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>

    <script>
        // Auto-dismiss after 10 seconds if user doesn't interact
        document.addEventListener( 'DOMContentLoaded', function () {
            const warningElement = document.getElementById( 'trialExpiredWarning' );
            if ( warningElement ) {
                setTimeout( function () {
                    // Only auto-dismiss if user hasn't clicked anything
                    if ( !warningElement.classList.contains( 'show' ) ) {
                        return;
                    }
                    // Keep visible but don't force dismiss
                }, 10000 );
            }
        } );
    </script>
@endif
