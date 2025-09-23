<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
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
            Plan::create( [
                'name'        => 'Basic',
                'slug'        => 'basic',
                'description' => 'Plano básico para iniciantes',
                'price'       => 9.99,
                'features'    => [ 'Acesso básico', '10 orçamentos/mês', '5 clientes' ],
                'is_active'   => true,
            ] );

            // Plan Pro - Recursos profissionais
            \App\Models\Plan::create( [
                'name'        => 'Pro',
                'slug'        => 'pro',
                'description' => 'Plano profissional com mais recursos',
                'price'       => 19.99,
                'features'    => [ 'Acesso completo', '50 orçamentos/mês', '20 clientes', 'Relatórios avançados' ],
                'is_active'   => true,
            ] );

            // Plan Enterprise - Solução completa para empresas
            \App\Models\Plan::create( [
                'name'        => 'Enterprise',
                'slug'        => 'enterprise',
                'description' => 'Plano enterprise com suporte dedicado',
                'price'       => 49.99,
                'features'    => [ 'Tudo incluso', 'Orçamentos ilimitados', 'Clientes ilimitados', 'Suporte prioritário', 'Integrações custom' ],
                'is_active'   => true,
            ] );
        } );
    }

}
