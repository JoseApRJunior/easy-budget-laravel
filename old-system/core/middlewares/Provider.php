<?php

/**
 * Classe Middleware que lida com funcionalidades específicas do prestador.
 *
 * Este middleware verifica se o usuário atual é um prestador, recupera as informações
 * do prestador do banco de dados e verifica a assinatura do plano do prestador. Se
 * o usuário não for um prestador, ou as informações do prestador ou assinatura do plano
 * não forem encontradas, o usuário é redirecionado para a página inicial e a sessão é removida.
 *
 * As informações do prestador e da assinatura do plano são armazenadas na sessão para
 * uso posterior por outras partes da aplicação.
 */

namespace core\middlewares;

use app\database\entities\PlansWithPlanSubscriptionEntity;
use app\database\models\PlanSubscription;
use app\database\models\Provider as ProviderModel;
use app\database\services\PlanService;
use core\dbal\EntityNotFound;
use core\interfaces\MiddlewareInterface;
use core\library\Auth as CoreLibraryAuth;
use core\library\Session;
use DateTime;
use http\Redirect;

class Provider implements MiddlewareInterface
{
    /**
     * Constrói uma nova instância do middleware Provider.
     *
     * @param ProviderModel $providerModel A instância do modelo de prestador.
     * @param PlanSubscription $planSubscription A instância do modelo de assinatura do plano.
     */
    public function __construct(
        private ProviderModel $providerModel,
        private PlanSubscription $planSubscription,
        private PlanService $planService,
    ) {}

    public function execute(): ?Redirect
    {
        // 1. Admin bypassa tudo
        if ( CoreLibraryAuth::isAdmin() ) return null;

        // 2. Verifica se precisa atualizar sessão
        if ( !$this->shouldUpdateSession() ) {
            return $this->validateExistingSession();
        }

        // 3. Carrega dados do provider
        $provider = $this->loadProviderData();
        if ( !$provider ) return $this->redirectToLogin();

        // 4. Valida plano
        return $this->validatePlan( $provider );
    }

    private function checkPlan( int $provider_id, int $tenant_id ): PlansWithPlanSubscriptionEntity|EntityNotFound
    {
        // Buscar apenas o plano ativo primeiro
        $activePlan = $this->planSubscription->getProviderPlan( $provider_id, $tenant_id, 'active' );

        if ( !$activePlan instanceof EntityNotFound ) {
            /** @var PlansWithPlanSubscriptionEntity $activePlan */
            return $activePlan;
        }

        // Se não tem ativo, buscar outros status por prioridade
        foreach ( [ 'pending', 'expired', 'cancelled' ] as $status ) {
            $plan = $this->planSubscription->getProviderPlan( $provider_id, $tenant_id, $status );
            if ( !$plan instanceof EntityNotFound ) {
                /** @var PlansWithPlanSubscriptionEntity $plan */
                return $plan;
            }
        }

        return new EntityNotFound();
    }

    private function validateExistingSession(): ?Redirect
    {
        $checkPlan   = Session::get( 'checkPlan' );
        $pendingPlan = Session::get( 'checkPlanPending' );

        if (
            ( !$checkPlan instanceof EntityNotFound && !$pendingPlan instanceof EntityNotFound )
            && ( $checkPlan->plan_id === $pendingPlan->plan_id ) && ( $checkPlan->status === "active" )
        ) {
            Session::set( "checkPlanPending", $checkPlan );
        }

        if ( $checkPlan instanceof EntityNotFound || !$checkPlan ) {
            Session::remove( "last_updated_session_provider" );
            // Evitar recursão infinita
            if ( !Session::has( 'provider' ) ) {
                return $this->redirectToLogin();
            }
            return $this->execute();
        }

        return $this->handleExpiredPlan( $checkPlan );
    }

    private function loadProviderData()
    {
        $provider = Session::get( 'auth' );

        if ( $provider instanceof EntityNotFound ) {
            return null;
        }

        if ( !CoreLibraryAuth::isProvider() ) {
            return null;
        }

        $providerData = $this->providerModel->getProviderFullByUserId(
            $provider->user_id,
            $provider->tenant_id,
        );

        if ( $providerData instanceof EntityNotFound ) {
            return null;
        }

        return $providerData;
    }

    private function redirectToLogin(): Redirect
    {
        return Redirect::redirect( '/login' )
            ->withMessage( 'error', 'Prestador não encontrado, crie uma conta ou entre em contato com o suporte.' );
    }

    private function validatePlan( $provider ): ?Redirect
    {
        $checkPlan = $this->checkPlan( $provider->id, $provider->tenant_id );

        if ( $checkPlan instanceof EntityNotFound ) {
            return Redirect::redirect( '/plans' )
                ->withMessage( 'error', 'Nenhum plano encontrado.' );
        }

        $this->cacheProviderData( $provider, $checkPlan );

        return $this->handleExpiredPlan( $checkPlan );
    }

    private function shouldUpdateSession(): bool
    {
        return handleLastUpdateSession( 'provider' ) ||
            !Session::has( 'provider' ) ||
            !Session::has( 'checkPlan' );
    }

    private function cacheProviderData( $provider, $plan ): void
    {
        Session::set( 'provider', $provider );
        Session::set( 'auth', $provider );
        Session::set( 'checkPlan', $plan );
        Session::set( 'last_provider_check', time() );
    }

    private function handleExpiredPlan( $checkPlan ): ?Redirect
    {
        if ( !$checkPlan instanceof PlansWithPlanSubscriptionEntity ) {
            return $this->redirectToLogin();
        }

        if ( $checkPlan->slug === 'free' ) {
            return null;
        }

        $today = new DateTime();
        if ( $checkPlan->end_date < $today && $checkPlan->status !== 'expired' ) {
            $this->planService->updateStatusExpired(
                $checkPlan->tenant_id,
                $checkPlan->provider_id,
                $checkPlan->id,
            );

            // Recarregar plano atualizado
            $checkPlan = $this->checkPlan( $checkPlan->provider_id, $checkPlan->tenant_id );
            Session::set( 'checkPlan', $checkPlan );
        }

        return $this->handlePlanStatus( $checkPlan );
    }

    private function handlePlanStatus( PlansWithPlanSubscriptionEntity $checkPlan ): ?Redirect
    {
        return match ( $checkPlan->status ) {
            'active'    => null,  // plano ativo não requer redirecionamento
            'pending'   => $this->pending( $checkPlan ),
            'expired'   => $this->expired( $checkPlan ),
            'cancelled' => $this->cancelled( $checkPlan ),
            default     => null
        };
    }

    private function cancelled( PlansWithPlanSubscriptionEntity $checkPlan ): Redirect
    {
        return Redirect::redirect( '/plans' )->withMessage( 'error', "Sua assinatura {$checkPlan->name} foi cancelada, por favor atualize seu plano." );
    }

    private function pending( PlansWithPlanSubscriptionEntity $checkPlan ): Redirect
    {
        return Redirect::redirect( '/plans' )->withMessage( 'error', "Sua assinatura {$checkPlan->name} está pendente, por favor regularize seu plano." );
    }

    private function expired( PlansWithPlanSubscriptionEntity $checkPlan ): Redirect
    {
        return Redirect::redirect( '/plans' )->withMessage( 'error', "Sua assinatura {$checkPlan->name} expirou, por favor atualize seu plano." );
    }

}
