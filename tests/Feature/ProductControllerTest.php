<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Tenant
        $this->tenant = Tenant::factory()->create();

        // Setup Tenant User
        $this->tenantUser = User::factory()->create( [
            'tenant_id'         => $this->tenant->id,
            'email_verified_at' => now(), // Mark as verified to avoid redirect
        ] );

        // Assign 'provider' role to tenantUser
        $providerRole = \App\Models\Role::firstOrCreate( [ 'name' => 'provider' ], [ 'description' => 'Provider' ] );
        $this->tenantUser->roles()->attach( $providerRole->id, [ 'tenant_id' => $this->tenant->id ] );

        // Create provider record for the user
        \App\Models\Provider::create( [
            'user_id'        => $this->tenantUser->id,
            'tenant_id'      => $this->tenant->id,
            'terms_accepted' => true,
        ] );
    }

    public function test_tenant_can_create_product()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'provider.products.store' ), [
                'name'        => 'Test Product',
                'category_id' => $category->id,
                'price'       => 100.00,
                'active'      => true,
            ] );

        $response->assertRedirect( route( 'provider.products.create' ) );
        $response->assertSessionHas( 'success' );
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas( 'products', [
            'name'      => 'Test Product',
            'tenant_id' => $this->tenant->id,
        ] );

        $product = Product::where( 'name', 'Test Product' )->first();
        $this->assertNotNull( $product );
        $this->assertEquals( $this->tenant->id, $product->tenant_id );
    }

    public function test_tenant_cannot_create_product_with_duplicate_sku()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        // Create first product
        $firstProduct = Product::create( [
            'name'        => 'First Product',
            'sku'         => 'TEST-001',
            'category_id' => $category->id,
            'price'       => 100.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => true,
        ] );

        // Try to create product with duplicate SKU
        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'provider.products.store' ), [
                'name'        => 'Second Product',
                'sku'         => 'TEST-001', // Duplicate SKU
                'category_id' => $category->id,
                'price'       => 200.00,
                'active'      => true,
            ] );

        $response->assertSessionHasErrors( [ 'sku' ] );
    }

    public function test_tenant_can_view_own_products()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        // Create products for this tenant
        $product1 = Product::create( [
            'name'        => 'Product A',
            'sku'         => 'PROD-A-001',
            'category_id' => $category->id,
            'price'       => 100.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => true,
        ] );

        $product2 = Product::create( [
            'name'        => 'Product B',
            'sku'         => 'PROD-B-001',
            'category_id' => $category->id,
            'price'       => 200.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => false,
        ] );

        // Test without filters
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index' ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.product.index' );

        // Test with active filter
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index', [ 'active' => '1' ] ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.product.index' );

        // Test with search filter
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index', [ 'search' => 'Product A' ] ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.product.index' );

        // Test with price range filter
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index', [
                'min_price' => '50,00',
                'max_price' => '150,00'
            ] ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.product.index' );

        // Test with deleted filter
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index', [ 'deleted' => 'only' ] ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.product.index' );
    }

    public function test_tenant_can_export_products_with_filters()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        // Create products
        Product::create( [
            'name'        => 'Product A',
            'sku'         => 'PROD-A-001',
            'category_id' => $category->id,
            'price'       => 100.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => true,
        ] );

        Product::create( [
            'name'        => 'Product B',
            'sku'         => 'PROD-B-001',
            'category_id' => $category->id,
            'price'       => 200.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => false,
        ] );

        // Test Excel export with filters
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.export', [
                'format' => 'xlsx',
                'active' => '1',
                'search' => 'Product A'
            ] ) );

        $response->assertStatus( 200 );

        // Test PDF export with filters
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.export', [
                'format'    => 'pdf',
                'active'    => '1',
                'min_price' => '50,00',
                'max_price' => '150,00'
            ] ) );

        $response->assertStatus( 200 );
    }

    public function test_different_tenants_can_have_same_sku()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        // Create product for first tenant
        Product::create( [
            'name'        => 'Product A',
            'sku'         => 'TEST-001',
            'category_id' => $category->id,
            'price'       => 100.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => true,
        ] );

        // Create second tenant and user
        $secondTenant = Tenant::factory()->create();
        $secondUser   = User::factory()->create( [
            'tenant_id'         => $secondTenant->id,
            'email_verified_at' => now(),
        ] );

        // Assign role
        $providerRole = \App\Models\Role::firstOrCreate( [ 'name' => 'provider' ], [ 'description' => 'Provider' ] );
        $secondUser->roles()->attach( $providerRole->id, [ 'tenant_id' => $secondTenant->id ] );

        // Create provider record for the second user
        \App\Models\Provider::create( [
            'user_id'        => $secondUser->id,
            'tenant_id'      => $secondTenant->id,
            'terms_accepted' => true,
        ] );

        $secondCategory = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $secondTenant->id,
            'is_active' => true,
        ] );

        // Second user can create product with same SKU
        $response = $this->actingAs( $secondUser )
            ->post( route( 'provider.products.store' ), [
                'name'        => 'Product A',
                'sku'         => 'TEST-001', // Same SKU
                'category_id' => $secondCategory->id,
                'price'       => 100.00,
                'active'      => true,
            ] );

        $response->assertRedirect( route( 'provider.products.create' ) );
        $response->assertSessionHas( 'success' );
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas( 'products', [
            'sku'       => 'TEST-001',
            'tenant_id' => $secondTenant->id,
        ] );
    }

    public function test_product_filters_persist_after_submission()
    {
        $category = Category::create( [
            'name'      => 'Test Category',
            'slug'      => 'test-category',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        Product::create( [
            'name'        => 'Test Product',
            'sku'         => 'TEST-001',
            'category_id' => $category->id,
            'price'       => 100.00,
            'tenant_id'   => $this->tenant->id,
            'active'      => true,
        ] );

        // Submit form with filters
        $response = $this->actingAs( $this->tenantUser )
            ->get( route( 'provider.products.index', [
                'search'      => 'Test',
                'active'      => '1',
                'category_id' => $category->id,
                'min_price'   => '50,00',
                'max_price'   => '150,00',
                'per_page'    => '10'
            ] ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'filters', function ( $filters ) use ( $category ) {
            return $filters[ 'search' ] === 'Test' &&
                $filters[ 'active' ] === '1' &&
                $filters[ 'category_id' ] == $category->id &&
                $filters[ 'min_price' ] === '50,00' &&
                $filters[ 'max_price' ] === '150,00' &&
                $filters[ 'per_page' ] === '10';
        } );
    }

}
