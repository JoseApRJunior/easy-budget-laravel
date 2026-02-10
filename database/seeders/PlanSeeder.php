<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $plans = [
            [
                'name' => 'Plano Trial',
                'slug' => 'trial',
                'stripe_id' => 'price_trial_placeholder',
                'description' => 'Plano trial de 7 dias para novos usuários conhecerem o sistema.',
                'price' => 0.00,
                'status' => true,
                'max_budgets' => 5,
                'max_clients' => 25,
                'features' => json_encode([
                    'analytics', 'customers', 'products', 'services', 'schedules', 'budgets', 'invoices', 'financial', 'reports'
                ]),
                'created_at' => $now,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'stripe_id' => 'price_basic_placeholder',
                'description' => 'Plano básico para começar: limites adequados para pequenos negócios.',
                'price' => 29.90,
                'status' => true,
                'max_budgets' => 50,
                'max_clients' => 100,
                'features' => json_encode(['customers', 'budgets', 'categories']),
                'created_at' => $now,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'stripe_id' => 'price_pro_placeholder',
                'description' => 'Plano profissional com recursos avançados e maiores limites.',
                'price' => 49.90,
                'status' => true,
                'max_budgets' => 1000,
                'max_clients' => 5000,
                'features' => json_encode(['customers', 'budgets', 'categories', 'services', 'schedules', 'reports']),
                'created_at' => $now,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'stripe_id' => 'price_enterprise_placeholder',
                'description' => 'Plano empresarial totalmente flexível e com limites elevados.',
                'price' => 99.90,
                'status' => true,
                'max_budgets' => 100000,
                'max_clients' => 100000,
                'features' => json_encode([
                    'analytics', 'customers', 'products', 'services', 'schedules', 'budgets', 'invoices', 'financial', 'qrcode', 'reports', 'inventory', 'categories'
                ]),
                'created_at' => $now,
            ],
        ];

        DB::table('plans')->upsert($plans, ['slug'], ['name', 'stripe_id', 'description', 'price', 'status', 'max_budgets', 'max_clients', 'features']);
    }
}
