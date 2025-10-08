<?php

namespace app\controllers;

use app\database\models\Plan;
use core\library\Response;
use core\library\Session;
use core\library\Twig;

class HomeController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private Plan $plan,
    ) {
    }

    public function index(): Response
    {
        try {
            $plans = $this->plan->findActivePlans();

            return new Response(
                $this->twig->env->render('pages/home/index.twig', [
                    'plans' => $plans,
                ]),
            );

        } catch (\Exception $e) {
            getDetailedErrorInfo($e);
            Session::flash('error', "Falha ao listar os planos ativos, tente mais tarde ou entre em contato com suporte.");

            return new Response(
                $this->twig->env->render('pages/home.twig', [
                ]),
            );
        }

    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
    }

}
