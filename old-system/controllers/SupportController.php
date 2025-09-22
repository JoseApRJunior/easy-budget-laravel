<?php

namespace app\controllers;

use app\database\servicesORM\ActivityService;
use app\database\servicesORM\SupportService;
use app\request\SupportCreateFormRequest;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

class SupportController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        protected SupportService $supportService,
        protected ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function support(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/home/support.twig' ),
        );
    }

    public function store(): Response
    {

        try {
            // Validar os dados do formulário de suporte
            $validated = SupportCreateFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página suporte e mostrar a mensagem de erro
            if ( !$validated ) {
                return Redirect::redirect( '/support' )->withMessage( 'error', 'Erro ao enviar o email de suporte.' );
            }

            // Obter os dados do formulário de suporte
            $data = $this->request->all();

            // Adicionar tenant_id aos dados se o usuário estiver autenticado
            if ( $this->authenticated && isset( $this->authenticated->tenant_id ) ) {
                $data[ 'tenant_id' ] = $this->authenticated->tenant_id;
            }

            // Salvar e enviar o email de suporte
            $serviceResult = $this->supportService->create( $data );

            // Se não foi possível salvar e enviar o email, redirecionar para a página de suporte e mostrar a mensagem de erro
            if ( !$serviceResult->isSuccess() ) {
                return Redirect::redirect( '/support' )->withMessage( 'error', "Falha ao enviar o email, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $supportEntity = $serviceResult->data;

            if ( $this->authenticated ) {
                $this->activityLogger(
                    $this->authenticated->tenant_id,
                    $this->authenticated->user_id,
                    'support_created',
                    'support',
                    $supportEntity->getId(),
                    "Email de suporte enviado com sucesso!",
                    $data,
                );
            }

            // Redirecionar para a página de clientes e mostrar a mensagem de sucesso
            return Redirect::redirect( '/support' )->withMessage( 'success', 'Email de suporte enviado com sucesso!' );

            // Se houver redirecionar para a página inicial e mostrar a mensagem de erro
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao enviar o email, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/support' );
        }
    }

    /**
     *
     *
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
