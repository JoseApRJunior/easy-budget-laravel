<?php

declare(strict_types=1);

namespace Tests\Unit\Validation;

use App\Http\Controllers\CategoryController;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\User;
use App\Repositories\CategoryRepository;
use App\Services\Core\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CategorySlugTenantValidationTest extends TestCase
{
    use RefreshDatabase;

    private CategoryRepository $categoryRepository;
    private PermissionService  $permissionService;
    private CategoryController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock CategoryRepository
        $this->categoryRepository = Mockery::mock( CategoryRepository::class);
        $this->app->instance( CategoryRepository::class, $this->categoryRepository );

        // Mock PermissionService
        $this->permissionService = Mockery::mock( PermissionService::class);
        $this->app->instance( PermissionService::class, $this->permissionService );

        // Create controller with mocked dependencies
        $this->controller = new CategoryController(
            $this->categoryRepository,
            app( \App\Services\Domain\CategoryManagementService::class),
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function admin_can_edit_category_same_slug_as_other_tenant(): void
    {
        // Arrange
        $tenantId1 = 1;
        $tenantId2 = 2;
        $adminUser = $this->createAdminUser( $tenantId1 );
        $category  = $this->createCategoryWithTenant( $tenantId1, 'alvenaria' );

        // Another tenant has category with same slug 'alvenaria'
        $otherTenantCategory = $this->createCategoryWithTenant( $tenantId2, 'alvenaria' );

        // Mock PermissionService for admin
        $this->permissionService->shouldReceive( 'canManageGlobalCategories' )
            ->once()
            ->with( $adminUser )
            ->andReturn( true );

        // Mock CategoryRepository to return true for slug exists (different tenant)
        $this->categoryRepository->shouldReceive( 'existsBySlug' )
            ->once()
            ->with( 'alvenaria', null, $category->id )
            ->andReturn( false ); // Should be false because different tenant shouldn't conflict for admin

        // Act
        $request = new UpdateCategoryRequest();
        $request->setUserResolver( function () use ($adminUser) {
            return $adminUser;
        } );
        $request->merge( [ 'name' => 'Alvenaria' ] );

        // Simulate route parameter
        $this->app[ 'router' ]->get( '/categories/{category}', [ $this->controller, 'edit' ] )
            ->where( 'category', '[0-9]+' );

        $this->instance( 'request', $request );

        $validator = $request->getValidatorInstance();

        // Act & Assert: Validation should pass
        $validator->after( function ( $validator ) {
            // If validation fails, add error to test
            if ( $validator->fails() ) {
                $this->fail( 'Validation should pass for admin editing category with same slug as other tenant' );
            }
        } );

        $validator->validate();

        // Assert
        $this->assertTrue( true, 'Admin can edit category with same slug as other tenant' );
    }

    /** @test */
    public function provider_cannot_create_category_same_slug_in_same_tenant(): void
    {
        // Arrange
        $tenantId         = 1;
        $providerUser     = $this->createProviderUser( $tenantId );
        $existingCategory = $this->createCategoryWithTenant( $tenantId, 'alvenaria' );

        // Mock PermissionService for provider
        $this->permissionService->shouldReceive( 'canManageGlobalCategories' )
            ->once()
            ->with( $providerUser )
            ->andReturn( false );

        // Mock CategoryRepository to return true for slug exists in same tenant
        $this->categoryRepository->shouldReceive( 'existsBySlug' )
            ->once()
            ->with( 'alvenaria', $tenantId, null )
            ->andReturn( true );

        // Act
        $request = new StoreCategoryRequest();
        $request->setUserResolver( function () use ($providerUser) {
            return $providerUser;
        } );
        $request->merge( [ 'name' => 'Alvenaria' ] );

        $validator = $request->getValidatorInstance();

        // Act & Assert: Validation should fail
        $validator->after( function ( $validator ) {
            // If validation passes, it should fail
            if ( !$validator->fails() ) {
                $this->fail( 'Validation should fail for provider creating category with same slug in same tenant' );
            }
        } );

        $validator->validate();

        // Assert
        $this->assertTrue( $validator->fails(), 'Provider cannot create category with same slug in same tenant' );
        $this->assertTrue( $validator->errors()->has( 'name' ) );
        $this->assertStringContainsString( 'Este nome já está em uso', $validator->errors()->first( 'name' ) );
    }

    /** @test */
    public function provider_can_create_category_same_slug_as_other_tenant(): void
    {
        // Arrange
        $tenantId1    = 1;
        $tenantId2    = 2;
        $providerUser = $this->createProviderUser( $tenantId1 );

        // Other tenant has category with same slug 'alvenaria'
        $otherTenantCategory = $this->createCategoryWithTenant( $tenantId2, 'alvenaria' );

        // Mock PermissionService for provider
        $this->permissionService->shouldReceive( 'canManageGlobalCategories' )
            ->once()
            ->with( $providerUser )
            ->andReturn( false );

        // Mock CategoryRepository to return false for slug exists in different tenant
        $this->categoryRepository->shouldReceive( 'existsBySlug' )
            ->once()
            ->with( 'alvenaria', $tenantId1, null )
            ->andReturn( false );

        // Act
        $request = new StoreCategoryRequest();
        $request->setUserResolver( function () use ($providerUser) {
            return $providerUser;
        } );
        $request->merge( [ 'name' => 'Alvenaria' ] );

        $validator = $request->getValidatorInstance();

        // Act & Assert: Validation should pass
        $validator->after( function ( $validator ) {
            // If validation fails, add error to test
            if ( $validator->fails() ) {
                $this->fail( 'Validation should pass for provider creating category with same slug as other tenant' );
            }
        } );

        $validator->validate();

        // Assert
        $this->assertTrue( true, 'Provider can create category with same slug as other tenant' );
    }

    /** @test */
    public function category_repository_exists_by_slug_works_correctly(): void
    {
        // Arrange
        $tenantId1 = 1;
        $tenantId2 = 2;

        // Create categories in different tenants
        $category1 = $this->createCategoryWithTenant( $tenantId1, 'alvenaria' );
        $category2 = $this->createCategoryWithTenant( $tenantId2, 'alvenaria' );

        $repository = new CategoryRepository( new Category() );

        // Test: Same tenant should find conflict
        $existsSameTenant = $repository->existsBySlug( 'alvenaria', $tenantId1, null );
        $this->assertTrue( $existsSameTenant, 'Should find conflict in same tenant' );

        // Test: Different tenant should not find conflict (for provider)
        $existsDifferentTenant = $repository->existsBySlug( 'alvenaria', $tenantId1, $category1->id );
        $this->assertFalse( $existsDifferentTenant, 'Should not find conflict in different tenant when excluding own ID' );

        // Test: Admin (null tenant) should find global conflict
        $existsForAdmin = $repository->existsBySlug( 'alvenaria', null, null );
        $this->assertTrue( $existsForAdmin, 'Admin should find global conflict' );
    }

    private function createAdminUser( int $tenantId ): User
    {
        return User::factory()->create( [
            'tenant_id' => $tenantId,
            'role'      => 'admin'
        ] );
    }

    private function createProviderUser( int $tenantId ): User
    {
        return User::factory()->create( [
            'tenant_id' => $tenantId,
            'role'      => 'provider'
        ] );
    }

    private function createCategoryWithTenant( int $tenantId, string $slug ): Category
    {
        $category = Category::factory()->create( [
            'name'      => ucfirst( $slug ),
            'slug'      => $slug,
            'is_active' => true
        ] );

        // Attach to tenant (simulate category_tenant relationship)
        $category->tenants()->attach( $tenantId, [ 'is_custom' => true ] );

        return $category;
    }

}
