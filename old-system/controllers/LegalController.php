<?php

namespace app\controllers;

use core\library\Response;
use core\library\Twig;

class LegalController
{
    public function __construct(
        protected Twig $twig,
    ) {
    }

    public function termsOfService(): Response
    {
        return new Response(
            $this->twig->env->render('pages/legal/terms_of_service.twig'),
        );
    }

    public function privacyPolicy(): Response
    {
        return new Response(
            $this->twig->env->render('pages/legal/privacy_policy.twig'),
        );
    }

}
