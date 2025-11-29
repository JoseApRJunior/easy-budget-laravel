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

    public function test_delete_category_without_dependencies(): void
    {
        $category = Category::create( [ 'name' => 'Categoria Sem Serviços', 'slug' => 'categoria-sem-servicos' ] );

        $result = $this->service->deleteCategory( $category );

        $this->assertTrue( $result->isSuccess() );
        $this->assertSoftDeleted( 'categories', [ 'id' => $category->id ] );
    }
}
