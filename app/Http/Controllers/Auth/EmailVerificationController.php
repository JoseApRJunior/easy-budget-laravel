<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use App\Repositories\UserRepository;
use App\Services\Application\EmailVerificationService;
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
    public function __construct(
        protected EmailVerificationService $emailVerificationService,
        protected UserRepository $userRepository,
    ) {}

    /**
     * Solicita novo e-mail de verificação.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return $this->redirectError(
                'login',
                'Você precisa estar logado para solicitar verificação de e-mail.',
            );
        }

        if ($user->email_verified_at) {
            return $this->redirectSuccess(
                'provider.dashboard',
                'Seu e-mail já está verificado.',
            );
        }

        $result = $this->emailVerificationService->resendConfirmationEmail($user);

        return $this->redirectSuccess(
            'verification.notice',
            'E-mail de verificação reenviado com sucesso. Verifique sua caixa de entrada.',
        );
    }

    /**
     * Página de aviso sobre verificação pendente.
     */
    public function notice(): View
    {
        return view('auth.verify-email-notice');
    }
}
