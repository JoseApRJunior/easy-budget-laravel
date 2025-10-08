<?php

namespace app\controllers;

use app\database\services\UserRegistrationService;
use app\request\EmailRequest;
use app\request\LoginFormRequest;
use core\library\Auth;
use core\library\Response;
use core\library\Session;
use core\library\Twig;
use http\Redirect;
use http\Request;

class LoginController extends AbstractController
{
    public function __construct(
        private Twig $twig,
        private Auth $auth,
        private UserRegistrationService $userRegistrationService,
        Request $request,
    ) {
        parent::__construct($request);
    }

    public function index(): Response
    {
        return new Response(
            $this->twig->env->render('pages/login/index.twig'),
        );
    }

    public function login(): Response
    {
        $validated = LoginFormRequest::validate($this->request);
        if (!$validated) {
            return Redirect::redirect('/login');
        }

        try {
            $response = $this->auth->attempt(
                $this->request->get('email'),
                $this->request->get('password'),
            );

            if ($response[ 'status' ] === 'error') {
                if (isset($response[ 'data' ][ 'is_active' ])) {
                    if (!$response[ 'data' ][ 'is_active' ]) {
                        return Redirect::redirect('/login')
                            ->withMessage('error', $response[ 'message' ])
                            ->withMessage('resendConfirmation', true);
                    }
                }

                return Redirect::redirect('/login')->withMessage('error', $response[ 'message' ]);
            }

            if (Session::has('admin')) {
                return Redirect::redirect('/admin');
            }

            return Redirect::redirect('/provider');

        } catch (\Throwable $th) {
            getDetailedErrorInfo($th);

            return Redirect::redirect('/login')->withMessage('error', 'Falha ao checar as credenciais, tente novamente mais tarde ou entre em contato com suporte!');
        }

    }

    public function logout(): Response
    {
        $this->auth->logout();

        return Redirect::redirect('/login');
    }

    public function forgotPassword(): Response
    {
        return new Response(
            $this->twig->env->render('pages/login/forgot_password.twig'),
        );
    }

    public function sendResetLink(): Response
    {
        $validated = EmailRequest::validate($this->request);
        if (!$validated) {
            return Redirect::redirect('/forgot-password');
        }

        try {
            $respose = $this->userRegistrationService->forgotPassword($this->request->get('email'));

            if ($respose[ 'status' ] === 'error') {
                return Redirect::redirect('/forgot-password')
                    ->withMessage('error', $respose[ 'message' ]);
            }
            $this->activityLogger(
                $respose[ 'data' ][ 'user' ]->tenant_id,
                $respose[ 'data' ][ 'user' ]->id,
                'user_updated',
                'user',
                $respose[ 'data' ][ 'user' ]->id,
                "Senha atualizada com sucesso!",
                $respose[ 'data' ],
            );

            return Redirect::redirect('/login')
                ->withMessage('success', 'Link enviado, confira seu email para redefinir sua senha.');
        } catch (\Throwable $th) {
            getDetailedErrorInfo($th);

            return Redirect::redirect('/forgot-password')
                ->withMessage('error', 'Falha ao reenviar o link de confirmação, tente novamente mais tarde ou entre em contato com suporte!');
        }

    }

    /**
     * @inheritDoc
     */
    public function activityLogger(int $tenant_id, int $user_id, string $action_type, string $entity_type, int $entity_id, string $description, array $metadata = [])
    {
    }

}
