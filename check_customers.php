<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;

$total = Customer::withoutGlobalScopes()->count();
$active = Customer::withoutGlobalScopes()->whereNull('deleted_at')->count();
$deleted = Customer::withoutGlobalScopes()->whereNotNull('deleted_at')->count();

echo "Total Customers: $total\n";
echo "Active Customers: $active\n";
echo "Deleted Customers: $deleted\n";
