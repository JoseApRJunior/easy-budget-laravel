<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;

class InfoController
{
    public function __construct(
        private Twig $twig,
    ) {
    }

    public function about(): Response
    {
        return new Response(
            $this->twig->env->render('pages/home/about.twig'),
        );
    }

}
