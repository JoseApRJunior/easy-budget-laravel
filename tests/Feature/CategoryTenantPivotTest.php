<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTenantPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_default_category_per_tenant_is_enforced(): void
    {
        $tenant = Tenant::factory()->create();
        $catA = Category::factory()->create(['name' => 'A', 'slug' => 'a']);
        $catB = Category::factory()->create(['name' => 'B', 'slug' => 'b']);

        $catA->tenants()->attach($tenant->id, ['is_default' => true, 'is_custom' => false]);
        $catB->tenants()->attach($tenant->id, ['is_default' => true, 'is_custom' => false]);

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

