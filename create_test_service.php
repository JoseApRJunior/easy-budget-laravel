<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

use App\Models\Service;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\UserConfirmationToken;

// Buscar um tenant existente
$tenant = Tenant::first();
if ( !$tenant ) {
    echo "Nenhum tenant encontrado. Criando tenant de teste...\n";
    $tenant = Tenant::create( [
        'name'      => 'Teste Tenant',
        'is_active' => true
    ] );
}

echo "Usando tenant: {$tenant->name} (ID: {$tenant->id})\n";

// Buscar um customer existente
$customer = Customer::first();
if ( !$customer ) {
    echo "Nenhum customer encontrado. Criando customer de teste...\n";
    $customer = Customer::create( [
        'tenant_id' => $tenant->id,
        'status'    => 'active'
    ] );
}

echo "Usando customer ID: {$customer->id}\n";

// Buscar um budget existente
$budget = Budget::first();
if ( !$budget ) {
    echo "Nenhum budget encontrado. Criando budget de teste...\n";
    $budget = Budget::create( [
        'tenant_id'          => $tenant->id,
        'customer_id'        => $customer->id,
        'budget_statuses_id' => 1,
        'code'               => 'TEST123',
        'discount'           => 0,
        'total'              => 1500.00,
        'description'        => 'Budget de teste para rotas públicas'
    ] );
}

echo "Usando budget ID: {$budget->id}\n";

// Buscar um serviço existente ou criar novo
$service = Service::where( 'code', 'TEST456' )->first();
if ( !$service ) {
    echo "Nenhum serviço encontrado. Criando serviço de teste...\n";
    $service                      = new Service();
    $service->tenant_id           = $tenant->id;
    $service->budget_id           = $budget->id;
    $service->category_id         = 1;
    $service->service_statuses_id = 1;
    $service->code                = 'TEST456';
    $service->description         = 'Serviço de teste para rotas públicas';
    $service->discount            = 0;
    $service->total               = 1500.00;
    $service->due_date            = now()->addDays( 30 );
}

try {
    $service->save();
    echo "Serviço configurado com sucesso! ID: {$service->id}\n";

    // Buscar um usuário existente para associar ao token
    $user = \App\Models\User::first();
    if ( !$user ) {
        echo "Nenhum usuário encontrado. Criando usuário de teste...\n";
        $user = \App\Models\User::create( [
            'tenant_id' => $tenant->id,
            'email'     => 'test@example.com',
            'password'  => bcrypt( 'password' ),
            'is_active' => true
        ] );
    }

    // Verificar se já existe um token TEST789
    $token = UserConfirmationToken::where( 'token', 'TEST789' )->first();

    if ( !$token ) {
        // Criar token de confirmação para o serviço
        $token             = new UserConfirmationToken();
        $token->user_id    = $user->id;
        $token->tenant_id  = $tenant->id;
        $token->token      = 'TEST789';
        $token->expires_at = now()->addDays( 7 );
        $token->save();
        echo "Token criado com sucesso! Token: {$token->token}\n";
    } else {
        echo "Token TEST789 já existe! ID: {$token->id}\n";
    }

    // Associar o token ao serviço
    $service->user_confirmation_token_id = $token->id;
    $service->save();

    echo "Serviço atualizado com token ID: {$service->user_confirmation_token_id}\n";
    echo "URL de teste: /services/view-service-status/code/TEST456/token/TEST789\n";

} catch ( Exception $e ) {
    echo "Erro ao criar serviço: " . $e->getMessage() . "\n";
}
