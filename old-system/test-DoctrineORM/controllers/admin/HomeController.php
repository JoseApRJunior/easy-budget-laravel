<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;

class HomeController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
    ) {}

    public function index(): Response
    {
        return new Response( $this->twig->env->render( 'pages/admin/home.twig' ) );
    }

}