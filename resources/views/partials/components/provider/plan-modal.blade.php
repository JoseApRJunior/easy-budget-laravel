{{-- Modal de Alerta de Plano --}}
<div class="modal fade" id="planAlertModal" tabindex="-1" aria-labelledby="planAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">

            <div class="modal-header border-0">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle text-primary me-2"></i>
                    <h5 class="modal-title" id="planAlertModalLabel">Informação do Plano</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>

            <div class="modal-body">
                @if( $pendingPlan && $pendingPlan->status === 'pending' )
                    <p class="text-muted mb-3">
                        Você possui uma assinatura para o plano
                        <strong>{{ $pendingPlan->name ?? 'Plano' }}</strong>
                        aguardando pagamento. O que você gostaria de fazer?
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route( 'plans.status' ) }}" class="btn btn-primary d-grid">
                            <i class="bi bi-hourglass-split me-2"></i>
                            Verificar Status do Pagamento
                        </a>
                        <form action="{{ route( 'plans.cancel-pending' ) }}" method="post" class="d-grid">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>
                                Cancelar e Escolher Outro Plano
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-muted mb-3">
                        Seu plano atual possui algumas limitações. Para uma melhor experiência, considere
                        atualizar para um plano com mais recursos.
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="{{ route( 'provider.plans.index' ) }}" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-up-circle me-2"></i>
                            Conhecer Planos
                        </a>
                    </div>
                @endif
            </div>

            <div class="modal-footer border-0">
                <button type="button" class="btn btn-link btn-sm text-muted" data-bs-dismiss="modal">
                    Continuar com o plano atual
                </button>
            </div>

        </div>
    </div>
</div>
