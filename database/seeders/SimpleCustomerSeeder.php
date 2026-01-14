<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimpleCustomerSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::first();
        $user = User::where('tenant_id', $tenant->id)->first();

        if (! $tenant || ! $user) {
            $this->command->info('Tenant or User not found. Please run other seeders first.');

            return;
        }

        // Criar clientes simples para teste
        $customers = [
            [
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ],
        ];

        foreach ($customers as $customerData) {
            Customer::create($customerData);
        }

        $this->command->info('Test customers created successfully!');
        $this->command->info('Customers created: '.Customer::where('tenant_id', $tenant->id)->count());
    }
}
