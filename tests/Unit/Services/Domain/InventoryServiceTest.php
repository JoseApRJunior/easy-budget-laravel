<?php

namespace Tests\Unit\Services\Domain;

use Tests\TestCase;
use App\Services\Domain\InventoryService;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\InventoryMovement;
use App\Models\User;
use App\Models\Tenant;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $inventoryService;
    protected User $user;
    protected Tenant $tenant;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = new InventoryService();
        
        // Criar tenant e usuário de teste
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // Criar produto de teste
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 100.00
        ]);
        
        // Criar inventário inicial
        ProductInventory::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'quantity' => 100,
            'min_quantity' => 10,
            'max_quantity' => 200
        ]);
    }

    /** @test */
    public function it_can_consume_product_successfully()
    {
        $result = $this->inventoryService->consumeProduct(
            $this->product->id,
            20,
            'Test consumption',
            'budget',
            1,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque foi atualizado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(80, $inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'exit')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals('Test consumption', $movement->reason);
        $this->assertEquals(100, $movement->previous_quantity);
        $this->assertEquals(80, $movement->current_quantity);
    }

    /** @test */
    public function it_prevents_negative_stock_when_configured()
    {
        config(['inventory.allow_negative_stock' => false]);
        
        $result = $this->inventoryService->consumeProduct(
            $this->product->id,
            150, // Mais do que o estoque disponível
            'Test negative stock',
            'budget',
            1,
            $this->tenant->id
        );

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('Estoque insuficiente', $result->getMessage());
        
        // Verificar que o estoque não foi alterado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
    }

    /** @test */
    public function it_can_add_product_successfully()
    {
        $result = $this->inventoryService->addProduct(
            $this->product->id,
            50,
            'Test addition',
            'purchase',
            1,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque foi atualizado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(150, $inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'entry')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(50, $movement->quantity);
        $this->assertEquals('Test addition', $movement->reason);
    }

    /** @test */
    public function it_can_adjust_stock_successfully()
    {
        $result = $this->inventoryService->adjustStock(
            $this->product->id,
            75,
            'Test adjustment',
            $this->user->id,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque foi ajustado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(75, $inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'adjustment')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(-25, $movement->quantity); // 75 - 100 = -25
        $this->assertEquals('Test adjustment', $movement->reason);
        $this->assertEquals(100, $movement->previous_quantity);
        $this->assertEquals(75, $movement->current_quantity);
    }

    /** @test */
    public function it_can_reserve_product_successfully()
    {
        $result = $this->inventoryService->reserveProduct(
            $this->product->id,
            30,
            'Test reservation',
            'budget',
            1,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque disponível foi reduzido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(70, $inventory->available_quantity); // 100 - 30
        $this->assertEquals(100, $inventory->quantity); // Estoque total permanece o mesmo
        
        // Verificar que a reserva foi criada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'reservation')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(30, $movement->quantity);
        $this->assertEquals('Test reservation', $movement->reason);
    }

    /** @test */
    public function it_can_release_reservation_successfully()
    {
        // Primeiro criar uma reserva
        $this->inventoryService->reserveProduct(
            $this->product->id,
            30,
            'Test reservation',
            'budget',
            1,
            $this->tenant->id
        );
        
        // Agora liberar a reserva
        $result = $this->inventoryService->releaseReservation(
            $this->product->id,
            30,
            'Test release reservation',
            'budget',
            1,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque disponível foi restaurado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->available_quantity);
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a liberação foi registrada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'release')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(-30, $movement->quantity);
        $this->assertEquals('Test release reservation', $movement->reason);
    }

    /** @test */
    public function it_can_return_product_successfully()
    {
        // Primeiro consumir um produto
        $this->inventoryService->consumeProduct(
            $this->product->id,
            20,
            'Test consumption',
            'budget',
            1,
            $this->tenant->id
        );
        
        // Agora devolver o produto
        $result = $this->inventoryService->returnProduct(
            $this->product->id,
            20,
            'Test return',
            'budget',
            1,
            $this->tenant->id
        );

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        // Verificar que o estoque foi restaurado
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a devolução foi registrada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'return')
            ->first();
        
        $this->assertNotNull($movement);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals('Test return', $movement->reason);
    }

    /** @test */
    public function it_can_get_stock_turnover_report()
    {
        // Criar movimentações de teste
        $this->inventoryService->consumeProduct(
            $this->product->id,
            20,
            'Test consumption',
            'budget',
            1,
            $this->tenant->id
        );
        
        $this->inventoryService->addProduct(
            $this->product->id,
            30,
            'Test addition',
            'purchase',
            2,
            $this->tenant->id
        );
        
        $filters = [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];
        
        $report = $this->inventoryService->getStockTurnoverReport($this->tenant->id, $filters);
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('products', $report);
        $this->assertArrayHasKey('total_entries', $report);
        $this->assertArrayHasKey('total_exits', $report);
        $this->assertArrayHasKey('average_turnover', $report);
        
        $this->assertNotEmpty($report['products']);
        
        $productReport = collect($report['products'])->firstWhere('id', $this->product->id);
        $this->assertNotNull($productReport);
        $this->assertEquals(20, $productReport['exits']);
        $this->assertEquals(30, $productReport['entries']);
    }

    /** @test */
    public function it_can_get_most_used_products_report()
    {
        // Criar movimentações de teste
        $this->inventoryService->consumeProduct(
            $this->product->id,
            25,
            'Test consumption',
            'budget',
            1,
            $this->tenant->id
        );
        
        $filters = [
            'start_date' => now()->subDays(30)->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d')
        ];
        
        $products = $this->inventoryService->getMostUsedProducts($this->tenant->id, 10, $filters);
        
        $this->assertIsArray($products);
        $this->assertNotEmpty($products);
        
        $productUsage = collect($products)->firstWhere('id', $this->product->id);
        $this->assertNotNull($productUsage);
        $this->assertEquals(25, $productUsage['total_usage']);
        $this->assertEquals($this->product->price * 25, $productUsage['total_value']);
    }

    /** @test */
    public function it_prevents_duplicate_movements()
    {
        $referenceType = 'budget';
        $referenceId = 1;
        
        // Criar primeira movimentação
        $result1 = $this->inventoryService->consumeProduct(
            $this->product->id,
            10,
            'First consumption',
            $referenceType,
            $referenceId,
            $this->tenant->id
        );
        
        $this->assertTrue($result1->isSuccess());
        
        // Tentar criar movimentação duplicada
        $result2 = $this->inventoryService->consumeProduct(
            $this->product->id,
            10,
            'Duplicate consumption',
            $referenceType,
            $referenceId,
            $this->tenant->id
        );
        
        $this->assertFalse($result2->isSuccess());
        $this->assertStringContainsString('Movimentação duplicada', $result2->getMessage());
    }

    /** @test */
    public function it_handles_concurrent_operations_correctly()
    {
        DB::transaction(function () {
            // Simular duas operações concorrentes
            $inventory1 = ProductInventory::where('product_id', $this->product->id)->lockForUpdate()->first();
            $inventory2 = ProductInventory::where('product_id', $this->product->id)->lockForUpdate()->first();
            
            // Ambas devem ver o mesmo estoque inicial
            $this->assertEquals(100, $inventory1->quantity);
            $this->assertEquals(100, $inventory2->quantity);
        });
    }
}