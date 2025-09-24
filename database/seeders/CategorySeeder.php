<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
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

        // Criar categorias globais (sem tenant_id)
        foreach ( $categoriesData as $data ) {
            Category::updateOrCreate(
                [ 'slug' => $data[ 'slug' ] ],
                [ 'name' => $data[ 'name' ] ]
            );
        }
    }

}
