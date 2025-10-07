<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;
use http\Request;

class TenantController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    public function profile(): Response
    {
        return new Response( $this->twig->env->render( 'pages/user/profile.twig', [ 'user' => $this->authenticated ] ) );
    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void {}

}
