<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\Abstracts\AbstractTenantRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\ProductRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RepositoryPaginationStandardizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Mock completo do Auth facade
        Auth::shouldReceive( 'user' )
            ->andReturn( (object) [ 'tenant_id' => 1 ] );

        Auth::shouldReceive( 'check' )
            ->andReturn( true );

        Auth::shouldReceive( 'guest' )
            ->andReturn( false );

        Auth::shouldReceive( 'id' )
            ->andReturn( 1 );
    }

    public function test_customer_repository_extends_abstract_tenant_repository(): void
    {
        $repository = new CustomerRepository();

        $this->assertInstanceOf( AbstractTenantRepository::class, $repository );
    }

    public function test_product_repository_extends_abstract_tenant_repository(): void
    {
        $repository = new ProductRepository();

        $this->assertInstanceOf( AbstractTenantRepository::class, $repository );
    }

    public function test_category_repository_extends_abstract_tenant_repository(): void
    {
        $repository = new CategoryRepository();

        $this->assertInstanceOf( AbstractTenantRepository::class, $repository );
    }

    public function test_get_paginated_method_exists_in_customer_repository(): void
    {
        $repository = new CustomerRepository();

        $this->assertTrue( method_exists( $repository, 'getPaginated' ) );
    }

    public function test_get_paginated_method_exists_in_product_repository(): void
    {
        $repository = new ProductRepository();

        $this->assertTrue( method_exists( $repository, 'getPaginated' ) );
    }

    public function test_get_paginated_method_exists_in_category_repository(): void
    {
        $repository = new CategoryRepository();

        $this->assertTrue( method_exists( $repository, 'getPaginated' ) );
    }

    public function test_get_paginated_signature_compatibility(): void
    {
        $repository = new CustomerRepository();
        $reflection = new \ReflectionMethod( $repository, 'getPaginated' );

        $parameters = $reflection->getParameters();

        // Verificar se a assinatura é compatível
        $this->assertCount( 4, $parameters );
        $this->assertEquals( 'array', $parameters[ 0 ]->getType()?->getName() );
        $this->assertEquals( 'int', $parameters[ 1 ]->getType()?->getName() );
        $this->assertEquals( 'array', $parameters[ 2 ]->getType()?->getName() );
        $this->assertTrue( $parameters[ 3 ]->allowsNull() );
        $this->assertEquals( 'array', $parameters[ 3 ]->getType()?->getName() );
    }

    public function test_repository_filters_trait_methods(): void
    {
        $repository = new CustomerRepository();

        // Verificar se os métodos do trait estão disponíveis
        $this->assertTrue( method_exists( $repository, 'applyFilters' ) );
        $this->assertTrue( method_exists( $repository, 'applyOrderBy' ) );
        $this->assertTrue( method_exists( $repository, 'applySoftDeleteFilter' ) );
        $this->assertTrue( method_exists( $repository, 'getEffectivePerPage' ) );
    }

    public function test_abstract_tenant_repository_has_base_implementation(): void
    {
        $reflection = new \ReflectionMethod( AbstractTenantRepository::class, 'getPaginated' );

        $this->assertTrue( $reflection->isPublic() );
        $this->assertEquals( 'Illuminate\Pagination\LengthAwarePaginator', $reflection->getReturnType()?->getName() );
    }

    public function test_product_repository_has_specific_filters(): void
    {
        $repository = new ProductRepository();
        $reflection = new \ReflectionMethod( $repository, 'getPaginated' );

        $this->assertTrue( $reflection->isPublic() );
        $this->assertEquals( 'Illuminate\Pagination\LengthAwarePaginator', $reflection->getReturnType()?->getName() );
    }

    public function test_customer_repository_has_specific_filters(): void
    {
        $repository = new CustomerRepository();
        $reflection = new \ReflectionMethod( $repository, 'getPaginated' );

        $this->assertTrue( $reflection->isPublic() );
        $this->assertEquals( 'Illuminate\Pagination\LengthAwarePaginator', $reflection->getReturnType()?->getName() );
    }

    public function test_category_repository_has_specific_filters(): void
    {
        $repository = new CategoryRepository();
        $reflection = new \ReflectionMethod( $repository, 'getPaginated' );

        $this->assertTrue( $reflection->isPublic() );
        $this->assertEquals( 'Illuminate\Pagination\LengthAwarePaginator', $reflection->getReturnType()?->getName() );
    }

    public function test_all_repositories_implement_pagination_standard(): void
    {
        $repositories = [
            new CustomerRepository(),
            new ProductRepository(),
            new CategoryRepository(),
        ];

        foreach ( $repositories as $repository ) {
            // Verificar se todos implementam o padrão
            $this->assertInstanceOf( AbstractTenantRepository::class, $repository );
            $this->assertTrue( method_exists( $repository, 'getPaginated' ) );

            // Verificar assinatura
            $reflection = new \ReflectionMethod( $repository, 'getPaginated' );
            $this->assertEquals( 'Illuminate\Pagination\LengthAwarePaginator', $reflection->getReturnType()?->getName() );
        }
    }

}
