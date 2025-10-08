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

class ProviderTeste implements MiddlewareInterface
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

    /**
     * Summary of PLAN_STATUSES
     * @var array
     */
    private const PLAN_STATUSES = [ 
        'active',
        'pending',
        'expired',
        'cancelled',
    ];

    public function execute()
    {
        if ( handleLastUpdateSession( 'provider' ) ) {

            // TODO REVER ESTA LOGICA, CONFLITO COM ADMINISTRADOR
            // Obtém as informações do prestador previamente armazenadas na sessão
            $provider = Session::get( 'auth' );

            // Se as informações do prestador não forem encontradas, redireciona para a página inicial
            if ( $provider instanceof EntityNotFound ) {
                return Redirect::redirect( '/login' )->withMessage( 'message', 'Prestador não encontrado, crie uma conta ou entre em contato com o suporte.' );
            }

            Session::set(
                "checkPlan",
                $this->checkPlan(
                    $provider->id,
                    $provider->tenant_id,
                ),
            );

            // Verifica se o usuário é um prestador
            if ( !CoreLibraryAuth::isProvider() ) {
                // Se o usuário não for um prestador, redireciona para a página inicial
                return Redirect::redirect( '/plans' )->withMessage( 'message', ' O usuário não é um prestador, atualize seu plano.' );
            }

            // Obtém as informações do prestador do banco de dados
            $provider = $this->providerModel->getProviderFullByUserId(
                $provider->user_id,
                $provider->tenant_id,
            );

            // Se as informações do prestador não forem encontradas, redireciona para a página de login e remove a sessão
            if ( $provider instanceof EntityNotFound ) {
                return Redirect::redirect( '/login' )->withMessage( 'error', 'Prestador não encontrado, crie uma conta ou entre em contato com o suporte.' );
            }

            // Armazena as informações do prestador na sessão
            // Carregar dados do usuário na sessão
            Session::set( 'provider', $provider );
            Session::set( 'auth', $provider );

            Session::set(
                "checkPlan",
                $this->checkPlan(
                    $provider->id,
                    $provider->tenant_id,
                ),
            );
        }

        // Recupera as informações da assinatura do plano do prestador da sessão

        $checkPlan = Session::get( 'checkPlan' );

        if ( $checkPlan instanceof EntityNotFound || $checkPlan == false ) {
            if ( !Session::has( 'provider' ) ) {
                Session::remove( "last_updated_session_provider" );

                return $this->execute();
            }
            $checkPlan = $this->checkPlan( Session::get( 'provider' )->id, Session::get( 'provider' )->tenant_id );

            // Se o plano não for encontrado, redireciona para a página de planos
            if ( $checkPlan instanceof EntityNotFound ) {
                return Redirect::redirect( '/plans' )->withMessage( 'error', 'Nenhum plano encontrado.' );
            }
        }

        /** @var PlansWithPlanSubscriptionEntity $checkPlan             */
        if ( $checkPlan->slug != 'free' ) {
            $today = new DateTime();

            if ( $checkPlan->end_date < $today ) {
                // Atualiza o status de expiração da assinatura do plano
                $updated = $this->planService->updateStatusExpired(
                    $checkPlan->tenant_id,
                    $checkPlan->provider_id,
                    $checkPlan->id,
                );
                if ( $updated ) {
                    Session::set(
                        "checkPlan",
                        $this->checkPlan(
                            $checkPlan->provider_id,
                            $checkPlan->tenant_id,
                        ),
                    );
                    /** @var PlansWithPlanSubscriptionEntity $checkPlan             */
                    $checkPlan = Session::get( 'checkPlan' );
                }
            }
        } else {
            Session::set(
                "checkPlan",
                $this->checkPlan(
                    $checkPlan->provider_id,
                    $checkPlan->tenant_id,
                ),
            );
        }

        // Chama o método correspondente ao status da assinatura do plano e retorna o resultado
        return $this->handlePlanStatus( $checkPlan );
    }

    private function checkPlan( int $provider_id, int $tenant_id ): PlansWithPlanSubscriptionEntity|EntityNotFound
    {
        // Busca planos em todos os status
        $plans = [];
        foreach ( self::PLAN_STATUSES as $status ) {
            $plan = $this->planSubscription->getProviderPlan(
                $provider_id,
                $tenant_id,
                $status,
            );

            // Armazena na sessão
            Session::set( "checkPlan" . ucfirst( $status ), $plan );

            // Guarda para verificação
            if ( !$plan instanceof EntityNotFound ) {
                $plans[ $status ] = $plan;
            }
        }

        // Retorna primeiro plano encontrado por ordem de prioridade
        foreach ( self::PLAN_STATUSES as $status ) {
            if ( isset( $plans[ $status ] ) ) {
                /** @var PlansWithPlanSubscriptionEntity $plan */
                $plan = $plans[ $status ];

                return $plan;
            }
        }

        return new EntityNotFound();
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
