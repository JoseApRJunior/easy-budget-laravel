<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Laravel\Cashier\Exceptions\IncompletePayment;

class SubscriptionController extends Controller
{
    /**
     * Inicia o processo de checkout para um plano específico.
     */
    public function checkout(Request $request, string $planSlug)
    {
        $plan = Plan::where('slug', $planSlug)->firstOrFail();
        $user = $request->user();

        // O Stripe exige um 'price ID' que é criado no dashboard do Stripe.
        // Para fins de desenvolvimento, usaremos o slug como identificador do preço,
        // mas em produção você deve mapear o slug para o ID do Stripe (ex: price_H5ggv...)
        $stripePriceId = config("cashier.plans.{$plan->slug}") ?? $plan->slug;

        try {
            return $user->newSubscription('default', $stripePriceId)
                ->checkout([
                    'success_url' => route('provider.plans.index') . '?checkout=success',
                    'cancel_url' => route('provider.plans.index') . '?checkout=cancelled',
                ]);
        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [$exception->payment->id, 'redirect' => route('provider.plans.index')]);
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao processar checkout: ' . $e->getMessage());
        }
    }

    /**
     * Redireciona o usuário para o Portal de Faturamento do Stripe.
     */
    public function billingPortal(Request $request)
    {
        return $request->user()->redirectToBillingPortal(
            route('provider.plans.index')
        );
    }
}
