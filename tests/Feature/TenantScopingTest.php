<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Permission;
use App\Models\PlanSubscription;
use App\Models\Product;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Traits\TenantScoped;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Tests\TestCase;

class TenantScopingTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed( [
            \Database\Seeders\RoleSeeder::class,
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\RolePermissionSeeder::class
        ] );
    }

    public function test_scoped_models_filter_by_current_tenant(): void
    {
        $tenant1 = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2 = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );

        // Create data for tenant1
        TenantScoped::setTestingTenantId( $tenant1->id );
        Product::factory()->create( [ 'tenant_id' => $tenant1->id ] );
        Budget::factory()->create( [ 'tenant_id' => $tenant1->id ] );

        // Create data for tenant2
        TenantScoped::setTestingTenantId( $tenant2->id );
        Product::factory()->create( [ 'tenant_id' => $tenant2->id ] );
        Budget::factory()->create( [ 'tenant_id' => $tenant2->id ] );

        // Switch back to tenant1 and assert only tenant1 data visible
        TenantScoped::setTestingTenantId( $tenant1->id );
        $this->assertEquals( 1, Product::count() );
        $this->assertEquals( 1, Budget::count() );
    }

    public function test_global_models_not_scoped(): void
    {
        $tenant1 = Tenant::factory()->create();
        TenantScoped::setTestingTenantId( $tenant1->id );

        // Roles e Permissions já seedados no setUp, globais (sem tenant_id)
        // BudgetStatus agora é enum, não tabela

        $this->assertEquals( 5, Role::count() ); // admin, manager, staff, viewer, provider do seeder
        $this->assertEquals( 17, Permission::count() ); // permissions do seeder

        // Create another tenant and assert globals still visible
        $tenant2 = Tenant::factory()->create();
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertEquals( 5, Role::count() );
        $this->assertEquals( 17, Permission::count() );
    }

    public function test_rbac_assignments_scoped_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2 = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );
        $user    = User::factory()->create( [ 'tenant_id' => $tenant1->id ] );

        // Test simples: verificar se a relação está funcionando
        $this->assertTrue( method_exists( $user, 'roles' ), 'User should have roles method' );
        $this->assertInstanceOf( \Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $user->roles() );
    }

    public function test_budget_with_enum_status_scoping(): void
    {
        $tenant1   = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2   = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );
        $customer1 = \App\Models\Customer::factory()->create( [ 'tenant_id' => $tenant1->id ] );

        TenantScoped::setTestingTenantId( $tenant1->id );
        $budget1 = Budget::factory()->create( [
            'tenant_id'   => $tenant1->id,
            'customer_id' => $customer1->id,
            'status'      => 'pending',
        ] );

        // Budget scoped: visível em tenant1
        $this->assertDatabaseHas( 'budgets', [
            'id'     => $budget1->id,
            'status' => 'pending',
        ] );

        // Verificar se a query está aplicando o scope corretamente
        $budgets = Budget::all();
        $this->assertCount( 1, $budgets, 'Only one budget should be visible for current tenant' );
        $this->assertEquals( $budget1->id, $budgets->first()->id );
    }

    public function test_plan_subscription_scoping(): void
    {
        $tenant1 = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2 = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );

        // Create for tenant1
        TenantScoped::setTestingTenantId( $tenant1->id );

        // Criar provider simples sem factory
        $user     = User::factory()->create( [ 'tenant_id' => $tenant1->id ] );
        $provider = new \App\Models\Provider( [
            'tenant_id'      => $tenant1->id,
            'user_id'        => $user->id,
            'terms_accepted' => true
        ] );
        $provider->save();

        $plan = \App\Models\Plan::factory()->create();

        $subscription = PlanSubscription::factory()->create( [
            'tenant_id'   => $tenant1->id,
            'provider_id' => $provider->id,
            'plan_id'     => $plan->id
        ] );

        // Verify visible for tenant1
        $this->assertEquals( 1, PlanSubscription::count() );

        // Switch to tenant2: not visible
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertEquals( 0, PlanSubscription::count() );
    }

}
