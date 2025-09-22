<?php

/**
 * Summary of namespace app\controllers
 */

namespace app\controllers;

use app\database\models\User;
use app\database\servicesORM\ActivityService;
use app\database\servicesORM\UserRegistrationService;
use app\request\EmailRequest;
use app\request\TokenRequest;
use app\request\UserCreateFormRequest;
use core\dbal\EntityNotFound;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Summary of UserController
 */
class UserController extends AbstractController
{
    public function __construct(
        protected Twig $twig,
        private User $user,
        private readonly UserRegistrationService $userRegistrationService,
        protected ActivityService $activityService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Summary of profile
     * @return \core\library\Response
     */
    public function profile(): Response
    {
        return new Response( $this->twig->env->render( 'pages/user/profile.twig' ) );
    }

    /**
     * Registers a new user.
     *
     * @return Response The HTTP response after attempting to register the user.
     */
    public function register(): Response
    {
        try {
            // Validar os dados do formulário de criação de usuário
            $validated = UserCreateFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página inicial
            if ( !$validated ) {
                return Redirect::redirect( '/' )->withMessage( 'error', 'Erro ao registrar o usuário.' );
            }

            // Obter os dados do formulário de criação de usuário
            $data = $this->request->all();

            // Verificar se email já existe
            $checkObj = $this->user->getUserByEmail( $data[ 'email' ] );

            // Se já existe um usuário com este email, redirecionar para a página inicial e mostrar a mensagem de erro
            if ( $checkObj[ 'success' ] ) {
                return Redirect::redirect( '/' )
                    ->withMessage( 'error', 'Este e-mail já está registrado!' );
            }

            // Cria novo usuário
            $response = $this->userRegistrationService->registerWithProvider( $data );

            // Se não foi possível criar o novo usuário, redirecionar para a página inicial e mostrar a mensagem de erro
            if ( !$response[ 'success' ] ) {
                return Redirect::redirect( '/' )->withMessage( 'error', "{$response[ 'message' ]}, tente novamente mais tarde ou entre em contato com suporte!" );
            }

            $this->activityLogger(
                $response[ 'data' ][ 'tenant_id' ],
                $response[ 'data' ][ 'user_id' ],
                'provider_created',
                'provider',
                $response[ 'data' ][ 'provider_id' ],
                "Prestador de serviços criado com sucesso!",
                $data,
            );

            // Redirecionar para a página de login e mostrar a mensagem de sucesso de registro
            return Redirect::redirect( '/login' )->withMessage( 'success', 'Registro realizado com sucesso! Um link de confirmação foi enviado para o seu e-mail. Por favor, verifique sua caixa de entrada e confirme sua conta para ativar o acesso.' );

            // Se houver redirecionar para a página inicial e mostrar a mensagem de erro
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao registrar o usuário, tente novamente mais tarde ou entre em contato com suporte!" );

            return Redirect::redirect( '/' );
        }

    }

    public function confirmAccount(): Response
    {
        $confirmAccount = false;
        $message        = 'Conta não ativada!';
        $validated      = TokenRequest::validate( $this->request );

        if ( $validated ) {
            $data = $this->request->all();

            $response = $this->confirmAccountLink( $data[ 'token' ] );

            if ( $response[ 'status' ] === 'error' && $response[ 'message' ] === "expired" ) {
                $confirmAccount = false;
                $message        = 'Token expirado!';
            } elseif ( $response[ 'status' ] === 'success' ) {
                $confirmAccount = true;

                $this->activityService->logActivity(
                    (int) $response[ 'data' ][ 'user' ]->tenant_id,
                    (int) $response[ 'data' ][ 'user' ]->id,
                    'user_updated',
                    'user',
                    $response[ 'data' ][ 'user' ]->id,
                    $response[ 'message' ],
                    [ 'token' => $data[ 'token' ] ],
                );
            } else {
                $confirmAccount = false;
                $message        = 'Token inválido!';
            }

        }

        return new Response(
            $this->twig->env->render( 'pages/user/confirm-account.twig', [ 'confirmAccount' => $confirmAccount, 'message' => $message ] ),
        );
    }

    /**
     * Confirma a conta do usuário usando o token fornecido.
     *
     * @param string $token Token de confirmação
     * @return array<string, mixed> Resposta da confirmação da conta
     */
    public function confirmAccountLink( string $token ): array
    {
        try {
            return $this->userRegistrationService->confirmAccount( $token );

        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', "Falha ao atualizar o token, tente novamente mais tarde ou entre em contato com suporte!" );

            return [ 'status' => 'error', 'message' => 'Erro interno do servidor' ];
        }

    }

    public function resendConfirmation(): Response
    {
        return new Response(
            $this->twig->env->render( 'pages/user/resend-confirmation.twig' ),
        );
    }

    public function resendConfirmationLink(): Response
    {
        $validated = EmailRequest::validate( $this->request );
        if ( !$validated ) {
            return Redirect::redirect( '/resend-confirmation' )->withMessage( 'error', 'Por favor, forneça um endereço de e-mail válido.' );
        }

        $response = $this->userRegistrationService->resendConfirmation( $this->request->get( 'email' ) );

        if ( $response[ 'data' ][ 'is_active' ] == true ) {
            return Redirect::redirect( '/login' )->withMessage( 'success', $response[ 'message' ] );
        } elseif ( $response[ 'status' ] === 'success' ) {
            $this->activityService->logActivity(
                $response[ 'data' ][ 'createdUserConfirmationToken' ][ 'tenant_id' ],
                (int) $response[ 'data' ][ 'createdUserConfirmationToken' ][ 'user_id' ],
                'user_confirmation_token_created',
                'user_confirmation_token',
                (int) $response[ 'data' ][ 'createdUserConfirmationToken' ][ 'id' ],
                "E-mail de confirmação reenviado com sucesso!",
                $response[ 'data' ],
            );

            return Redirect::redirect( '/login' )->withMessage( 'success', $response[ 'message' ] );
        } else {
            return Redirect::redirect( '/resend-confirmation' )->withMessage( 'error', "{$response[ 'message' ]} Tente novamente mais tarde ou entre em contato com suporte!" );
        }

    }

    public function blockAccount(): Response
    {
        $message      = '';
        $blockAccount = false;
        $validated    = TokenRequest::validate( $this->request );
        if ( !$validated ) {
            return Redirect::redirect( '/support' )->withMessage( 'error', 'Token inválido, por favor tente novamente mais tarde ou entre em contato com suporte.' );
        }
        $token = $this->request->get( 'token' );

        if ( $token !== null ) {
            $response = $this->userRegistrationService->blockAccount( $token );

            if ( $response[ 'status' ] === 'success' ) {
                $blockAccount = true;
                $this->activityService->logActivity(
                    $response[ 'data' ][ 'user' ]->tenant_id,
                    $response[ 'data' ][ 'user' ]->id,
                    'user_updated',
                    'user',
                    $response[ 'data' ][ 'id' ],
                    "Conta bloqueada com sucesso!",
                    [ 'token' => $token ],
                );
                // Limpa a sessão
                Session::removeAll();
            } elseif ( $response[ 'message' ] === "expired" ) {
                $blockAccount = false;
                $message      = 'Token expirado!';
            } elseif ( $response[ 'status' ] === "error" ) {
                $message      = $response[ 'message' ];
                $blockAccount = false;
            } else {
                $blockAccount = false;
                $message      = 'A conta ja está bloqueada!';
            }
        }

        return new Response(
            $this->twig->env->render( 'pages/user/block-account.twig', [ 'blockAccount' => $blockAccount, 'message' => $message ] ),
        );

    }

    /**
     * @inheritDoc
     */
    public function activityLogger( int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [] ): void
    {
        $this->activityService->logActivity( $tenant_id, $user_id, $action_type, $entity_type, $entity_id, $description, $metadata );
    }

}
