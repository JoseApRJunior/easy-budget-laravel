<?php

namespace app\controllers;

use app\database\servicesORM\PlanService;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Request;

class HomeController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        protected PlanService $planService,
        Request $request,

    ) {
        parent::__construct( $request );
    }

    public function index(): Response
    {

        try {
            $result = $this->planService->list();

            // Verificar se o resultado foi bem-sucedido e se há dados
            $plansData = [];
            if ( $result->isSuccess() && is_array( $result->data ) ) {
                $plansData = $result->data;
            }

            return new Response(
                $this->twig->env->render( 'pages/home/index.twig', [ 
                    'plans' => $plansData,
                ] ),
            );

        } catch ( \Exception $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao listar os planos ativos, tente mais tarde ou entre em contato com suporte." );

            return new Response(
                $this->twig->env->render( 'pages/home/index.twig', [ 
                    'plans' => [],
                ] ),
            );
        }

    }

}