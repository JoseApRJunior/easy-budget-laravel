<?php

namespace app\controllers;

use app\database\entitiesORM\ProviderCredentialEntity;
use app\database\models\ProviderCredential;
use app\database\servicesORM\MercadoPagoService;
use app\database\entitiesORM\PaymentMercadoPagoPlansEntity;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Twig;
use http\Redirect;
use http\Request;

class MercadoPagoController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private ProviderCredential $providerCredential,
        protected MercadoPagoService $mercadoPagoService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        $credentials = $this->providerCredential->findByProvider(
            $this->authenticated->tenant_id,
        );

        $isConnected = $credentials[ 'success' ];

        /** @var ProviderCredentialEntity $credentials  */
        return new Response( $this->twig->env->render( 'pages/mercadopago/index.twig', [ 
            'isConnected'        => $isConnected,
            'mercadoPagoAuthUrl' => $this->mercadoPagoService->getAuthorizationUrl(),
            'publicKey'          => $isConnected ? $credentials[ 'data' ]->public_key : null,
        ] ) );
    }

    public function callback(): Response
    {
        $code  = $this->request->get( 'code' );
        $state = $this->request->get( 'state' );

        if ( empty( $code ) ) {
            return Redirect::redirect( '/provider/integrations/mercadopago' )
                ->withMessage( 'error', 'A autorização foi cancelada ou falhou.' );
        }

        $success = $this->mercadoPagoService->handleCallback(
            $code,
            $state,
            $this->authenticated->id,
            $this->authenticated->tenant_id,
        );

        if ( $success ) {
            return Redirect::redirect( '/provider/integrations/mercadopago' )
                ->withMessage( 'success', 'Sua conta Mercado Pago foi conectada com sucesso!' );
        }

        return Redirect::redirect( '/provider/integrations/mercadopago' )
            ->withMessage( 'error', 'Ocorreu um erro ao conectar sua conta Mercado Pago. Tente novamente.' );
    }

    public function disconnect(): Response
    {
        $success = $this->mercadoPagoService->disconnect( $this->authenticated->tenant_id );

        if ( $success ) {
            return Redirect::redirect( '/provider/integrations/mercadopago' )
                ->withMessage( 'success', 'Sua conta Mercado Pago foi desconectada.' );
        }

        return Redirect::redirect( '/provider/integrations/mercadopago' )
            ->withMessage( 'error', 'Ocorreu um erro ao desconectar sua conta.' );
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
