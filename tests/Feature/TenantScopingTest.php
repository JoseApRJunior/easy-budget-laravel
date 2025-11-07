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
        $this->seed( [ RolePermissionSeeder::class] );
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

        $this->assertEquals( 3, Role::count() ); // admin, provider, user do seeder
        $this->assertEquals( 10, Permission::count() ); // permissions básicas do seeder

        // Create another tenant and assert globals still visible
        $tenant2 = Tenant::factory()->create();
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertEquals( 3, Role::count() );
        $this->assertEquals( 10, Permission::count() );
    }

    /**
     * Teste demonstra que Role e Permission são globais (sem TenantScoped trait),
     * visíveis em todos tenants. Assignments (user_roles, role_permissions) são scoped via pivot tenant_id.
     * Custom RBAC sem Spatie: usa relationships Eloquent belongsToMany com pivots custom.
     */

    public function test_rbac_assignments_scoped_by_tenant(): void
    {
        $tenant1 = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2 = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );
        $user    = User::factory()->create( [ 'tenant_id' => $tenant1->id ] );

        TenantScoped::setTestingTenantId( $tenant1->id );

        // Get seeded roles (globais)
        $adminRole = Role::where( 'name', 'admin' )->first();
        $userRole  = Role::where( 'name', 'user' )->first();

        // Attach role ao user para tenant1 via pivot com tenant_id
        $user->roles()->attach( $adminRole->id, [ 'tenant_id' => $tenant1->id ] );

        // Assert: role assignment visível para tenant1
        $this->assertTrue(
            $user->roles()->wherePivot( 'tenant_id', $tenant1->id )->exists(),
        );
        $this->assertEquals( 1, $user->roles()->wherePivot( 'tenant_id', $tenant1->id )->count() );

        // Switch to tenant2: assignment NÃO visível
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertFalse(
            $user->roles()->wherePivot( 'tenant_id', $tenant2->id )->exists(),
        );
        $this->assertEquals( 0, $user->roles()->wherePivot( 'tenant_id', $tenant2->id )->count() );

        // Test detach scoped
        TenantScoped::setTestingTenantId( $tenant1->id );
        $user->roles()->wherePivot( 'tenant_id', $tenant1->id )->detach( $adminRole->id );
        $this->assertFalse(
            $user->roles()->wherePivot( 'tenant_id', $tenant1->id )->exists(),
        );

        // Test direct permission attach (assumindo tabela user_permissions existe)
        $permission = Permission::where( 'name', 'view-dashboard' )->first();
        $user->permissions()->attach( $permission->id, [ 'tenant_id' => $tenant1->id ] );
        $this->assertTrue(
            $user->permissions()->wherePivot( 'tenant_id', $tenant1->id )->exists(),
        );
    }

    /**
     * Teste integra com Budget usando status enum (sem tabela budget_statuses).
     * Verifica scoping em Budget (tenant-scoped) com status como enum.
     */
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

        // Switch to tenant2: budget1 NÃO visível
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertDatabaseMissing( 'budgets', [ 'id' => $budget1->id ] );
    }

    public function test_plan_subscription_scoping(): void
    {
        $tenant1 = Tenant::factory()->create( [ 'name' => 'Tenant 1' ] );
        $tenant2 = Tenant::factory()->create( [ 'name' => 'Tenant 2' ] );

        // Create for tenant1
        TenantScoped::setTestingTenantId( $tenant1->id );
        $subscription = PlanSubscription::factory()->create( [
            'tenant_id' => $tenant1->id
        ] );

        // Verify visible for tenant1
        $this->assertEquals( 1, PlanSubscription::count() );

        // Switch to tenant2: not visible
        TenantScoped::setTestingTenantId( $tenant2->id );
        $this->assertEquals( 0, PlanSubscription::count() );
    }

}
