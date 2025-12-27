<?php

use App\Actions\Provider\RegisterProviderAction;
use App\DTOs\Provider\ProviderRegistrationDTO;
use Illuminate\Support\Facades\DB;

try {
    DB::beginTransaction();

    $action = app(RegisterProviderAction::class);

    $dto = new ProviderRegistrationDTO(
        first_name: 'Test',
        last_name: 'Provider',
        email: 'test_provider_' . uniqid() . '@example.com',
        password: 'Password123!',
        terms_accepted: true
    );

    echo "Executing RegisterProviderAction...\n";
    $result = $action->execute($dto);

    echo "Registration Successful!\n";
    echo "User ID: " . $result['user']->id . "\n";
    echo "Tenant ID: " . $result['tenant']->id . "\n";
    echo "Provider ID: " . $result['provider']->id . "\n";
    echo "Plan: " . $result['plan']->name . "\n";
    echo "Subscription Status: " . $result['subscription']->status . "\n";

    // Check if inventory was created for the provider's products (if any)
    // In this case, a new provider has no products yet.

    DB::rollBack();
    echo "Transaction rolled back successfully.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during registration: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
