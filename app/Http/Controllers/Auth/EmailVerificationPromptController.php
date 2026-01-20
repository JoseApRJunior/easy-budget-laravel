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
        $user = $request->user();

        // Se já está verificado e ativo, vai para o dashboard
        if ($user->hasVerifiedEmail() && $user->is_active) {
            return redirect()->intended(route('provider.dashboard', absolute: false));
        }

        $data = [];

        // CASO 1: Conta Desativada/Bloqueada (já verificou e-mail mas foi desativado ou desativado manualmente)
        if ($user->hasVerifiedEmail() && ! $user->is_active) {
            $data['status'] = 'deactivated';
            $data['title'] = 'Conta Desativada';
            $data['message'] = 'Sua conta foi desativada pelo administrador. Para reativá-la, entre em contato com nosso suporte.';
        }
        // CASO 2: Conta Pendente de Ativação (ainda não verificou e-mail)
        elseif (! $user->hasVerifiedEmail()) {
            $data['status'] = 'pending_activation';
            $data['title'] = 'Ative sua Conta';
            $data['message'] = 'Para começar a usar o Easy Budget, você precisa confirmar seu endereço de e-mail.';
        }

        return view('auth.verify-email', $data);
    }
}
