<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\controllers\AbstractController;
use app\database\entitiesORM\UserEntity;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\RoleService;
use app\database\servicesORM\UserRegistrationService;
use app\database\servicesORM\UserService;
use app\request\UserFormRequest;
use core\library\Response;
use core\library\Twig;
use http\Redirect;
use http\Request;

/**
 * Controller para gerenciamento administrativo de usuários.
 *
 * Este controller utiliza exclusivamente services ORM seguindo o padrão
 * arquitetural ServiceInterface → Repository → Entity.
 *
 * Funcionalidades:
 * - Listagem de usuários com paginação
 * - Criação de novos usuários
 * - Visualização de detalhes
 * - Atualização de dados
 * - Gerenciamento de roles
 */
class UserController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private UserService $userService,
        private UserRegistrationService $userRegistrationService,
        private RoleService $roleService,
        private ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Lista todos os usuários com paginação.
     */
    public function index(): Response
    {
        $page   = (int) ( $this->request->get( 'page' ) ?? 1 );
        $limit  = 20;
        $offset = ( $page - 1 ) * $limit;

        // Buscar usuários usando UserService
        $usersResponse = $this->userService->findAllByTenantId(
            $this->authenticated->tenant_id,
            [],
            [ 'createdAt' => 'DESC' ],
            $limit,
            $offset,
        );

        $users = $usersResponse->isSuccess() ? $usersResponse->data : [];

        return new Response( $this->twig->env->render( 'pages/admin/user/index.twig', [ 
            'users'        => $users,
            'current_page' => $page,
            'has_next'     => count( $users ) === $limit
        ] ) );
    }

    /**
     * Exibe formulário de criação de usuário.
     */
    public function create(): Response
    {
        // Buscar roles disponíveis usando RoleService
        $rolesResponse = $this->roleService->findAll();
        $roles         = $rolesResponse->isSuccess() ? $rolesResponse->data : [];

        return new Response( $this->twig->env->render( 'pages/admin/user/create.twig', [ 
            'roles' => $roles
        ] ) );
    }

    /**
     * Armazena novo usuário.
     */
    public function store(): Response
    {
        try {
            // Validar dados do formulário
            $validated = UserFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( '/admin/users/create' )
                    ->withMessage( 'error', 'Dados inválidos' );
            }

            $data = $this->request->all();

            // Criar usuário usando UserRegistrationService
            $response = $this->userRegistrationService->registerUser(
                $data[ 'email' ],
                $data[ 'password' ],
                $data[ 'first_name' ],
                $data[ 'last_name' ],
                $this->authenticated->tenant_id,
            );

            if ( !$response->isSuccess() ) {
                return Redirect::redirect( '/admin/users/create' )
                    ->withMessage( 'error', $response->message );
            }

            // Log da atividade
            $this->activityService->logActivity(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'user_created',
                'user',
                $response->data->getId(),
                "Usuário {$data[ 'first_name' ]} {$data[ 'last_name' ]} criado",
                $data,
            );

            return Redirect::redirect( '/admin/users' )
                ->withMessage( 'success', 'Usuário criado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao criar o usuário, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/users/create' );
        }
    }

    /**
     * Exibe detalhes de usuário específico.
     */
    public function show(): Response
    {
        $userId = (int) $this->request->get( 'id' );

        if ( !$userId ) {
            return Redirect::redirect( '/admin/users' )
                ->withMessage( 'error', 'Usuário não encontrado' );
        }

        // Buscar usuário usando UserService
        $userResponse = $this->userService->findByIdAndTenantId(
            $userId,
            $this->authenticated->tenant_id,
        );

        if ( !$userResponse->isSuccess() ) {
            return Redirect::redirect( '/admin/users' )
                ->withMessage( 'error', 'Usuário não encontrado' );
        }

        return new Response( $this->twig->env->render( 'pages/admin/user/show.twig', [ 
            'user' => $userResponse->data
        ] ) );
    }

    /**
     * Lista usuários por letra inicial.
     */
    public function alpha(): Response
    {
        $letter = $this->request->get( 'letter', 'A' );

        // Buscar usuários que começam com a letra especificada
        $usersResponse = $this->userService->findAllByTenantId(
            $this->authenticated->tenant_id,
            [],
            [ 'firstName' => 'ASC' ],
        );

        $users = [];
        if ( $usersResponse->isSuccess() ) {
            // Filtrar por letra inicial
            $users = array_filter( $usersResponse->data, function ($user) use ($letter) {
                return strtoupper( substr( $user->getFirstName(), 0, 1 ) ) === strtoupper( $letter );
            } );
        }

        return new Response( $this->twig->env->render( 'pages/admin/user/index.twig', [ 
            'users'           => $users,
            'selected_letter' => $letter,
            'letters'         => range( 'A', 'Z' )
        ] ) );
    }

    /**
     * Atualiza usuário.
     */
    public function update(): Response
    {
        try {
            $userId = (int) $this->request->get( 'id' );

            if ( !$userId ) {
                return Redirect::redirect( '/admin/users' )
                    ->withMessage( 'error', 'Usuário não encontrado' );
            }

            // Validar dados
            $validated = UserFormRequest::validate( $this->request );

            if ( !$validated ) {
                return Redirect::redirect( "/admin/users/{$userId}" )
                    ->withMessage( 'error', 'Dados inválidos' );
            }

            $data = $this->request->all();

            // Buscar usuário atual
            $userResponse = $this->userService->findByIdAndTenantId(
                $userId,
                $this->authenticated->tenant_id,
            );

            if ( !$userResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/users' )
                    ->withMessage( 'error', 'Usuário não encontrado' );
            }

            /** @var UserEntity $user */
            $user = $userResponse->data;

            // Atualizar dados
            $user->setFirstName( $data[ 'first_name' ] );
            $user->setLastName( $data[ 'last_name' ] );
            $user->setEmail( $data[ 'email' ] );

            // Atualizar usando UserService
            $updateResponse = $this->userService->update( $user, $this->authenticated->tenant_id );

            if ( !$updateResponse->isSuccess() ) {
                return Redirect::redirect( "/admin/users/{$userId}" )
                    ->withMessage( 'error', $updateResponse->message );
            }

            // Log da atividade
            $this->activityService->logActivity(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'user_updated',
                'user',
                $userId,
                "Usuário {$data[ 'first_name' ]} {$data[ 'last_name' ]} atualizado",
                $data,
            );

            return Redirect::redirect( '/admin/users' )
                ->withMessage( 'success', 'Usuário atualizado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar o usuário, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/users' );
        }
    }

    /**
     * Remove usuário.
     */
    public function delete(): Response
    {
        try {
            $userId = (int) $this->request->get( 'id' );

            if ( !$userId ) {
                return Redirect::redirect( '/admin/users' )
                    ->withMessage( 'error', 'Usuário não encontrado' );
            }

            // Buscar usuário para log
            $userResponse = $this->userService->findByIdAndTenantId(
                $userId,
                $this->authenticated->tenant_id,
            );

            if ( !$userResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/users' )
                    ->withMessage( 'error', 'Usuário não encontrado' );
            }

            /** @var UserEntity $user */
            $user     = $userResponse->data;
            $userName = $user->getFirstName() . ' ' . $user->getLastName();

            // Remover usando UserService
            $deleteResponse = $this->userService->delete( $userId, $this->authenticated->tenant_id );

            if ( !$deleteResponse->isSuccess() ) {
                return Redirect::redirect( '/admin/users' )
                    ->withMessage( 'error', $deleteResponse->message );
            }

            // Log da atividade
            $this->activityService->logActivity(
                $this->authenticated->tenant_id,
                $this->authenticated->user_id,
                'user_deleted',
                'user',
                $userId,
                "Usuário {$userName} removido",
                [ 'user_id' => $userId, 'user_name' => $userName ],
            );

            return Redirect::redirect( '/admin/users' )
                ->withMessage( 'success', 'Usuário removido com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao excluir o usuário, tente novamente mais tarde ou entre em contato com suporte!" );
            return Redirect::redirect( '/admin/users' );
        }
    }

}
