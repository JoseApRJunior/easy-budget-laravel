<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
use App\Support\ServiceResult;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de verificação de e-mail.
 *
 * Este controller fornece endpoints para:
 * - Solicitar novo e-mail de verificação
 * - Reenviar e-mail de verificação
 * - Página de confirmação pendente
 *
 * Utiliza o EmailVerificationService para toda a lógica de negócio,
 * seguindo a arquitetura Controller → Service → Repository estabelecida.
 */
class EmailVerificationController extends Controller
{
    protected EmailVerificationService $emailVerificationService;
    protected UserRepository           $userRepository;

    public function __construct(
        EmailVerificationService $emailVerificationService,
        UserRepository $userRepository,
    ) {
        $this->emailVerificationService = $emailVerificationService;
        $this->userRepository           = $userRepository;
    }

    /**
     * Exibe página de verificação pendente.
     */
    public function show(): View
    {
        $user = Auth::user();

        if ( !$user ) {
            return view( 'auth.login', [
                'error' => 'Você precisa estar logado para acessar esta página.',
            ] );
        }

        if ( $user->hasVerifiedEmail() ) {
            return view( 'dashboard', [
                'success' => 'Seu e-mail já está verificado.',
            ] );
        }

        return view( 'auth.verify-email-pending', [
            'user' => $user,
        ] );
    }

    /**
     * Solicita novo e-mail de verificação.
     */
    public function resend( Request $request ): RedirectResponse
    {
        $user = Auth::user();

        if ( !$user ) {
            return $this->redirectError(
                'login',
                'Você precisa estar logado para solicitar verificação de e-mail.',
            );
        }

        if ( $user->hasVerifiedEmail() ) {
            return $this->redirectSuccess(
                'dashboard',
                'Seu e-mail já está verificado.',
            );
        }

        $result = $this->emailVerificationService->resendConfirmationEmail( $user );

        if ( $result->isSuccess() ) {
            return $this->redirectSuccess(
                'verification.notice',
                'E-mail de verificação reenviado com sucesso. Verifique sua caixa de entrada.',
            );
        }

        return $this->redirectError(
            'verification.notice',
            $result->getMessage(),
        );
    }

    /**
     * Página de aviso sobre verificação pendente.
     */
    public function notice(): View
    {
        return view( 'auth.verify-email-notice' );
    }

}
