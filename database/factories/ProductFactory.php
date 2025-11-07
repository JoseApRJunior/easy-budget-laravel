<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define o estado padrão da factory para o modelo Product.
     * Gera atributos básicos sem dependências de tenant ou category.
     * Use states 'withTenant' e 'withCategory' para associações.
     * Preço aleatório entre 10 e 1000 com 2 casas decimais.
     * Campo 'active' com 80% de chance de true.
     * Unit_id null até factory separada.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code'        => $this->faker->unique()->lexify( 'PROD-???' ),
            'name'        => $this->faker->word,
            'description' => $this->faker->sentence( 3 ),
            'price'       => $this->faker->randomFloat( 2, 10, 1000 ),
            'active'      => $this->faker->boolean( 80 ), // 80% chance de ativo
        ];
    }

    /**
     * Estado para associar Product a um tenant específico.
     * Cria tenant via factory se não fornecido nos atributos.
     *
     * @return static
     */
    public function withTenant(): static
    {
        return $this->state( fn( array $attributes ) => [
            'tenant_id' => $attributes[ 'tenant_id' ] ?? Tenant::factory()->create()->id,
        ] );
    }

    /**
     * Estado para associar Product a uma category no tenant atual.
     * Cria category via factory com tenant_id dos atributos ou tenant default.
     * Use após 'withTenant' para garantir tenant_id disponível.
     *
     * @return static
     */
    public function withCategory(): static
    {
        return $this->state( fn( array $attributes ) => [
            'category_id' => $attributes[ 'category_id' ] ??
                Category::factory()
                    ->forTenant( $attributes[ 'tenant_id' ] ?? Tenant::first()->id )
                    ->create()
                    ->id,
        ] );
    }

}
