<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $now   = now();
        $plans = [
            [
                'name'        => 'Basic',
                'slug'        => 'basic',
                'description' => 'Plano básico para começar: limites adequados para pequenos negócios.',
                'price'       => 29.90,
                'status'      => true,
                'max_budgets' => 50,
                'max_clients' => 100,
                'features'    => json_encode( [ "Acesso a recursos básicos", "Até 3 orçamentos por mês", "1 Cliente por mês" ] ),
                'created_at'  => $now,
            ],
            [
                'name'        => 'Pro',
                'slug'        => 'pro',
                'description' => 'Plano profissional com recursos avançados e maiores limites.',
                'price'       => 49.90,
                'status'      => true,
                'max_budgets' => 1000,
                'max_clients' => 5000,
                'features'    => json_encode( [ "Acesso a recursos básicos", "Até 15 orçamentos por mês", "5 Clientes por mês", "Relatórios básicos" ] ),
                'created_at'  => $now,
            ],
            [
                'name'        => 'Enterprise',
                'slug'        => 'enterprise',
                'description' => 'Plano empresarial totalmente flexível e com limites elevados.',
                'price'       => 99.90,
                'status'      => true,
                'max_budgets' => 100000,
                'max_clients' => 100000,
                'features'    => json_encode( [ "Acesso a todos os recursos", "Orçamentos ilimitados", "Clientes ilimitados", "Relatórios avançados", "Integração com pagamentos", "Gerencimento de projetos" ] ),
                'created_at'  => $now,
            ],
        ];

        DB::table( 'plans' )->upsert( $plans, [ 'slug' ], [ 'name', 'description', 'price', 'status', 'max_budgets', 'max_clients', 'features' ] );
    }

}
