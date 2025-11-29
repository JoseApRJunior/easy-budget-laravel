<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $adminUser;
    protected $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Tenant
        $this->tenant = Tenant::factory()->create();

        // Setup Tenant User
        $this->tenantUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->tenantUser->roles()->attach($providerRole->id, ['tenant_id' => $this->tenant->id]);

        // Setup Admin User
        $adminTenant = Tenant::factory()->create();
        $this->adminUser = User::factory()->create([
            'tenant_id' => $adminTenant->id,
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);
        $this->adminUser->roles()->attach($adminRole->id, ['tenant_id' => $adminTenant->id]);
    }

    public function test_admin_can_view_deleted_categories()
    {
        $category = Category::create(['name' => 'Test Deleted', 'slug' => 'test-deleted']);
        $category->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('categories.index', ['deleted' => 'only']));

        $response->assertOk();
        $response->assertSee('Test Deleted');
    }

    public function test_admin_can_restore_deleted_category()
    {
        $category = Category::create(['name' => 'Test Restore', 'slug' => 'test-restore']);
        $category->delete();

        $this->assertTrue($category->trashed());

        $response = $this->actingAs($this->adminUser)
            ->post(route('categories.restore', $category->id));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($category->fresh()->trashed());
    }

    public function test_tenant_cannot_restore_category()
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        $category->delete();

        $response = $this->actingAs($this->tenantUser)
            ->post(route('categories.restore', $category->id));

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHas('error');

        $this->assertTrue($category->fresh()->trashed());
    }

    public function test_deleted_category_not_shown_in_normal_list()
    {
        $activeCategory = Category::create(['name' => 'Active', 'slug' => 'active']);
        $deletedCategory = Category::create(['name' => 'Deleted', 'slug' => 'deleted']);
        $deletedCategory->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('categories.index', ['all' => true]));

        $response->assertOk();
        $response->assertSee('Active');
        $response->assertDontSee('Deleted');
    }

    public function test_orphan_categories_not_shown_in_active_list()
    {
        $parent = Category::create(['name' => 'Parent', 'slug' => 'parent']);
        $child = Category::create([
            'name' => 'Child',
            'slug' => 'child',
            'parent_id' => $parent->id
        ]);

        // Deletar parent
        $parent->delete();

        // Buscar categorias ativas via repository
        $repository = app(\App\Repositories\CategoryRepository::class);
        $activeCategories = $repository->listActive();

        // Child não deve aparecer pois parent foi deletado
        $this->assertFalse($activeCategories->contains($child));
    }
}
