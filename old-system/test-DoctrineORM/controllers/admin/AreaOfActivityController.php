<?php
declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\AreaOfActivityEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\AreaOfActivityService;
use app\request\AreaOfActivityFormRequest;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de áreas de atividade
 */
class AreaOfActivityController extends AbstractController
{

    /**
     * Construtor da classe AreaOfActivityController
     *
     * @param Twig $twig Serviço de template
     * @param AreaOfActivityService $areaOfActivityService Serviço de áreas de atividade
     * @param Sanitize $sanitize Serviço de sanitização
     * @param ActivityService $activityService Serviço de atividades
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private AreaOfActivityService $areaOfActivityService,
        protected Sanitize $sanitize,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de áreas de atividade
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as áreas de atividade usando o AreaOfActivityService
            $areasOfActivity = $this->areaOfActivityService->list();

            // Renderizar o template passando as áreas de atividade
            return new Response( $this->twig->env->render( 'pages/area-of-activity/index.twig', [ 
                'areasOfActivity' => $areasOfActivity
            ] ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar as áreas de atividade. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/area-of-activity/index.twig', [ 
                'areasOfActivity' => []
            ] ) );
        }
    }

    /**
     * Exibe o formulário de criação de área de atividade
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/area-of-activity/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de área de atividade
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = AreaOfActivityFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação
            if ( !$validated ) {
                return Redirect::redirect( '/admin/areas-of-activity/create' )
                    ->withMessage( 'error', 'Erro ao cadastrar a área de atividade.' );
            }

            // Obter e sanitizar automaticamente os dados do formulário com base na entidade AreaOfActivityEntity
            $data     = $this->autoSanitizeForEntity( $this->request->all(), AreaOfActivityEntity::class);
            $response = $this->areaOfActivityService->create( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/areas-of-activity/create' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao cadastrar a área de atividade.' );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'area_of_activity_created',
                'area_of_activity',
                $response->data->getId(),
                "Área de atividade {$data[ 'name' ]} criada",
                [ 
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de áreas de atividade
            return Redirect::redirect( '/admin/areas-of-activity' )
                ->withMessage( 'success', 'Área de atividade cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao cadastrar a área de atividade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/areas-of-activity/create' );
        }
    }

    /**
     * Exibe os detalhes de uma área de atividade
     *
     * @param string $id ID da área de atividade
     * @return Response
     */
    public function show( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params                 = $this->autoSanitizeForEntity( [ 'id' => $id ], AreaOfActivityEntity::class);
        $areaOfActivityResponse = $this->areaOfActivityService->getById( $params[ 'id' ] );

        if ( !$areaOfActivityResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/areas-of-activity' )
                ->withMessage( 'error', $areaOfActivityResponse[ 'message' ] );
        }

        $areaOfActivity = $areaOfActivityResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/area-of-activity/show.twig', [ 
            'areaOfActivity' => $areaOfActivity,
        ] ) );
    }

    /**
     * Exibe o formulário de edição de área de atividade
     *
     * @param string $id ID da área de atividade
     * @return Response
     */
    public function edit( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params                 = $this->autoSanitizeForEntity( [ 'id' => $id ], AreaOfActivityEntity::class);
        $areaOfActivityResponse = $this->areaOfActivityService->getById( $params[ 'id' ] );

        if ( !$areaOfActivityResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/areas-of-activity' )
                ->withMessage( 'error', $areaOfActivityResponse[ 'message' ] );
        }

        $areaOfActivity = $areaOfActivityResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/area-of-activity/edit.twig', [ 
            'areaOfActivity' => $areaOfActivity,
        ] ) );
    }

    /**
     * Processa o formulário de edição de área de atividade
     *
     * @return Response
     */
    public function update(): Response
    {
        $id = null; // Inicializar a variável para evitar erro no catch

        try {
            // Validar os dados do formulário
            $validated   = AreaOfActivityFormRequest::validate( $this->request );
            $requestData = $this->request->all();
            $id          = $requestData[ 'id' ] ?? null;

            // Se os dados não forem válidos, redirecionar para a página de edição
            if ( !$validated ) {
                return Redirect::redirect( "/admin/areas-of-activity/edit/{$id}" )
                    ->withMessage( 'error', 'Erro ao atualizar a área de atividade.' );
            }

            // Sanitizar o ID manualmente para garantir que seja um inteiro válido
            $id = $this->sanitize->sanitizeParamValue( $id, 'int' );

            // Sanitizar automaticamente os dados do formulário com base na entidade AreaOfActivityEntity
            $data     = $this->autoSanitizeForEntity( $requestData, AreaOfActivityEntity::class);
            $response = $this->areaOfActivityService->update( $id, $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response->isSuccess() ) {
                return Redirect::redirect( "/admin/areas-of-activity/edit/{$id}" )
                    ->withMessage( 'error', $response->message ?? 'Erro ao atualizar a área de atividade.' );
            }

            // Log da atividade de atualização
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'area_of_activity_updated',
                'area_of_activity',
                $response->data->getId(),
                "Área de atividade {$data[ 'name' ]} atualizada",
                [ 
                    'entity' => $response->data->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de áreas de atividade
            return Redirect::redirect( '/admin/areas-of-activity' )
                ->withMessage( 'success', 'Área de atividade atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar a área de atividade, tente novamente mais tarde ou entre em contato com suporte!" );

            // Se $id não estiver definido, redirecionar para a lista
            if ( $id === null ) {
                return Redirect::redirect( '/admin/areas-of-activity' );
            }

            return Redirect::redirect( "/admin/areas-of-activity/edit/{$id}" );
        }
    }

    /**
     * Exclui uma área de atividade
     *
     * @param string $id ID da área de atividade
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params           = $this->autoSanitizeForEntity( [ 'id' => $id ], AreaOfActivityEntity::class);
            $areaOfActivityId = $params[ 'id' ];

            // Buscar dados da área de atividade antes de deletar para o log
            $areaOfActivityResponse = $this->areaOfActivityService->getById( $areaOfActivityId );

            if ( !$areaOfActivityResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/areas-of-activity' )
                    ->withMessage( 'error', $areaOfActivityResponse->message ?? 'Área de atividade não encontrada.' );
            }

            $areaOfActivityData = $areaOfActivityResponse->data;

            $response = $this->areaOfActivityService->delete( $areaOfActivityId );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/areas-of-activity' )
                    ->withMessage( 'error', $response->message ?? 'Erro ao deletar área de atividade.' );
            }

            // Log da atividade de exclusão
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'area_of_activity_deleted',
                'area_of_activity',
                $areaOfActivityId,
                "Área de atividade {$areaOfActivityData->getName()} removida",
                [ 'entity' => $areaOfActivityData->jsonSerialize() ],

            );

            return Redirect::redirect( '/admin/areas-of-activity' )
                ->withMessage( 'success', 'Área de atividade removida com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao remover a área de atividade, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/areas-of-activity' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
