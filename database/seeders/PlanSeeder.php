<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction( function () {
            // Plan Basic - Plano básico com recursos essenciais
            \App\Models\Plan::create( [ 
                'name'        => 'Basic',
                'slug'        => 'basic',
                'description' => 'Plano básico para iniciantes',
                'price'       => 9.99,
                'status'      => true,
                'max_budgets' => 10,
                'max_clients' => 5,
                'features'    => [ 'Acesso básico', '10 orçamentos/mês', '5 clientes' ],
                'is_active'   => true,
            ] );

            // Plan Pro - Recursos profissionais
            \App\Models\Plan::create( [ 
                'name'        => 'Pro',
                'slug'        => 'pro',
                'description' => 'Plano profissional com mais recursos',
                'price'       => 19.99,
                'status'      => true,
                'max_budgets' => 50,
                'max_clients' => 20,
                'features'    => [ 'Acesso completo', '50 orçamentos/mês', '20 clientes', 'Relatórios avançados' ],
                'is_active'   => true,
            ] );

            // Plan Enterprise - Solução completa para empresas
            \App\Models\Plan::create( [ 
                'name'        => 'Enterprise',
                'slug'        => 'enterprise',
                'description' => 'Plano enterprise com suporte dedicado',
                'price'       => 49.99,
                'status'      => true,
                'max_budgets' => -1, // Ilimitado
                'max_clients' => -1, // Ilimitado
                'features'    => [ 'Tudo incluso', 'Orçamentos ilimitados', 'Clientes ilimitados', 'Suporte prioritário', 'Integrações custom' ],
                'is_active'   => true,
            ] );
        } );
    }

}
