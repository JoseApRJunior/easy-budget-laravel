<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTypingFixTest extends TestCase
{
    use RefreshDatabase;

    public function test_wouldCreateCircularReference_accepts_string_and_converts_to_int(): void
    {
        // Criar tenant para atender requisito multi-tenant
        $tenant = Tenant::factory()->create();

        // Criar categorias hierárquicas
        $categoryA = Category::factory()->create( [
            'name'      => 'Category A',
            'slug'      => 'category-a',
            'tenant_id' => $tenant->id
        ] );

        $categoryB = Category::factory()->create( [
            'name'      => 'Category B',
            'slug'      => 'category-b',
            'parent_id' => $categoryA->id,
            'tenant_id' => $tenant->id
        ] );

        $categoryC = Category::factory()->create( [
            'name'      => 'Category C',
            'slug'      => 'category-c',
            'parent_id' => $categoryB->id,
            'tenant_id' => $tenant->id
        ] );

        // Teste 1: String deve ser convertida para int sem erro
        // Categoria A tentar ser parent de si mesma = loop direto = true
        $this->assertTrue( $categoryA->wouldCreateCircularReference( (string) $categoryA->id ) === true );

        // Teste 2: String com valor válido (sem loop)
        // Categoria C ter A como parent = hierarquia válida = false
        $this->assertTrue( $categoryC->wouldCreateCircularReference( (string) $categoryA->id ) === false );

        // Teste 3: String que criaria loop deve retornar true
        $this->assertTrue( $categoryA->wouldCreateCircularReference( (string) $categoryC->id ) === true );

        // Teste 4: Verificar que tanto int quanto string funcionam
        $this->assertEquals(
            $categoryA->wouldCreateCircularReference( $categoryB->id ),
            $categoryA->wouldCreateCircularReference( (string) $categoryB->id ),
        );
    }

    public function test_category_service_accepts_string_parent_id(): void
    {
        // Criar tenant
        $tenant = Tenant::factory()->create();

        // Criar categorias
        $categoryA = Category::factory()->create( [
            'name'      => 'Category A',
            'slug'      => 'category-a',
            'tenant_id' => $tenant->id
        ] );

        $categoryB = Category::factory()->create( [
            'name'      => 'Category B',
            'slug'      => 'category-b',
            'tenant_id' => $tenant->id
        ] );

        // Simular dados como viriam do formulário (string)
        $data = [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'parent_id' => (string) $categoryA->id, // String, não int
            'tenant_id' => $tenant->id
        ];

        // Criar categoria com parent_id como string
        $category = Category::create( $data );

        $this->assertEquals( $categoryA->id, $category->parent_id );
        $this->assertEquals( $categoryA->id, (int) $category->parent_id );
    }

}
