<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\ProfessionEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\ProfessionService;
use app\request\ProfessionFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de profissões
 */
class ProfessionController extends AbstractController
{

    /**
     * Construtor da classe ProfessionController
     *
     * @param Twig $twig Serviço de template
     * @param ProfessionService $professionService Serviço de profissões
     * @param Sanitize $sanitize Serviço de sanitização
     * @param ActivityService $activityService Serviço de atividades
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private ProfessionService $professionService,
        protected Sanitize $sanitize,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de profissões
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todas as profissões usando o ProfessionService
            $professions = $this->professionService->list();

            // Renderizar o template passando as profissões
            return new Response( $this->twig->env->render( 'pages/profession/index.twig', [ 
                'professions' => $professions
            ] ) );
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar as profissões. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/profession/index.twig', [ 
                'professions' => []
            ] ) );
        }
    }

    /**
     * Exibe o formulário de criação de profissão
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/profession/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de profissão
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = ProfessionFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação
            if ( !$validated ) {
                return Redirect::redirect( '/admin/professions/create' )
                    ->withMessage( 'error', 'Erro ao cadastrar a profissão.' );
            }

            // Obter e sanitizar automaticamente os dados do formulário com base na entidade ProfessionEntity
            $data     = $this->autoSanitizeForEntity( $this->request->all(), ProfessionEntity::class);
            $response = $this->professionService->create( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/admin/professions/create' )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'profession_created',
                'profession',
                $response[ 'data' ][ 'entity' ]->getId(),
                "Profissão {$data[ 'name' ]} criada",
                [ 
                    'entity' => $response[ 'data' ][ 'entity' ]->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de profissões
            return Redirect::redirect( '/admin/professions' )
                ->withMessage( 'success', 'Profissão cadastrada com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao cadastrar a profissão, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/professions/create' );
        }
    }

    /**
     * Exibe os detalhes de uma profissão
     *
     * @param string $id ID da profissão
     * @return Response
     */
    public function show( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params             = $this->autoSanitizeForEntity( [ 'id' => $id ], ProfessionEntity::class);
        $professionResponse = $this->professionService->getById( $params[ 'id' ] );

        if ( !$professionResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/professions' )
                ->withMessage( 'error', $professionResponse[ 'message' ] );
        }

        $profession = $professionResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/profession/show.twig', [ 
            'profession' => $profession,
        ] ) );
    }

    /**
     * Exibe o formulário de edição de profissão
     *
     * @param string $id ID da profissão
     * @return Response
     */
    public function edit( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params             = $this->autoSanitizeForEntity( [ 'id' => $id ], ProfessionEntity::class);
        $professionResponse = $this->professionService->getById( $params[ 'id' ] );

        if ( !$professionResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/professions' )
                ->withMessage( 'error', $professionResponse[ 'message' ] );
        }

        $profession = $professionResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/profession/edit.twig', [ 
            'profession' => $profession,
        ] ) );
    }

    /**
     * Processa o formulário de edição de profissão
     *
     * @return Response
     */
    public function update(): Response
    {
        try {
            // Validar os dados do formulário
            $validated   = ProfessionFormRequest::validate( $this->request );
            $requestData = $this->request->all();
            $id          = $requestData[ 'id' ];

            // Se os dados não forem válidos, redirecionar para a página de edição
            if ( !$validated ) {
                return Redirect::redirect( "/admin/professions/edit/{$id}" )
                    ->withMessage( 'error', 'Erro ao atualizar a profissão.' );
            }

            // Sanitizar o ID manualmente para garantir que seja um inteiro válido
            $id = $this->sanitize->sanitizeParamValue( $id, 'int' );

            // Sanitizar automaticamente os dados do formulário com base na entidade ProfessionEntity
            $data     = $this->autoSanitizeForEntity( $requestData, ProfessionEntity::class);
            $response = $this->professionService->update( $id, $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( "/admin/professions/edit/{$id}" )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            // Log da atividade de atualização
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'profession_updated',
                'profession',
                $response[ 'data' ][ 'entity' ]->getId(),
                "Profissão {$data[ 'name' ]} atualizada",
                [ 
                    'entity' => $response[ 'data' ][ 'entity' ]->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de profissões
            return Redirect::redirect( '/admin/professions' )
                ->withMessage( 'success', 'Profissão atualizada com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao atualizar a profissão, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( "/admin/professions/edit/{$id}" );
        }
    }

    /**
     * Exclui uma profissão
     *
     * @param string $id ID da profissão
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], ProfessionEntity::class);
            $professionId = $params[ 'id' ];

            // Buscar dados da profissão antes de deletar para o log
            $professionResponse = $this->professionService->getById( $professionId );

            if ( !$professionResponse[ 'success' ] ) {
                return Redirect::redirect( '/admin/professions' )
                    ->withMessage( 'error', $professionResponse[ 'message' ] );
            }

            $professionData = $professionResponse[ 'data' ][ 'entity' ];

            $response = $this->professionService->delete( $professionId );

            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/admin/professions' )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            // Log da atividade de exclusão
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'profession_deleted',
                'profession',
                $professionId,
                "Profissão {$professionData->getName()} removida",
                [ 
                    'deleted_profession' => [ 
                        'id'         => $professionData->getId(),
                        'slug'       => $professionData->getSlug(),
                        'name'       => $professionData->getName(),
                        'created_at' => $professionData->getCreatedAt()->format( 'Y-m-d H:i:s' ),
                        'updated_at' => $professionData->getUpdatedAt()?->format( 'Y-m-d H:i:s' )
                    ]
                ],
            );

            return Redirect::redirect( '/admin/professions' )
                ->withMessage( 'success', 'Profissão removida com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao remover a profissão, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/professions' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
