<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use core\library\Response;
use core\library\Twig;

/**
 * Controlador para gerenciamento de tenants na área administrativa
 */
class TenantController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
    ) {}

    /**
     * Lista todos os tenants
     */
    public function index(): Response
    {
        return new Response($this->twig->env->render('pages/admin/tenant/index.twig'));
    }
}