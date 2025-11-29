<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDefaultTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        $this->tenantUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);

        $providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->tenantUser->roles()->attach($providerRole->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_tenant_can_set_default_category()
    {
        $category = Category::create(['name' => 'Test Default', 'slug' => 'test-default']);
        $category->tenants()->attach($this->tenant->id, ['is_custom' => true, 'is_default' => false]);

        $response = $this->actingAs($this->tenantUser)
            ->post(route('categories.set-default', $category->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $pivot = $category->tenants()->where('tenant_id', $this->tenant->id)->first()->pivot;
        $this->assertTrue((bool) $pivot->is_default);
    }

    public function test_only_one_default_per_tenant()
    {
        $cat1 = Category::create(['name' => 'Cat1', 'slug' => 'cat1']);
        $cat2 = Category::create(['name' => 'Cat2', 'slug' => 'cat2']);

        $cat1->tenants()->attach($this->tenant->id, ['is_custom' => true, 'is_default' => true]);
        $cat2->tenants()->attach($this->tenant->id, ['is_custom' => true, 'is_default' => false]);

        // Definir cat2 como default
        $this->actingAs($this->tenantUser)
            ->post(route('categories.set-default', $cat2->id));

        // cat1 não deve mais ser default
        $pivot1 = $cat1->fresh()->tenants()->where('tenant_id', $this->tenant->id)->first()->pivot;
        $this->assertFalse((bool) $pivot1->is_default);

        // cat2 deve ser default
        $pivot2 = $cat2->fresh()->tenants()->where('tenant_id', $this->tenant->id)->first()->pivot;
        $this->assertTrue((bool) $pivot2->is_default);
    }

    public function test_cannot_set_default_for_unavailable_category()
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);
        // Não associar ao tenant

        $response = $this->actingAs($this->tenantUser)
            ->post(route('categories.set-default', $category->id));

        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }
}
