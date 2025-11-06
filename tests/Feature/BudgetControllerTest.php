<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Budget;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário para os testes
        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        // Autenticar o usuário
        $this->actingAs( $this->user );
    }

    public function test_store_creates_budget_with_unique_code(): void
    {
        // Criar customer para o teste
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $data = [
            'customer_id' => $customer->id,
            'description' => 'Orçamento teste',
            'due_date'    => now()->addDays( 30 )->format( 'Y-m-d' ),
            'items'       => [
                [ 'description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100.00 ]
            ]
        ];

        $response = $this->post( route( 'provider.budgets.store' ), $data );

        $response->assertRedirect();
        $this->assertDatabaseHas( 'budgets', [
            'customer_id' => $customer->id,
            'description' => 'Orçamento teste'
        ] );

        $budget = Budget::latest()->first();
        $this->assertStringStartsWith( 'ORC-' . date( 'Ymd' ), $budget->code );
    }

    public function test_show_displays_budget_details(): void
    {
        // Criar customer para o teste
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id
        ] );

        $response = $this->get( route( 'provider.budgets.show', $budget->code ) );

        $response->assertOk();
        $response->assertViewIs( 'pages.budget.show' );
        $response->assertViewHas( 'budget' );
    }

    public function test_update_modifies_budget_data(): void
    {
        // Criar customer para o teste
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
            'description' => 'Descrição original'
        ] );

        $updateData = [
            'description' => 'Descrição atualizada',
            'due_date'    => now()->addDays( 15 )->format( 'Y-m-d' ),
            'items'       => [
                [ 'description' => 'Item atualizado', 'quantity' => 2, 'unit_price' => 150.00 ]
            ]
        ];

        $response = $this->post( route( 'provider.budgets.update', $budget->code ), $updateData );

        $response->assertRedirect();
        $this->assertDatabaseHas( 'budgets', [
            'id'          => $budget->id,
            'description' => 'Descrição atualizada'
        ] );
    }

    public function test_update_store_updates_budget_via_store_method(): void
    {
        // Criar customer para o teste
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
            'description' => 'Descrição store original'
        ] );

        $updateData = [
            'description' => 'Descrição store atualizada',
            'due_date'    => now()->addDays( 20 )->format( 'Y-m-d' ),
            'items'       => [
                [ 'description' => 'Item store', 'quantity' => 1, 'unit_price' => 200.00 ]
            ]
        ];

        $response = $this->post( route( 'provider.budgets.update', $budget->code ), $updateData );

        $response->assertRedirect();
        $this->assertDatabaseHas( 'budgets', [
            'id'          => $budget->id,
            'description' => 'Descrição store atualizada'
        ] );
    }

}
