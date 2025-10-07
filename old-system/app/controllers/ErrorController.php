<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;

class ErrorController
{
    public function __construct(
        private Twig $twig,
    ) {
    }

    public function internal(): Response
    {
        return new Response(
            $this->twig->env->render('pages/error/internalError.twig'),
            500,
        );
    }

    public function notAllowed(): Response
    {
        return new Response(
            $this->twig->env->render('pages/error/notAllowed.twig'),
            405,
        );
    }

    public function notFound(): Response
    {
        return new Response(
            $this->twig->env->render('pages/error/notFound.twig'),
            404,
        );
    }

}
