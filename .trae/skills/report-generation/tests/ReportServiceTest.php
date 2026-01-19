<?php

namespace Tests\Feature\Services;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Domain\DashboardService;
use App\Services\Domain\ReportCacheService;
use App\Services\Domain\ReportExportService;
use App\Services\Domain\ReportScheduleService;
use App\Services\Domain\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $reportService;

    protected $exportService;

    protected $scheduleService;

    protected $cacheService;

    protected $dashboardService;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant de teste
        $this->tenant = Tenant::factory()->create();

        // Inicializar serviços
        $this->reportService = app(ReportService::class);
        $this->exportService = app(ReportExportService::class);
        $this->scheduleService = app(ReportScheduleService::class);
        $this->cacheService = app(ReportCacheService::class);
        $this->dashboardService = app(DashboardService::class);
    }

    public function test_financial_report_generation()
    {
        // Criar dados de teste
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // Criar faturas de teste
        Invoice::factory()->count(5)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total' => 1000.00,
            'created_at' => now()->subDays(10),
        ]);

        Invoice::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 500.00,
            'created_at' => now()->subDays(5),
        ]);

        $filters = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

        $result = $this->reportService->generateReport('financial_summary', $filters, $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $reportData = $result->getData();
        $this->assertArrayHasKey('type', $reportData);
        $this->assertArrayHasKey('data', $reportData);
        $this->assertArrayHasKey('summary', $reportData);
        $this->assertArrayHasKey('charts', $reportData);

        $this->assertEquals('financial_summary', $reportData['type']);
        $this->assertNotEmpty($reportData['data']);
        $this->assertNotEmpty($reportData['summary']);
    }

    public function test_inventory_report_generation()
    {
        // Criar produto físico
        $product = Product::factory()->physical()->create([
            'tenant_id' => $this->tenant->id,
            'price' => 100.00,
        ]);

        // Criar movimentações de estoque
        $this->createInventoryMovement($product, 'in', 100, 'Compra inicial');
        $this->createInventoryMovement($product, 'out', 20, 'Venda');
        $this->createInventoryMovement($product, 'out', 10, 'Devolução');

        $filters = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

        $result = $this->reportService->generateReport('inventory_movements', $filters, $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $reportData = $result->getData();
        $this->assertEquals('inventory_movements', $reportData['type']);
        $this->assertNotEmpty($reportData['data']);
    }

    public function test_sales_report_generation()
    {
        // Criar orçamentos e serviços para teste de vendas
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id, 'price' => 200.00]);

        // Criar orçamentos
        Budget::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => 'approved',
            'total' => 600.00,
            'created_at' => now()->subDays(5),
        ]);

        $filters = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

        $result = $this->reportService->generateReport('sales_performance', $filters, $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $reportData = $result->getData();
        $this->assertEquals('sales_performance', $reportData['type']);
        $this->assertNotEmpty($reportData['data']);
    }

    public function test_report_export_to_pdf()
    {
        Storage::fake('public');

        $reportData = [
            'type' => 'financial_summary',
            'data' => [],
            'summary' => [],
            'charts' => [],
        ];

        $result = $this->exportService->exportReport($reportData, 'pdf', $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $exportData = $result->getData();
        $this->assertEquals('pdf', $exportData['format']);
        $this->assertNotNull($exportData['download_url']);
        $this->assertGreaterThan(0, $exportData['size']);
    }

    public function test_report_export_to_excel()
    {
        Storage::fake('public');

        $reportData = [
            'type' => 'financial_summary',
            'data' => [
                ['date' => '2025-01-01', 'description' => 'Venda', 'amount' => 1000.00],
                ['date' => '2025-01-02', 'description' => 'Compra', 'amount' => 500.00],
            ],
            'summary' => [],
            'charts' => [],
        ];

        $result = $this->exportService->exportReport($reportData, 'excel', $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $exportData = $result->getData();
        $this->assertEquals('excel', $exportData['format']);
        $this->assertNotNull($exportData['download_url']);
    }

    public function test_report_export_to_csv()
    {
        Storage::fake('public');

        $reportData = [
            'type' => 'financial_summary',
            'data' => [
                ['date' => '2025-01-01', 'description' => 'Venda', 'amount' => 1000.00],
            ],
            'summary' => [],
            'charts' => [],
        ];

        $result = $this->exportService->exportReport($reportData, 'csv', $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $exportData = $result->getData();
        $this->assertEquals('csv', $exportData['format']);
        $this->assertNotNull($exportData['download_url']);
    }

    public function test_report_scheduling()
    {
        $scheduleData = [
            'report_type' => 'financial_summary',
            'filters' => [
                'start_date' => now()->startOfMonth(),
                'end_date' => now()->endOfMonth(),
            ],
            'schedule_type' => 'monthly',
            'schedule_config' => [
                'day_of_month' => 1,
                'hour' => 9,
                'minute' => 0,
            ],
            'recipients' => ['admin@empresa.com'],
            'formats' => ['pdf', 'excel'],
        ];

        $result = $this->scheduleService->scheduleReport($scheduleData, $this->tenant->id);

        $this->assertTrue($result->isSuccess());

        $schedule = $result->getData();
        $this->assertEquals('financial_summary', $schedule->report_type);
        $this->assertEquals('active', $schedule->status);
        $this->assertNotNull($schedule->next_run_at);
    }

    public function test_report_cache()
    {
        Cache::clear();

        $filters = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

        // Primeira chamada - deve gerar relatório
        $result1 = $this->cacheService->getCachedReport('financial_summary', $filters, $this->tenant->id);
        $this->assertTrue($result1->isSuccess());

        // Segunda chamada - deve usar cache
        $result2 = $this->cacheService->getCachedReport('financial_summary', $filters, $this->tenant->id);
        $this->assertTrue($result2->isSuccess());

        // Verificar que os dados são iguais
        $this->assertEquals($result1->getData(), $result2->getData());
    }

    public function test_dashboard_generation()
    {
        // Criar dados de teste para dashboard
        Customer::factory()->count(10)->create(['tenant_id' => $this->tenant->id]);
        Invoice::factory()->count(20)->create(['tenant_id' => $this->tenant->id]);

        $result = $this->dashboardService->getExecutiveDashboard($this->tenant->id);
        $this->assertTrue($result->isSuccess());

        $dashboardData = $result->getData();
        $this->assertArrayHasKey('summary', $dashboardData);
        $this->assertArrayHasKey('charts', $dashboardData);
        $this->assertArrayHasKey('metrics', $dashboardData);
        $this->assertArrayHasKey('alerts', $dashboardData);
    }

    public function test_report_validation()
    {
        // Testar validação de filtros obrigatórios
        $filters = []; // Sem filtros obrigatórios

        $result = $this->reportService->generateReport('financial_summary', $filters, $this->tenant->id);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('INVALID_DATA', $result->getStatus());
    }

    public function test_report_performance()
    {
        // Criar grande volume de dados para teste de performance
        $customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        // Criar 1000 faturas
        Invoice::factory()->count(1000)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'total' => 100.00,
        ]);

        $filters = [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ];

        $startTime = microtime(true);

        $result = $this->reportService->generateReport('financial_summary', $filters, $this->tenant->id);

        $executionTime = microtime(true) - $startTime;

        $this->assertTrue($result->isSuccess());
        $this->assertLessThan(10, $executionTime, 'Relatório deve ser gerado em menos de 10 segundos');
    }

    private function createInventoryMovement($product, $type, $quantity, $reason)
    {
        return \App\Models\InventoryMovement::create([
            'tenant_id' => $this->tenant->id,
            'product_id' => $product->id,
            'type' => $type,
            'quantity' => $quantity,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
