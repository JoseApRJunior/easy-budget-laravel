<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
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

        // Assign 'provider' role to tenantUser
        $providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->tenantUser->roles()->attach($providerRole->id, ['tenant_id' => $this->tenant->id]);

        // Setup Admin User
        $adminTenant = Tenant::factory()->create();
        $this->adminUser = User::factory()->create([
            'tenant_id' => $adminTenant->id,
        ]);

        // Create 'admin' role and assign
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Admin']);
        $this->adminUser->roles()->attach($adminRole->id, ['tenant_id' => $adminTenant->id]);
    }

    public function test_cannot_create_self_parenting()
    {
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);

        $response = $this->actingAs($this->adminUser)
            ->put(route('categories.update', $category->id), [
                'name' => 'Test Category',
                'parent_id' => $category->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_create_circular_reference_two_levels()
    {
        // Create A → B
        $categoryA = Category::create(['name' => 'Category A', 'slug' => 'category-a']);
        $categoryB = Category::create(['name' => 'Category B', 'slug' => 'category-b', 'parent_id' => $categoryA->id]);

        // Try to make A child of B (would create A → B → A loop)
        $response = $this->actingAs($this->adminUser)
            ->put(route('categories.update', $categoryA->id), [
                'name' => 'Category A',
                'parent_id' => $categoryB->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_create_circular_reference_three_levels()
    {
        // Create A → B → C
        $categoryA = Category::create(['name' => 'Category A', 'slug' => 'category-a']);
        $categoryB = Category::create(['name' => 'Category B', 'slug' => 'category-b', 'parent_id' => $categoryA->id]);
        $categoryC = Category::create(['name' => 'Category C', 'slug' => 'category-c', 'parent_id' => $categoryB->id]);

        // Try to make A child of C (would create A → B → C → A loop)
        $response = $this->actingAs($this->adminUser)
            ->put(route('categories.update', $categoryA->id), [
                'name' => 'Category A',
                'parent_id' => $categoryC->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_can_create_valid_hierarchy()
    {
        // Create A → B
        $categoryA = Category::create(['name' => 'Category A', 'slug' => 'category-a']);
        $categoryB = Category::create(['name' => 'Category B', 'slug' => 'category-b', 'parent_id' => $categoryA->id]);

        // Create C and make it child of B (valid: A → B → C)
        $categoryC = Category::create(['name' => 'Category C', 'slug' => 'category-c']);

        $response = $this->actingAs($this->adminUser)
            ->put(route('categories.update', $categoryC->id), [
                'name' => 'Category C',
                'parent_id' => $categoryB->id,
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();

        $categoryC->refresh();
        $this->assertEquals($categoryB->id, $categoryC->parent_id);
    }

    public function test_wouldCreateCircularReference_method_direct()
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);

        // Self-reference should return true
        $this->assertTrue($category->wouldCreateCircularReference($category->id));
    }

    public function test_wouldCreateCircularReference_method_indirect()
    {
        // Create A → B → C
        $categoryA = Category::create(['name' => 'A', 'slug' => 'a']);
        $categoryB = Category::create(['name' => 'B', 'slug' => 'b', 'parent_id' => $categoryA->id]);
        $categoryC = Category::create(['name' => 'C', 'slug' => 'c', 'parent_id' => $categoryB->id]);

        // A trying to be child of C would create loop
        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryC->id));

        // C trying to be child of A is valid (would just move it)
        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryA->id));
    }
}
