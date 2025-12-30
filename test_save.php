<?php

use App\Models\Customer;
use App\Models\Profession;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\CustomerRepository;
use App\DTOs\Customer\CustomerDTO;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Load Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Get a user and login
$user = User::first();
if (!$user) {
    echo "No user found\n";
    exit;
}
Auth::login($user);

// Get a tenant
$tenant = $user->tenant;
if (!$tenant) {
    echo "No tenant found for user\n";
    exit;
}

// Get a profession
$profession = Profession::first();
if (!$profession) {
    echo "No profession found\n";
    exit;
}

$repository = app(CustomerRepository::class);

// Mock request data
$data = [
    'tenant_id' => $tenant->id,
    'person_type' => 'pf',
    'first_name' => 'Test',
    'last_name' => 'User',
    'birth_date' => '15/05/1990',
    'profession_id' => $profession->id,
    'email_personal' => 'test_' . time() . '@example.com',
    'phone_personal' => '11999999999',
    'cep' => '01001000',
    'address' => 'Test Street',
    'neighborhood' => 'Test Neighborhood',
    'city' => 'Test City',
    'state' => 'SP',
];

echo "Creating customer...\n";
$dto = CustomerDTO::fromRequest($data);
$customer = $repository->createFromDTO($dto);

echo "Customer ID: " . $customer->id . "\n";
echo "Birth Date in DB: " . ($customer->commonData->birth_date ? $customer->commonData->birth_date->format('Y-m-d') : 'NULL') . "\n";
echo "Profession ID in DB: " . ($customer->commonData->profession_id ?? 'NULL') . "\n";

if ($customer->commonData->birth_date && $customer->commonData->birth_date->format('Y-m-d') === '1990-05-15' && $customer->commonData->profession_id == $profession->id) {
    echo "SUCCESS: Birth date and Profession ID saved correctly during creation.\n";
} else {
    echo "FAILURE: Birth date or Profession ID NOT saved correctly during creation.\n";
}

// Test update
echo "\nUpdating customer...\n";
$updateData = $data;
$updateData['birth_date'] = '20/10/1985';
$updateData['profession_id'] = $profession->id;

$updateDto = CustomerDTO::fromRequest($updateData);
$repository->updateFromDTO($customer, $updateDto);
$customer->refresh();

echo "Updated Birth Date in DB: " . ($customer->commonData->birth_date ? $customer->commonData->birth_date->format('Y-m-d') : 'NULL') . "\n";
echo "Updated Profession ID in DB: " . ($customer->commonData->profession_id ?? 'NULL') . "\n";

if ($customer->commonData->birth_date && $customer->commonData->birth_date->format('Y-m-d') === '1985-10-20') {
    echo "SUCCESS: Birth date updated correctly.\n";
} else {
    echo "FAILURE: Birth date NOT updated correctly.\n";
}

// Clean up
// $customer->delete();
