<?php

namespace Tests\Unit;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCircularReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_self_reference()
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);

        $this->assertTrue($category->wouldCreateCircularReference($category->id));
    }

    public function test_detects_two_level_loop()
    {
        $categoryA = Category::create(['name' => 'A', 'slug' => 'a']);
        $categoryB = Category::create(['name' => 'B', 'slug' => 'b', 'parent_id' => $categoryA->id]);

        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryB->id));
    }

    public function test_detects_three_level_loop()
    {
        $categoryA = Category::create(['name' => 'A', 'slug' => 'a']);
        $categoryB = Category::create(['name' => 'B', 'slug' => 'b', 'parent_id' => $categoryA->id]);
        $categoryC = Category::create(['name' => 'C', 'slug' => 'c', 'parent_id' => $categoryB->id]);

        $this->assertTrue($categoryA->wouldCreateCircularReference($categoryC->id));
    }

    public function test_allows_valid_hierarchy()
    {
        $categoryA = Category::create(['name' => 'A', 'slug' => 'a']);
        $categoryB = Category::create(['name' => 'B', 'slug' => 'b', 'parent_id' => $categoryA->id]);
        $categoryC = Category::create(['name' => 'C', 'slug' => 'c']);

        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryA->id));
        $this->assertFalse($categoryC->wouldCreateCircularReference($categoryB->id));
    }

    public function test_handles_nonexistent_parent()
    {
        $category = Category::create(['name' => 'Test', 'slug' => 'test']);

        $this->assertFalse($category->wouldCreateCircularReference(99999));
    }

    public function test_handles_deleted_parent()
    {
        $categoryA = Category::create(['name' => 'A', 'slug' => 'a']);
        $categoryB = Category::create(['name' => 'B', 'slug' => 'b', 'parent_id' => $categoryA->id]);

        $categoryA->delete();

        $this->assertFalse($categoryB->wouldCreateCircularReference($categoryA->id));
    }
}
