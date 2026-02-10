<?php

namespace App\Http\Middleware;

use App\Models\Provider;
use App\Models\User;
use App\Models\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProviderMiddleware
{
    /**
     * Rotas que permitem acesso mesmo com trial expirado
     * Estas são rotas básicas de visualização e configuração
     */
    private const ALLOWED_ROUTES_WITH_EXPIRED_TRIAL = [
        'provider.dashboard',           // Dashboard principal
        'provider.update',              // Atualizar perfil
        'provider.update_store',        // Salvar atualização de perfil
        'provider.change_password',     // Mudar senha
        'provider.change_password_store', // Salvar mudança de senha
        'settings.index',               // Configurações
        'settings.general.update',      // Atualizar configurações gerais
        'settings.profile.update',      // Atualizar perfil
        'settings.security.update',     // Atualizar segurança
        'settings.notifications.update', // Atualizar notificações
        'settings.customization.update', // Atualizar customização
        'settings.avatar.update',       // Atualizar avatar
        'settings.avatar.remove',       // Remover avatar
        'settings.company-logo.update', // Atualizar logo
        'settings.audit',               // Auditoria
        'provider.plans.index',         // Visualizar planos
        'provider.plans.show',          // Visualizar detalhes do plano
        'provider.plans.checkout',      // Iniciar checkout Stripe
        'provider.plans.billing-portal', // Portal de faturamento Stripe
        'provider.plans.redirect-to-payment', // Redirecionamento Mercado Pago
        'settings.profile.edit',        // Editar perfil
        'settings.profile.update',      // Atualizar perfil
        'settings.profile.destroy',     // Deletar perfil
    ];

    /**
     * Rotas que requerem redirecionamento para planos quando trial expirado
     * Estas são rotas críticas de negócio
     */
    private const CRITICAL_ROUTES_REQUIRING_PLAN = [
        'provider.customers',
        'provider.products',
        'provider.services',
        'provider.budgets',
        'provider.invoices',
        'reports',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Check if user is a provider
        $isProvider = UserRole::where('user_id', $user->id)
            ->where('tenant_id', $user->tenant_id)
            ->whereHas('role', function ($query) {
                $query->where('name', 'provider');
            })
            ->exists();

        if (! $isProvider) {
            Log::warning('Provider access denied', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'attempted_route' => $request->route()->getName(),
            ]);

            abort(403, 'Acesso negado. Você não tem permissões de provedor.');
        }

        // Check if provider is active
        $provider = Provider::where('user_id', $user->id)->first();
        if (! $provider || $user->is_active !== true) {
            return redirect()->route('verification.notice')->with('warning', 'Sua conta ainda não está ativa. Por favor, verifique seu e-mail ou entre em contato com o suporte.');
        }

        // Check if trial is expired
        if ($user->isTrialExpired()) {
            $currentRoute = $request->route()->getName();

            // Se está tentando acessar rota permitida com trial expirado, permitir com aviso
            if ($this->isAllowedRouteWithExpiredTrial($currentRoute)) {
                // Adicionar flag à sessão para mostrar aviso na página
                session()->put('trial_expired_warning', true);

                return $next($request);
            }

            // Se está tentando acessar rota crítica, redirecionar para planos
            if ($this->isCriticalRoute($currentRoute)) {
                Log::info('Trial expired - redirecting to plans from critical route', [
                    'user_id' => $user->id,
                    'attempted_route' => $currentRoute,
                    'ip' => $request->ip(),
                ]);

                return redirect()->route('provider.plans.index')
                    ->with('warning', 'Seu período de trial expirou. Escolha um plano para continuar usando o sistema.');
            }

            // Para outras rotas, permitir com aviso
            session()->put('trial_expired_warning', true);
        }

        return $next($request);
    }

    /**
     * Verifica se a rota atual é permitida mesmo com trial expirado
     */
    private function isAllowedRouteWithExpiredTrial(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        // Verificar correspondência exata
        if (in_array($routeName, self::ALLOWED_ROUTES_WITH_EXPIRED_TRIAL)) {
            return true;
        }

        // Verificar correspondência parcial (prefixo)
        foreach (self::ALLOWED_ROUTES_WITH_EXPIRED_TRIAL as $allowedRoute) {
            if (strpos($routeName, $allowedRoute) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica se a rota atual é crítica e requer plano ativo
     */
    private function isCriticalRoute(?string $routeName): bool
    {
        if (! $routeName) {
            return false;
        }

        // Verificar correspondência parcial (prefixo)
        foreach (self::CRITICAL_ROUTES_REQUIRING_PLAN as $criticalRoute) {
            if (strpos($routeName, $criticalRoute) === 0) {
                return true;
            }
        }

        return false;
    }
}
