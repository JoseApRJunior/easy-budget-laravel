<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Budget;
use App\Models\BudgetStatus;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /**
     * Define o estado padrão da factory para o modelo Budget.
     * Gera apenas código único e valor total básico.
     * Use states 'forTenant', 'forCustomer', 'withStatus' para associações.
     * Total aleatório entre 100 e 10000 com 2 casas decimais.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [ 
            'code'  => $this->faker->unique()->regexify( 'BUD-[A-Z0-9]{6}' ),
            'total' => $this->faker->randomFloat( 2, 100, 10000 ),
        ];
    }

    /**
     * Estado para associar Budget a um tenant específico.
     * Gera código único per-tenant usando Str::uuid() + tenant_id.
     * Use como primeiro state para garantir tenant_id disponível.
     *
     * @return static
     */
    public function forTenant(): static
    {
        return $this->state( fn( array $attributes ) => [ 
            'tenant_id' => $attributes[ 'tenant_id' ] ?? Tenant::factory()->create()->id,
            'code'      => fn( array $attributes ) =>
                Str::uuid()->toString() . '_' . ( $attributes[ 'tenant_id' ] ?? Tenant::first()->id ),
        ] );
    }

    /**
     * Estado para associar Budget a um customer (User).
     * Cria user via factory se não fornecido.
     *
     * @return static
     */
    public function forCustomer(): static
    {
        return $this->state( fn( array $attributes ) => [ 
            'customer_id' => $attributes[ 'customer_id' ] ?? User::factory()->create()->id,
        ] );
    }

    /**
     * Estado para associar Budget a um status específico.
     * Use BudgetStatus::factory()->pending() para status 'pending'.
     * Cria status via factory se não fornecido.
     *
     * @return static
     */
    public function withStatus(): static
    {
        return $this->state( fn( array $attributes ) => [ 
            'budget_statuses_id' => $attributes[ 'budget_statuses_id' ] ??
                BudgetStatus::factory()->pending()->create()->id,
        ] );
    }

}
