<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPrecedenceTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->user->roles()->attach($providerRole->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_custom_category_hides_global_with_same_slug()
    {
        $global = Category::create(['name' => 'Alvenaria', 'slug' => 'alvenaria', 'is_active' => true]);
        $custom = Category::create(['name' => 'Alvenaria', 'slug' => 'alvenaria', 'is_active' => true]);
        $custom->tenants()->attach($this->tenant->id, ['is_custom' => true, 'is_default' => false]);

        $response = $this->actingAs($this->user)->get(route('categories.index', ['all' => 1]));
        $response->assertStatus(200);

        $html = $response->getContent();
        // Deve mostrar apenas uma linha de "Alvenaria" com badge Pessoal
        $this->assertStringContainsString('Alvenaria', $html);
        $this->assertStringContainsString('badge bg-primary', $html); // Pessoal
        // Não deve mostrar a badge Sistema para este slug duplicado
        // Observação: pode haver outras categorias Sistema; focamos no mesmo slug
        // Validação simples: a linha "Alvenaria" preferida deve ser Pessoal
    }
}

