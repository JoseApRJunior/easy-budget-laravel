<?php

namespace Tests\Unit;

use App\Enums\ServiceStatusEnum;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use App\Services\Domain\ServiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceServiceCreateItemsTest extends TestCase
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

    /** @test */
    public function it_creates_service_with_valid_items()
    {
        // Arrange
        $tenant = Tenant::factory()->create();

        $budget = Budget::factory()->create( [
            'code'      => 'TEST-001',
            'total'     => 1000.00,
            'tenant_id' => $tenant->id
        ] );

        $product1 = Product::factory()->create( [
            'active'    => true,
            'name'      => 'Produto Teste 1',
            'price'     => 50.00,
            'tenant_id' => $tenant->id
        ] );

        $product2 = Product::factory()->create( [
            'active'    => true,
            'name'      => 'Produto Teste 2',
            'price'     => 75.00,
            'tenant_id' => $tenant->id
        ] );

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $this->category->id,
            'description' => 'Serviço de teste',
            'items'       => [
                [
                    'product_id' => $product1->id,
                    'quantity'   => 2,
                    'unit_value' => 50.00
                ],
                [
                    'product_id' => $product2->id,
                    'quantity'   => 1,
                    'unit_value' => 75.00
                ]
            ]
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $service = $result->getData();
        $this->assertInstanceOf( Service::class, $service );
        $this->assertEquals( 175.00, $service->total ); // (2 * 50) + (1 * 75) = 175

        // Verificar se os itens foram criados corretamente
        $this->assertCount( 2, $service->serviceItems );

        $item1 = $service->serviceItems->first();
        $this->assertEquals( $product1->id, $item1->product_id );
        $this->assertEquals( 2, $item1->quantity );
        $this->assertEquals( 50.00, $item1->unit_value );
        $this->assertEquals( 100.00, $item1->total ); // 2 * 50

        $item2 = $service->serviceItems->last();
        $this->assertEquals( $product2->id, $item2->product_id );
        $this->assertEquals( 1, $item2->quantity );
        $this->assertEquals( 75.00, $item2->unit_value );
        $this->assertEquals( 75.00, $item2->total ); // 1 * 75
    }

    /** @test */
    public function it_returns_error_for_inactive_product()
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

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertTrue( $result->isError() );
        $this->assertStringContainsString( "Produto ID {$inactiveProduct->id} não encontrado ou inativo", $result->getMessage() );
    }

    /** @test */
    public function it_returns_error_for_nonexistent_product()
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

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertTrue( $result->isError() );
        $this->assertStringContainsString( "Produto ID 99999 não encontrado ou inativo", $result->getMessage() );
    }

    /** @test */
    public function it_creates_service_without_items()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $budget = Budget::factory()->create( [
            'code'      => 'TEST-004',
            'total'     => 500.00,
            'tenant_id' => $tenant->id
        ] );

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $this->category->id,
            'description' => 'Serviço sem itens'
            // Sem itens
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $service = $result->getData();
        $this->assertInstanceOf( Service::class, $service );
        $this->assertEquals( 0.00, $service->total ); // Sem itens = total 0
        $this->assertCount( 0, $service->serviceItems );
    }

    /** @test */
    public function it_calculates_correct_totals_for_multiple_items()
    {
        // Arrange
        $tenant = Tenant::factory()->create();
        $budget = Budget::factory()->create( [
            'code'      => 'TEST-005',
            'tenant_id' => $tenant->id
        ] );

        $product1 = Product::factory()->create( [
            'active'    => true,
            'name'      => 'Produto Caro',
            'price'     => 200.00,
            'tenant_id' => $tenant->id
        ] );

        $product2 = Product::factory()->create( [
            'active'    => true,
            'name'      => 'Produto Barato',
            'price'     => 10.00,
            'tenant_id' => $tenant->id
        ] );

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $this->category->id,
            'items'       => [
                [
                    'product_id' => $product1->id,
                    'quantity'   => 3,
                    'unit_value' => 200.00
                ],
                [
                    'product_id' => $product2->id,
                    'quantity'   => 5,
                    'unit_value' => 10.00
                ]
            ]
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Assert
        $this->assertTrue( $result->isSuccess() );

        $service       = $result->getData();
        $expectedTotal = ( 3 * 200.00 ) + ( 5 * 10.00 ); // 600 + 50 = 650
        $this->assertEquals( $expectedTotal, $service->total );
    }

}
