<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG PASSO A PASSO DO CADASTRO ===\n\n";

try {
    // Dados de teste
    $data = [
        'first_name'            => 'João',
        'last_name'             => 'Silva',
        'email'                 => 'debug' . time() . '@example.com',
        'phone'                 => '(11) 99999-9999',
        'password'              => 'Senha123!',
        'password_confirmation' => 'Senha123!',
        'terms_accepted'        => '1'
    ];

    echo "1. TESTANDO CRIAÇÃO DO TENANT...\n";
    $tenant = \App\Models\Tenant::create( [
        'name'      => $data[ 'first_name' ] . ' ' . $data[ 'last_name' ] . ' ' . time(),
        'is_active' => true,
    ] );
    echo "✅ Tenant criado: ID {$tenant->id}\n\n";

    echo "2. TESTANDO CRIAÇÃO DO PLANO...\n";
    $plan = \App\Models\Plan::where( 'slug', 'pro' )->where( 'status', true )->first();
    if ( !$plan ) {
        echo "❌ Plano 'pro' não encontrado!\n";
        $plan = \App\Models\Plan::first();
        if ( $plan ) {
            echo "✅ Usando primeiro plano disponível: {$plan->name} (ID: {$plan->id})\n";
        } else {
            echo "❌ Nenhum plano encontrado!\n";
            exit( 1 );
        }
    } else {
        echo "✅ Plano encontrado: {$plan->name} (ID: {$plan->id})\n";
    }
    echo "\n";

    echo "3. TESTANDO CRIAÇÃO DO USUÁRIO...\n";
    $user = \App\Models\User::create( [
        'tenant_id' => $tenant->id,
        'email'     => $data[ 'email' ],
        'password'  => \Illuminate\Support\Facades\Hash::make( $data[ 'password' ] ),
        'is_active' => true,
    ] );
    echo "✅ Usuário criado: ID {$user->id}\n\n";

    echo "4. TESTANDO CRIAÇÃO DOS DADOS COMUNS...\n";
    $commonData = \App\Models\CommonData::create( [
        'tenant_id'    => $tenant->id,
        'first_name'   => $data[ 'first_name' ],
        'last_name'    => $data[ 'last_name' ],
        'cpf'          => null,
        'cnpj'         => null,
        'company_name' => null,
        'description'  => null,
    ] );
    echo "✅ Dados comuns criados: ID {$commonData->id}\n\n";

    echo "5. TESTANDO CRIAÇÃO DO PROVIDER...\n";
    $provider = \App\Models\Provider::create( [
        'tenant_id'      => $tenant->id,
        'user_id'        => $user->id,
        'common_data_id' => $commonData->id,
        'contact_id'     => null,
        'address_id'     => null,
        'terms_accepted' => $data[ 'terms_accepted' ],
    ] );
    echo "✅ Provider criado: ID {$provider->id}\n\n";

    echo "6. TESTANDO CRIAÇÃO DA ASSINATURA DO PLANO...\n";
    $planSubscription = \App\Models\PlanSubscription::create( [
        'tenant_id'          => $tenant->id,
        'plan_id'            => $plan->id,
        'user_id'            => $user->id,
        'provider_id'        => $provider->id,
        'status'             => 'active',
        'transaction_amount' => $plan->price ?? 0.00,
        'start_date'         => now(),
        'starts_at'          => now(),
        'ends_at'            => date( 'Y-m-d H:i:s', strtotime( '+7 days' ) ),
    ] );
    echo "✅ Assinatura do plano criada: ID {$planSubscription->id}\n\n";

    echo "🎉 CADASTRO COMPLETO COM SUCESSO!\n";
    echo "Usuário: {$data[ 'email' ]}\n";
    echo "Tenant ID: {$tenant->id}\n";
    echo "User ID: {$user->id}\n";

} catch ( \Exception $e ) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
