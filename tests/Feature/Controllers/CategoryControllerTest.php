<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_index_shows_categories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('categories.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.category.index');
        $response->assertViewHas('categories');
    }

    public function test_create_shows_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('categories.create'));

        $response->assertStatus(200);
        $response->assertViewIs('pages.category.create');
    }

    public function test_store_creates_category(): void
    {
        $response = $this->actingAs($this->user)->post(route('categories.store'), [
            'name' => 'Nova Categoria',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'name' => 'Nova Categoria',
            'is_active' => 1,
        ]);
    }

    public function test_show_displays_category(): void
    {
        $category = Category::factory()->create(['name' => 'Categoria X', 'slug' => 'categoria-x']);

        $response = $this->actingAs($this->user)->get(route('categories.show', ['slug' => $category->slug]));

        $response->assertStatus(200);
        $response->assertViewIs('pages.category.show');
        $response->assertViewHas('category', function ($c) use ($category) {
            return $c->id === $category->id;
        });
    }

    public function test_edit_shows_edit_form(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user)->get(route('categories.edit', ['id' => $category->id]));

        $response->assertStatus(200);
        $response->assertViewIs('pages.category.edit');
        $response->assertViewHas('category', function ($c) use ($category) {
            return $c->id === $category->id;
        });
    }

    public function test_update_updates_category(): void
    {
        $category = Category::factory()->create(['name' => 'Antiga']);

        $response = $this->actingAs($this->user)->put(route('categories.update', ['id' => $category->id]), [
            'name' => 'Atualizada',
        ]);

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Atualizada',
        ]);
    }

    public function test_destroy_deletes_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('categories.destroy', ['id' => $category->id]));

        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }
}

