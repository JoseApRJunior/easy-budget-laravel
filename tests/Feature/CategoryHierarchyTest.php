<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $providerUser;

    protected $providerRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Tenant
        $this->tenant = Tenant::factory()->create();

        // Setup Provider User (usuário normal que pode acessar categorias)
        $this->providerUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(), // Mark as verified to avoid redirect
        ]);

        // Create and assign 'provider' role to providerUser
        $this->providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->providerUser->roles()->attach($this->providerRole->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_cannot_create_self_parenting()
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'tenant_id' => $this->tenant->id,
        ]);

        // Act as provider user (who should have access to categories)
        $response = $this->actingAs($this->providerUser)
            ->put(route('categories.update', $category->slug), [
                'name' => 'Test Category',
                'parent_id' => $category->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_create_circular_reference_two_levels()
    {
        // Create A → B
        $categoryA = Category::create([
            'name' => 'Category A',
            'slug' => 'category-a',
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryB = Category::create([
            'name' => 'Category B',
            'slug' => 'category-b',
            'parent_id' => $categoryA->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Try to make A child of B (would create A → B → A loop)
        $response = $this->actingAs($this->providerUser)
            ->put(route('categories.update', $categoryA->slug), [
                'name' => 'Category A',
                'parent_id' => $categoryB->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_cannot_create_circular_reference_three_levels()
    {
        // Create A → B → C
        $categoryA = Category::create([
            'name' => 'Category A',
            'slug' => 'category-a',
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryB = Category::create([
            'name' => 'Category B',
            'slug' => 'category-b',
            'parent_id' => $categoryA->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryC = Category::create([
            'name' => 'Category C',
            'slug' => 'category-c',
            'parent_id' => $categoryB->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Try to make A child of C (would create A → B → C → A loop)
        $response = $this->actingAs($this->providerUser)
            ->put(route('categories.update', $categoryA->slug), [
                'name' => 'Category A',
                'parent_id' => $categoryC->id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('parent_id');
    }

    public function test_can_create_valid_hierarchy()
    {
        // Create A → B
        $categoryA = Category::create([
            'name' => 'Category A',
            'slug' => 'category-a',
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryB = Category::create([
            'name' => 'Category B',
            'slug' => 'category-b',
            'parent_id' => $categoryA->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // Create C and make it child of B (valid: A → B → C)
        $categoryC = Category::create([
            'name' => 'Category C',
            'slug' => 'category-c',
            'tenant_id' => $this->tenant->id,
        ]);

        $response = $this->actingAs($this->providerUser)
            ->put(route('categories.update', $categoryC->slug), [
                'name' => 'Category C',
                'parent_id' => $categoryB->id,
            ]);

        $response->assertRedirect(route('categories.index'));
        $response->assertSessionHasNoErrors();

        $categoryC->refresh();
        $this->assertEquals($categoryB->id, $categoryC->parent_id);
    }

    public function test_would_create_circular_reference_method_direct()
    {
        $category = Category::create([
            'name' => 'Test',
            'slug' => 'test',
            'tenant_id' => $this->tenant->id,
        ]);

        // Self-reference should return true
        $this->assertTrue($category->wouldCreateCircularReference($category->id));
    }

    public function test_would_create_circular_reference_method_indirect()
    {
        // Create A → B → C
        $categoryA = Category::create([
            'name' => 'A',
            'slug' => 'a',
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryB = Category::create([
            'name' => 'B',
            'slug' => 'b',
            'parent_id' => $categoryA->id,
            'tenant_id' => $this->tenant->id,
        ]);
        $categoryC = Category::create([
            'name' => 'C',
            'slug' => 'c',
            'parent_id' => $categoryB->id,
            'tenant_id' => $this->tenant->id,
        ]);

        // A trying to be child of C would create loop
        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryC->id));

        // C trying to be child of A is valid (would just move it)
        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryA->id));
    }
}
