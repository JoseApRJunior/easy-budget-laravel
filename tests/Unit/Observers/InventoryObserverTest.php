<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Observers\InventoryObserver;
use App\Models\Budget;
use App\Models\Service;
use App\Models\BudgetItem;
use App\Models\ServiceItem;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\User;
use App\Models\Tenant;
use App\Services\Domain\InventoryService;
use App\Services\Shared\CacheService;
use App\Services\AlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InventoryObserverTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryObserver $inventoryObserver;
    protected InventoryService $inventoryService;
    protected CacheService $cacheService;
    protected AlertService $alertService;
    protected User $user;
    protected Tenant $tenant;
    protected Product $product;
    protected ProductInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryService = $this->app->make(InventoryService::class);
        $this->cacheService = $this->app->make(CacheService::class);
        $this->alertService = $this->app->make(AlertService::class);
        
        $this->inventoryObserver = new InventoryObserver($this->cacheService, $this->alertService);
        
        // Criar tenant e usuário de teste
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // Criar produto e inventário de teste
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        $this->inventory = ProductInventory::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'quantity' => 100,
            'min_quantity' => 10
        ]);
    }

    /** @test */
    public function it_consumes_stock_when_budget_is_approved()
    {
        // Criar orçamento com itens
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending'
        ]);
        
        $budgetItem = BudgetItem::factory()->create([
            'budget_id' => $budget->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'price' => 50.00
        ]);
        
        // Simular aprovação do orçamento
        $budget->status = 'approved';
        $budget->save();
        
        // Executar o observer
        $this->inventoryObserver->updated($budget);
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(80, $inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'budget')
            ->where('reference_id', $budget->id)
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(20, $movement->quantity);
        $this->assertEquals('exit', $movement->type);
    }

    /** @test */
    public function it_returns_stock_when_budget_is_cancelled()
    {
        // Criar orçamento aprovado com itens
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'approved'
        ]);
        
        $budgetItem = BudgetItem::factory()->create([
            'budget_id' => $budget->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'price' => 50.00
        ]);
        
        // Consumir o estoque inicial
        $this->inventoryService->consumeProduct(
            $this->product->id,
            20,
            'Budget approval',
            'budget',
            $budget->id,
            $this->tenant->id
        );
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(80, $inventory->quantity);
        
        // Simular cancelamento do orçamento
        $budget->status = 'cancelled';
        $budget->save();
        
        // Executar o observer
        $this->inventoryObserver->updated($budget);
        
        // Verificar que o estoque foi devolvido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a devolução foi registrada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'budget')
            ->where('reference_id', $budget->id)
            ->where('type', 'return')
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(20, $movement->quantity);
    }

    /** @test */
    public function it_handles_service_completion()
    {
        // Criar serviço com itens
        $service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'in_progress'
        ]);
        
        $serviceItem = ServiceItem::factory()->create([
            'service_id' => $service->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'price' => 75.00
        ]);
        
        // Simular conclusão do serviço
        $service->status = 'completed';
        $service->save();
        
        // Executar o observer
        $this->inventoryObserver->updated($service);
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(85, $inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'service')
            ->where('reference_id', $service->id)
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(15, $movement->quantity);
        $this->assertEquals('exit', $movement->type);
    }

    /** @test */
    public function it_handles_service_cancellation()
    {
        // Criar serviço concluído com itens
        $service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed'
        ]);
        
        $serviceItem = ServiceItem::factory()->create([
            'service_id' => $service->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'price' => 75.00
        ]);
        
        // Consumir o estoque inicial
        $this->inventoryService->consumeProduct(
            $this->product->id,
            15,
            'Service completion',
            'service',
            $service->id,
            $this->tenant->id
        );
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(85, $inventory->quantity);
        
        // Simular cancelamento do serviço
        $service->status = 'cancelled';
        $service->save();
        
        // Executar o observer
        $this->inventoryObserver->updated($service);
        
        // Verificar que o estoque foi devolvido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a devolução foi registrada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'service')
            ->where('reference_id', $service->id)
            ->where('type', 'return')
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(15, $movement->quantity);
    }

    /** @test */
    public function it_handles_budget_item_removal()
    {
        // Criar orçamento aprovado com itens
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'approved'
        ]);
        
        $budgetItem = BudgetItem::factory()->create([
            'budget_id' => $budget->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'price' => 50.00
        ]);
        
        // Consumir o estoque inicial
        $this->inventoryService->consumeProduct(
            $this->product->id,
            20,
            'Budget approval',
            'budget',
            $budget->id,
            $this->tenant->id
        );
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(80, $inventory->quantity);
        
        // Simular remoção do item do orçamento
        $budgetItem->delete();
        
        // Executar o observer
        $this->inventoryObserver->deleted($budgetItem);
        
        // Verificar que o estoque foi devolvido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a devolução foi registrada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'budget_item')
            ->where('reference_id', $budgetItem->id)
            ->where('type', 'return')
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(20, $movement->quantity);
    }

    /** @test */
    public function it_handles_service_item_removal()
    {
        // Criar serviço concluído com itens
        $service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'completed'
        ]);
        
        $serviceItem = ServiceItem::factory()->create([
            'service_id' => $service->id,
            'product_id' => $this->product->id,
            'quantity' => 15,
            'price' => 75.00
        ]);
        
        // Consumir o estoque inicial
        $this->inventoryService->consumeProduct(
            $this->product->id,
            15,
            'Service completion',
            'service',
            $service->id,
            $this->tenant->id
        );
        
        // Verificar que o estoque foi consumido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(85, $inventory->quantity);
        
        // Simular remoção do item do serviço
        $serviceItem->delete();
        
        // Executar o observer
        $this->inventoryObserver->deleted($serviceItem);
        
        // Verificar que o estoque foi devolvido
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(100, $inventory->quantity);
        
        // Verificar que a devolução foi registrada
        $movement = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'service_item')
            ->where('reference_id', $serviceItem->id)
            ->where('type', 'return')
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(15, $movement->quantity);
    }

    /** @test */
    public function it_prevents_duplicate_operations()
    {
        // Criar orçamento com itens
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'status' => 'pending'
        ]);
        
        $budgetItem = BudgetItem::factory()->create([
            'budget_id' => $budget->id,
            'product_id' => $this->product->id,
            'quantity' => 20,
            'price' => 50.00
        ]);
        
        // Simular aprovação do orçamento
        $budget->status = 'approved';
        $budget->save();
        
        // Executar o observer pela primeira vez
        $this->inventoryObserver->updated($budget);
        
        // Tentar executar novamente (simulando duplicação)
        $this->inventoryObserver->updated($budget);
        
        // Verificar que apenas uma movimentação foi criada
        $movements = \App\Models\InventoryMovement::where('product_id', $this->product->id)
            ->where('reference_type', 'budget')
            ->where('reference_id', $budget->id)
            ->where('type', 'exit')
            ->get();
            
        $this->assertCount(1, $movements);
        $this->assertEquals(20, $movements->first()->quantity);
        
        // Verificar que o estoque foi consumido apenas uma vez
        $inventory = ProductInventory::where('product_id', $this->product->id)->first();
        $this->assertEquals(80, $inventory->quantity);
    }
}