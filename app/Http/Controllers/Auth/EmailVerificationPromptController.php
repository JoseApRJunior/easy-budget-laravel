<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Abstracts\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Exibir o aviso de verificação de e-mail.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail() && $request->user()->is_active) {
            return redirect()->intended(route('provider.dashboard', absolute: false));
        }

        // Se o e-mail já está verificado mas a conta está inativa (ex: bloqueada pelo admin)
        if ($request->user()->hasVerifiedEmail() && ! $request->user()->is_active) {
            return view('auth.verify-email', [
                'status' => 'inactive',
                'message' => 'Sua conta está aguardando ativação ou foi desativada pelo administrador. Entre em contato com o suporte para mais informações.',
            ]);
        }

        return view('auth.verify-email');
    }
}
