<?php

namespace Tests\Feature\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\InventoryMovement;
use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected Tenant $tenant;
    protected Product $product;
    protected ProductInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Criar tenant e usuário de teste
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);
        
        // Criar produto e inventário de teste
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Teste',
            'sku' => 'TEST001',
            'price' => 100.00
        ]);
        
        $this->inventory = ProductInventory::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'quantity' => 100,
            'min_quantity' => 10,
            'max_quantity' => 200
        ]);
    }

    /** @test */
    public function it_shows_inventory_dashboard()
    {
        $response = $this->actingAs($this->user)->get('/inventory/dashboard');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.dashboard');
        $response->assertViewHas('totalProducts');
        $response->assertViewHas('productsWithInventory');
        $response->assertViewHas('lowStockProducts');
        $response->assertViewHas('highStockProducts');
        $response->assertViewHas('lowStockItems');
        $response->assertViewHas('recentMovements');
        $response->assertViewHas('totalInventoryValue');
        
        $response->assertSee('Dashboard de Inventário');
        $response->assertSee('Produtos com Inventário');
        $response->assertSee('Produtos com Estoque Baixo');
        $response->assertSee('Valor Total do Estoque');
    }

    /** @test */
    public function it_shows_inventory_list()
    {
        $response = $this->actingAs($this->user)->get('/inventory');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.index');
        $response->assertViewHas('products');
        
        $response->assertSee('Inventário');
        $response->assertSee($this->product->name);
        $response->assertSee($this->product->sku);
    }

    /** @test */
    public function it_shows_product_details()
    {
        $response = $this->actingAs($this->user)->get("/inventory/{$this->product->id}");
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.show');
        $response->assertViewHas('product');
        $response->assertViewHas('inventory');
        $response->assertViewHas('movements');
        
        $response->assertSee($this->product->name);
        $response->assertSee($this->product->sku);
        $response->assertSee('Estoque Atual');
        $response->assertSee('Movimentações');
    }

    /** @test */
    public function it_shows_stock_adjustment_form()
    {
        $response = $this->actingAs($this->user)->get("/inventory/{$this->product->id}/adjust");
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.adjust');
        $response->assertViewHas('product');
        $response->assertViewHas('inventory');
        
        $response->assertSee('Ajustar Estoque');
        $response->assertSee($this->product->name);
        $response->assertSee('Nova Quantidade');
        $response->assertSee('Motivo do Ajuste');
    }

    /** @test */
    public function it_processes_stock_adjustment_successfully()
    {
        $response = $this->actingAs($this->user)->post("/inventory/{$this->product->id}/adjust", [
            'new_quantity' => 150,
            'reason' => 'Ajuste de inventário mensal'
        ]);
        
        $response->assertRedirect("/inventory/{$this->product->id}");
        $response->assertSessionHas('success', 'Estoque ajustado com sucesso!');
        
        // Verificar que o estoque foi atualizado
        $this->inventory->refresh();
        $this->assertEquals(150, $this->inventory->quantity);
        
        // Verificar que a movimentação foi criada
        $movement = InventoryMovement::where('product_id', $this->product->id)
            ->where('type', 'adjustment')
            ->first();
            
        $this->assertNotNull($movement);
        $this->assertEquals(50, $movement->quantity); // 150 - 100
        $this->assertEquals('Ajuste de inventário mensal', $movement->reason);
    }

    /** @test */
    public function it_validates_stock_adjustment_form()
    {
        $response = $this->actingAs($this->user)->post("/inventory/{$this->product->id}/adjust", [
            'new_quantity' => -10, // Quantidade inválida
            'reason' => 'abc' // Motivo muito curto
        ]);
        
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors(['new_quantity', 'reason']);
    }

    /** @test */
    public function it_shows_inventory_movements()
    {
        // Criar movimentações de teste
        InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'entry',
            'quantity' => 50,
            'reason' => 'Compra de estoque'
        ]);
        
        $response = $this->actingAs($this->user)->get('/inventory/movements');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.movements');
        $response->assertViewHas('movements');
        
        $response->assertSee('Movimentações de Estoque');
        $response->assertSee('Compra de estoque');
        $response->assertSee('Entrada');
    }

    /** @test */
    public function it_filters_inventory_movements_by_product()
    {
        // Criar outro produto e movimentação
        $otherProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Outro Produto'
        ]);
        
        InventoryMovement::factory()->create([
            'product_id' => $otherProduct->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'entry',
            'quantity' => 25,
            'reason' => 'Outra movimentação'
        ]);
        
        $response = $this->actingAs($this->user)->get("/inventory/movements?product_id={$this->product->id}");
        
        $response->assertStatus(200);
        $response->assertViewHas('movements');
        
        // Não deve mostrar a movimentação do outro produto
        $response->assertDontSee('Outra movimentação');
    }

    /** @test */
    public function it_shows_stock_turnover_report()
    {
        // Criar movimentações para o relatório
        InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'exit',
            'quantity' => 30,
            'created_at' => now()->subDays(15)
        ]);
        
        $response = $this->actingAs($this->user)->get('/inventory/stock-turnover');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.stock-turnover');
        $response->assertViewHas('report');
        $response->assertViewHas('filters');
        
        $response->assertSee('Relatório de Giro de Estoque');
        $response->assertSee('Análise de Giro de Estoque');
    }

    /** @test */
    public function it_shows_most_used_products_report()
    {
        // Criar movimentações para o relatório
        InventoryMovement::factory()->create([
            'product_id' => $this->product->id,
            'tenant_id' => $this->tenant->id,
            'type' => 'exit',
            'quantity' => 40,
            'created_at' => now()->subDays(10)
        ]);
        
        $response = $this->actingAs($this->user)->get('/inventory/most-used');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.most-used');
        $response->assertViewHas('products');
        $response->assertViewHas('filters');
        
        $response->assertSee('Produtos Mais Utilizados');
        $response->assertSee($this->product->name);
    }

    /** @test */
    public function it_shows_inventory_alerts()
    {
        // Criar produto com estoque baixo
        $lowStockProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Estoque Baixo'
        ]);
        
        ProductInventory::factory()->create([
            'product_id' => $lowStockProduct->id,
            'tenant_id' => $this->tenant->id,
            'quantity' => 5, // Abaixo do mínimo
            'min_quantity' => 10
        ]);
        
        $response = $this->actingAs($this->user)->get('/inventory/alerts');
        
        $response->assertStatus(200);
        $response->assertViewIs('inventory.alerts');
        $response->assertViewHas('lowStockProducts');
        $response->assertViewHas('highStockProducts');
        
        $response->assertSee('Alertas de Estoque');
        $response->assertSee('Produtos com Estoque Baixo');
        $response->assertSee('Produto Estoque Baixo');
    }

    /** @test */
    public function it_checks_stock_availability_via_api()
    {
        $response = $this->actingAs($this->user)->post('/inventory/check-availability', [
            'product_id' => $this->product->id,
            'quantity' => 50
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available' => true,
            'current_stock' => 100,
            'requested_quantity' => 50
        ]);
    }

    /** @test */
    public function it_detects_insufficient_stock_via_api()
    {
        $response = $this->actingAs($this->user)->post('/inventory/check-availability', [
            'product_id' => $this->product->id,
            'quantity' => 150 // Mais do que o estoque disponível
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'available' => false,
            'current_stock' => 100,
            'requested_quantity' => 150,
            'message' => 'Estoque insuficiente'
        ]);
    }

    /** @test */
    public function it_validates_api_request_parameters()
    {
        $response = $this->actingAs($this->user)->post('/inventory/check-availability', [
            'product_id' => 'invalid',
            'quantity' => -10
        ]);
        
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_id', 'quantity']);
    }

    /** @test */
    public function it_prevents_access_to_other_tenant_products()
    {
        // Criar outro tenant e produto
        $otherTenant = Tenant::factory()->create();
        $otherProduct = Product::factory()->create([
            'tenant_id' => $otherTenant->id
        ]);
        
        $response = $this->actingAs($this->user)->get("/inventory/{$otherProduct->id}");
        
        $response->assertStatus(403); // Forbidden
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->get('/inventory/dashboard');
        
        $response->assertRedirect('/login');
    }
}