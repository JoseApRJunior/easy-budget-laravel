<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;
use http\Request;

class DevelopmentController extends AbstractController
{
    public function __construct(
        protected Twig $twig,  // Using private Twig instance from the parent class.
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/development.twig' ),
        );
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
