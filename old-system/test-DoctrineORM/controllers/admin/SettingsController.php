<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;

/**
 * Controlador para configurações administrativas
 */
class SettingsController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
    ) {}

    /**
     * Exibe configurações do sistema
     */
    public function index(): Response
    {
        return new Response($this->twig->env->render('pages/admin/settings/index.twig'));
    }
}