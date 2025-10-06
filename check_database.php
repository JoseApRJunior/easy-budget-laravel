<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFICAÇÃO DO BANCO DE DADOS ===\n\n";

try {
    echo "PLANOS:\n";
    $plans = App\Models\Plan::all();
    if ( $plans->count() == 0 ) {
        echo "❌ Nenhum plano encontrado!\n";
    } else {
        foreach ( $plans as $plan ) {
            echo "- {$plan->name} ({$plan->slug}) - Status: {$plan->status} - Price: {$plan->price}\n";
        }
    }

    echo "\nTENANTS: " . App\Models\Tenant::count() . "\n";
    echo "USERS: " . App\Models\User::count() . "\n";
    echo "PROVIDERS: " . App\Models\Provider::count() . "\n";
    echo "PLAN SUBSCRIPTIONS: " . App\Models\PlanSubscription::count() . "\n";

    echo "\n=== VERIFICAÇÃO ESPECÍFICA DO PLANO 'PRO' ===\n";
    $proPlan = App\Models\Plan::where( 'slug', 'pro' )->where( 'status', true )->first();
    if ( !$proPlan ) {
        echo "❌ Plano 'pro' ativo não encontrado!\n";

        echo "\nPlanos disponíveis:\n";
        $allPlans = App\Models\Plan::all();
        foreach ( $allPlans as $plan ) {
            echo "- ID: {$plan->id}, Name: {$plan->name}, Slug: {$plan->slug}, Status: {$plan->status}\n";
        }
    } else {
        echo "✅ Plano 'pro' encontrado: {$proPlan->name} (ID: {$proPlan->id})\n";
    }

} catch ( Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
