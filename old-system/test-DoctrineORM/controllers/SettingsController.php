<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;
use http\Request;

class SettingsController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {
        return new Response( $this->twig->env->render( 'pages/settings/index.twig' ) );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
