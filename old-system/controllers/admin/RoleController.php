<?php

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\RoleEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\RoleService;
use app\request\RoleFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Sanitize;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de roles
 */
class RoleController extends AbstractController
{

    /**
     * Construtor da classe RoleController
     *
     * @param Twig $twig Serviço de template
     * @param RoleService $roleService Serviço de roles
     * @param Sanitize $sanitize Serviço de sanitização
     * @param ActivityService $activityService Serviço de atividades
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        private RoleService $roleService,
        protected Sanitize $sanitize,
        protected ActivityService $activityService,

        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a lista de roles
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            // Buscar todos os roles usando o RoleService
            $roles = $this->roleService->list();

            // Renderizar o template passando os roles
            return new Response( $this->twig->env->render( 'pages/role/index.twig', [ 
                'roles' => $roles
            ] ) );
        } catch ( Throwable $e ) {
            // Em caso de erro, renderizar a página com array vazio e mensagem de erro
            Session::flash( 'error', 'Erro ao carregar os roles. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/role/index.twig', [ 
                'roles' => []
            ] ) );
        }
    }

    /**
     * Exibe o formulário de criação de role
     *
     * @return Response
     */
    public function create(): Response
    {
        return new Response( $this->twig->env->render( 'pages/role/create.twig' ) );
    }

    /**
     * Processa o formulário de criação de role
     *
     * @return Response
     */
    public function store(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = RoleFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de criação
            if ( !$validated ) {
                return Redirect::redirect( '/admin/roles/create' )
                    ->withMessage( 'error', 'Erro ao cadastrar o role.' );
            }

            // Obter e sanitizar automaticamente os dados do formulário com base na entidade RoleEntity
            $data     = $this->autoSanitizeForEntity( $this->request->all(), RoleEntity::class);
            $response = $this->roleService->create( $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/admin/roles/create' )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'role_created',
                'role',
                $response[ 'data' ][ 'entity' ]->getId(),
                "Role {$data[ 'name' ]} criado",
                [ 
                    'entity' => $response[ 'data' ][ 'entity' ]->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de roles
            return Redirect::redirect( '/admin/roles' )
                ->withMessage( 'success', 'Role cadastrado com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao cadastrar o role, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/roles/create' );
        }
    }

    /**
     * Exibe os detalhes de um role
     *
     * @param string $id ID do role
     * @return Response
     */
    public function show( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], RoleEntity::class);
        $roleResponse = $this->roleService->getById( $params[ 'id' ] );

        if ( !$roleResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/roles' )
                ->withMessage( 'error', $roleResponse[ 'message' ] );
        }

        $role = $roleResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/role/show.twig', [ 
            'role' => $role,
        ] ) );
    }

    /**
     * Exibe o formulário de edição de role
     *
     * @param string $id ID do role
     * @return Response
     */
    public function edit( string $id ): Response
    {
        // Sanitizar o ID usando o método do trait AutoSanitizationTrait
        $params       = $this->autoSanitizeForEntity( [ 'id' => $id ], RoleEntity::class);
        $roleResponse = $this->roleService->getById( $params[ 'id' ] );

        if ( !$roleResponse[ 'success' ] ) {
            return Redirect::redirect( '/admin/roles' )
                ->withMessage( 'error', $roleResponse[ 'message' ] );
        }

        $role = $roleResponse[ 'data' ][ 'entity' ];

        return new Response( $this->twig->env->render( 'pages/role/edit.twig', [ 
            'role' => $role,
        ] ) );
    }

    /**
     * Processa o formulário de edição de role
     *
     * @return Response
     */
    public function update(): Response
    {
        try {
            // Validar os dados do formulário
            $validated   = RoleFormRequest::validate( $this->request );
            $requestData = $this->request->all();
            $id          = $requestData[ 'id' ];

            // Se os dados não forem válidos, redirecionar para a página de edição
            if ( !$validated ) {
                return Redirect::redirect( "/admin/roles/edit/{$id}" )
                    ->withMessage( 'error', 'Erro ao atualizar o role.' );
            }

            // Sanitizar o ID manualmente para garantir que seja um inteiro válido
            $id = $this->sanitize->sanitizeParamValue( $id, 'int' );

            // Sanitizar automaticamente os dados do formulário com base na entidade RoleEntity
            $data     = $this->autoSanitizeForEntity( $requestData, RoleEntity::class);
            $response = $this->roleService->update( $id, $data );

            // Se houve erro, redirecionar com a mensagem adequada
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( "/admin/roles/edit/{$id}" )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            // Log da atividade de atualização
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'role_updated',
                'role',
                $response[ 'data' ][ 'entity' ]->getId(),
                "Role {$data[ 'name' ]} atualizado",
                [ 
                    'entity' => $response[ 'data' ][ 'entity' ]->jsonSerialize()
                ],
            );

            // Se tudo ocorreu bem, redirecionar para a lista de roles
            return Redirect::redirect( '/admin/roles' )
                ->withMessage( 'success', 'Role atualizado com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao atualizar o role, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( "/admin/roles/edit/{$id}" );
        }
    }

    /**
     * Exclui um role
     *
     * @param string $id ID do role
     * @return Response
     */
    public function delete( string $id ): Response
    {
        try {
            // Sanitizar o ID usando o método do trait AutoSanitizationTrait
            $params = $this->autoSanitizeForEntity( [ 'id' => $id ], RoleEntity::class);
            $roleId = $params[ 'id' ];

            // Buscar dados do role antes de deletar para o log
            $roleResponse = $this->roleService->getById( $roleId );

            if ( !$roleResponse[ 'success' ] ) {
                return Redirect::redirect( '/admin/roles' )
                    ->withMessage( 'error', $roleResponse[ 'message' ] );
            }

            $roleData = $roleResponse[ 'data' ][ 'entity' ];

            $response = $this->roleService->delete( $roleId );

            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/admin/roles' )
                    ->withMessage( 'error', $response[ 'message' ] );
            }

            // Log da atividade de exclusão
            $this->activityLogger(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'role_deleted',
                'role',
                $roleId,
                "Role {$roleData->getName()} removido",
                [ 
                    'entity' => $roleData->jsonSerialize(),
                ],
            );

            return Redirect::redirect( '/admin/roles' )
                ->withMessage( 'success', 'Role removido com sucesso!' );
        } catch ( Throwable $e ) {
            Session::flash( 'error', "Falha ao remover o role, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/roles' );
        }
    }

    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
