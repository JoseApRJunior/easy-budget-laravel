<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        
        // Define feature groups
        $basicFeatures = ['categories', 'customers', 'products', 'services', 'schedules', 'budgets'];
        $proFeatures = array_merge($basicFeatures, ['financial', 'invoices', 'inventory', 'qrcode', 'reports']);
        $enterpriseFeatures = array_merge($proFeatures, ['analytics']);
        
        // Trial gets everything available in Enterprise
        $trialFeatures = $enterpriseFeatures;

        $plans = [
            [
                'name' => 'Plano Trial',
                'slug' => 'trial',
                'description' => 'Plano trial de 7 dias para novos usuários conhecerem o sistema.',
                'price' => 0.00,
                'status' => true,
                'max_budgets' => 5,
                'max_clients' => 25,
                'features' => json_encode($trialFeatures),
                'created_at' => $now,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Plano básico para começar: limites adequados para pequenos negócios.',
                'price' => 29.90,
                'status' => true,
                'max_budgets' => 50,
                'max_clients' => 100,
                'features' => json_encode($basicFeatures),
                'created_at' => $now,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Plano profissional com recursos avançados e maiores limites.',
                'price' => 49.90,
                'status' => true,
                'max_budgets' => 1000,
                'max_clients' => 5000,
                'features' => json_encode($proFeatures),
                'created_at' => $now,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Plano empresarial totalmente flexível e com limites elevados.',
                'price' => 99.90,
                'status' => true,
                'max_budgets' => 100000,
                'max_clients' => 100000,
                'features' => json_encode($enterpriseFeatures),
                'created_at' => $now,
            ],
        ];

        DB::table('plans')->upsert($plans, ['slug'], ['name', 'description', 'price', 'status', 'max_budgets', 'max_clients', 'features']);
    }
}
