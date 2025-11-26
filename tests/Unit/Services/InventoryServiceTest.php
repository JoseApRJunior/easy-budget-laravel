<?php

namespace Tests\Unit\Services;

use App\Models\ProductInventory;
use App\Repositories\InventoryRepository;
use App\Services\Domain\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private $inventoryRepository;
    private $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inventoryRepository = Mockery::mock(InventoryRepository::class);
        $this->inventoryService = new InventoryService($this->inventoryRepository);
    }

    public function test_add_stock_success()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $productId = 1;
        $quantity = 10;
        $currentStock = 5;

        $inventoryMock = Mockery::mock(ProductInventory::class);
        $inventoryMock->shouldReceive('getAttribute')->with('quantity')->andReturn($currentStock);
        $inventoryMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $inventoryMock->shouldReceive('getAttribute')->with('tenant_id')->andReturn($user->tenant_id);
        $inventoryMock->shouldReceive('fresh')->andReturn($inventoryMock);

        $this->inventoryRepository->shouldReceive('findByProductId')
            ->with($productId)
            ->andReturn($inventoryMock);

        $this->inventoryRepository->shouldReceive('update')
            ->once()
            ->with(1, ['quantity' => $currentStock + $quantity]);

        $result = $this->inventoryService->addStock($productId, $quantity);

        $this->assertTrue($result->isSuccess(), 'Add stock failed: ' . $result->getMessage());
        $this->assertEquals('Estoque atualizado com sucesso', $result->getMessage());
    }

    public function test_remove_stock_success()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $productId = 1;
        $quantity = 5;
        $currentStock = 10;

        $inventoryMock = Mockery::mock(ProductInventory::class);
        $inventoryMock->shouldReceive('getAttribute')->with('quantity')->andReturn($currentStock);
        $inventoryMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $inventoryMock->shouldReceive('getAttribute')->with('tenant_id')->andReturn($user->tenant_id);
        $inventoryMock->shouldReceive('fresh')->andReturn($inventoryMock);

        $this->inventoryRepository->shouldReceive('findByProductId')
            ->with($productId)
            ->andReturn($inventoryMock);

        $this->inventoryRepository->shouldReceive('update')
            ->once()
            ->with(1, ['quantity' => $currentStock - $quantity]);

        $result = $this->inventoryService->removeStock($productId, $quantity);

        $this->assertTrue($result->isSuccess(), 'Remove stock failed: ' . $result->getMessage());
    }

    public function test_remove_stock_insufficient()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        $productId = 1;
        $quantity = 15;
        $currentStock = 10;

        $inventoryMock = Mockery::mock(ProductInventory::class);
        $inventoryMock->shouldReceive('getAttribute')->with('quantity')->andReturn($currentStock);
        $inventoryMock->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $inventoryMock->shouldReceive('getAttribute')->with('tenant_id')->andReturn($user->tenant_id);

        $this->inventoryRepository->shouldReceive('findByProductId')
            ->with($productId)
            ->andReturn($inventoryMock);

        $result = $this->inventoryService->removeStock($productId, $quantity);

        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Estoque insuficiente para esta operação', $result->getMessage());
    }
}
