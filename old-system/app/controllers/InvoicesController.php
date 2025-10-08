<?php

namespace app\controllers;

use app\controllers\AbstractController;
use app\database\services\ActivityService;
use core\library\Response;
use core\library\Twig;

class InvoicesController extends AbstractController
{
    public function __construct(
        private Twig $twig,

        private ActivityService $activityService,

    ) {
        parent::__construct();
    }

    public function create(): Response
    {
        // TODO VER LOGICA PARA GERAR FATURA COMPLETA OU PARCIAL

        $data = $this->request->getRequest( 'post' );

        \var_dump( $data );
        return new Response( $this->twig->env->render( 'pages/budget/create.twig' ) );
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] )
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
