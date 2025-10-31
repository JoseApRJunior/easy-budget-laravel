<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BudgetStatus;
use App\Models\Budget;
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
            'code'               => $this->faker->unique()->regexify( 'BUD-[A-Z0-9]{6}' ),
            'total'              => $this->faker->randomFloat( 2, 100, 10000 ),
            'discount'           => $this->faker->randomFloat( 2, 0, 100 ),
            'budget_statuses_id' => BudgetStatus::DRAFT->value,
            'customer_id'        => Customer::factory(),
        ];
    }

    /**
     * State para associar o orçamento a um tenant específico.
     */
    public function forTenant( Tenant $tenant ): static
    {
        return $this->state( fn( array $attributes ) => [
            'tenant_id' => $tenant->id,
        ] );
    }

    /**
     * State para associar o orçamento a um cliente específico.
     */
    public function forCustomer( Customer $customer ): static
    {
        return $this->state( fn( array $attributes ) => [
            'customer_id' => $customer->id,
        ] );
    }

    /**
     * State para definir um status específico do orçamento.
     */
    public function withStatus( BudgetStatus $status ): static
    {
        return $this->state( fn( array $attributes ) => [
            'budget_statuses_id' => $status->value,
        ] );
    }

}
