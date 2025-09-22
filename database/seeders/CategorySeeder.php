<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Dados das categorias de exemplo, sem ID fixo (auto-increment)
        // Cada categoria será criada para todos os tenants existentes
        $categoriesData = [ 
            [ 'slug' => 'carpentry', 'name' => 'Carpintaria' ],
            [ 'slug' => 'construction_civil', 'name' => 'Construção Civil' ],
            [ 'slug' => 'construction_furniture', 'name' => 'Construção de Móveis' ],
            [ 'slug' => 'construction_doors', 'name' => 'Construção de Portas' ],
            [ 'slug' => 'construction_electric', 'name' => 'Elétrica' ],
            [ 'slug' => 'construction_hydraulic', 'name' => 'Hidráulica' ],
            [ 'slug' => 'installation_pumps', 'name' => 'Instalação de Bombas' ],
            [ 'slug' => 'installation_pipes', 'name' => 'Instalação de Tubulações' ],
            [ 'slug' => 'installation_glass', 'name' => 'Instalação de Vidros' ],
            [ 'slug' => 'electrical_installation', 'name' => 'Instalação Elétrica' ],
            [ 'slug' => 'maintenance_pumps', 'name' => 'Manutenção de Bombas' ],
            [ 'slug' => 'maintenance_vehicles', 'name' => 'Manutenção de Veículos' ],
            [ 'slug' => 'maintenance_electric', 'name' => 'Manutenção Elétrica' ],
            [ 'slug' => 'mechanical', 'name' => 'Mecânica' ],
            [ 'slug' => 'masonry', 'name' => 'Obra de Alvenaria' ],
            [ 'slug' => 'painting', 'name' => 'Pintura' ],
            [ 'slug' => 'painting_wall', 'name' => 'Pintura de Parede' ],
            [ 'slug' => 'painting_ceiling', 'name' => 'Pintura de Teto' ],
            [ 'slug' => 'reforms', 'name' => 'Reformas' ],
            [ 'slug' => 'engine_repair', 'name' => 'Reparo de Motores' ],
            [ 'slug' => 'repair_furniture', 'name' => 'Reparo de Móveis' ],
            [ 'slug' => 'repair_doors', 'name' => 'Reparo de Portas' ],
            [ 'slug' => 'glass_repair', 'name' => 'Reparo de Vidros' ],
            [ 'slug' => 'metal_working', 'name' => 'Serralheria' ],
            [ 'slug' => 'soldagem', 'name' => 'Soldagem' ],
            [ 'slug' => 'vidraceiro', 'name' => 'Vidraceiro' ],
            [ 'slug' => 'others', 'name' => 'Outros' ],
        ];

        // Se não há tenants, criar default tenant para seeding inicial
        if ( Tenant::count() === 0 ) {
            $defaultTenant = Tenant::create( [ 
                'name'      => 'Default',
                'slug'      => 'default',
                'is_active' => true,
            ] );
            $tenantId      = $defaultTenant->id;
            $tenants       = collect( [ $defaultTenant ] );
        } else {
            $tenants  = Tenant::all();
            $tenantId = $tenants->first()->id; // Use first tenant for any global fallback if needed
        }

        // Loop através de todos os tenants existentes no sistema
        // Garante que cada categoria seja criada scoped ao tenant correto
        foreach ( $tenants as $tenant ) {
            foreach ( $categoriesData as $data ) {
                // Usa updateOrCreate para evitar duplicatas por tenant e slug
                // Chave única: ['tenant_id', 'slug'] conforme constraint da migration
                Category::updateOrCreate(
                    [ 
                        'tenant_id' => $tenant->id,
                        'slug'      => $data[ 'slug' ],
                    ],
                    [ 
                        'name'        => $data[ 'name' ],
                        'description' => null, // Descrição opcional, pode ser adicionada posteriormente
                        'is_active'   => true,
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ],
                );
            }
        }
    }

}
