<?php

namespace app\controllers;

use app\controllers\AbstractController;
use app\database\entitiesORM\ActivityEntity;
use app\enums\OperationStatus;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de atividades do sistema
 * Responsável por exibir, criar e gerenciar logs de atividades
 */
class ActivityController extends AbstractController
{

    /**
     * Construtor da classe ActivityController
     *
     * @param Twig $twig Serviço de template
     * @param Sanitize $sanitize Serviço de sanitização
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        protected Sanitize $sanitize,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de atividades do tenant autenticado
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as atividades do tenant usando o ActivityService
            $activities = $this->activityService->listByTenantId( $this->getAuthenticatedTenantId() );

            return match ( $activities->status ) {
                OperationStatus::SUCCESS   => new Response( $this->twig->env->render( 'pages/activity/index.twig', [ 
                    'activities'   => $activities->data,
                ] ) ),
                OperationStatus::NOT_FOUND => new Response( $this->twig->env->render( 'pages/activity/index.twig', [ 
                    'activities' => [],
                ] ) ),
                default                    => new Response( $this->twig->env->render( 'pages/activity/index.twig', [ 
                    'activities'                    => [],
                    'error'                         => $activities->message
                ] ), 500 )
            };
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar as atividades. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/activity/index.twig', [ 
                'activities' => [],
                'error'      => 'Erro interno do servidor'
            ] ), 500 );
        }
    }

    /**
     * Exibe os detalhes de uma atividade específica
     *
     * @param string $id ID da atividade
     * @return Response
     */
    public function show( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params         = $this->autoSanitizeForEntity( [ 'id' => $id ], ActivityEntity::class);
            $activityResult = $this->activityService->getByIdAndTenantId( $params[ 'id' ], $this->getAuthenticatedTenantId() );

            return match ( $activityResult->status ) {
                OperationStatus::SUCCESS   => new Response( $this->twig->env->render( 'pages/activity/show.twig', [ 
                    'activity'   => $activityResult->data,
                ] ) ),
                OperationStatus::NOT_FOUND => new Response( $this->twig->env->render( 'pages/activity/show.twig', [ 
                    'activity' => null,
                    'error'    => 'Atividade não encontrada.',
                ] ) ),
                default                    => new Response( $this->twig->env->render( 'pages/activity/show.twig', [ 
                    'activity'                    => null,
                    'error'                       => $activityResult->message,
                ] ), 500 )
            };
        } catch ( Throwable $e ) {
            Session::flash( 'error', 'Erro interno do servidor ao carregar a atividade.' );
            return Redirect::redirect( '/admin/activities' );
        }
    }

}
