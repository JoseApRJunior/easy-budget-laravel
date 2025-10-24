<?php

require 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Criando budget de teste...\n";

// Verificar se existe customer
$customer = App\Models\Customer::where( 'tenant_id', 3 )->first();
if ( !$customer ) {
    echo "Nenhum customer encontrado para tenant 3. Criando customer...\n";
    $customer            = new App\Models\Customer();
    $customer->tenant_id = 3;
    $customer->status    = 'active';
    $customer->save();
    echo "Customer criado com ID: " . $customer->id . "\n";
}

// Criar budget
$budget                     = new App\Models\Budget();
$budget->tenant_id          = 3;
$budget->customer_id        = $customer->id;
$budget->budget_statuses_id = 1;
$budget->code               = 'TEST123';
$budget->total              = 1500.00;
$budget->discount           = 0.00;
$budget->description        = 'Budget de teste para rotas públicas';
$budget->save();

echo "Budget criado com ID: " . $budget->id . "\n";

// Criar token de confirmação
$token             = new App\Models\UserConfirmationToken();
$token->user_id    = 3;
$token->tenant_id  = 3;
$token->token      = 'TEST456';
$token->expires_at = now()->addDays( 7 );
$token->save();

echo "Token criado com ID: " . $token->id . "\n";

// Vincular token ao budget
$budget->user_confirmation_token_id = $token->id;
$budget->save();

echo "Budget e token vinculados!\n";
echo "URL de teste: http://localhost:8000/budgets/choose-budget-status/code/TEST123/token/TEST456\n";
