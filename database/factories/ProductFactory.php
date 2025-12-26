<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define o estado padrão da factory para o modelo Product.
     * Gera atributos básicos sem dependências de tenant ou category.
     * Use states 'withTenant' e 'withCategory' para associações.
     * Preço aleatório entre 10 e 1000 com 2 casas decimais.
     * Campo 'active' com 80% de chance de true.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'description' => $this->faker->sentence,
            'sku' => $this->faker->unique()->ean8,
            'price' => $this->faker->randomFloat(2, 10, 500),
            'unit' => $this->faker->randomElement(['un', 'h', 'm²']),
            'active' => true,
            'image' => null,
        ];
    }

    /**
     * Estado para associar Product a um tenant específico.
     * Cria tenant via factory se não fornecido nos atributos.
     */
    public function withTenant(): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $attributes['tenant_id'] ?? Tenant::factory()->create()->id,
        ]);
    }

    /**
     * Estado para associar Product a uma category no tenant atual.
     * Cria category via factory com tenant_id dos atributos ou tenant default.
     * Use após 'withTenant' para garantir tenant_id disponível.
     */
    public function withCategory(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $attributes['category_id'] ??
                Category::factory()
                    ->forTenant($attributes['tenant_id'] ?? Tenant::first())
                    ->create()
                    ->id,
        ]);
    }
}
