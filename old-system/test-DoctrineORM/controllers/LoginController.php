<?php

namespace app\controllers;

use app\controllers\AbstractController;
use app\database\servicesORM\NotificationService;
use app\database\servicesORM\UserRegistrationService;
use app\request\EmailRequest;
use app\request\LoginFormRequest;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use core\library\AuthService;
use http\Redirect;
use http\Request;
use Throwable;

/**
 * Controller para gerenciamento de autenticação
 */
class LoginController extends AbstractController
{
    /**
     * Construtor da classe LoginController
     *
     * @param Twig $twig Serviço de template
     * @param AuthService $authService Serviço de autenticação
     * @param UserRegistrationService $userRegistrationService Serviço de registro de usuários
     * @param NotificationService $notificationService Serviço de notificações
     * @param Request $request Requisição HTTP
     */
    public function __construct(
        protected Twig $twig,
        protected AuthService $authService,
        protected UserRegistrationService $userRegistrationService,
        protected NotificationService $notificationService,
        Request $request,
    ) {
        parent::__construct( $request );
    }

    /**
     * Exibe a página de login
     *
     * @return Response
     */
    public function index(): Response
    {
        try {
            return new Response( $this->twig->env->render( 'pages/login/index.twig' ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar a página de login. Tente novamente mais tarde.' );
            return new Response( $this->twig->env->render( 'pages/login/index.twig' ) );
        }
    }

    /**
     * Processa a tentativa de login
     *
     * @return Response
     */
    public function login(): Response
    {
        try {
            // Validar os dados do formulário
            $validated = LoginFormRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de login
            if ( !$validated ) {
                return Redirect::redirect( '/login' )
                    ->withMessage( 'error', 'Dados de login inválidos.' );
            }

            // Obter e sanitizar os dados do formulário
            $requestData = $this->request->all();
            $email       = $this->sanitizeInput( $requestData[ 'email' ], 'string' );
            $password    = $this->sanitizeInput( $requestData[ 'password' ], 'string' );

            // Tentar autenticar o usuário
            $authResult = $this->authService->attempt( [ 
                'email'    => $email,
                'password' => $password
            ] );

            // Verificar se a autenticação foi bem-sucedida
            if ( $authResult->isSuccess() ) {
                $user = $this->authService->user();

                // Log da atividade de login
                $this->activityLogger(
                    $user[ 'tenant_id' ],
                    $user[ 'user_id' ],
                    'user_login',
                    'user',
                    $user[ 'id' ],
                    "Usuário com email {$user[ 'email' ]} logado com sucesso.",
                    [ 
                        'entity' => [ 
                            'id'        => $user[ 'id' ],
                            'email'     => $user[ 'email' ],
                            'tenant_id' => $user[ 'tenant_id' ]
                        ],
                    ],
                );

                // Redirecionar baseado no tipo de usuário
                if ( $this->authService->isAdmin() ) {
                    return Redirect::redirect( '/admin' )
                        ->withMessage( 'success', 'Login realizado com sucesso!' );
                }
                return Redirect::redirect( '/provider' )
                    ->withMessage( 'success', 'Login realizado com sucesso!' );
            }

            // Credenciais inválidas - usar mensagem do AuthService ou fallback
            $errorMessage = $authResult->message ?? 'E-mail ou senha inválidos.';
            return Redirect::redirect( '/login' )
                ->withMessage( 'error', $errorMessage );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Falha ao realizar login, tente novamente mais tarde ou entre em contato com suporte!' );
            return Redirect::redirect( '/login' );
        }
    }

    /**
     * Realiza o logout do usuário
     *
     * @return Response
     */
    public function logout(): Response
    {
        try {
            $user = $this->authService->user();

            if ( $user ) {
                // Log da atividade de logout
                $this->activityLogger(
                    $user[ 'tenant_id' ],
                    $user[ 'user_id' ],
                    'user_logout',
                    'user',
                    $user[ 'id' ],
                    "Usuário com email {$user[ 'email' ]} fez logout.",
                    [ 
                        'entity' => [ 
                            'id'        => $user[ 'id' ],
                            'email'     => $user[ 'email' ],
                            'tenant_id' => $user[ 'tenant_id' ]
                        ]
                    ],
                );
            }

            // Realizar logout
            $this->authService->logout();

            return Redirect::redirect( '/login' )
                ->withMessage( 'success', 'Logout realizado com sucesso!' );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Falha ao realizar logout, tente novamente mais tarde!' );
            return Redirect::redirect( '/login' );
        }
    }

    /**
     * Exibe a página de recuperação de senha
     *
     * @return Response
     */
    public function forgotPassword(): Response
    {
        try {
            return new Response( $this->twig->env->render( 'pages/login/forgot_password.twig' ) );
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            Session::flash( 'error', 'Erro ao carregar a página de recuperação de senha. Tente novamente mais tarde.' );
            return Redirect::redirect( '/login' );
        }
    }

    /**
     * Envia o link de redefinição de senha por email
     *
     * @return Response
     */
    public function sendResetLink(): Response
    {
        try {
            // Validar os dados do formulário usando EmailRequest
            $validated = EmailRequest::validate( $this->request );

            // Se os dados não forem válidos, redirecionar para a página de recuperação
            if ( !$validated ) {
                return Redirect::redirect( '/forgot-password' )
                    ->withMessage( 'error', 'Por favor, forneça um endereço de email válido.' );
            }

            // Obter e sanitizar o email do formulário
            $requestData = $this->request->all();
            $email       = $this->sanitizeInput( $requestData[ 'email' ], 'email' );

            // Processar recuperação de senha usando o UserRegistrationService
            $result = $this->userRegistrationService->forgotPassword( $email );

            if ( $result[ 'status' ] === 'success' ) {
                // Log da atividade de redefinição de senha com dados do usuário
                $this->activityLogger(
                    $result[ 'data' ][ 'user' ]->tenant_id,
                    $result[ 'data' ][ 'user' ]->id,
                    'password_reset',
                    'user',
                    $result[ 'data' ][ 'user' ]->id,
                    "Link de redefinição de senha enviado com sucesso para o email: {$email}",
                    [ 'entity' => $result[ 'data' ][ 'user' ]->jsonSerialize() ],
                );

                return Redirect::redirect( '/forgot-password' )
                    ->withMessage( 'success', 'Se o email estiver cadastrado, você receberá as instruções para redefinir sua senha.' );
            } else {
                // Mesmo em caso de erro, não revelar se o email existe ou não por segurança
                return Redirect::redirect( '/forgot-password' )
                    ->withMessage( 'success', 'Se o email estiver cadastrado, você receberá as instruções para redefinir sua senha.' );
            }
        } catch ( Throwable $e ) {
            getDetailedErrorInfo( $e );
            return Redirect::redirect( '/forgot-password' )
                ->withMessage( 'error', 'Erro ao processar solicitação de redefinição de senha. Tente novamente mais tarde.' );
        }
    }

}
