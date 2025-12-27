<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\TokenType;
use App\Events\PasswordResetRequested;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Exibir a tela de solicitação de link de redefinição de senha.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Lidar com uma solicitação de link de redefinição de senha recebida.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        Log::info('PasswordResetLinkController: Iniciando processo de reset de senha', [
            'email' => $request->email,
            'ip' => $request->ip(),
        ]);

        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Buscar usuário pelo e-mail
        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->is_active) {
            Log::warning('PasswordResetLinkController: Tentativa de reset para e-mail inexistente ou usuário inativo', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            // Retornar mensagem genérica por segurança
            return back()->with('status', __('passwords.sent'));
        }

        $tenant = $user->tenant;

        if (! $tenant) {
            Log::error('PasswordResetLinkController: Tenant não encontrado para usuário', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Erro ao processar solicitação. Tente novamente mais tarde.']);
        }

        // Criar token de reset (sistema legado)
        $resetToken = generateSecureTokenUrl();

        UserConfirmationToken::create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'token' => $resetToken,
            'expires_at' => now()->addMinutes(15),
            'type' => TokenType::PASSWORD_RESET,
        ]);

        // Disparar evento de solicitação de reset
        PasswordResetRequested::dispatch($user, $resetToken, $tenant);

        Log::info('PasswordResetLinkController: Link de redefinição de senha enviado com sucesso', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return back()->with('status', __('passwords.sent'));
    }
}
