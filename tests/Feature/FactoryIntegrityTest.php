<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BudgetStatusEnum;
use App\Models\Budget;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Assert as PHPUnit;
use Tests\TestCase;

class FactoryIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_factory_creates_valid_model(): void
    {
        $tenant = Tenant::factory()->create();

        $this->assertDatabaseHas( 'tenants', [ 'id' => $tenant->id ] );
        $this->assertNotNull( $tenant->name );
        $this->assertNotNull( $tenant->slug );
    }

    public function test_product_factory_creates_valid_model_with_relationships(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas( 'products', [ 'id' => $product->id ] );
        $this->assertNotNull( $product->tenant_id );
        $this->assertNotNull( $product->code );
        $this->assertNotNull( $product->name );
        $this->assertNotNull( $product->price );
        $this->assertIsBool( $product->active );
        $this->assertNotNull( $product->category_id ); // Relacionamento com categoria

        // Assert relationship
        $this->assertInstanceOf( Tenant::class, $product->tenant );
        $this->assertEquals( $product->tenant_id, $product->tenant->id );
    }

    public function test_product_factory_enforces_code_uniqueness_per_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $code   = 'PROD-1234';

        // Create first product
        Product::factory()->create( [ 'tenant_id' => $tenant->id, 'code' => $code ] );

        // Attempt duplicate
        $this->expectException( QueryException::class);
        Product::factory()->create( [ 'tenant_id' => $tenant->id, 'code' => $code ] );
    }

    public function test_budget_factory_creates_valid_model_with_relationships(): void
    {
        // Use BudgetStatusEnum
        $budgetStatus = BudgetStatusEnum::DRAFT;

        $budget = Budget::factory()->create( [ 'budget_statuses_id' => $budgetStatus->value ] );

        $this->assertDatabaseHas( 'budgets', [ 'id' => $budget->id ] );
        $this->assertNotNull( $budget->tenant_id );
        $this->assertNotNull( $budget->customer_id );
        $this->assertNotNull( $budget->budget_statuses_id );
        $this->assertNotNull( $budget->code );
        $this->assertGreaterThan( 0, $budget->total );

        // Assert relationships
        $this->assertInstanceOf( Tenant::class, $budget->tenant );
        $this->assertEquals( $budget->tenant_id, $budget->tenant->id );
        $this->assertInstanceOf( User::class, $budget->customer );
        $this->assertEquals( $budget->customer_id, $budget->customer->id );
        $this->assertEquals( 'draft', $budget->budget_statuses_id );
    }

    public function test_budget_factory_enforces_code_uniqueness_per_tenant(): void
    {
        $tenant       = Tenant::factory()->create();
        $budgetStatus = BudgetStatusEnum::DRAFT;
        $user         = User::factory()->create( [ 'tenant_id' => $tenant->id ] );
        $code         = 'BUD-ABC123';

        // Create first budget
        Budget::factory()->create( [
            'tenant_id'          => $tenant->id,
            'customer_id'        => $user->id,
            'code'               => $code,
            'budget_statuses_id' => $budgetStatus->value,
        ] );

        // Attempt duplicate
        $this->expectException( QueryException::class);
        Budget::factory()->create( [
            'tenant_id'          => $tenant->id,
            'customer_id'        => $user->id,
            'code'               => $code,
            'budget_statuses_id' => $budgetStatus->value,
        ] );
    }

    public function test_user_factory_creates_valid_model(): void
    {
        $user = User::factory()->create();

        $this->assertDatabaseHas( 'users', [ 'id' => $user->id ] );
        $this->assertNotNull( $user->name );
        $this->assertNotNull( $user->email );
    }

    public function test_role_factory_creates_valid_model(): void
    {
        $role = Role::factory()->create();

        $this->assertDatabaseHas( 'roles', [ 'id' => $role->id ] );
        $this->assertNotNull( $role->name );
    }

    public function test_permission_factory_creates_valid_model(): void
    {
        $permission = Permission::factory()->create();

        $this->assertDatabaseHas( 'permissions', [ 'id' => $permission->id ] );
        $this->assertNotNull( $permission->name );
    }

    /**
     * Teste básico de scoping por tenant para Product, garantindo isolamento de dados.
     * Cria produtos em tenants diferentes e verifica que apenas dados do tenant atual são visíveis.
     */
    public function test_product_tenant_scoping_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();

        // Cria produto para tenant1
        $product1 = Product::factory()->create( [ 'tenant_id' => $tenant1->id ] );

        // Cria produto para tenant2
        $product2 = Product::factory()->create( [ 'tenant_id' => $tenant2->id ] );

        // Verifica que, sem scoping, ambos são visíveis
        $this->assertEquals( 2, Product::count() );

        // Simula query scoped para tenant1 (usando where explicitamente)
        $scopedProductsTenant1 = Product::where( 'tenant_id', $tenant1->id )->get();
        $this->assertEquals( 1, $scopedProductsTenant1->count() );
        $this->assertEquals( $product1->id, $scopedProductsTenant1->first()->id );

        // Simula query scoped para tenant2
        $scopedProductsTenant2 = Product::where( 'tenant_id', $tenant2->id )->get();
        $this->assertEquals( 1, $scopedProductsTenant2->count() );
        $this->assertEquals( $product2->id, $scopedProductsTenant2->first()->id );
    }

}
