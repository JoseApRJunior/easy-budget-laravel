<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Tenant;
use App\Services\Domain\CategoryManagementService;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CategoryManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app( CategoryManagementService::class);
    }

    public function test_set_default_category_switches_flags_correctly(): void
    {
        $tenant = Tenant::create( [ 'name' => 'TestTenant' ] );
        $catA   = Category::create( [ 'name' => 'A', 'slug' => 'a' ] );
        $catB   = Category::create( [ 'name' => 'B', 'slug' => 'b' ] );

        $catA->tenants()->attach( $tenant->id, [ 'is_default' => true, 'is_custom' => false ] );
        $catB->tenants()->attach( $tenant->id, [ 'is_default' => false, 'is_custom' => false ] );

        $result = $this->service->setDefaultCategory( $catB, $tenant->id );

        $this->assertTrue( $result->isSuccess() );
        $this->assertInstanceOf( ServiceResult::class, $result );

        $this->assertDatabaseHas( 'category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catB->id,
            'is_default'  => 1,
        ] );

        $this->assertDatabaseHas( 'category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catA->id,
            'is_default'  => 0,
        ] );
    }

    public function test_create_category_with_valid_data(): void
    {
        $tenant       = Tenant::create( [ 'name' => 'TestTenant' ] );
        $categoryData = [
            'name'      => 'Tecnologia',
            'slug'      => 'tecnologia',
            'is_active' => true
        ];

        $result = $this->service->createCategory( $categoryData, $tenant->id );

        $this->assertTrue( $result->isSuccess() );
        $this->assertInstanceOf( ServiceResult::class, $result );

        $this->assertDatabaseHas( 'categories', [
            'name'      => 'Tecnologia',
            'slug'      => 'tecnologia',
            'is_active' => true
        ] );

        $category = Category::where( 'slug', 'tecnologia' )->first();
        $this->assertNotNull( $category );
        $this->assertTrue( $category->tenants()->where( 'tenant_id', $tenant->id )->exists() );
    }

    public function test_create_category_with_invalid_parent_returns_error(): void
    {
        $tenant = Tenant::create( [ 'name' => 'TestTenant' ] );

        $categoryData = [
            'name'      => 'Categoria Filha',
            'slug'      => 'categoria-filha',
            'parent_id' => 999999, // Parent inexistente
            'is_active' => true
        ];

        $result = $this->service->createCategory( $categoryData, $tenant->id );

        $this->assertFalse( $result->isSuccess() );
        $this->assertInstanceOf( ServiceResult::class, $result );
        $this->assertNotEmpty( $result->getMessage() );
    }

    public function test_update_category_with_children_cannot_be_deactivated(): void
    {
        $parent = Category::create( [ 'name' => 'Categoria Pai', 'slug' => 'categoria-pai', 'is_active' => true ] );
        $child  = Category::create( [ 'name' => 'Categoria Filho', 'slug' => 'categoria-filho', 'parent_id' => $parent->id, 'is_active' => true ] );

        $updateData = [
            'name'      => 'Categoria Pai Atualizada',
            'is_active' => false // Tentar desativar categoria com filhos
        ];

        $result = $this->service->updateCategory( $parent, $updateData );

        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContainsString( 'subcategorias', strtolower( $result->getMessage() ) );
    }

    public function test_delete_category_with_services_returns_error(): void
    {
        $category = Category::create( [ 'name' => 'Categoria com Serviços', 'slug' => 'categoria-servicos' ] );

        // Simula que categoria tem serviços dependentes
        $this->mockCategoryWithServices( $category );

        $result = $this->service->deleteCategory( $category );

        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContains( 'serviços', strtolower( $result->getErrorMessage() ) );
    }

    public function test_delete_category_without_dependencies(): void
    {
        $category = Category::create( [ 'name' => 'Categoria Sem Serviços', 'slug' => 'categoria-sem-servicos' ] );

        $result = $this->service->deleteCategory( $category );

        $this->assertTrue( $result->isSuccess() );
        $this->assertSoftDeleted( 'categories', [ 'id' => $category->id ] );
    }

    public function test_attach_category_to_tenant_successfully(): void
    {
        $tenant   = Tenant::create( [ 'name' => 'TestTenant' ] );
        $category = Category::create( [ 'name' => 'Teste', 'slug' => 'teste' ] );

        $result = $this->service->attachToTenant( $category, $tenant->id, true, true );

        $this->assertTrue( $result->isSuccess() );
        $this->assertInstanceOf( ServiceResult::class, $result );

        // Verificar se foi attachado
        $this->assertTrue( $category->tenants()->where( 'tenant_id', $tenant->id )->exists() );
    }

    public function test_set_default_category_when_another_already_default(): void
    {
        $tenant = Tenant::create( [ 'name' => 'TestTenant' ] );
        $catA   = Category::create( [ 'name' => 'A', 'slug' => 'a' ] );
        $catB   = Category::create( [ 'name' => 'B', 'slug' => 'b' ] );

        $catA->tenants()->attach( $tenant->id, [ 'is_default' => true, 'is_custom' => false ] );

        $result = $this->service->setDefaultCategory( $catB, $tenant->id );

        $this->assertTrue( $result->isSuccess() );

        // Verificar que catB é default e catA não é mais
        $this->assertDatabaseHas( 'category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catB->id,
            'is_default'  => true,
        ] );

        $this->assertDatabaseHas( 'category_tenant', [
            'tenant_id'   => $tenant->id,
            'category_id' => $catA->id,
            'is_default'  => false,
        ] );
    }

    public function test_detach_category_from_tenant_when_not_in_use(): void
    {
        $tenant   = Tenant::create( [ 'name' => 'TestTenant' ] );
        $category = Category::create( [ 'name' => 'Teste', 'slug' => 'teste' ] );
        $category->tenants()->attach( $tenant->id, [ 'is_default' => false, 'is_custom' => false ] );

        $result = $this->service->detachFromTenant( $category, $tenant->id );

        $this->assertTrue( $result->isSuccess() );
        $this->assertFalse( $category->tenants()->where( 'tenant_id', $tenant->id )->exists() );
    }

    public function test_detach_category_from_tenant_when_in_use_returns_error(): void
    {
        $tenant   = Tenant::create( [ 'name' => 'TestTenant' ] );
        $category = Category::create( [ 'name' => 'Teste', 'slug' => 'teste' ] );
        $category->tenants()->attach( $tenant->id, [ 'is_default' => false, 'is_custom' => false ] );

        // Simula que categoria está em uso
        $this->mockCategoryInUse( $category, $tenant->id );

        $result = $this->service->detachFromTenant( $category, $tenant->id );

        $this->assertFalse( $result->isSuccess() );
        $this->assertStringContains( 'em uso', strtolower( $result->getErrorMessage() ) );
    }

    public function test_is_in_use_with_descendants(): void
    {
        $parent = Category::create( [ 'name' => 'Pai', 'slug' => 'pai' ] );
        $child  = Category::create( [ 'name' => 'Filho', 'slug' => 'filho', 'parent_id' => $parent->id ] );

        // Adiciona serviços apenas na categoria filho
        $this->mockCategoryInUse( $child, 1 );

        // Verifica se o pai também está "em uso" (através dos descendentes)
        $result = $this->service->isInUse( $parent );
        $this->assertTrue( $result );
    }

    public function test_can_delete_category_that_can_be_deleted(): void
    {
        $category = Category::create( [ 'name' => 'Categoria Limpa', 'slug' => 'categoria-limpa' ] );

        $result = $this->service->canDelete( $category );

        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Categoria pode ser deletada', $result->getMessage() );
    }

    public function test_get_descendant_ids_recursively(): void
    {
        $parent     = Category::create( [ 'name' => 'Pai', 'slug' => 'pai' ] );
        $child      = Category::create( [ 'name' => 'Filho', 'slug' => 'filho', 'parent_id' => $parent->id ] );
        $grandchild = Category::create( [ 'name' => 'Neto', 'slug' => 'neto', 'parent_id' => $child->id ] );

        $descendants = $this->service->getDescendantIds( $parent->id );

        $this->assertContains( $child->id, $descendants );
        $this->assertContains( $grandchild->id, $descendants );
        $this->assertCount( 2, $descendants );
    }

    /**
     * Mock category with services to test deletion constraints
     */
    private function mockCategoryWithServices( Category $category ): void
    {
        // Simula que categoria tem serviços dependentes
        $category->services_count = 5; // Simula que tem 5 serviços
    }

    /**
     * Mock category that is in use to test detach constraints
     */
    private function mockCategoryInUse( Category $category, int $tenantId ): void
    {
        // Simula que categoria está sendo usada
        $category->in_use = true;
    }

}
