<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySetDefaultActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_default_category_toggles_pivot_correctly(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $catA = Category::factory()->create(['name' => 'A', 'slug' => 'a']);
        $catB = Category::factory()->create(['name' => 'B', 'slug' => 'b']);

        $catA->tenants()->attach($tenant->id, ['is_default' => false, 'is_custom' => true]);
        $catB->tenants()->attach($tenant->id, ['is_default' => false, 'is_custom' => true]);

        $this->actingAs($user)->post(route('categories.set-default', ['id' => $catA->id]))->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('category_tenant', [
            'tenant_id' => $tenant->id,
            'category_id' => $catA->id,
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('category_tenant', [
            'tenant_id' => $tenant->id,
            'category_id' => $catB->id,
            'is_default' => 0,
        ]);

        $this->actingAs($user)->post(route('categories.set-default', ['id' => $catB->id]))->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('category_tenant', [
            'tenant_id' => $tenant->id,
            'category_id' => $catB->id,
            'is_default' => 1,
        ]);
        $this->assertDatabaseHas('category_tenant', [
            'tenant_id' => $tenant->id,
            'category_id' => $catA->id,
            'is_default' => 0,
        ]);
    }
}

