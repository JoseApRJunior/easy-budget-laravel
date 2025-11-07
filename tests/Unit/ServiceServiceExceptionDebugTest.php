<?php

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use App\Services\Domain\ServiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceServiceExceptionDebugTest extends TestCase
{
    use RefreshDatabase;

    private ServiceService $serviceService;
    private Category       $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceService = app( ServiceService::class);
        $this->category       = Category::create( [
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
        ] );
    }

    public function test_debug_inactive_product()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $budget = Budget::factory()->create( [
            'code'      => 'TEST-002',
            'tenant_id' => $tenant->id
        ] );

        $inactiveProduct = Product::factory()->create( [
            'active'    => false,
            'name'      => 'Produto Inativo',
            'tenant_id' => $tenant->id
        ] );

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $this->category->id,
            'items'       => [
                [
                    'product_id' => $inactiveProduct->id,
                    'quantity'   => 1,
                    'unit_value' => 50.00
                ]
            ]
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Debug
        dump( 'Inactive Product Test:', [
            'isSuccess'     => $result->isSuccess(),
            'isError'       => $result->isError(),
            'getStatus'     => $result->getStatus(),
            'getMessage'    => $result->getMessage(),
            'productActive' => $inactiveProduct->active,
            'productId'     => $inactiveProduct->id
        ] );
    }

    public function test_debug_nonexistent_product()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $budget = Budget::factory()->create( [
            'code'      => 'TEST-003',
            'tenant_id' => $tenant->id
        ] );

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $this->category->id,
            'items'       => [
                [
                    'product_id' => 99999, // Produto inexistente
                    'quantity'   => 1,
                    'unit_value' => 50.00
                ]
            ]
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Debug
        dump( 'Nonexistent Product Test:', [
            'isSuccess'     => $result->isSuccess(),
            'isError'       => $result->isError(),
            'getStatus'     => $result->getStatus(),
            'getMessage'    => $result->getMessage(),
            'productExists' => Product::where( 'id', 99999 )->exists()
        ] );
    }

}
