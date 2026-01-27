<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\TokenType;
use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Models\UserConfirmationToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Exibir a tela de login.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->query('action') === 'block_account') {
            $email = $request->query('email');
            $token = $request->query('token');

            Log::info('Tentativa de bloqueio de conta via link', [
                'email' => $email,
                'has_token' => ! empty($token),
                'ip' => $request->ip(),
            ]);

            if ($email && $token) {
                // Buscar usuário ignorando o escopo de tenant para garantir que o encontramos globalmente
                $user = User::withoutGlobalScopes()->where('email', $email)->first();

                if ($user) {
                    Log::info('Usuário encontrado para bloqueio', ['user_id' => $user->id]);

                    // Buscar token na tabela user_confirmation_tokens (sistema legado usado pelo reset de senha)
                    $confirmationToken = UserConfirmationToken::withoutGlobalScopes()
                        ->where('user_id', $user->id)
                        ->where('token', $token)
                        ->where('type', TokenType::PASSWORD_RESET)
                        ->where('expires_at', '>', now())
                        ->first();

                    if ($confirmationToken) {
                        // Bloqueia a conta e desverifica o e-mail para forçar nova validação/segurança
                        $user->update([
                            'is_active' => false,
                            'email_verified_at' => null,
                        ]);

                        // Invalida o token usado para que não possa ser usado para resetar a senha também
                        $confirmationToken->delete();

                        Log::info('Conta bloqueada e e-mail desverificado com sucesso via link', ['user_id' => $user->id]);

                        session()->flash('success', 'Sua conta foi bloqueada com sucesso por medida de segurança. Para reativá-la, você deve solicitar a redefinição de sua senha abaixo.');

                        return redirect()->route('password.request');
                    } else {
                        Log::warning('Token legado inválido ou expirado para bloqueio de conta', [
                            'user_id' => $user->id,
                            'email' => $email,
                            'token' => substr($token, 0, 10).'...',
                        ]);
                    }
                } else {
                    Log::warning('Usuário não encontrado para e-mail fornecido no bloqueio', ['email' => $email]);
                }
            }

            // Fallback: mensagem se o processo falhar (token inválido, etc)
            session()->flash('warning', 'Não foi possível processar o bloqueio automático. Por favor, entre em contato com o suporte para garantir a segurança da sua conta.');

            return redirect()->route('login');
        }

        return view('auth.login');
    }

    /**
     * Lidar com uma solicitação de autenticação recebida.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Força invalidação de qualquer sessão anterior para evitar conflitos entre navegadores
        $this->ensureCleanSession($request);

        return redirect()->intended(route('provider.dashboard', absolute: false));
    }

    /**
     * Destruir uma sessão autenticada.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Garante que cada navegador tenha sua própria sessão limpa.
     * Remove qualquer sessão anterior que possa estar causando conflitos.
     */
    private function ensureCleanSession(Request $request): void
    {
        // Marca única por navegador para evitar conflitos
        $browserFingerprint = $this->getBrowserFingerprint($request);

        // Remove sessões anteriores do mesmo navegador
        $request->session()->put('browser_fingerprint', $browserFingerprint);
        $request->session()->put('login_time', now()->timestamp);

        // Força limpeza de cache de sessão
        $request->session()->save();

        if (! app()->environment('production')) {
            Log::debug('Sessão limpa criada para navegador', [
                'session_id' => substr($request->session()->getId(), -8),
                'browser_fingerprint' => substr($browserFingerprint, -8),
                'user_id' => Auth::id(),
            ]);
        }
    }

    /**
     * Cria uma marca única para identificar o navegador.
     */
    private function getBrowserFingerprint(Request $request): string
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();
        $acceptLanguage = $request->header('Accept-Language', '');

        return hash('sha256', $userAgent.$ip.$acceptLanguage);
    }
}
