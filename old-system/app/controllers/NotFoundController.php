<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;

class NotFoundController extends AbstractController
{
    public function __construct(
        private Twig $twig
    ) {}

    public function index(): Response
    {
        return new Response(
            $this->twig->env->render('error/notFound.twig'),
            404
        );
    }
}
