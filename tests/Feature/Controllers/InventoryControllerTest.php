<?php

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Provider $provider;
    private Product $product;
    private ProductInventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::factory()->create();
        $this->provider = Provider::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'provider_id' => $this->provider->id,
        ]);
        
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        
        $this->product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Produto Teste',
            'sku' => 'TEST-001',
            'price' => 99.90,
        ]);
        
        $this->inventory = ProductInventory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'min_quantity' => 10,
            'max_quantity' => 100,
        ]);
    }

    public function test_index_shows_inventory_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory');

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.index');
        $response->assertViewHas('inventories');
        $response->assertViewHas('lowStockCount');
        $response->assertViewHas('totalValue');
        $response->assertViewHas('filters');
    }

    public function test_index_filters_by_search(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory?search=Produto');

        $response->assertStatus(200);
        $inventories = $response->viewData('inventories');
        $this->assertGreaterThan(0, $inventories->count());
    }

    public function test_index_filters_by_low_stock(): void
    {
        // Criar produto com estoque baixo
        $lowStockProduct = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produto Estoque Baixo',
            'sku' => 'LOW-001',
        ]);
        
        ProductInventory::factory()->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $lowStockProduct->id,
            'quantity' => 5,
            'min_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/provider/inventory?low_stock=1');

        $response->assertStatus(200);
        $inventories = $response->viewData('inventories');
        $this->assertGreaterThan(0, $inventories->count());
    }

    public function test_show_displays_product_inventory_details(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/provider/inventory/{$this->product->id}");

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.show');
        $response->assertViewHas('product', $this->product);
        $response->assertViewHas('inventory');
        $response->assertViewHas('movements');
    }

    public function test_adjust_shows_adjustment_form(): void
    {
        $response = $this->actingAs($this->user)
            ->get("/provider/inventory/{$this->product->id}/adjust");

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.adjust');
        $response->assertViewHas('product', $this->product);
        $response->assertViewHas('inventory');
    }

    public function test_store_adjustment_creates_inventory_entry(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/provider/inventory/{$this->product->id}/adjust", [
                'type' => 'in',
                'quantity' => 20,
                'reason' => 'Compra de novo estoque',
            ]);

        $response->assertRedirect("/provider/inventory/{$this->product->id}");
        $response->assertSessionHas('success');

        // Verifica se o inventário foi atualizado
        $this->inventory->refresh();
        $this->assertEquals(70, $this->inventory->quantity);

        // Verifica se a movimentação foi criada
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'type' => 'in',
            'quantity' => 20,
            'reason' => 'Compra de novo estoque',
        ]);
    }

    public function test_store_adjustment_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/provider/inventory/{$this->product->id}/adjust", []);

        $response->assertSessionHasErrors(['type', 'quantity', 'reason']);
    }

    public function test_store_adjustment_validates_quantity_minimum(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/provider/inventory/{$this->product->id}/adjust", [
                'type' => 'in',
                'quantity' => 0,
                'reason' => 'Teste',
            ]);

        $response->assertSessionHasErrors(['quantity']);
    }

    public function test_store_adjustment_prevents_negative_stock(): void
    {
        $response = $this->actingAs($this->user)
            ->post("/provider/inventory/{$this->product->id}/adjust", [
                'type' => 'out',
                'quantity' => 60, // Mais do que o disponível (50)
                'reason' => 'Venda grande',
            ]);

        $response->assertSessionHas('error');
        
        // Verifica que o estoque não foi alterado
        $this->inventory->refresh();
        $this->assertEquals(50, $this->inventory->quantity);
    }

    public function test_report_shows_summary_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory/report?type=summary');

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.report');
        $response->assertViewHas('reportData');
        $response->assertViewHas('type', 'summary');
    }

    public function test_report_shows_movements_report(): void
    {
        // Criar algumas movimentações
        InventoryMovement::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/provider/inventory/report?type=movements');

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.report');
        $response->assertViewHas('reportData');
        $response->assertViewHas('type', 'movements');
    }

    public function test_report_shows_valuation_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory/report?type=valuation');

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.report');
        $response->assertViewHas('reportData');
        $response->assertViewHas('type', 'valuation');
    }

    public function test_report_shows_low_stock_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory/report?type=low-stock');

        $response->assertStatus(200);
        $response->assertViewIs('pages.inventory.report');
        $response->assertViewHas('reportData');
        $response->assertViewHas('type', 'low-stock');
    }

    public function test_api_low_stock_returns_products_with_low_stock(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/provider/inventory/api/low-stock');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'count',
        ]);
    }

    public function test_api_update_min_quantity_updates_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/provider/inventory/api/{$this->product->id}/min-quantity", [
                'min_quantity' => 15,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quantidade mínima atualizada com sucesso.',
        ]);

        $this->inventory->refresh();
        $this->assertEquals(15, $this->inventory->min_quantity);
    }

    public function test_api_update_max_quantity_updates_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/provider/inventory/api/{$this->product->id}/max-quantity", [
                'max_quantity' => 200,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Quantidade máxima atualizada com sucesso.',
        ]);

        $this->inventory->refresh();
        $this->assertEquals(200, $this->inventory->max_quantity);
    }

    public function test_api_search_returns_matching_products(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/provider/inventory/api/search?q=Produto');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
        ]);
    }

    public function test_export_generates_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get('/provider/inventory/export?type=summary&format=csv');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_supports_different_formats(): void
    {
        $formats = ['csv', 'xlsx', 'pdf'];
        
        foreach ($formats as $format) {
            $response = $this->actingAs($this->user)
                ->get("/provider/inventory/export?type=summary&format={$format}");

            $response->assertStatus(200);
        }
    }

    public function test_export_supports_different_report_types(): void
    {
        $types = ['summary', 'movements', 'valuation', 'low-stock'];
        
        foreach ($types as $type) {
            $response = $this->actingAs($this->user)
                ->get("/provider/inventory/export?type={$type}&format=csv");

            $response->assertStatus(200);
        }
    }
}