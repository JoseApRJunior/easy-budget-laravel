<?php

require 'vendor/autoload.php';

$app    = require_once 'bootstrap/app.php';
$kernel = $app->make( Illuminate\Contracts\Http\Kernel::class);

use App\Models\User;
use App\Models\Budget;
use App\Models\Service;
use App\Models\Customer;
use App\Models\BudgetStatus;
use App\Models\ServiceStatus;
use App\Models\Category;
use App\Models\UserConfirmationToken;

// Find the provider user
$provider = User::where( 'email', 'provider@easybudget.net.br' )->first();
if ( !$provider ) {
    echo "Provider user not found!\n";
    exit( 1 );
}

// Find or create a customer
$customer = Customer::where( 'tenant_id', $provider->tenant_id )->first();
if ( !$customer ) {
    echo "No customer found for tenant {$provider->tenant_id}\n";
    exit( 1 );
}

// Find budget status
$budgetStatus = BudgetStatus::where( 'slug', 'pending' )->first();
if ( !$budgetStatus ) {
    $budgetStatus = BudgetStatus::first();
}

// Find service status
$serviceStatus = ServiceStatus::where( 'slug', 'pending' )->first();
if ( !$serviceStatus ) {
    $serviceStatus = ServiceStatus::first();
}

// Find category
$category = Category::first();
if ( !$category ) {
    echo "No category found!\n";
    exit( 1 );
}

// Create a budget with token
$budget = Budget::create( [
    'tenant_id'             => $provider->tenant_id,
    'customer_id'           => $customer->id,
    'budget_statuses_id'    => $budgetStatus->id,
    'code'                  => 'BUD-' . date( 'Y' ) . '-001',
    'due_date'              => now()->addDays( 7 ),
    'discount'              => 0,
    'total'                 => 1500.00,
    'description'           => 'Test budget for public route testing',
    'payment_terms'         => '30 days',
    'pdf_verification_hash' => 'test12345678901234567890123456789012'
] );

echo "Budget created with ID: {$budget->id} and token: {$budget->pdf_verification_hash}\n";

// Create a service with token
$service = Service::create( [
    'tenant_id'             => $provider->tenant_id,
    'budget_id'             => $budget->id,
    'category_id'           => $category->id,
    'service_statuses_id'   => $serviceStatus->id,
    'code'                  => 'SERV-' . date( 'Y' ) . '-001',
    'description'           => 'Test service for public route testing',
    'discount'              => 0,
    'total'                 => 1500.00,
    'due_date'              => now()->addDays( 7 ),
    'pdf_verification_hash' => 'test12345678901234567890123456789012'
] );

echo "Service created with ID: {$service->id} and token: {$service->pdf_verification_hash}\n";

// Create a user confirmation token
$token = UserConfirmationToken::create( [
    'user_id'    => $provider->id,
    'tenant_id'  => $provider->tenant_id,
    'token'      => 'test12345678901234567890123456789012',
    'expires_at' => now()->addHour()
] );

echo "User confirmation token created: {$token->token}\n";

echo "Test data created successfully!\n";
