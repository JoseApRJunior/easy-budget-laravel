<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;

class HomeController extends AbstractController
{
    public function __construct(
        private Twig $twig,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->twig->env->render('pages/admin/home.twig'));
    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
    }

}
