<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Buscar usuário para verificar status e se é social login
        $user = User::where('email', $this->input('email'))->first();

        if ($user) {
            // Verificar se o usuário está ativo antes de qualquer coisa
            if ($user->is_active === false) {
                Log::warning('Tentativa de login em conta bloqueada', [
                    'email' => $this->input('email'),
                    'user_id' => $user->id,
                    'ip' => $this->ip(),
                ]);

                throw ValidationException::withMessages([
                    'email' => 'Sua conta ainda não está ativa. Por favor, verifique seu e-mail ou entre em contato com o suporte.',
                ]);
            }

            if (! empty($user->google_id)) {
                // Verificar se o usuário tem senha definida
                if (empty($user->password)) {
                    Log::warning('Tentativa de login local para usuário Google OAuth sem senha', [
                        'email' => $this->input('email'),
                        'user_id' => $user->id,
                        'ip' => $this->ip(),
                    ]);

                    throw ValidationException::withMessages([
                        'email' => 'Esta conta usa login social (Google) e não possui senha definida. Use o botão "Login com Google" ou defina uma senha em suas configurações.',
                    ]);
                }

                // Usuário tem google_id e senha, permitir login por senha
                Log::info('Tentativa de login local para usuário Google OAuth com senha', [
                    'email' => $this->input('email'),
                    'user_id' => $user->id,
                    'ip' => $this->ip(),
                ]);
            }
        }

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            // Forçar locale pt-BR para garantir tradução correta
            app()->setLocale('pt-BR');

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
