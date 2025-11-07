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

class ServiceServiceDebugTest extends TestCase
{
    use RefreshDatabase;

    private ServiceService $serviceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceService = app( ServiceService::class);
    }

    public function test_debug_create_service()
    {
        // Arrange
        $tenant   = Tenant::factory()->create();
        $category = Category::create( [
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
        ] );

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

        $data = [
            'budget_code' => $budget->code,
            'category_id' => $category->id, // Adicionar categoria obrigatória
            'description' => 'Serviço de teste',
            'items'       => [
                [
                    'product_id' => $product1->id,
                    'quantity'   => 2,
                    'unit_value' => 50.00
                ]
            ]
        ];

        // Act
        $result = $this->serviceService->createService( $data );

        // Debug
        dump( 'Result:', [
            'isSuccess'  => $result->isSuccess(),
            'isError'    => $result->isError(),
            'getStatus'  => $result->getStatus(),
            'getMessage' => $result->getMessage(),
            'getData'    => $result->getData()
        ] );

        // Verificar se orçamento existe
        dump( 'Budget exists:', Budget::where( 'code', $budget->code )->exists() );

        // Verificar se produto existe
        dump( 'Product exists:', Product::where( 'id', $product1->id )->exists() );
        dump( 'Product active:', Product::find( $product1->id )->active ?? 'null' );
    }

}
