<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCircularReferenceTest extends TestCase
{
    use RefreshDatabase;

    private function createCategoryWithTenant(string $name, string $slug, ?int $parentId = null): Category
    {
        $tenant = Tenant::factory()->create();

        $data = [
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => $slug,
        ];

        if ($parentId) {
            $data['parent_id'] = $parentId;
        }

        return Category::create($data);
    }

    public function test_detects_self_reference()
    {
        $category = $this->createCategoryWithTenant('Test', 'test');

        $this->assertTrue($category->wouldCreateCircularReference($category->id));
    }

    public function test_detects_two_level_loop()
    {
        $categoryA = $this->createCategoryWithTenant('A', 'a');
        $categoryB = $this->createCategoryWithTenant('B', 'b', $categoryA->id);

        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryB->id));
    }

    public function test_detects_three_level_loop()
    {
        $categoryA = $this->createCategoryWithTenant('A', 'a');
        $categoryB = $this->createCategoryWithTenant('B', 'b', $categoryA->id);
        $categoryC = $this->createCategoryWithTenant('C', 'c', $categoryB->id);

        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryC->id));
    }

    public function test_allows_valid_hierarchy()
    {
        $categoryA = $this->createCategoryWithTenant('A', 'a');
        $categoryB = $this->createCategoryWithTenant('B', 'b', $categoryA->id);
        $categoryC = $this->createCategoryWithTenant('C', 'c');

        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryA->id));
        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryB->id));
    }

    public function test_handles_nonexistent_parent()
    {
        $category = $this->createCategoryWithTenant('Test', 'test');

        $this->assertFalse($category->wouldCreateCircularReference(99999));
    }

    public function test_handles_deleted_parent()
    {
        $categoryA = $this->createCategoryWithTenant('A', 'a');
        $categoryB = $this->createCategoryWithTenant('B', 'b', $categoryA->id);

        $categoryA->delete();

        $this->assertFalse($categoryB->wouldCreateCircularReference($categoryA->id));
    }
}
