<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Tenant;
use App\Services\Domain\CategoryManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_set_default_category_switches_flags_correctly(): void
    {
        $tenant = Tenant::create(['name' => 'TestTenant']);
        $catA   = Category::create(['name' => 'A', 'slug' => 'a']);
        $catB   = Category::create(['name' => 'B', 'slug' => 'b']);

        $catA->tenants()->attach($tenant->id, ['is_default' => true, 'is_custom' => false]);
        $catB->tenants()->attach($tenant->id, ['is_default' => false, 'is_custom' => false]);

        $service = app(CategoryManagementService::class);
        $result  = $service->setDefaultCategory($catB, $tenant->id);

        $this->assertTrue($result->isSuccess());

        $this->assertDatabaseHas('category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catB->id,
            'is_default'  => 1,
        ]);

        $this->assertDatabaseHas('category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catA->id,
            'is_default'  => 0,
        ]);
    }
}

